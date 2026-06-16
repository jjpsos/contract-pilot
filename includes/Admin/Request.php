<?php

namespace Jjpsos\ContractPilot\Admin;

defined('ABSPATH') || exit();

/**
 * Centralized admin input verification and sanitization.
 *
 * Read-only GET parameters on Contract Pilot admin screens are gated by
 * capability checks, screen nonces (verified when _wpnonce is in the URL),
 * and sanitization. POST handlers verify action-specific nonces before reading
 * superglobals. Nonce checks run only inside these accessors — never on
 * global hooks — so front-end and unrelated admin requests are unaffected.
 */
final class Request
{
    public const ADMIN_SCREEN_NONCE = 'contract_pilot_admin_screen';

    /** @var array<string, bool> */
    private static $verified_capabilities = [];

    public function __construct()
    {
        // Intentionally empty — no global admin_init input checks for performance.
    }

    /**
     * Whether the current request is a Contract Pilot admin screen.
     */
    public static function is_plugin_admin_screen(): bool
    {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Admin screen detection; read-only GET sanitized with sanitize_key.
        if (!is_admin() || !isset($_GET['page'])) {
            return false;
        }

        $page = sanitize_key(wp_unslash($_GET['page']));
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        return 0 === strpos($page, 'contract-pilot');
    }

    public static function verify_capability(string $capability = 'contract_pilot_access'): void
    {
        if (!is_admin() || !current_user_can($capability)) {
            wp_die(
                esc_html__(
                    'You do not have permission to access this page.',
                    'contract-pilot',
                ),
                esc_html__('Contract Pilot', 'contract-pilot'),
                ['response' => 403],
            );
        }
    }

    /**
     * Gate read-only GET input on Contract Pilot admin screens.
     */
    public static function verify_admin_read(string $capability = 'contract_pilot_access'): void
    {
        if (!self::is_plugin_admin_screen()) {
            return;
        }

        if (isset(self::$verified_capabilities[$capability])) {
            return;
        }

        self::verify_capability($capability);
        self::$verified_capabilities[$capability] = true;
    }

    /**
     * Read and sanitize a GET parameter on Contract Pilot admin screens.
     *
     * Capability is verified first. When _wpnonce is present in the query
     * string (links from admin_url() / list_table_url()), wp_verify_nonce()
     * runs in this same function before $_GET is read so Plugin Check can
     * trace the check. GET action URLs (delete, bulk actions, etc.) use
     * action-specific nonces verified by their handlers. POST form nonces
     * are verified by their save handlers. Standard WordPress submenu URLs
     * omit a nonce; capability checks still apply.
     *
     * @param string               $key         GET parameter name.
     * @param mixed                $default     Value when the key is absent or rejected.
     * @param string               $capability  Required capability on plugin admin screens.
     * @param callable(mixed):mixed $sanitize   Sanitizer applied to the unslashed value.
     *
     * @return mixed
     */
    private static function read_get_param(
        string $key,
        $default,
        string $capability,
        callable $sanitize
    ) {
        self::verify_admin_read($capability);

        // Only verify screen nonces from URL query args (admin_url/list_table_url).
        // POST forms and GET action links (delete, bulk actions) use their own
        // nonce actions, verified by the handler that performs the mutation.
        if (self::is_plugin_admin_screen() && isset($_GET['_wpnonce'])) {
            $nonce = sanitize_text_field(wp_unslash($_GET['_wpnonce']));
            if (
                !wp_verify_nonce($nonce, self::ADMIN_SCREEN_NONCE)
                && !isset($_GET['action'])
            ) {
                wp_die(
                    esc_html__(
                        'Security check failed. Please reload the page and try again.',
                        'contract-pilot',
                    ),
                    esc_html__('Contract Pilot', 'contract-pilot'),
                    ['response' => 403],
                );
            }
        }

        if (!isset($_GET[$key])) {
            return $default;
        }

        $raw = sanitize_text_field(wp_unslash($_GET[$key]));

        return $sanitize($raw);
    }

    public static function admin_url(string $url): string
    {
        return wp_nonce_url($url, self::ADMIN_SCREEN_NONCE);
    }

    public static function list_table_url(string $url): string
    {
        return wp_nonce_url($url, self::ADMIN_SCREEN_NONCE);
    }

    public static function get_string(
        string $key,
        string $default = '',
        string $capability = 'contract_pilot_access'
    ): string {
        $value = self::read_get_param(
            $key,
            $default,
            $capability,
            static function ($raw) use ($default) {
                if (!is_string($raw)) {
                    return $default;
                }

                return sanitize_text_field($raw);
            },
        );

        return is_string($value) ? $value : $default;
    }

    public static function get_key(
        string $key,
        string $default = '',
        string $capability = 'contract_pilot_access'
    ): string {
        $value = self::get_string($key, $default, $capability);

        return '' === $value ? $default : sanitize_key($value);
    }

    public static function get_int(
        string $key,
        int $default = 0,
        string $capability = 'contract_pilot_access'
    ): int {
        $value = self::read_get_param(
            $key,
            $default,
            $capability,
            static function ($raw) {
                return absint($raw);
            },
        );

        return (int) $value;
    }

    public static function get_post_string(string $key, string $default = ''): string
    {
        // phpcs:disable WordPress.Security.NonceVerification.Missing -- POST sanitized here; nonce verified by caller before this method runs.
        if (!isset($_POST[$key])) {
            return $default;
        }

        $value = sanitize_text_field(wp_unslash($_POST[$key]));
        // phpcs:enable WordPress.Security.NonceVerification.Missing

        return $value;
    }

    /**
     * Sanitize POST data for invoice preview/calculation (not persisted).
     *
     * @return array<string, mixed>
     */
    public static function sanitize_invoice_post(): array
    {
        // phpcs:disable WordPress.Security.NonceVerification.Missing -- POST sanitized here; nonce verified by caller before this method runs.
        return [
            'id' => isset($_POST['id']) ? absint(wp_unslash($_POST['id'])) : 0,
            'issue_date' => isset($_POST['issue_date'])
                ? sanitize_text_field(wp_unslash($_POST['issue_date']))
                : '',
            'due_date' => isset($_POST['due_date'])
                ? sanitize_text_field(wp_unslash($_POST['due_date']))
                : '',
            'contact_id' => isset($_POST['contact_id'])
                ? absint(wp_unslash($_POST['contact_id']))
                : 0,
            'contact_name' => isset($_POST['contact_name'])
                ? sanitize_text_field(wp_unslash($_POST['contact_name']))
                : '',
            'contact_company' => isset($_POST['contact_company'])
                ? sanitize_text_field(wp_unslash($_POST['contact_company']))
                : '',
            'contact_email' => isset($_POST['contact_email'])
                ? sanitize_email(wp_unslash($_POST['contact_email']))
                : '',
            'contact_phone' => isset($_POST['contact_phone'])
                ? sanitize_text_field(wp_unslash($_POST['contact_phone']))
                : '',
            'contact_address' => isset($_POST['contact_address'])
                ? sanitize_text_field(wp_unslash($_POST['contact_address']))
                : '',
            'contact_city' => isset($_POST['contact_city'])
                ? sanitize_text_field(wp_unslash($_POST['contact_city']))
                : '',
            'contact_state' => isset($_POST['contact_state'])
                ? sanitize_text_field(wp_unslash($_POST['contact_state']))
                : '',
            'contact_postcode' => isset($_POST['contact_postcode'])
                ? sanitize_text_field(wp_unslash($_POST['contact_postcode']))
                : '',
            'contact_country' => isset($_POST['contact_country'])
                ? sanitize_text_field(wp_unslash($_POST['contact_country']))
                : '',
            'contact_tax_number' => isset($_POST['contact_tax_number'])
                ? sanitize_text_field(wp_unslash($_POST['contact_tax_number']))
                : '',
            'order_number' => isset($_POST['order_number'])
                ? sanitize_text_field(wp_unslash($_POST['order_number']))
                : '',
            'discount_type' => isset($_POST['discount_type'])
                ? sanitize_text_field(wp_unslash($_POST['discount_type']))
                : '',
            'discount_value' => isset($_POST['discount_value'])
                ? floatval(wp_unslash($_POST['discount_value']))
                : 0,
            'status' => isset($_POST['status'])
                ? sanitize_text_field(wp_unslash($_POST['status']))
                : '',
            'note' => isset($_POST['note'])
                ? sanitize_textarea_field(wp_unslash($_POST['note']))
                : '',
            'terms' => isset($_POST['terms'])
                ? sanitize_textarea_field(wp_unslash($_POST['terms']))
                : '',
            'items' => isset($_POST['items'])
                ? map_deep(wp_unslash($_POST['items']), 'sanitize_text_field')
                : [],
        ];
        // phpcs:enable WordPress.Security.NonceVerification.Missing
    }

    /**
     * @param string $nonce_action Nonce action verified against $_POST['_wpnonce'].
     *
     * @return array<string, mixed>
     */
    public static function sanitize_post_data(
        string $nonce_action = 'contract_pilot_save_settings'
    ): array {
        check_admin_referer($nonce_action);

        if (empty($_POST) || !is_array($_POST)) {
            return [];
        }

        return map_deep(wp_unslash($_POST), static function ($value) {
            if (is_array($value)) {
                return $value;
            }

            return is_scalar($value) ? contract_pilot_clean($value) : $value;
        });
    }
}
