<?php

namespace Otto\Frontend;

defined("ABSPATH") || exit();


class Frontend
{
    
    public function __construct()
    {
        add_action("eac_page_header", [__CLASS__, "render_page_header"]);
        add_action("eac_page_footer", [__CLASS__, "render_page_footer"]);
        add_action("wp_enqueue_scripts", [__CLASS__, "enqueue_scripts"]);
        add_action("eac_handle_request_invoice", [__CLASS__, "render_invoice"]);
        add_action("eac_handle_request_bill", [__CLASS__, "render_bill"]);
        add_action("eac_handle_request_payment", [__CLASS__, "render_payment"]);
        add_action("eac_handle_request_expense", [__CLASS__, "render_expense"]);
    }

    
    public static function render_page_header()
    {
        wp_enqueue_style("eac-frontend");
        eac_get_template("site-header.php");
    }

    
    public static function render_page_footer()
    {
        eac_get_template("site-footer.php");
    }

    
    public static function enqueue_scripts()
    {
        EAC()->scripts->register_style("eac-frontend", "styles/frontend.css");
    }

    
    public static function render_invoice($vars)
    {
        $uuid = isset($vars["uuid"])
            ? sanitize_text_field(wp_unslash($vars["uuid"]))
            : "";
        $invoice = EAC()->invoices->get(["uuid" => $uuid]);
        if (!$invoice) {
            wp_die(
                esc_html__(
                    "You attempted to view an invoice that does not exist.",
                    "otto-contracts",
                ),
            );
        }

        eac_get_template("single-invoice.php", ["invoice" => $invoice]);
    }

    
    public static function render_bill($vars)
    {
        $uuid = isset($vars["uuid"])
            ? sanitize_text_field(wp_unslash($vars["uuid"]))
            : "";
        $bill = EAC()->bills->get(["uuid" => $uuid]);
        if (!$bill) {
            wp_die(
                esc_html__(
                    "You attempted to view a bill that does not exist.",
                    "otto-contracts",
                ),
            );
        }

        eac_get_template("single-bill.php", ["bill" => $bill]);
    }

    
    public static function render_payment($vars)
    {
        $uuid = isset($vars["uuid"])
            ? sanitize_text_field(wp_unslash($vars["uuid"]))
            : "";
        $payment = EAC()->payments->get(["uuid" => $uuid]);
        if (!$payment) {
            wp_die(
                esc_html__(
                    "You attempted to view a payment that does not exist.",
                    "otto-contracts",
                ),
            );
        }

        eac_get_template("single-payment.php", ["payment" => $payment]);
    }

    
    public static function render_expense($vars)
    {
        $uuid = isset($vars["uuid"])
            ? sanitize_text_field(wp_unslash($vars["uuid"]))
            : "";
        $expense = EAC()->expenses->get(["uuid" => $uuid]);
        if (!$expense) {
            wp_die(
                esc_html__(
                    "You attempted to view an expense that does not exist.",
                    "otto-contracts",
                ),
            );
        }

        eac_get_template("single-expense.php", ["expense" => $expense]);
    }
}
