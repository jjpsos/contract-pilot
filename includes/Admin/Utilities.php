<?php

namespace Otto\Admin;

defined("ABSPATH") || exit();


class Utilities
{
    
    public static function get_menus()
    {
        $menus = [
            [
                "capability" => "eac_read_items",
                "menu_slug" => "eac-items",
                "menu_title" => __("Services", "otto-contracts"),
                "page_title" => __("Services", "otto-contracts"),
                "position" => 20,
            ],
            [
                "page_title" => __("Contracts", "otto-contracts"),
                "menu_title" => __("Contracts", "otto-contracts"),
                "capability" => "read_accounting",
                "menu_slug" => "eac-sales",
                "position" => 30,
            ],
            [
                "page_title" => __("Purchases", "otto-contracts"),
                "menu_title" => __("Purchases", "otto-contracts"),
                "capability" => "read_accounting",
                "menu_slug" => "eac-purchases",
                "position" => 40,
            ],
            [
                "page_title" => __("Banking", "otto-contracts"),
                "menu_title" => __("Banking", "otto-contracts"),
                "capability" => "eac_manage_options",
                "menu_slug" => "eac-banking",
                "position" => 50,
            ],
            [
                "page_title" => __("Tools", "otto-contracts"),
                "menu_title" => __("Tools", "otto-contracts"),
                "capability" => "eac_manage_options",
                "menu_slug" => "eac-tools",
                "position" => 60,
            ],
            [
                "page_title" => __("Settings", "otto-contracts"),
                "menu_title" => __("Settings", "otto-contracts"),
                "capability" => "eac_manage_options",
                "menu_slug" => "eac-settings",
                "position" => 100,
            ],
        ];

        return apply_filters("eac_admin_menus", $menus);
    }

    
    public static function get_screen_ids()
    {
        $screen_ids = [
            "toplevel_page_" . Menus::PARENT_SLUG,
            Menus::PARENT_SLUG . "_page_dashboard",
        ];

        foreach (self::get_menus() as $page) {
            $screen_ids[] = Menus::PARENT_SLUG . "_page_" . $page["menu_slug"];
        }

        return $screen_ids;
    }
}
