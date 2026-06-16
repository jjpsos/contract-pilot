<?php

namespace Jjpsos\ContractPilot\Admin;

defined("ABSPATH") || exit();


class Utilities
{
    public static function get_menus()
    {
        $menus = [
            [
                "capability" => "contract_pilot_read_items",
                "menu_slug" => "contract-pilot-items",
                "menu_title" => __("Services", "contract-pilot"),
                "page_title" => __("Services", "contract-pilot"),
                "position" => 20,
            ],
            [
                "page_title" => __("Contracts", "contract-pilot"),
                "menu_title" => __("Contracts", "contract-pilot"),
                "capability" => "contract_pilot_access",
                "menu_slug" => "contract-pilot-sales",
                "position" => 30,
            ],
            [
                "page_title" => __("Purchases", "contract-pilot"),
                "menu_title" => __("Purchases", "contract-pilot"),
                "capability" => "contract_pilot_access",
                "menu_slug" => "contract-pilot-purchases",
                "position" => 40,
            ],
            [
                "page_title" => __("Banking", "contract-pilot"),
                "menu_title" => __("Banking", "contract-pilot"),
                "capability" => "contract_pilot_manage_options",
                "menu_slug" => "contract-pilot-banking",
                "position" => 50,
            ],
            [
                "page_title" => __("Settings", "contract-pilot"),
                "menu_title" => __("Settings", "contract-pilot"),
                "capability" => "contract_pilot_manage_options",
                "menu_slug" => "contract-pilot-settings",
                "position" => 100,
            ],
        ];

        return apply_filters("contract_pilot_admin_menus", $menus);
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
