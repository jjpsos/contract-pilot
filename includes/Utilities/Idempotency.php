<?php

namespace Jjpsos\ContractPilot\Utilities;

defined("ABSPATH") || exit();

class Idempotency
{
    private const TOKEN_OPTION_PREFIX = "contract_pilot_idem_token_";
    private const LOCK_OPTION_PREFIX = "contract_pilot_idem_lock_";
    private const REQUEST_LOCK_OPTION_PREFIX = "contract_pilot_idem_req_lock_";
    private const REQUEST_CONSUMED_OPTION_PREFIX = "contract_pilot_idem_req_done_";
    private const TOKEN_TTL = 300;
    private const LOCK_TTL = 300;

    /**
     * @param string $action
     * @param string $context
     * @param int $ttl
     * @return string
     */
    public static function create_token($action, $context = "create", $ttl = self::TOKEN_TTL)
    {
        $action = self::sanitize_key_part($action);
        $context = self::sanitize_key_part($context);
        $user_id = get_current_user_id();
        $expires_at = time() + max(60, (int) $ttl);

        for ($i = 0; $i < 3; $i++) {
            try {
                $token = bin2hex(random_bytes(16));
            } catch (\Exception $e) {
                $token = wp_generate_password(32, false, false);
            }

            $payload = [
                "action" => $action,
                "context" => $context,
                "user_id" => $user_id,
                "expires_at" => $expires_at,
                "consumed_at" => 0,
            ];

            if (add_option(self::TOKEN_OPTION_PREFIX . $token, $payload, "", "no")) {
                return $token;
            }
        }

        return "";
    }

    /**
     * @param string $action
     * @param string $token
     * @param string $context
     * @return array<string, mixed>
     */
    public static function acquire_lock($action, $token, $context = "create")
    {
        $token = self::sanitize_token($token);
        if ("" === $token) {
            return ["ok" => false, "status" => "invalid_token"];
        }

        $action = self::sanitize_key_part($action);
        $context = self::sanitize_key_part($context);
        $payload = get_option(self::TOKEN_OPTION_PREFIX . $token);

        if (!is_array($payload)) {
            return ["ok" => false, "status" => "invalid_token"];
        }

        if (
            $action !== ($payload["action"] ?? "") ||
            $context !== ($payload["context"] ?? "") ||
            (int) get_current_user_id() !== (int) ($payload["user_id"] ?? 0)
        ) {
            return ["ok" => false, "status" => "invalid_token"];
        }

        if ((int) ($payload["consumed_at"] ?? 0) > 0) {
            return ["ok" => false, "status" => "already_consumed"];
        }

        if ((int) ($payload["expires_at"] ?? 0) < time()) {
            delete_option(self::TOKEN_OPTION_PREFIX . $token);
            delete_option(self::LOCK_OPTION_PREFIX . $token);
            return ["ok" => false, "status" => "expired"];
        }

        $new_expiry = time() + self::LOCK_TTL;

        if (!add_option(self::LOCK_OPTION_PREFIX . $token, $new_expiry, "", "no")) {
            $existing_expiry = (int) get_option(self::LOCK_OPTION_PREFIX . $token, 0);
            if ($existing_expiry >= time()) {
                return ["ok" => false, "status" => "duplicate_in_flight"];
            }

            delete_option(self::LOCK_OPTION_PREFIX . $token);
            if (!add_option(self::LOCK_OPTION_PREFIX . $token, $new_expiry, "", "no")) {
                return ["ok" => false, "status" => "duplicate_in_flight"];
            }
        }

        return ["ok" => true, "status" => "ok", "token" => $token];
    }

    /**
     * @param string $token
     * @return void
     */
    public static function consume_token($token)
    {
        $token = self::sanitize_token($token);
        if ("" === $token) {
            return;
        }

        $payload = get_option(self::TOKEN_OPTION_PREFIX . $token);
        if (!is_array($payload)) {
            delete_option(self::LOCK_OPTION_PREFIX . $token);
            return;
        }

        $payload["consumed_at"] = time();
        $payload["expires_at"] = time() + DAY_IN_SECONDS;
        update_option(self::TOKEN_OPTION_PREFIX . $token, $payload, false);
        delete_option(self::LOCK_OPTION_PREFIX . $token);
    }

    /**
     * @param string $token
     * @return void
     */
    public static function release_lock($token)
    {
        $token = self::sanitize_token($token);
        if ("" === $token) {
            return;
        }

        delete_option(self::LOCK_OPTION_PREFIX . $token);
    }

    /**
     * @param string $status
     * @return string
     */
    public static function get_error_message($status)
    {
        switch ($status) {
            case "duplicate_in_flight":
                return __(
                    "This create request is already being processed. Please wait before trying again.",
                    "contract-pilot",
                );
            case "already_consumed":
                return __(
                    "This create request has already been processed. Please refresh the page before saving again.",
                    "contract-pilot",
                );
            case "expired":
                return __(
                    "Your form has expired. Please refresh the page and try again.",
                    "contract-pilot",
                );
            case "invalid_token":
            default:
                return __(
                    "Could not validate this create request. Please refresh the page and try again.",
                    "contract-pilot",
                );
        }
    }

    /**
     * @param string $action
     * @param string $context
     * @return string
     */
    public static function render_token_input($action, $context = "create")
    {
        $token = self::create_token($action, $context);
        if ("" === $token) {
            return "";
        }

        return sprintf(
            '<input type="hidden" name="_cp_idempotency_token" value="%s"/>',
            esc_attr($token),
        );
    }

    /**
     * @param string $action
     * @param string $context
     * @return void
     */
    public static function output_token_input($action, $context = "create")
    {
        // Safe: render_token_input escapes the token value before output.
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo self::render_token_input($action, $context);
    }

    /**
     * Acquire a request-level idempotency lock for REST calls.
     *
     * @param string $action
     * @param string $request_key
     * @param string $context
     * @param int $ttl
     * @return array<string, mixed>
     */
    public static function acquire_request_lock(
        $action,
        $request_key,
        $context = "rest",
        $ttl = self::LOCK_TTL
    ) {
        $request_hash = self::request_key_hash($action, $context, $request_key);
        if ("" === $request_hash) {
            return ["ok" => false, "status" => "invalid_token"];
        }

        $ttl = max(30, (int) $ttl);
        $consumed_until = (int) get_option(
            self::REQUEST_CONSUMED_OPTION_PREFIX . $request_hash,
            0,
        );
        if ($consumed_until >= time()) {
            return ["ok" => false, "status" => "already_consumed"];
        }

        $lock_until = time() + $ttl;
        if (!add_option(self::REQUEST_LOCK_OPTION_PREFIX . $request_hash, $lock_until, "", "no")) {
            $existing_until = (int) get_option(
                self::REQUEST_LOCK_OPTION_PREFIX . $request_hash,
                0,
            );
            if ($existing_until >= time()) {
                return ["ok" => false, "status" => "duplicate_in_flight"];
            }

            delete_option(self::REQUEST_LOCK_OPTION_PREFIX . $request_hash);
            if (!add_option(self::REQUEST_LOCK_OPTION_PREFIX . $request_hash, $lock_until, "", "no")) {
                return ["ok" => false, "status" => "duplicate_in_flight"];
            }
        }

        return ["ok" => true, "status" => "ok", "request_hash" => $request_hash];
    }

    /**
     * @param string $request_hash
     * @param int $ttl
     * @return void
     */
    public static function consume_request_lock($request_hash, $ttl = self::TOKEN_TTL)
    {
        $request_hash = self::sanitize_hash($request_hash);
        if ("" === $request_hash) {
            return;
        }

        $ttl = max(30, (int) $ttl);
        update_option(
            self::REQUEST_CONSUMED_OPTION_PREFIX . $request_hash,
            time() + $ttl,
            false,
        );
        delete_option(self::REQUEST_LOCK_OPTION_PREFIX . $request_hash);
    }

    /**
     * @param string $request_hash
     * @return void
     */
    public static function release_request_lock($request_hash)
    {
        $request_hash = self::sanitize_hash($request_hash);
        if ("" === $request_hash) {
            return;
        }

        delete_option(self::REQUEST_LOCK_OPTION_PREFIX . $request_hash);
    }

    /**
     * @param string $value
     * @return string
     */
    private static function sanitize_key_part($value)
    {
        return sanitize_key((string) $value);
    }

    /**
     * @param string $token
     * @return string
     */
    private static function sanitize_token($token)
    {
        $token = strtolower(sanitize_text_field((string) $token));
        if (!preg_match("/^[a-f0-9]{32}$/", $token)) {
            return "";
        }

        return $token;
    }

    /**
     * @param string $hash
     * @return string
     */
    private static function sanitize_hash($hash)
    {
        $hash = strtolower(sanitize_text_field((string) $hash));
        if (!preg_match("/^[a-f0-9]{64}$/", $hash)) {
            return "";
        }

        return $hash;
    }

    /**
     * @param string $action
     * @param string $context
     * @param string $request_key
     * @return string
     */
    private static function request_key_hash($action, $context, $request_key)
    {
        $action = self::sanitize_key_part($action);
        $context = self::sanitize_key_part($context);
        $request_key = trim(sanitize_text_field((string) $request_key));
        if ("" === $action || "" === $context || "" === $request_key) {
            return "";
        }

        return hash(
            "sha256",
            implode("|", [
                $action,
                $context,
                (string) get_current_user_id(),
                $request_key,
            ]),
        );
    }
}
