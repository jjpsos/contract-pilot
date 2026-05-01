<?php

namespace Otto\Admin;

use Otto\Models\Bill;

defined("ABSPATH") || exit();


class Scripts
{
    
    public function __construct()
    {
        add_action("admin_enqueue_scripts", [$this, "register_scripts"]);
        add_action("admin_enqueue_scripts", [$this, "enqueue_scripts"]);
    }

    
    public function register_scripts()
    {
        
        EAC()->scripts->register_script(
            "eac-chartjs",
            "scripts/chartjs.js",
            ["jquery"],
            true,
        );
        EAC()->scripts->register_script(
            "eac-inputmask",
            "scripts/inputmask.js",
            ["jquery"],
            true,
        );
        EAC()->scripts->register_script(
            "eac-select2",
            "scripts/select2.js",
            ["jquery"],
            true,
        );
        EAC()->scripts->register_script(
            "eac-tiptip",
            "scripts/tiptip.js",
            ["jquery"],
            true,
        );
        EAC()->scripts->register_script(
            "eac-printthis",
            "scripts/printthis.js",
            ["jquery"],
            true,
        );
        EAC()->scripts->register_script(
            "eac-timepicker",
            "scripts/timepicker.js",
            ["jquery", "jquery-ui-datepicker"],
            true,
        );

        
        EAC()->scripts->register_script("eac-money", "packages/money.js");
        EAC()->scripts->register_script(
            "eac-autonumeric",
            "scripts/autonumeric.js",
            [],
            true,
        );

        
        EAC()->scripts->register_script(
            "eac-modal",
            "scripts/modal.js",
            ["jquery"],
            true,
        );
        EAC()->scripts->register_script(
            "eac-form",
            "scripts/form.js",
            ["jquery"],
            true,
        );
        EAC()->scripts->register_script(
            "eac-api",
            "scripts/api.js",
            ["wp-backbone"],
            true,
        );

        
        EAC()->scripts->register_script(
            "eac-admin",
            "scripts/admin.js",
            [
                "jquery",
                "eac-inputmask",
                "eac-select2",
                "eac-printthis",
                "eac-tiptip",
                "eac-timepicker",
                "jquery-ui-tooltip",
                "eac-money",
                "wp-ajax-response",
            ],
            true,
        );

        EAC()->scripts->register_style("eac-jquery-ui", "styles/jquery-ui.css");
        EAC()->scripts->register_style("eac-admin", "styles/admin.css", [
            "eac-jquery-ui",
        ]);
    }

    
    public function enqueue_scripts($hook)
    {
        
        EAC()->scripts->enqueue_style(
            "eac-black-friday",
            "styles/admin-black-friday.css",
        );

        if (!in_array($hook, Utilities::get_screen_ids(), true)) {
            return;
        }
        wp_enqueue_media();
        wp_enqueue_script("eac-api");
        wp_enqueue_script("eac-form");
        wp_enqueue_script("eac-modal");
        wp_enqueue_script("eac-admin");
        wp_enqueue_style("eac-admin");

        
        wp_localize_script("eac-api", "eac_api_vars", [
            "root" => sanitize_url(get_rest_url()),
            "nonce" => wp_create_nonce("wp_rest"),
            "namespace" => "eac/v1/",
        ]);

        wp_localize_script("eac-admin", "eac_admin_vars", [
            "ajax_url" => admin_url("admin-ajax.php"),
            "base_currency" => eac_base_currency(),
            "currencies" => eac_get_currencies(),
            "search_nonce" => wp_create_nonce("eac_search_action"),
            "upload_nonce" => wp_create_nonce("eac_upload_action"),
            "i18n" => [
                "confirm_delete" => __(
                    "Are you sure you want to delete this?",
                    "otto-contracts",
                ),
                "close" => __("Close", "otto-contracts"),
            ],
        ]);

        if (
            "toplevel_page_otto-accounting" === $hook ||
            "otto-accounting_page_eac-banking" === $hook
        ) {
            wp_enqueue_script("eac-chartjs");
        }
    }
}
