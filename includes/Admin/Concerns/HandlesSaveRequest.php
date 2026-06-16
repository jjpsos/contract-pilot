<?php

namespace Jjpsos\ContractPilot\Admin\Concerns;

defined('ABSPATH') || exit;

/**
 * Shared scaffolding for admin "save" (create/update) POST handlers.
 *
 * Every domain's handle_edit() repeats the same shape: verify the nonce,
 * gate on a capability, sanitize $_POST into a data array, hand it to a
 * service, then flash a message and redirect for either the error or the
 * success outcome.
 *
 * The nonce check and the $_POST sanitization deliberately stay in each
 * handler: keeping check_admin_referer() and the superglobal reads in the
 * same scope is what satisfies the WordPress nonce-verification sniff, and
 * the sanitization is domain-specific by nature. This trait factors out the
 * two parts that are genuinely identical and easy to get subtly wrong:
 * the capability gate and the error/success flash + redirect tail.
 */
trait HandlesSaveRequest
{
    /**
     * Gate a request on a capability, dying with a localized message otherwise.
     *
     * @param string $capability   Capability the current user must have.
     * @param string $deny_message Already-localized message shown via wp_die().
     *
     * @return void
     */
    protected static function contract_pilot_require_capability($capability, $deny_message): void
    {
        if (!current_user_can($capability)) {
            wp_die(esc_html($deny_message));
        }
    }

    /**
     * Finish a save request by flashing a message and redirecting.
     *
     * Mirrors the historical per-handler behavior exactly: on a WP_Error the
     * error message is flashed and the request redirects to the unmodified
     * referer; on success the message is flashed and the referer is passed
     * through $success_redirect to build the final URL.
     *
     * $referer is intentionally untyped so a falsy wp_get_referer() result is
     * forwarded verbatim to add_query_arg()/wp_safe_redirect(), preserving the
     * previous fallback semantics.
     *
     * @param object|\WP_Error $entity           Result of the service save call.
     * @param mixed            $referer          Redirect target (typically wp_get_referer()).
     * @param string           $success_message  Already-localized success flash message.
     * @param callable         $success_redirect (mixed $referer, object $entity) => mixed final URL.
     *
     * @return void
     */
    protected static function contract_pilot_complete_save($entity, $referer, $success_message, callable $success_redirect): void
    {
        if (is_wp_error($entity)) {
            contract_pilot()->flash->error($entity->get_error_message());
            wp_safe_redirect($referer);
            exit;
        }

        contract_pilot()->flash->success($success_message);
        wp_safe_redirect(call_user_func($success_redirect, $referer, $entity));
        exit;
    }
}
