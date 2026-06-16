<?php

namespace Jjpsos\ContractPilot\Admin;

use Jjpsos\ContractPilot\Admin\Concerns\ContractPilotListTableScreen;
use Jjpsos\ContractPilot\Admin\Concerns\HandlesSaveRequest;
use Jjpsos\ContractPilot\Models\Transfer;

defined("ABSPATH") || exit();


class Transfers
{
    use ContractPilotListTableScreen;
    use HandlesSaveRequest;


    public function __construct()
    {
        add_filter("contract_pilot_banking_page_tabs", [__CLASS__, "register_tabs"]);
        add_action("admin_post_contract_pilot_edit_transfer", [__CLASS__, "handle_edit"]);
        add_action("contract_pilot_banking_page_transfers_loaded", [
            __CLASS__,
            "page_loaded",
        ]);
        add_action("contract_pilot_banking_page_transfers_content", [
            __CLASS__,
            "page_content",
        ]);
    }


    public static function register_tabs($tabs)
    {
        if (
            current_user_can("contract_pilot_read_transfers") ||
            current_user_can("contract_pilot_banking_tools_access") ||
            current_user_can("contract_pilot_manage_options")
        ) {
            $tabs["transfers"] = __("Transfers", "contract-pilot");
        }

        return $tabs;
    }


    public static function handle_edit()
    {
        check_admin_referer("contract_pilot_edit_transfer");
        self::contract_pilot_require_capability(
            "contract_pilot_edit_transfers",
            __("You do not have permission to edit transfers.", "contract-pilot"),
        );

        $referer = wp_get_referer();
        $data = [
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
        ];
        $transfer = contract_pilot()->transfer_service->save($data);

        self::contract_pilot_complete_save(
            $transfer,
            $referer,
            __("Transfer saved successfully.", "contract-pilot"),
            static function ($referer, $transfer) {
                $referer = add_query_arg("id", $transfer->id, $referer);

                return remove_query_arg(["add"], $referer);
            },
        );
    }


    public static function page_loaded($action)
    {
        self::contract_pilot_reset_list_table();
        switch ($action) {
            case "add":
                self::contract_pilot_require_capability(
                    "contract_pilot_edit_transfers",
                    __("You do not have permission to add transfers.", "contract-pilot"),
                );
                break;

            case "edit":
                $id = Request::get_int("id");
                if (!contract_pilot()->transfers->get($id)) {
                    wp_die(
                        esc_html__(
                            "You attempted to retrieve a transfer that does not exist. Perhaps it was deleted?",
                            "contract-pilot",
                        ),
                    );
                }
                self::contract_pilot_require_capability(
                    "contract_pilot_edit_transfers",
                    __("You do not have permission to edit transfers.", "contract-pilot"),
                );
                break;

            default:
                $screen = get_current_screen();
                $contract_pilot_list_table = new ListTables\Transfers();
                $contract_pilot_list_table->prepare_items();
                self::contract_pilot_store_list_table($contract_pilot_list_table);
                $screen->add_option("per_page", [
                    "label" => __(
                        "Number of items per page:",
                        "contract-pilot",
                    ),
                    "default" => 20,
                    "option" => "contract_pilot_transfers_per_page",
                ]);
                break;
        }
    }


    public static function page_content($action)
    {
        switch ($action) {
            case "add":
            case "edit":
                contract_pilot_render_admin_view(
                    'screens/transfer-edit',
                    ScreenViewData::transfer_edit(Request::get_int('id')),
                );
                break;
            default:
                contract_pilot_render_admin_view(
                    'screens/transfer-list',
                    ScreenViewData::transfer_list(self::contract_pilot_fetch_list_table()),
                );
                self::contract_pilot_reset_list_table();
                break;
        }
    }
}
