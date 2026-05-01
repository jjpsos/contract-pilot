<?php

namespace Otto\Admin\Settings;

defined("ABSPATH") || exit();


class Sales extends Page
{
    
    public function __construct()
    {
        parent::__construct("sales", __("Sales", "otto-contracts"));
    }

    
    protected function get_own_sections()
    {
        return [
            "" => __("Options", "otto-contracts"),
            "invoices" => __("Contracts/Bills", "otto-contracts"),
        ];
    }

    
    public function get_default_section_settings()
    {
        return [
            [
                "title" => __("Payment Settings", "otto-contracts"),
                "desc" => __(
                    "Customize how your payment number gets generated automatically when you create a new payment.",
                    "otto-contracts",
                ),
                "type" => "title",
                "id" => "payment_settings",
            ],
            [
                "title" => __("Number Prefix", "otto-contracts"),
                "desc" => __(
                    "The prefix of the payment number.",
                    "otto-contracts",
                ),
                "id" => "eac_payment_prefix",
                "type" => "text",
                "placeholder" => "e.g. PAY-",
                "default" => "PAY-",
                "desc_tip" => true,
            ],
            [
                "title" => __("Minimum Digits", "otto-contracts"),
                "desc" => __(
                    "The minimum digits of the payment number.",
                    "otto-contracts",
                ),
                "id" => "eac_payment_digits",
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
                "title" => __("Contract Settings", "otto-contracts"),
                "desc" => __(
                    "Customize how your contract number gets generated automatically when you create a new contract.",
                    "otto-contracts",
                ),
                "type" => "title",
                "id" => "general_settings",
            ],
            
            [
                "title" => __("Number Prefix", "otto-contracts"),
                "desc" => __(
                    "The prefix of the contract number.",
                    "otto-contracts",
                ),
                "id" => "eac_invoice_prefix",
                "type" => "text",
                "placeholder" => "e.g. INV-",
                "default" => "INV-",
                "desc_tip" => true,
            ],
            
            [
                "title" => __("Minimum Digits", "otto-contracts"),
                "desc" => __(
                    "The minimum digits of the contract number.",
                    "otto-contracts",
                ),
                "id" => "eac_invoice_digits",
                "type" => "number",
                "placeholder" => "e.g. 4",
                "default" => 4,
                "desc_tip" => true,
            ],
            
            [
                "title" => __("Due Date", "otto-contracts"),
                "desc" => __(
                    "Specify how due date is automatically set when you create a contract.",
                    "otto-contracts",
                ),
                "id" => "eac_invoice_due_date",
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
                "title" => __("Contract Defaults", "otto-contracts"),
                "desc" => __(
                    "Customize the default values of your contracts.",
                    "otto-contracts",
                ),
                "type" => "title",
                "id" => "defaults_settings",
            ],
            [
                "title" => __("Notes", "otto-contracts"),
                "desc" => __(
                    "The note that will be added to the contract automatically when you create a new contract.",
                    "otto-contracts",
                ),
                "id" => "eac_invoice_note",
                "type" => "textarea",
                "placeholder" => "e.g. Thank you for your business!",
                "default" => __(
                    "Thank you for your business!",
                    "otto-contracts",
                ),
                "desc_tip" => true,
            ],
            [
                "title" => __("Terms", "otto-contracts"),
                "desc" => __(
                    "The terms that will be added to the contract automatically when you create a new contract.",
                    "otto-contracts",
                ),
                "id" => "eac_invoice_terms",
                "type" => "textarea",
                "placeholder" => "e.g. Payment is due within 30 days.",
                "default" => __(
                    "Payment is due within 30 days.",
                    "otto-contracts",
                ),
                "desc_tip" => true,
            ],
            
            [
                "type" => "sectionend",
                "id" => "defaults_settings",
            ],
            [
                "title" => __("Contract Columns", "otto-contracts"),
                "desc" => __(
                    "Customize the columns of your contracts.",
                    "otto-contracts",
                ),
                "type" => "title",
                "id" => "columns_settings",
            ],

            
            [
                "title" => __("Service Label", "otto-contracts"),
                "desc" => __(
                    "The name of the service column.",
                    "otto-contracts",
                ),
                "id" => "eac_invoice_col_item_label",
                "type" => "text",
                "placeholder" => "e.g. Item",
                "default" => __("Items", "otto-contracts"),
                "desc_tip" => true,
            ],
            
            [
                "title" => __("Price Label", "otto-contracts"),
                "desc" => __(
                    "The name of the price column.",
                    "otto-contracts",
                ),
                "id" => "eac_invoice_col_price_label",
                "type" => "text",
                "placeholder" => "e.g. Price",
                "default" => __("Price", "otto-contracts"),
                "desc_tip" => true,
            ],
            
            [
                "title" => __("Quantity Label", "otto-contracts"),
                "desc" => __(
                    "The name of the quantity column.",
                    "otto-contracts",
                ),
                "id" => "eac_invoice_col_quantity_label",
                "type" => "text",
                "placeholder" => "e.g. Quantity",
                "default" => __("Quantity", "otto-contracts"),
                "desc_tip" => true,
            ],
            
            [
                "title" => __("Tax Label", "otto-contracts"),
                "desc" => __(
                    "The name of the tax column.",
                    "otto-contracts",
                ),
                "id" => "eac_invoice_col_tax_label",
                "type" => "text",
                "placeholder" => "e.g. Tax",
                "default" => __("Tax", "otto-contracts"),
                "desc_tip" => true,
            ],
            
            [
                "title" => __("Subtotal Label", "otto-contracts"),
                "desc" => __(
                    "The name of the subtotal column.",
                    "otto-contracts",
                ),
                "id" => "eac_invoice_col_subtotal_label",
                "type" => "text",
                "placeholder" => "e.g. Subtotal",
                "default" => __("Subtotal", "otto-contracts"),
                "desc_tip" => true,
            ],
            
            [
                "type" => "sectionend",
                "id" => "columns_settings",
            ],
        ];
    }
}
