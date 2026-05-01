<?php

namespace Otto\Controllers;

use Otto\Models\DocumentItem;
use Otto\Models\DocumentTax;
use Otto\Models\Invoice;

defined("ABSPATH") || exit();


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
            "draft" => esc_html__("Draft", "otto-contracts"),
            "sent" => esc_html__("Sent", "otto-contracts"),
            "accept" => esc_html__("Accept", "otto-contracts"),
            "partial" => esc_html__("Partial", "otto-contracts"),
            "paid" => esc_html__("Paid", "otto-contracts"),
            "overdue" => esc_html__("Overdue", "otto-contracts"),
            "otto" => esc_html__("Otto", "otto-contracts"),
            "cancelled" => esc_html__("Cancelled", "otto-contracts"),
        ];

        return apply_filters("eac_invoice_statuses", $statuses);
    }

    
    public function get_columns()
    {
        $columns = [
            "item" => get_option(
                "eac_invoice_col_item_label",
                esc_html__("Service", "otto-contracts"),
            ),
            "quantity" => get_option(
                "eac_invoice_col_quantity_label",
                esc_html__("Quantity", "otto-contracts"),
            ),
            "price" => get_option(
                "eac_invoice_col_price_label",
                esc_html__("Price", "otto-contracts"),
            ),
            "tax" => get_option(
                "eac_invoice_col_tax_label",
                esc_html__("Tax", "otto-contracts"),
            ),
            "subtotal" => get_option(
                "eac_invoice_col_subtotal_label",
                esc_html__("Subtotal", "otto-contracts"),
            ),
        ];

        return apply_filters("eac_invoice_columns", $columns);
    }
}
