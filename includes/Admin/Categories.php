<?php

namespace Otto\Admin;

use Otto\Models\Category;

defined("ABSPATH") || exit();


class Categories
{
    
    public function __construct()
    {
        add_filter("eac_settings_page_tabs", [__CLASS__, "register_tabs"]);
        add_action("admin_post_eac_edit_category", [__CLASS__, "handle_edit"]);
        add_action("eac_settings_page_categories_content", [
            __CLASS__,
            "render_content",
        ]);
    }

    
    public static function register_tabs($tabs)
    {
        if (current_user_can("eac_read_categories")) {
            
            $tabs["categories"] = __("Categories", "otto-contracts");
        }

        return $tabs;
    }

    
    public static function handle_edit()
    {
        check_admin_referer("eac_edit_category");
        if (!current_user_can("eac_edit_categories")) {
            
            wp_die(
                esc_html__(
                    "You do not have permission to edit categories.",
                    "otto-contracts",
                ),
            );
        }
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

        $item = EAC()->categories->insert($data);
        if (is_wp_error($item)) {
            EAC()->flash->error($item->get_error_message());
        } else {
            EAC()->flash->success(
                __("Category saved successfully.", "otto-contracts"),
            );
            $referer = add_query_arg("id", $item->id, $referer);
            $referer = remove_query_arg(["add"], $referer);
        }

        wp_safe_redirect($referer);
        exit();
    }

    
    public static function handle_actions()
    {
        if (
            isset($_POST["action"]) &&
            "eac_edit_category" === $_POST["action"] &&
            check_admin_referer("eac_edit_category") &&
            current_user_can("eac_edit_categories")
        ) {
            
            $referer = wp_get_referer();
            $data = [
                "id" => isset($_POST["id"])
                    ? absint(wp_unslash($_POST["id"]))
                    : 0,
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

            $item = EAC()->categories->insert($data);
            if (is_wp_error($item)) {
                EAC()->flash->error($item->get_error_message());
            } else {
                EAC()->flash->success(
                    __("Category saved successfully.", "otto-contracts"),
                );
                $referer = add_query_arg("id", $item->id, $referer);
                $referer = remove_query_arg(["add"], $referer);
            }

            wp_safe_redirect($referer);
            exit();
        }
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
                include __DIR__ . "/views/category-edit.php";
                break;
            default:
                global $eac_list_table;
                $eac_list_table = new ListTables\Categories();
                $eac_list_table->prepare_items();
                include __DIR__ . "/views/category-list.php";
                break;
        }
    }
}
