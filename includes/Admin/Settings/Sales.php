<?php

namespace Jjpsos\ContractPilot\Admin\Settings;

defined("ABSPATH") || exit();


class Sales extends Page
{
    public function __construct()
    {
        parent::__construct("sales", __("Sales", "contract-pilot"));
    }


    protected function get_own_sections()
    {
        return [
            "" => __("Options", "contract-pilot"),
            "invoices" => __("Contracts/Bills", "contract-pilot"),
        ];
    }


    public function get_default_section_settings()
    {
        return [
            [
                "title" => __("Payment Settings", "contract-pilot"),
                "desc" => __(
                    "Customize how your payment number gets generated automatically when you create a new payment.",
                    "contract-pilot",
                ),
                "type" => "title",
                "id" => "payment_settings",
            ],
            [
                "title" => __("Number Prefix", "contract-pilot"),
                "desc" => __(
                    "The prefix of the payment number.",
                    "contract-pilot",
                ),
                "id" => "contract_pilot_payment_prefix",
                "type" => "text",
                "placeholder" => "e.g. PAY-",
                "default" => "PAY-",
                "desc_tip" => true,
            ],
            [
                "title" => __("Minimum Digits", "contract-pilot"),
                "desc" => __(
                    "The minimum digits of the payment number.",
                    "contract-pilot",
                ),
                "id" => "contract_pilot_payment_digits",
                "type" => "number",
                "placeholder" => "e.g. 4",
                "default" => 4,
                "desc_tip" => true,
            ],
            [
                "type" => "sectionend",
                "id" => "payment_settings",
            ],
        ];
    }


    public function get_invoices_section_settings()
    {
        return [
            [
                "title" => __("Contract Settings", "contract-pilot"),
                "desc" => __(
                    "Customize how your contract number gets generated automatically when you create a new contract.",
                    "contract-pilot",
                ),
                "type" => "title",
                "id" => "general_settings",
            ],

            [
                "title" => __("Number Prefix", "contract-pilot"),
                "desc" => __(
                    "The prefix of the contract number.",
                    "contract-pilot",
                ),
                "id" => "contract_pilot_invoice_prefix",
                "type" => "text",
                "placeholder" => "e.g. INV-",
                "default" => "INV-",
                "desc_tip" => true,
            ],

            [
                "title" => __("Minimum Digits", "contract-pilot"),
                "desc" => __(
                    "The minimum digits of the contract number.",
                    "contract-pilot",
                ),
                "id" => "contract_pilot_invoice_digits",
                "type" => "number",
                "placeholder" => "e.g. 4",
                "default" => 4,
                "desc_tip" => true,
            ],

            [
                "title" => __("Due Date", "contract-pilot"),
                "desc" => __(
                    "Specify how due date is automatically set when you create a contract.",
                    "contract-pilot",
                ),
                "id" => "contract_pilot_invoice_due_date",
                "type" => "number",
                "placeholder" => "e.g. 30",
                "default" => 30,
                "desc_tip" => true,
            ],

            [
                "type" => "sectionend",
                "id" => "general_settings",
            ],
            [
                "title" => __("Contract Defaults", "contract-pilot"),
                "desc" => __(
                    "Customize the default values of your contracts.",
                    "contract-pilot",
                ),
                "type" => "title",
                "id" => "defaults_settings",
            ],
            [
                "title" => __("Notes", "contract-pilot"),
                "desc" => __(
                    "The note that will be added to the contract automatically when you create a new contract.",
                    "contract-pilot",
                ),
                "id" => "contract_pilot_invoice_note",
                "type" => "textarea",
                "placeholder" => "e.g. Thank you for your business!",
                "default" => __(
                    "Thank you for your business!",
                    "contract-pilot",
                ),
                "desc_tip" => true,
            ],
            [
                "title" => __("Terms", "contract-pilot"),
                "desc" => __(
                    "The terms that will be added to the contract automatically when you create a new contract.",
                    "contract-pilot",
                ),
                "id" => "contract_pilot_invoice_terms",
                "type" => "textarea",
                "placeholder" => "e.g. Payment is due within 30 days.",
                "default" => __(
                    "Payment is due within 30 days.",
                    "contract-pilot",
                ),
                "desc_tip" => true,
            ],

            [
                "type" => "sectionend",
                "id" => "defaults_settings",
            ],
            [
                "title" => __("Contract Columns", "contract-pilot"),
                "desc" => __(
                    "Customize the columns of your contracts.",
                    "contract-pilot",
                ),
                "type" => "title",
                "id" => "columns_settings",
            ],


            [
                "title" => __("Service Label", "contract-pilot"),
                "desc" => __(
                    "The name of the service column.",
                    "contract-pilot",
                ),
                "id" => "contract_pilot_invoice_col_item_label",
                "type" => "text",
                "placeholder" => "e.g. Item",
                "default" => __("Items", "contract-pilot"),
                "desc_tip" => true,
            ],

            [
                "title" => __("Price Label", "contract-pilot"),
                "desc" => __(
                    "The name of the price column.",
                    "contract-pilot",
                ),
                "id" => "contract_pilot_invoice_col_price_label",
                "type" => "text",
                "placeholder" => "e.g. Price",
                "default" => __("Price", "contract-pilot"),
                "desc_tip" => true,
            ],

            [
                "title" => __("Quantity Label", "contract-pilot"),
                "desc" => __(
                    "The name of the quantity column.",
                    "contract-pilot",
                ),
                "id" => "contract_pilot_invoice_col_quantity_label",
                "type" => "text",
                "placeholder" => "e.g. Quantity",
                "default" => __("Quantity", "contract-pilot"),
                "desc_tip" => true,
            ],

            [
                "title" => __("Tax Label", "contract-pilot"),
                "desc" => __(
                    "The name of the tax column.",
                    "contract-pilot",
                ),
                "id" => "contract_pilot_invoice_col_tax_label",
                "type" => "text",
                "placeholder" => "e.g. Tax",
                "default" => __("Tax", "contract-pilot"),
                "desc_tip" => true,
            ],

            [
                "title" => __("Subtotal Label", "contract-pilot"),
                "desc" => __(
                    "The name of the subtotal column.",
                    "contract-pilot",
                ),
                "id" => "contract_pilot_invoice_col_subtotal_label",
                "type" => "text",
                "placeholder" => "e.g. Subtotal",
                "default" => __("Subtotal", "contract-pilot"),
                "desc_tip" => true,
            ],

            [
                "type" => "sectionend",
                "id" => "columns_settings",
            ],
        ];
    }
}
