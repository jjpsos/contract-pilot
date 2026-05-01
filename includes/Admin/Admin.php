<?php

namespace Otto\Admin;

defined("ABSPATH") || exit();


class Admin
{
    
    public function __construct()
    {
        add_action("admin_init", [$this, "buffer_start"], 1);
        add_filter("admin_body_class", [$this, "body_class"]);
        add_filter(
            "admin_footer_text",
            [$this, "admin_footer_text"],
            PHP_INT_MAX,
        );
        add_filter("update_footer", [$this, "update_footer"], PHP_INT_MAX);
        add_action("in_admin_header", [__CLASS__, "in_admin_header"]);
        add_filter(
            "set-screen-option",
            [__CLASS__, "set_screen_option"],
            10,
            3,
        );
        add_action("admin_head", [$this, "print_scripts"]);
        add_action("admin_head", [__CLASS__, "hide_sidebar_submenus"], 20);
        add_action("admin_footer", [$this, "print_js_templates"]);
    }

    
    public function buffer_start()
    {
        $screen = get_current_screen();
        if (
            !$screen ||
            (false === strpos($screen->id, "eac-") &&
                false === strpos($screen->id, "otto-accounting"))
        ) {
            return;
        }
        ob_start();
    }

    
    public function body_class($classes)
    {
        if (
            in_array(
                get_current_screen()->id,
                Utilities::get_screen_ids(),
                true,
            )
        ) {
            $classes .= " eac-admin";
        }

        return $classes;
    }

    
    public function admin_footer_text($text)
    {
        if (
            in_array(
                get_current_screen()->id,
                Utilities::get_screen_ids(),
                true,
            )
        ) {
            $text = sprintf(
                
                __("Thank you for using %s!", "otto-contracts"),
                "<strong>" . esc_html(EAC()->get_name()) . "</strong>",
            );
            if (EAC()->review_url) {
                $text .= sprintf(
                    
                    __(
                        " Share your appreciation with a five-star review %s.",
                        "otto-contracts",
                    ),
                    '<a href="' .
                        esc_url(EAC()->review_url) .
                        '" target="_blank">here</a>',
                );
            }
        }

        return $text;
    }

    
    public function update_footer($footer_text)
    {
        if (
            in_array(
                get_current_screen()->id,
                Utilities::get_screen_ids(),
                true,
            )
        ) {
            
            $footer_text = sprintf(
                esc_html__("Version %s", "otto-contracts"),
                EAC()->get_version(),
            );
        }

        return $footer_text;
    }

    
    public static function in_admin_header()
    {
        if (
            !in_array(
                get_current_screen()->id,
                Utilities::get_screen_ids(),
                true,
            )
        ) {
            return;
        }
        $menus = [
            [
                "title" => __("Dashboard", "otto-contracts"),
                "icon" => "dashicons dashicons-dashboard",
                "url" => admin_url("admin.php?page=otto-accounting"),
            ],
            [
                "title" => __("Sales", "otto-contracts"),
                "icon" => "dashicons dashicons-money-alt",
                "url" => admin_url("admin.php?page=eac-sales"),
            ],
            [
                "title" => __("Purchases", "otto-contracts"),
                "icon" => "dashicons dashicons-cart",
                "url" => admin_url("admin.php?page=eac-purchases"),
            ],
            [
                "title" => __("Banking", "otto-contracts"),
                "icon" => "dashicons dashicons-store",
                "url" => admin_url("admin.php?page=eac-banking"),
            ],
            [
                "title" => __("New", "otto-contracts"),
                "icon" => "dashicons dashicons-plus-alt",
                "url" => "#",
                "submenu" => [
                    [
                        "title" => __("Service", "otto-contracts"),
                        "url" => admin_url(
                            "admin.php?page=eac-items&tab=items&action=add",
                        ),
                    ],
                    [
                        "title" => __("Payment", "otto-contracts"),
                        "url" => admin_url(
                            "admin.php?page=eac-sales&tab=payments&action=add",
                        ),
                    ],
                    [
                        "title" => __("Expense", "otto-contracts"),
                        "url" => admin_url(
                            "admin.php?page=eac-purchases&tab=expenses&action=add",
                        ),
                    ],
                    [
                        "title" => __("Contract", "otto-contracts"),
                        "url" => admin_url(
                            "admin.php?page=eac-sales&tab=invoices&action=add",
                        ),
                    ],
                    [
                        "title" => __("Bill", "otto-contracts"),
                        "url" => admin_url(
                            "admin.php?page=eac-purchases&tab=bills&action=add",
                        ),
                    ],
                    [
                        "title" => __("Customer", "otto-contracts"),
                        "url" => admin_url(
                            "admin.php?page=eac-sales&tab=customers&action=add",
                        ),
                    ],
                    [
                        "title" => __("Vendor", "otto-contracts"),
                        "url" => admin_url(
                            "admin.php?page=eac-purchases&tab=vendors&action=add",
                        ),
                    ],
                ],
            ],
            [
                "title" => __("Support", "otto-contracts"),
                "icon" => "dashicons dashicons-editor-help",
                "url" => "https://www.softestate.net/",
            ],
        ];
        ?>
		<div class="eac-admin-header">
			<div class="eac-admin-header__wrapper">
				<div class="eac-admin-header__logo">
					<a href="<?php echo esc_url(admin_url("admin.php?page=otto-accounting")); ?>">
						<svg class="svg-icon" width="40" height="40" aria-hidden="true" role="img" focusable="false" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path fill-rule="evenodd" clip-rule="evenodd"
									d="M 18 1.609375 C 14.292969 -0.539062 9.707031 -0.539062 6 1.609375 C 2.292969 3.757812 0 7.714844 0 12 C 0 16.285156 2.292969 20.242188 6 22.390625 C 9.707031 24.539062 14.292969 24.539062 18 22.390625 C 21.707031 20.242188 24 16.285156 24 12 C 24 7.714844 21.707031 3.757812 18 1.609375 Z M 18.371094 13.390625 L 17.496094 18.070312 C 17.339844 18.898438 16.621094 19.488281 15.78125 19.488281 L 14.664062 19.488281 L 15.167969 16.894531 C 13.738281 18.347656 11.039062 19.691406 8.964844 19.691406 C 7.65625 19.691406 6.574219 19.222656 5.722656 18.300781 C 4.871094 17.375 4.441406 16.199219 4.441406 14.785156 C 4.441406 12.898438 5.125 11.230469 6.480469 9.78125 L 6.625 9.636719 C 7.980469 8.257812 9.996094 7.273438 11.964844 7.667969 C 13.65625 8.003906 14.914062 9.457031 15.3125 11.089844 L 15.371094 11.292969 C 15.503906 11.84375 15.203125 12.324219 14.652344 12.46875 L 8.484375 14.039062 L 8.484375 13.164062 L 13.90625 11.304688 C 13.824219 11.136719 13.726562 10.96875 13.609375 10.800781 C 13.019531 9.984375 12.226562 9.574219 11.242188 9.574219 C 10.019531 9.574219 8.941406 10.078125 8.039062 11.074219 C 7.714844 11.4375 7.453125 11.808594 7.246094 12.203125 C 7.007812 12.660156 6.851562 13.140625 6.757812 13.644531 C 6.707031 13.945312 6.671875 14.257812 6.671875 14.578125 C 6.671875 15.492188 6.960938 16.246094 7.523438 16.835938 C 8.101562 17.425781 8.832031 17.6875 9.71875 17.710938 C 10.765625 17.746094 12.238281 17.328125 13.296875 16.777344 C 13.894531 16.464844 14.605469 16.5 15.15625 16.882812 L 15.167969 16.894531 C 15.179688 16.824219 15.191406 16.753906 15.214844 16.679688 C 15.503906 15.191406 15.792969 13.714844 16.078125 12.226562 C 16.378906 10.65625 16.65625 9.109375 15.816406 7.65625 C 15.144531 6.46875 13.859375 5.855469 12.527344 5.773438 C 11.460938 5.699219 10.367188 5.929688 9.382812 6.335938 C 9.300781 6.371094 8.460938 6.757812 8.460938 6.769531 L 7.609375 5.257812 C 7.570312 5.171875 9.144531 4.5 9.289062 4.453125 C 9.898438 4.222656 10.523438 4.042969 11.160156 3.9375 C 12.421875 3.707031 13.738281 3.730469 14.976562 4.09375 C 16.621094 4.585938 18.011719 5.84375 18.515625 7.5 C 19.105469 9.382812 18.636719 11.878906 18.371094 13.390625 Z M 18.371094 13.390625"
									fill="currentColor"/>
						</svg>
					</a>
				</div>
				<h1 class="eac-admin-header__title"><?php esc_html_e(
        "Otto Contracts", 
        "otto-contracts",
    ); ?></h1>
				<?php if (!empty($menus)): ?>
					<ul class="eac-admin-header__menu">
						<?php foreach ($menus as $menu): ?>
							<li>
								<a href="<?php echo esc_url($menu["url"]); ?>">
									<?php if (!empty($menu["icon"])): ?>
										<i class="eac-admin-header__menu-icon <?php echo esc_attr(
              $menu["icon"],
          ); ?>"></i>
									<?php endif; ?>
									<?php if (!empty($menu["title"])): ?>
										<?php echo esc_html($menu["title"]); ?>
									<?php endif; ?>
								</a>
								<?php if (!empty($menu["submenu"])): ?>
									<ul>
										<?php foreach ($menu["submenu"] as $submenu): ?>
											<li><a href="<?php echo esc_url($submenu["url"]); ?>"><?php echo esc_html(
    $submenu["title"],
); ?></a></li>
										<?php endforeach; ?>
									</ul>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		</div>
		<?php
    }

    
    public static function set_screen_option($status, $option, $value)
    {
        $options = [
            "eac_items_per_page",
            "eac_payments_per_page",
            "eac_invoices_per_page",
            "eac_customers_per_page",
            "eac_expenses_per_page",
            "eac_vendors_per_page",
            "eac_bills_per_page",
            "eac_accounts_per_page",
            "eac_transactions_per_page",
            "eac_transfers_per_page",
            "eac_taxes_per_page",
            "eac_categories_per_page",
        ];

        if (in_array($option, $options, true)) {
            return $value;
        }

        return $status;
    }

    
    public function print_scripts()
    {
        
        if (!current_user_can("manage_accounting")) {
            
            return;
        }

        $currencies = eac_get_currencies();
        $base_currency = eac_base_currency();
        ?>
		<script type="text/javascript">
			var eac_currencies = JSON.parse(decodeURIComponent('<?php echo rawurlencode(
       wp_json_encode($currencies),
   ); ?>'));
			var eac_base_currency = '<?php echo esc_js($base_currency); ?>';
		</script>
		<?php
    }

    /**
     * Hide Banking/Tools sidebar submenu items while keeping pages accessible.
     *
     * @return void
     */
    public static function hide_sidebar_submenus()
    {
        $screen = get_current_screen();
        $screen_id = $screen ? $screen->id : "";
        ?>
		<style>
			#toplevel_page_otto-accounting .wp-submenu a[href*="page=eac-banking"],
			#toplevel_page_otto-accounting .wp-submenu a[href*="page=eac-tools"] {
				display: none !important;
			}
			<?php if ("toplevel_page_otto-accounting" === $screen_id): ?>
			.eac-admin-header__title {
				display: none !important;
			}
			#wpbody-content .wrap.eac-wrap {
				margin-top: 0 !important;
				padding-top: 0 !important;
			}
			<?php endif; ?>
		</style>
		<?php
    }

    
    public function print_js_templates()
    {
        $screen = get_current_screen();
        if (!in_array($screen->id, Utilities::get_screen_ids(), true)) {
            return;
        }
        $templates = ["add-payment"];

        foreach ($templates as $template) {
            $file = __DIR__ . "/views/tmpl-" . $template . ".php";
            if (file_exists($file)) {
                include $file;
            }
        }
    }
}
