<?php

namespace Jjpsos\ContractPilot;

defined("ABSPATH") || exit();


class Shortcodes
{
    public function __construct()
    {
        add_filter("query_vars", [$this, "add_query_vars"], 99);
        add_shortcode("contract_pilot_payment", [$this, "render_payment"]);
        add_shortcode("contract_pilot_payment", [$this, "render_payment"]);
    }


    public function add_query_vars($vars)
    {
        $vars[] = "uuid";

        return $vars;
    }


    public function render_payment($atts)
    {

        $uuid = get_query_var("uuid");

        $atts = shortcode_atts(
            [
                "uuid" => sanitize_text_field($uuid),
            ],
            $atts,
            "contract_pilot_payment",
        );

        $payment = contract_pilot()->payments->get(["uuid" => $atts["uuid"]]);

        if (!$payment) {
            return "";
        }

        ob_start();
        contract_pilot_get_template("payment.php", ["payment" => $payment]);

        return ob_get_clean();
    }
}
