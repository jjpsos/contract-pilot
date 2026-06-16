<?php

namespace Jjpsos\ContractPilot\Admin;

use Jjpsos\ContractPilot\Admin\Concerns\ContractPilotListTableScreen;
use Jjpsos\ContractPilot\Admin\Concerns\HandlesSaveRequest;
use Jjpsos\ContractPilot\Models\Category;

defined("ABSPATH") || exit();


class Categories
{
    use ContractPilotListTableScreen;
    use HandlesSaveRequest;

    public function __construct()
    {
        add_filter("contract_pilot_settings_page_tabs", [__CLASS__, "register_tabs"], -2);
        add_action("admin_post_contract_pilot_edit_category", [__CLASS__, "handle_edit"]);
        add_action("contract_pilot_settings_page_categories_loaded", [
            __CLASS__,
            "page_loaded",
        ]);
        add_action("contract_pilot_settings_page_categories_content", [
            __CLASS__,
            "render_content",
        ]);
    }


    public static function register_tabs($tabs)
    {
        if (current_user_can("contract_pilot_read_categories")) {
            $tabs["categories"] = __("Categories", "contract-pilot");
        }

        return $tabs;
    }


    public static function handle_edit()
    {
        check_admin_referer("contract_pilot_edit_category");
        self::contract_pilot_require_capability(
            "contract_pilot_edit_categories",
            __("You do not have permission to edit categories.", "contract-pilot"),
        );
        $referer = wp_get_referer();
        $data = [
            "id" => isset($_POST["id"]) ? absint(wp_unslash($_POST["id"])) : 0,
            "name" => isset($_POST["name"])
                ? sanitize_text_field(wp_unslash($_POST["name"]))
                : "",
            "type" => isset($_POST["type"])
                ? sanitize_text_field(wp_unslash($_POST["type"]))
                : "",
            "description" => isset($_POST["description"])
                ? sanitize_textarea_field(wp_unslash($_POST["description"]))
                : "",
        ];

        $item = contract_pilot()->category_service->save($data);

        self::contract_pilot_complete_save(
            $item,
            $referer,
            __("Category saved successfully.", "contract-pilot"),
            static function ($referer, $item) {
                $referer = add_query_arg("id", $item->id, $referer);

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
                $screen = get_current_screen();
                $contract_pilot_list_table = new ListTables\Categories();
                $contract_pilot_list_table->prepare_items();
                self::contract_pilot_store_list_table($contract_pilot_list_table);
                $screen->add_option("per_page", [
                    "label" => __(
                        "Number of categories per page:",
                        "contract-pilot",
                    ),
                    "default" => 20,
                    "option" => "contract_pilot_categories_per_page",
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
                    'screens/category-edit',
                    ScreenViewData::category_edit($id),
                );
                break;
            default:
                contract_pilot_render_admin_view(
                    'screens/category-list',
                    ScreenViewData::category_list(self::contract_pilot_fetch_list_table()),
                );
                self::contract_pilot_reset_list_table();
                break;
        }
    }
}
