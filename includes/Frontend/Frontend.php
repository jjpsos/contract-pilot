<?php

namespace Jjpsos\ContractPilot\Frontend;

defined("ABSPATH") || exit();


class Frontend
{
    public function __construct()
    {
        add_action("contract_pilot_page_header", [__CLASS__, "render_page_header"]);
        add_action("contract_pilot_page_footer", [__CLASS__, "render_page_footer"]);
        add_action("wp_enqueue_scripts", [__CLASS__, "enqueue_scripts"]);
        add_action("contract_pilot_handle_request_invoice", [__CLASS__, "render_invoice"]);
        add_action("contract_pilot_handle_request_payment", [__CLASS__, "render_payment"]);
    }


    public static function render_page_header()
    {
        wp_enqueue_style("contract-pilot-frontend");
        contract_pilot_get_template("site-header.php");
    }


    public static function render_page_footer()
    {
        contract_pilot_get_template("site-footer.php");
    }


    public static function enqueue_scripts()
    {
        contract_pilot()->scripts->register_style(
            "contract-pilot-frontend",
            "styles/frontend.css",
        );
    }


    public static function render_invoice($vars)
    {
        $uuid = isset($vars["uuid"])
            ? sanitize_text_field(wp_unslash($vars["uuid"]))
            : "";
        $invoice = contract_pilot()->invoices->get(["uuid" => $uuid]);
        if (!$invoice) {
            wp_die(
                esc_html__(
                    "You attempted to view an invoice that does not exist.",
                    "contract-pilot",
                ),
            );
        }

        contract_pilot_get_template("single-invoice.php", ["invoice" => $invoice]);
    }


    public static function render_payment($vars)
    {
        $uuid = isset($vars["uuid"])
            ? sanitize_text_field(wp_unslash($vars["uuid"]))
            : "";
        $payment = contract_pilot()->payments->get(["uuid" => $uuid]);
        if (!$payment) {
            wp_die(
                esc_html__(
                    "You attempted to view a payment that does not exist.",
                    "contract-pilot",
                ),
            );
        }

        contract_pilot_get_template("single-payment.php", ["payment" => $payment]);
    }


}
