<?php

namespace Jjpsos\ContractPilot\Repositories;

use Jjpsos\ContractPilot\Models\DocumentItem;
use Jjpsos\ContractPilot\Models\DocumentTax;
use Jjpsos\ContractPilot\Models\Invoice;

defined("ABSPATH") || exit();

/**
 * Repository-style facade for invoice (contract/bill) records.
 *
 * Container: contract_pilot()->invoices. Use for get(), query(), delete(),
 * and lookups such as get_statuses(). For admin save and status workflows, use
 * {@see \Jjpsos\ContractPilot\Services\InvoiceService}.
 */
class Invoices
{
    public function get($invoice)
    {
        return Invoice::find($invoice);
    }


    public function insert($data, $wp_error = true)
    {
        return Invoice::insert($data, $wp_error);
    }


    public function delete($id)
    {
        $invoice = $this->get($id);
        if (!$invoice) {
            return false;
        }

        return $invoice->delete();
    }


    public function query($args = [], $count = false)
    {
        if ($count) {
            return Invoice::count($args);
        }

        return Invoice::results($args);
    }


    public function get_statuses()
    {
        $statuses = [
            "draft" => esc_html__("Draft", "contract-pilot"),
            "sent" => esc_html__("Sent", "contract-pilot"),
            "accept" => esc_html__("Accept", "contract-pilot"),
            "partial" => esc_html__("Partial", "contract-pilot"),
            "paid" => esc_html__("Paid", "contract-pilot"),
            "overdue" => esc_html__("Overdue", "contract-pilot"),
            "otto" => esc_html__("Auto", "contract-pilot"),
            "cancelled" => esc_html__("Cancelled", "contract-pilot"),
        ];

        return apply_filters("contract_pilot_invoice_statuses", $statuses);
    }


    public function get_columns()
    {
        $columns = [
            "item" => get_option(
                "contract_pilot_invoice_col_item_label",
                esc_html__("Service", "contract-pilot"),
            ),
            "quantity" => get_option(
                "contract_pilot_invoice_col_quantity_label",
                esc_html__("Quantity", "contract-pilot"),
            ),
            "price" => get_option(
                "contract_pilot_invoice_col_price_label",
                esc_html__("Price", "contract-pilot"),
            ),
            "tax" => get_option(
                "contract_pilot_invoice_col_tax_label",
                esc_html__("Tax", "contract-pilot"),
            ),
            "subtotal" => get_option(
                "contract_pilot_invoice_col_subtotal_label",
                esc_html__("Subtotal", "contract-pilot"),
            ),
        ];

        return apply_filters("contract_pilot_invoice_columns", $columns);
    }
}
