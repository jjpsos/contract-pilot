<?php

namespace Jjpsos\ContractPilot;

use Jjpsos\ContractPilot\Models\Invoice;

defined("ABSPATH") || exit();


class Documents
{
    public function __construct()
    {

        add_action("contract_pilot_payment_inserted", [
            __CLASS__,
            "invoice_payment_updated",
        ]);
        add_action("contract_pilot_payment_deleted", [
            __CLASS__,
            "invoice_payment_updated",
        ]);
        add_action("contract_pilot_payment_updated", [
            __CLASS__,
            "invoice_payment_updated",
        ]);
        add_action("contract_pilot_hourly_event", [__CLASS__, "maybe_overdue_invoices"]);
        add_action(
            "contract_pilot_invoice_status_transition",
            [__CLASS__, "invoice_status_transition"],
            10,
            2,
        );
    }


    public static function invoice_payment_updated($payment)
    {
        $original = $payment->get_original();
        if (
            array_key_exists("document_id", $original) &&
            $original["document_id"] !== $payment->document_id &&
            $original["document_id"] > 0
        ) {
            $old_document = contract_pilot()->invoices->get($original["document_id"]);
            if ($old_document) {
                $old_document->calculate_totals();
                $old_document->save();
            }
        }

        if ($payment->invoice_id && $payment->invoice) {
            $invoice = $payment->invoice;
            $invoice->calculate_totals();



            $invoice->save();
        }
    }


    public static function maybe_overdue_invoices()
    {
        global $wpdb;
        // phpcs:ignore -- Cron path reads current overdue candidates from plugin table.
        $invoices = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}pilot_documents WHERE type = 'invoice' AND status NOT IN ('paid', 'cancelled', 'draft', 'overdue') AND due_date < %s",
                current_time("mysql"),
            ),
        );

        if (!empty($invoices)) {
            foreach ($invoices as $invoice_id) {
                $invoice = contract_pilot()->invoices->get($invoice_id);
                if ($invoice) {
                    $invoice->status = "overdue";
                    $invoice->save();
                }
            }
        }
    }


    public static function invoice_status_transition($invoice, $status)
    {
        $invoice->notes()->insert([
            "parent_type" => "invoice",

            "content" => sprintf(
                /* translators: %s: new invoice status label */
                __("Status changed to %s", "contract-pilot"),
                esc_html($status),
            ),
            "created_by" => get_current_user_id(),
        ]);
    }
}
