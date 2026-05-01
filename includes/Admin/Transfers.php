<?php

namespace Otto\Admin;

use Otto\Models\Transfer;

defined("ABSPATH") || exit();


class Transfers
{
    
    public function __construct()
    {
        add_filter("eac_banking_page_tabs", [__CLASS__, "register_tabs"]);
        add_filter("admin_post_eac_edit_transfer", [__CLASS__, "handle_edit"]);
        add_action("eac_banking_page_transfers_loaded", [
            __CLASS__,
            "page_loaded",
        ]);
        add_action("eac_banking_page_transfers_content", [
            __CLASS__,
            "page_content",
        ]);
    }

    
    public static function register_tabs($tabs)
    {
        if (
            current_user_can("eac_read_transfers") ||
            current_user_can("eac_banking_tools_access") ||
            current_user_can("eac_manage_options")
        ) {
            
            $tabs["transfers"] = __("Transfers", "otto-contracts");
        }

        return $tabs;
    }

    
    public static function handle_edit()
    {
        check_admin_referer("eac_edit_transfer");

        if (!current_user_can("eac_edit_transfers")) {
            
            wp_die(
                esc_html__(
                    "You do not have permission to edit transfers.",
                    "otto-contracts",
                ),
            );
        }

        $referer = wp_get_referer();
        $transfer = EAC()->transfers->insert([
            "id" => isset($_POST["id"]) ? absint(wp_unslash($_POST["id"])) : 0,
            "from_account_id" => isset($_POST["from_account_id"])
                ? absint(wp_unslash($_POST["from_account_id"]))
                : 0,
            "from_exchange_rate" => isset($_POST["from_exchange_rate"])
                ? floatval(wp_unslash($_POST["from_exchange_rate"]))
                : 1,
            "to_account_id" => isset($_POST["to_account_id"])
                ? absint(wp_unslash($_POST["to_account_id"]))
                : 0,
            "to_exchange_rate" => isset($_POST["to_exchange_rate"])
                ? floatval(wp_unslash($_POST["to_exchange_rate"]))
                : 1,
            "amount" => isset($_POST["amount"])
                ? floatval(wp_unslash($_POST["amount"]))
                : 0,
            "transfer_date" => isset($_POST["transfer_date"])
                ? get_gmt_from_date(
                    sanitize_text_field(wp_unslash($_POST["transfer_date"])),
                )
                : "",
            "payment_method" => isset($_POST["payment_method"])
                ? sanitize_text_field(wp_unslash($_POST["payment_method"]))
                : "",
            "reference" => isset($_POST["reference"])
                ? sanitize_text_field(wp_unslash($_POST["reference"]))
                : "",
            "note" => isset($_POST["note"])
                ? sanitize_textarea_field(wp_unslash($_POST["note"]))
                : "",
        ]);

        if (is_wp_error($transfer)) {
            EAC()->flash->error($transfer->get_error_message());
        } else {
            EAC()->flash->success(
                __("Transfer saved successfully.", "otto-contracts"),
            );
            $referer = add_query_arg("id", $transfer->id, $referer);
            $referer = remove_query_arg(["add"], $referer);
        }

        wp_safe_redirect($referer);
        exit();
    }

    
    public static function page_loaded($action)
    {
        global $eac_list_table;
        switch ($action) {
            case "add":
                if (!current_user_can("eac_edit_transfers")) {
                    
                    wp_die(
                        esc_html__(
                            "You do not have permission to add transfers.",
                            "otto-contracts",
                        ),
                    );
                }
                break;

            case "edit":
                $id = filter_input(INPUT_GET, "id", FILTER_SANITIZE_NUMBER_INT);
                if (!EAC()->transfers->get($id)) {
                    wp_die(
                        esc_html__(
                            "You attempted to retrieve a transfer that does not exist. Perhaps it was deleted?",
                            "otto-contracts",
                        ),
                    );
                }
                break;

            default:
                $screen = get_current_screen();
                $eac_list_table = new ListTables\Transfers();
                $eac_list_table->prepare_items();
                $screen->add_option("per_page", [
                    "label" => __(
                        "Number of items per page:",
                        "otto-contracts",
                    ),
                    "default" => 20,
                    "option" => "eac_transfers_per_page",
                ]);
                break;
        }
    }

    
    public static function page_content($action)
    {
        switch ($action) {
            case "add":
            case "edit":
                include __DIR__ . "/views/transfer-edit.php";
                break;
            default:
                include __DIR__ . "/views/transfer-list.php";
                break;
        }
    }
}
