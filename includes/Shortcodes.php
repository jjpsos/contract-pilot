<?php

namespace Otto;

defined("ABSPATH") || exit();


class Shortcodes
{
    
    public function __construct()
    {
        add_filter("query_vars", [$this, "add_query_vars"], 99);
        add_shortcode("eac_payment", [$this, "render_payment"]);
        add_shortcode("eac_expense", [$this, "render_expense"]);
        add_shortcode("eac_invoice", [$this, "render_invoice"]);
        add_shortcode("eac_bill", [$this, "render_bill"]);
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
            "eac_payment",
        );

        $payment = EAC()->payments->get(["uuid" => $atts["uuid"]]);

        if (!$payment) {
            return "";
        }

        ob_start();
        eac_get_template("payment.php", ["payment" => $payment]);

        return ob_get_clean();
    }
}
