<?php

namespace Otto\Frontend;

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
        $vars[] = "eac";
        $vars[] = "route";
        $vars[] = "uuid";

        return $vars;
    }

    
    public static function add_endpoint()
    {
        add_rewrite_rule(
            "^eac/([^/]*)/?",
            'index.php?eac=1&route=$matches[1]',
            "top",
        );
    }

    
    public static function handle_request($wp)
    {
        if (
            !empty($wp->query_vars["eac"]) &&
            !empty($wp->query_vars["route"])
        ) {
            $route = sanitize_text_field(wp_unslash($wp->query_vars["route"]));

            if (has_action("eac_handle_request_{$route}")) {
                
                do_action("eac_handle_request_{$route}", $wp->query_vars);

                exit();
            }
        }
    }
}
