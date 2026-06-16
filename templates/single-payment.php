<?php


defined("ABSPATH") || exit();

do_action("contract_pilot_page_header");

( static function ($contract_pilot_payment) {
    if (! isset($contract_pilot_payment) || ! is_object($contract_pilot_payment)) {
        return;
    }
    contract_pilot_get_template("content-payment.php", array( "payment" => $contract_pilot_payment ));
} )($payment);

do_action("contract_pilot_page_footer");
