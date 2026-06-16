<?php

namespace Jjpsos\ContractPilot\Services;

use Jjpsos\ContractPilot\Models\Payment;

defined("ABSPATH") || exit();

/**
 * Application service for payment workflows.
 *
 * Holds the persistence/business orchestration that admin request handlers
 * previously performed inline. Request concerns (nonces, capabilities,
 * idempotency, flash messages, redirects) remain in the admin layer.
 * For reads and listings, use contract_pilot()->payments.
 */
class PaymentService
{
    /**
     * Create or update a payment from sanitized input.
     *
     * @param array $data Sanitized payment fields.
     *
     * @return Payment|\WP_Error Saved payment on success, WP_Error on failure.
     */
    public function save(array $data)
    {
        return Payment::insert($data);
    }

    /**
     * Apply editable fields (status, attachment) to an existing payment.
     *
     * Does not persist; returns whether the payment now has unsaved changes so
     * the caller can decide how to save and report the result.
     *
     * @param Payment $payment       Existing payment.
     * @param string  $status        New status (ignored when empty/unchanged).
     * @param int     $attachment_id New attachment id.
     *
     * @return bool Whether the payment has pending changes.
     */
    public function apply_updates($payment, $status, $attachment_id)
    {
        if (!empty($status) && $status !== $payment->status) {
            $payment->status = $status;
        }

        if ($attachment_id !== $payment->attachment_id) {
            $payment->attachment_id = $attachment_id;
        }

        return $payment->is_dirty();
    }
}
