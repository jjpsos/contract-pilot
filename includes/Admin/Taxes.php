<?php

namespace Otto\Admin;

use Otto\Models\Tax;

defined("ABSPATH") || exit();


class Taxes
{
    
    public function __construct()
    {
        add_action("admin_post_eac_edit_tax", [__CLASS__, "handle_edit"]);
        add_action("eac_settings_taxes_tab_rates_content", [
            __CLASS__,
            "render_content",
        ]);
    }

    
    public static function handle_edit()
    {
        check_admin_referer("eac_edit_tax");
        if (!current_user_can("eac_edit_taxes")) {
            
            wp_die(
                esc_html__(
                    "You do not have permission to edit taxes.",
                    "otto-contracts",
                ),
            );
        }
        $referer = wp_get_referer();
        $id = isset($_POST["id"]) ? absint(wp_unslash($_POST["id"])) : 0;
        $name = isset($_POST["name"])
            ? sanitize_text_field(wp_unslash($_POST["name"]))
            : "";
        $rate = isset($_POST["rate"])
            ? doubleval(wp_unslash($_POST["rate"]))
            : "";
        $compound = isset($_POST["compound"])
            ? sanitize_text_field(wp_unslash($_POST["compound"]))
            : "";
        if ($compound) {
            $compound = "yes" === $compound ? true : false;
        }
        $tax = EAC()->taxes->insert([
            "id" => $id,
            "name" => $name,
            "rate" => $rate,
            "compound" => $compound,
        ]);

        if (is_wp_error($tax)) {
            EAC()->flash->error($tax->get_error_message());
        } else {
            EAC()->flash->success(
                __("Tax saved successfully.", "otto-contracts"),
            );
            $referer = add_query_arg(
                [
                    "action" => "edit",
                    "id" => $tax->id,
                ],
                $referer,
            );
            $referer = remove_query_arg(["add"], $referer);
        }

        wp_safe_redirect($referer);
        exit();
    }

    
    public static function render_content()
    {
        $action = filter_input(
            INPUT_GET,
            "action",
            FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        );
        $id = filter_input(INPUT_GET, "id", FILTER_SANITIZE_NUMBER_INT);

        switch ($action) {
            case "add":
            case "edit":
                include __DIR__ . "/views/tax-edit.php";
                break;
            default:
                global $eac_list_table;
                $eac_list_table = new ListTables\Taxes();
                $eac_list_table->prepare_items();
                include __DIR__ . "/views/tax-list.php";
                break;
        }
    }
}
