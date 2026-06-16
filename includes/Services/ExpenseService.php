<?php

namespace Jjpsos\ContractPilot\Services;

use Jjpsos\ContractPilot\Models\Expense;

defined("ABSPATH") || exit();

/**
 * Application service for expense workflows.
 *
 * Holds the persistence/business orchestration that admin request handlers
 * previously performed inline. Request concerns (nonces, capabilities,
 * idempotency, flash messages, redirects) remain in the admin layer.
 * For reads and listings, use contract_pilot()->expenses.
 */
class ExpenseService
{
    /**
     * Create or update an expense from sanitized input.
     *
     * @param array $data Sanitized expense fields.
     *
     * @return Expense|\WP_Error Saved expense on success, WP_Error on failure.
     */
    public function save(array $data)
    {
        return Expense::insert($data);
    }

    /**
     * Apply editable fields (status, attachment) to an existing expense.
     *
     * Does not persist; returns whether the expense now has unsaved changes so
     * the caller can decide how to save and report the result.
     *
     * @param Expense $expense       Existing expense.
     * @param string  $status        New status (ignored when empty/unchanged).
     * @param int     $attachment_id New attachment id.
     *
     * @return bool Whether the expense has pending changes.
     */
    public function apply_updates($expense, $status, $attachment_id)
    {
        if (!empty($status) && $status !== $expense->status) {
            $expense->status = $status;
        }

        if ($attachment_id !== $expense->attachment_id) {
            $expense->attachment_id = $attachment_id;
        }

        return $expense->is_dirty();
    }
}
