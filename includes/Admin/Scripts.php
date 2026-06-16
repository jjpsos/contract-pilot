<?php

namespace Jjpsos\ContractPilot\Admin;

defined("ABSPATH") || exit();


class Scripts
{
    public function __construct()
    {
        add_action("admin_enqueue_scripts", [$this, "register_scripts"]);
        add_action("admin_enqueue_scripts", [$this, "enqueue_global_admin_assets"], 5);
        add_action("admin_enqueue_scripts", [$this, "enqueue_scripts"]);
    }


    public function register_scripts()
    {

        contract_pilot()->scripts->register_script(
            "contract-pilot-line-chart",
            "scripts/line-chart.js",
            [],
            true,
        );
        contract_pilot()->scripts->register_script(
            "contract-pilot-amount-mask",
            "scripts/amount-mask.js",
            ["jquery"],
            true,
        );
        contract_pilot()->scripts->register_script(
            "contract-pilot-select2",
            "scripts/select2.js",
            ["jquery"],
            true,
        );
        contract_pilot()->scripts->register_script(
            "contract-pilot-printthis",
            "scripts/printthis.js",
            ["jquery"],
            true,
        );
        contract_pilot()->scripts->register_script("contract-pilot-money", "packages/money.js");

        contract_pilot()->scripts->register_script(
            "contract-pilot-modal",
            "scripts/modal.js",
            ["jquery"],
            true,
        );
        contract_pilot()->scripts->register_script(
            "contract-pilot-form",
            "scripts/form.js",
            ["jquery"],
            true,
        );
        contract_pilot()->scripts->register_script(
            "contract-pilot-admin",
            "scripts/admin.js",
            [
                "jquery",
                "contract-pilot-amount-mask",
                "contract-pilot-select2",
                "contract-pilot-printthis",
                "jquery-ui-tooltip",
                "contract-pilot-money",
                "contract-pilot-line-chart",
                "wp-ajax-response",
            ],
            true,
        );

        contract_pilot()->scripts->register_style("contract-pilot-admin", "styles/admin.css", [
            "wp-jquery-ui-dialog",
        ]);
    }

    /**
     * Admin-wide CSS (sidebar menu visibility). Enqueued on every wp-admin screen.
     *
     * @param string $hook_suffix Current admin page hook suffix.
     * @return void
     */
    public function enqueue_global_admin_assets($hook_suffix)
    {
        wp_register_style(
            "contract-pilot-admin-global",
            false,
            [],
            contract_pilot()->get_version(),
        );
        wp_enqueue_style("contract-pilot-admin-global");

        $parent = preg_quote(Menus::PARENT_SLUG, "/");
        $css =
            "#toplevel_page_{$parent} .wp-submenu a[href*=\"page=contract-pilot-banking\"] {
				display: none !important;
			}";

        if ("toplevel_page_" . Menus::PARENT_SLUG === $hook_suffix) {
            $css .= '
			#wpbody-content .wrap.contract-pilot-wrap {
				margin-top: 0 !important;
				padding-top: 0 !important;
			}';
        }

        wp_add_inline_style("contract-pilot-admin-global", $css);
    }


    public function enqueue_scripts($hook)
    {
        if (!in_array($hook, Utilities::get_screen_ids(), true)) {
            return;
        }
        wp_enqueue_media();
        wp_enqueue_script("jquery-ui-datepicker");
        wp_enqueue_script("contract-pilot-form");
        wp_enqueue_script("contract-pilot-modal");
        wp_enqueue_script("contract-pilot-admin");
        wp_enqueue_style("contract-pilot-admin");

        wp_add_inline_script(
            "contract-pilot-money",
            sprintf(
                "window.contractPilotMoneyConfig = %s; window.contract_pilot_base_currency = %s;",
                wp_json_encode(contract_pilot_get_money_js_config()),
                wp_json_encode(contract_pilot_base_currency()),
            ),
            "before",
        );

        wp_localize_script("contract-pilot-admin", "contract_pilot_admin_vars", [
            "ajax_url" => admin_url("admin-ajax.php"),
            "admin_nonce" => wp_create_nonce("contract_pilot_admin_action"),
            "base_currency" => contract_pilot_base_currency(),
            "currencies" => contract_pilot_get_currencies(),
            "search_nonce" => wp_create_nonce("contract_pilot_search_action"),
            "i18n" => [
                "confirm_delete" => __(
                    "Are you sure you want to delete this?",
                    "contract-pilot",
                ),
                "close" => __("Close", "contract-pilot"),
            ],
        ]);
        wp_add_inline_script(
            "contract-pilot-admin",
            "(function(){if(window.contractPilotSubmitOnceInit){return;}window.contractPilotSubmitOnceInit=true;document.addEventListener('submit',function(event){var form=event.target;if(!(form instanceof HTMLFormElement)){return;}if(form.matches('#contract-pilot-edit-invoice,#contract-pilot-edit-payment,#contract-pilot-edit-expense')){var idInput=form.querySelector('input[name=\"id\"]');if(idInput&&parseInt(idInput.value||'0',10)>0){return;}if(form.dataset.cpSubmitting==='1'){event.preventDefault();return false;}form.dataset.cpSubmitting='1';var buttons=form.querySelectorAll('button, input[type=\"submit\"]');buttons.forEach(function(btn){btn.disabled=true;btn.classList.add('disabled');});return;}var isInvoicePaymentModal=!!form.closest('.contract-pilot-modal')&&!!form.querySelector('input[name=\"invoice_id\"]');if(!isInvoicePaymentModal){return;}if(form.dataset.cpModalSubmitting==='1'){event.preventDefault();return false;}form.dataset.cpModalSubmitting='1';var modalButtons=form.querySelectorAll('button[type=\"submit\"],input[type=\"submit\"]');modalButtons.forEach(function(btn){btn.disabled=true;btn.classList.add('disabled');});window.setTimeout(function(){if(form.dataset.cpModalSubmitting==='1'){delete form.dataset.cpModalSubmitting;modalButtons.forEach(function(btn){btn.disabled=false;btn.classList.remove('disabled');});}},1500);},true);})();",
            "after",
        );

    }
}
