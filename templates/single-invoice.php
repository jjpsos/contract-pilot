<?php


defined("ABSPATH") || exit();

do_action("eac_page_header");

$view = eac_build_invoice_view_data($invoice);

eac_get_template("content-invoice.php", ["view" => $view]);

do_action("eac_page_footer");
