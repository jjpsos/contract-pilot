<?php

namespace Jjpsos\ContractPilot\Admin;

defined("ABSPATH") || exit();


class Menus
{
    const PARENT_SLUG = "contract-pilot";


    /**
     * Top-level menu position (WordPress: Settings is 80; higher = lower in sidebar).
     */
    private const MENU_POSITION = 81;


    public $tabs = [];


    public $current_page = "";


    public $current_tab = "";


    public $current_action = "";


    public function __construct()
    {
        add_action("admin_menu", [$this, "admin_menu"]);
    }


    public function admin_menu()
    {
        global $menu, $admin_page_hooks;
        if (current_user_can("contract_pilot_access")) {
            $menu[] = [
                "",
                "read",
                "contract-pilot-separator",
                "",
                "wp-menu-separator accounting",
            ];
        }
        $icon =
            "data:image/svg+xml;base64," .
            base64_encode(
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><path fill="#82878c" d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14zM7 7h10v2H7V7zm0 4h10v2H7v-2zm0 4h7v2H7v-2z"/></svg>',
            );

        add_menu_page(
            __("Contract Pilot", "contract-pilot"),
            __("Contract Pilot", "contract-pilot"),
            "contract_pilot_access",
            self::PARENT_SLUG,
            null,
            $icon,
            self::MENU_POSITION,
        );


        $admin_page_hooks["contract-pilot"] = "contract-pilot";


        $this->register_menu([
            "menu_title" => __("Dashboard", "contract-pilot"),
            "page_title" => __("Dashboard", "contract-pilot"),
            "capability" => "contract_pilot_access",
            "menu_slug" => self::PARENT_SLUG,
            "callback" => [Dashboard::class, "render_page"],
        ]);

        $submenus = Utilities::get_menus();
        usort($submenus, function ($a, $b) {
            $a = isset($a["position"]) ? $a["position"] : 10;
            $b = isset($b["position"]) ? $b["position"] : 10;

            return $a - $b;
        });

        foreach ($submenus as $submenu) {
            $this->register_menu($submenu);
        }
    }


    public function register_menu($menu)
    {
        global $plugin_page, $pagenow;

        $menu = wp_parse_args($menu, [
            "parent" => self::PARENT_SLUG,
            "menu_title" => "",
            "page_title" => "",
            "capability" => "manage_options",
            "menu_slug" => "",
            "callback" => [$this, "render_page"],
        ]);

        $menu_page = $this->normalize_page($menu["menu_slug"]);


        $this->tabs[$menu_page] = apply_filters(
            "contract_pilot_" . $menu_page . "_page_tabs",
            [],
        );


        if (
            empty($this->tabs[$menu_page]) &&
            [$this, "render_page"] === $menu["callback"]
        ) {
            return;
        }

        $load = add_submenu_page(
            $menu["parent"],
            $menu["page_title"],
            $menu["menu_title"],
            $menu["capability"],
            $menu["menu_slug"],
            $menu["callback"],
        );


        if (
            empty($plugin_page) ||
            "admin.php" !== $pagenow ||
            $plugin_page !== $menu["menu_slug"]
        ) {
            return;
        }


        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Admin page tab/action routing; verified via Request after screen nonce.
        $tab = Request::get_key('tab');
        $action = Request::get_key('action');
        // phpcs:enable WordPress.Security.NonceVerification.Recommended


        $this->current_page = $this->normalize_page($plugin_page);
        $this->current_tab =
            !empty($tab) &&
            array_key_exists($tab, $this->tabs[$this->current_page])
                ? sanitize_key($tab)
                : current(array_keys($this->tabs[$this->current_page]));
        $this->current_action = !empty($action) ? sanitize_key($action) : "";


        if (
            $this->tabs[$this->current_page] &&
            $tab &&
            !array_key_exists($tab, $this->tabs[$this->current_page])
        ) {
            wp_safe_redirect(remove_query_arg("tab"));
            exit();
        }


        add_filter("admin_title", [$this, "admin_title"]);
        add_action("load-" . $load, [$this, "handle_load"]);
    }


    public function admin_title($title)
    {
        if (!empty($this->tab)) {
            $title = sprintf("%s - %s", $this->tabs[$this->tab], $title);
        }

        return $title;
    }


    public function handle_load()
    {
        if (
            !empty($this->current_page) &&
            !empty($this->current_tab) &&
            has_action(
                "contract_pilot_" .
                    $this->current_page .
                    "_page_" .
                    $this->current_tab .
                    "_loaded",
            )
        ) {
            do_action(
                "contract_pilot_" .
                    $this->current_page .
                    "_page_" .
                    $this->current_tab .
                    "_loaded",
                $this->current_action,
            );
        }

        do_action(
            "contract_pilot_" . $this->current_page . "_page_loaded",
            $this->current_tab,
            $this->current_action,
        );
    }


    public function render_page()
    {
        global $plugin_page;
        ob_start();
        ?>
        <div class="wrap contract-pilot-wrap">
            <?php if (
            !empty($this->tabs[$this->current_page]) &&
            count($this->tabs[$this->current_page]) > 1
) : ?>
   <nav class="nav-tab-wrapper contract-pilot-navbar">
    <?php
    foreach ($this->tabs[$this->current_page] as $name => $label) {
        printf(
            '<a href="%s" class="nav-tab %s">%s</a>',
            esc_url(
                Request::admin_url(
                    admin_url("admin.php?page=" . $plugin_page . "&tab=" . $name),
                ),
            ),
            esc_attr($this->current_tab === $name ? "nav-tab-active" : ""),
            esc_html($label),
        );
    }
    ?>

        <?php do_action(
            "contract_pilot_" . $this->current_page . "_page_after_tab_items",
            $this->current_tab,
            $this->tabs[$this->current_page],
        ); ?>
                </nav>
            <?php endif; ?>

            <hr class="wp-header-end">

            <?php if (
            !empty($this->current_page) &&
            !empty($this->current_tab) &&
            has_action(
                "contract_pilot_" .
                $this->current_page .
                "_page_" .
                $this->current_tab .
                "_content",
            )
) {
       do_action(
           "contract_pilot_" .
               $this->current_page .
               "_page_" .
               $this->current_tab .
               "_content",
           $this->current_action,
       );
            } else {
                do_action(
                    "contract_pilot_" . $this->current_page . "_page_content",
                    $this->current_tab,
                    $this->current_action,
                );
            } ?>
        </div>
        <?php
        ob_end_flush();
    }


    public function normalize_page($page)
    {
        $page = preg_replace("#^.*?contract-pilot-#", "", $page);

        return self::PARENT_SLUG === $page ? "dashboard" : sanitize_key($page);
    }
}
