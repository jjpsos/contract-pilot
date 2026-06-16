<?php

namespace Jjpsos\ContractPilot\Admin;

defined("ABSPATH") || exit();


class Admin
{
    public function __construct()
    {
        add_filter("admin_body_class", [$this, "body_class"]);
        add_filter(
            "admin_footer_text",
            [$this, "admin_footer_text"],
            PHP_INT_MAX,
        );
        add_filter("update_footer", [$this, "update_footer"], PHP_INT_MAX);
        add_filter(
            "set-screen-option",
            [__CLASS__, "set_screen_option"],
            10,
            3,
        );
        add_action("admin_footer", [$this, "print_js_templates"]);
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
            $classes .= " contract-pilot-admin";
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
                /* translators: %s: plugin name (may contain HTML markup) */
                __("Thank you for using %s!", "contract-pilot"),
                "<strong>" . esc_html(contract_pilot()->get_name()) . "</strong>",
            );
            if (contract_pilot()->review_url) {
                $text .= sprintf(
                    /* translators: %s: "here" link HTML to leave a review */
                    __(
                        " Share your appreciation with a five-star review %s.",
                        "contract-pilot",
                    ),
                    '<a href="' .
                        esc_url(contract_pilot()->review_url) .
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
                /* translators: %s: plugin version number */
                esc_html__("Version %s", "contract-pilot"),
                contract_pilot()->get_version(),
            );
        }

        return $footer_text;
    }

    public static function set_screen_option($status, $option, $value)
    {
        $options = [
            "contract_pilot_items_per_page",
            "contract_pilot_payments_per_page",
            "contract_pilot_invoices_per_page",
            "contract_pilot_customers_per_page",
            "contract_pilot_expenses_per_page",
            "contract_pilot_accounts_per_page",
            "contract_pilot_transactions_per_page",
            "contract_pilot_transfers_per_page",
            "contract_pilot_taxes_per_page",
            "contract_pilot_categories_per_page",
        ];

        if (in_array($option, $options, true)) {
            return $value;
        }

        return $status;
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
