<?php

namespace Otto;

use Otto\Models\Expense;
use Otto\Models\Invoice;
use Otto\Models\Payment;

defined("ABSPATH") || exit();


class Documents
{
    
    public function __construct()
    {
        
        add_action("eac_payment_inserted", [
            __CLASS__,
            "invoice_payment_updated",
        ]);
        add_action("eac_payment_deleted", [
            __CLASS__,
            "invoice_payment_updated",
        ]);
        add_action("eac_payment_updated", [
            __CLASS__,
            "invoice_payment_updated",
        ]);
        add_action("eac_hourly_event", [__CLASS__, "maybe_overdue_invoices"]);
        add_action(
            "eac_invoice_status_transition",
            [__CLASS__, "invoice_status_transition"],
            10,
            2,
        );

        
        add_action("eac_expense_inserted", [__CLASS__, "bill_expense_updated"]);
        add_action("eac_expense_deleted", [__CLASS__, "bill_expense_updated"]);
        add_action("eac_expense_updated", [__CLASS__, "bill_expense_updated"]);
        add_action("eac_hourly_event", [__CLASS__, "maybe_overdue_bills"]);
        add_action(
            "eac_bill_status_transition",
            [__CLASS__, "bill_status_transition"],
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
            $old_document = EAC()->invoices->get($original["document_id"]);
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
        $invoices = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}otto_documents WHERE status NOT IN ('paid', 'cancelled', 'draft', 'overdue') AND due_date < %s",
                current_time("mysql"),
            ),
        );

        if (!empty($invoices)) {
            foreach ($invoices as $invoice_id) {
                $invoice = EAC()->invoices->get($invoice_id);
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
                __("Status changed to %s", "otto-contracts"),
                esc_html($status),
            ),
            "created_by" => get_current_user_id(),
        ]);
    }

    
    public static function bill_expense_updated($expense)
    {
        $original = $expense->get_original();
        if (
            array_key_exists("document_id", $original) &&
            $original["document_id"] !== $expense->document_id &&
            $original["document_id"] > 0
        ) {
            $old_document = EAC()->bills->get($original["document_id"]);
            if ($old_document) {
                $old_document->calculate_totals();
                $old_document->save();
            }
        }

        if ($expense->bill_id && $expense->bill) {
            $bill = $expense->bill;
            $bill->calculate_totals();
            $bill->save();
        }
    }

    
    public static function maybe_overdue_bills()
    {
        global $wpdb;
        $bills = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}otto_documents WHERE status NOT IN ('paid', 'cancelled', 'draft', 'overdue') AND due_date < %s",
                current_time("mysql"),
            ),
        );

        if (!empty($bills)) {
            foreach ($bills as $bill_id) {
                $bill = EAC()->bills->get($bill_id);
                if ($bill) {
                    $bill->status = "overdue";
                    $bill->save();
                }
            }
        }
    }

    
    public static function bill_status_transition($bill, $status)
    {
        $bill->notes()->insert([
            "parent_type" => "bill",
            
            "content" => sprintf(
                __("Status changed to %s", "otto-contracts"),
                esc_html($status),
            ),
            "created_by" => get_current_user_id(),
        ]);
    }
}
