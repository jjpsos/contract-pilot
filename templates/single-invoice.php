<?php


defined("ABSPATH") || exit();

do_action("contract_pilot_page_header");

( static function ($contract_pilot_invoice) {
    if (! isset($contract_pilot_invoice) || ! is_object($contract_pilot_invoice)) {
        return;
    }
    $view = contract_pilot_build_invoice_view_data($contract_pilot_invoice);
    contract_pilot_get_template("content-invoice.php", array( "view" => $view ));
} )($invoice);

do_action("contract_pilot_page_footer");
