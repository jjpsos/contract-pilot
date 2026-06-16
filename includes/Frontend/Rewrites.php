<?php

namespace Jjpsos\ContractPilot\Frontend;

defined("ABSPATH") || exit();


class Rewrites
{
    public function __construct()
    {
        add_filter("query_vars", [__CLASS__, "add_query_vars"], 0);
        add_action("init", [__CLASS__, "add_endpoint"], 0);
        add_action("parse_request", [__CLASS__, "handle_request"], PHP_INT_MAX);
    }


    public static function add_query_vars($vars)
    {
        $vars[] = "contract_pilot";
        $vars[] = "route";
        $vars[] = "uuid";

        return $vars;
    }


    public static function add_endpoint()
    {
        add_rewrite_rule(
            "^contract-pilot/([^/]*)/?",
            'index.php?contract_pilot=1&route=$matches[1]',
            "top",
        );
    }


    public static function handle_request($wp)
    {
        $has_cp_flag = !empty($wp->query_vars["contract_pilot"]);
        $route_raw = isset($wp->query_vars["route"])
            ? $wp->query_vars["route"]
            : "";

        if ($has_cp_flag && "" !== $route_raw) {
            $route = sanitize_text_field(wp_unslash($route_raw));

            if (has_action("contract_pilot_handle_request_{$route}")) {
                do_action(
                    "contract_pilot_handle_request_{$route}",
                    $wp->query_vars,
                );

                exit();
            }
        }
    }
}
