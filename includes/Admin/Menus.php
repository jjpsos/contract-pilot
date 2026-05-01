<?php

namespace Otto\Admin;

defined("ABSPATH") || exit();


class Menus
{
    
    const PARENT_SLUG = "otto-accounting";

    
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
        if (current_user_can("manage_accounting")) {
            
            $menu[] = [
                "",
                "read",
                "ea-separator",
                "",
                "wp-menu-separator accounting",
            ];
        }
        $icon =
            "data:image/svg+xml;base64," .
            base64_encode(
                '<svg xmlns="http://www.w3.org/2000/svg" width="2px" height="2px" viewBox="0 0 24 24"><path style=" stroke:none;fill-rule:nonzero;fill:rgb(93.333333%,93.333333%,93.333333%);fill-opacity:1;" d="M 18 1.609375 C 14.292969 -0.539062 9.707031 -0.539062 6 1.609375 C 2.292969 3.757812 0 7.714844 0 12 C 0 16.285156 2.292969 20.242188 6 22.390625 C 9.707031 24.539062 14.292969 24.539062 18 22.390625 C 21.707031 20.242188 24 16.285156 24 12 C 24 7.714844 21.707031 3.757812 18 1.609375 Z M 18.371094 13.390625 L 17.496094 18.070312 C 17.339844 18.898438 16.621094 19.488281 15.78125 19.488281 L 14.664062 19.488281 C 14.832031 18.625 15 17.761719 15.167969 16.894531 C 13.738281 18.347656 11.039062 19.691406 8.964844 19.691406 C 7.65625 19.691406 6.574219 19.222656 5.722656 18.300781 C 4.871094 17.375 4.441406 16.199219 4.441406 14.785156 C 4.441406 12.898438 5.125 11.230469 6.480469 9.78125 C 6.527344 9.730469 6.574219 9.683594 6.625 9.636719 C 7.980469 8.257812 9.996094 7.273438 11.964844 7.667969 C 13.65625 8.003906 14.914062 9.457031 15.3125 11.089844 L 15.371094 11.292969 C 15.503906 11.84375 15.203125 12.324219 14.652344 12.46875 L 8.484375 14.039062 L 8.484375 13.164062 L 13.90625 11.304688 C 13.824219 11.136719 13.726562 10.96875 13.609375 10.800781 C 13.019531 9.984375 12.226562 9.574219 11.242188 9.574219 C 10.019531 9.574219 8.941406 10.078125 8.039062 11.074219 C 7.714844 11.4375 7.453125 11.808594 7.246094 12.203125 C 7.007812 12.660156 6.851562 13.140625 6.757812 13.644531 C 6.707031 13.945312 6.671875 14.257812 6.671875 14.578125 C 6.671875 15.492188 6.960938 16.246094 7.523438 16.835938 C 8.101562 17.425781 8.832031 17.6875 9.71875 17.710938 C 10.765625 17.746094 12.238281 17.328125 13.296875 16.777344 C 13.894531 16.464844 14.605469 16.5 15.15625 16.882812 L 15.167969 16.894531 C 15.179688 16.824219 15.191406 16.753906 15.214844 16.679688 C 15.503906 15.191406 15.792969 13.714844 16.078125 12.226562 C 16.378906 10.65625 16.65625 9.109375 15.816406 7.65625 C 15.144531 6.46875 13.859375 5.855469 12.527344 5.773438 C 11.460938 5.699219 10.367188 5.929688 9.382812 6.335938 C 9.300781 6.371094 8.460938 6.757812 8.460938 6.769531 C 8.460938 6.769531 7.609375 5.257812 7.609375 5.257812 C 7.570312 5.171875 9.144531 4.5 9.289062 4.453125 C 9.898438 4.222656 10.523438 4.042969 11.160156 3.9375 C 12.421875 3.707031 13.738281 3.730469 14.976562 4.09375 C 16.621094 4.585938 18.011719 5.84375 18.515625 7.5 C 19.105469 9.382812 18.636719 11.878906 18.371094 13.390625 Z M 18.371094 13.390625 "/></svg>',
            ); 

        add_menu_page(
            __("Otto", "otto-contracts"), 
            __("Otto", "otto-contracts"),
            "read_accounting", 
            self::PARENT_SLUG,
            null,
            
            null,
            2,
        );

        
        $admin_page_hooks["otto-accounting"] = "otto-accounting";

        
        $this->register_menu([
            "menu_title" => __("Dashboard", "otto-contracts"),
            "page_title" => __("Dashboard", "otto-contracts"),
            "capability" => "read_accounting",
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
            "eac_" . $menu_page . "_page_tabs",
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

        
        $tab = filter_input(INPUT_GET, "tab", FILTER_SANITIZE_SPECIAL_CHARS);
        $action = filter_input(
            INPUT_GET,
            "action",
            FILTER_SANITIZE_SPECIAL_CHARS,
        );

        
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
                "eac_" .
                    $this->current_page .
                    "_page_" .
                    $this->current_tab .
                    "_loaded",
            )
        ) {
            

            do_action(
                "eac_" .
                    $this->current_page .
                    "_page_" .
                    $this->current_tab .
                    "_loaded",
                $this->current_action,
            );
        } else {
            

            do_action(
                "eac_" . $this->current_page . "_page_loaded",
                $this->current_tab,
                $this->current_action,
            );
        }
    }

    
    public function render_page()
    {
        global $plugin_page;
        ob_start();
        ?>
		<div class="wrap eac-wrap">
			<?php if (
       !empty($this->tabs[$this->current_page]) &&
       count($this->tabs[$this->current_page]) > 1
   ): ?>

   <nav class="nav-tab-wrapper eac-navbar">
	<?php
 foreach ($this->tabs[$this->current_page] as $name => $label) {
     printf(
         '<a href="%s" class="nav-tab %s">%s</a>',
         esc_url(admin_url("admin.php?page=" . $plugin_page . "&tab=" . $name)),
         esc_attr($this->current_tab === $name ? "nav-tab-active" : ""),
         esc_html($label),
     );
 }
 ?>

        <?php do_action(
            "eac_" . $this->current_page . "_page_after_tab_items",
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
           "eac_" .
               $this->current_page .
               "_page_" .
               $this->current_tab .
               "_content",
       )
   ) {
       
       do_action(
           "eac_" .
               $this->current_page .
               "_page_" .
               $this->current_tab .
               "_content",
           $this->current_action,
       );
   } else {
       
       do_action(
           "eac_" . $this->current_page . "_page_content",
           $this->current_tab,
           $this->current_action,
       );
   } ?>
		</div>
		<?php
    }

    
    public function normalize_page($page)
    {
        $page = preg_replace("/^.*?eac-/", "", $page);

        return self::PARENT_SLUG === $page ? "dashboard" : sanitize_key($page);
    }
}
