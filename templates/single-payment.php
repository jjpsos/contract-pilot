<?php


defined("ABSPATH") || exit();

do_action("eac_page_header");

eac_get_template("content-payment.php", ["payment" => $payment]);

do_action("eac_page_footer");
