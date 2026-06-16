<?php

namespace Jjpsos\ContractPilot\Admin;

use Jjpsos\ContractPilot\Admin\Concerns\ContractPilotListTableScreen;
use Jjpsos\ContractPilot\Admin\Concerns\HandlesSaveRequest;
use Jjpsos\ContractPilot\Models\Tax;

defined("ABSPATH") || exit();


class Taxes
{
    use ContractPilotListTableScreen;
    use HandlesSaveRequest;

    public function __construct()
    {
        add_action("admin_post_contract_pilot_edit_tax", [__CLASS__, "handle_edit"]);
        add_action("contract_pilot_settings_page_taxes_loaded", [
            __CLASS__,
            "page_loaded",
        ]);
        add_action("contract_pilot_settings_taxes_tab_rates_content", [
            __CLASS__,
            "render_content",
        ]);
    }


    public static function handle_edit()
    {
        check_admin_referer("contract_pilot_edit_tax");
        self::contract_pilot_require_capability(
            "contract_pilot_edit_taxes",
            __("You do not have permission to edit taxes.", "contract-pilot"),
        );
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
        $tax = contract_pilot()->tax_service->save([
            "id" => $id,
            "name" => $name,
            "rate" => $rate,
            "compound" => $compound,
        ]);

        self::contract_pilot_complete_save(
            $tax,
            $referer,
            __("Tax saved successfully.", "contract-pilot"),
            static function ($referer, $tax) {
                $referer = add_query_arg(
                    [
                        "action" => "edit",
                        "id" => $tax->id,
                    ],
                    $referer,
                );

                return remove_query_arg(["add"], $referer);
            },
        );
    }


    public static function page_loaded($action)
    {
        self::contract_pilot_reset_list_table();

        switch ($action) {
            case "add":
            case "edit":
                break;

            default:
                if ("rates" !== Request::get_key("section")) {
                    return;
                }

                $screen = get_current_screen();
                $contract_pilot_list_table = new ListTables\Taxes();
                $contract_pilot_list_table->prepare_items();
                self::contract_pilot_store_list_table($contract_pilot_list_table);
                $screen->add_option("per_page", [
                    "label" => __(
                        "Number of taxes per page:",
                        "contract-pilot",
                    ),
                    "default" => 20,
                    "option" => "contract_pilot_taxes_per_page",
                ]);
                break;
        }
    }

    public static function render_content()
    {
        $action = Request::get_key('action');
        $id = Request::get_int('id');

        switch ($action) {
            case "add":
            case "edit":
                contract_pilot_render_admin_view(
                    'screens/tax-edit',
                    ScreenViewData::tax_edit($id),
                );
                break;
            default:
                contract_pilot_render_admin_view(
                    'screens/tax-list',
                    ScreenViewData::tax_list(self::contract_pilot_fetch_list_table()),
                );
                self::contract_pilot_reset_list_table();
                break;
        }
    }
}
