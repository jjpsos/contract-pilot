<?php

namespace Otto\Admin\Settings;

use Otto\Admin\Settings as AdminSettings;
use Otto\Utilities\I18nUtil;

defined("ABSPATH") || exit();


class General extends Page
{
    
    public function __construct()
    {
        parent::__construct("general", __("General", "otto-contracts"));
    }

    
    protected function get_own_sections()
    {
        return [
            "" => __("General", "otto-contracts"),
            "currency" => __("Currency", "otto-contracts"),
        ];
    }

    
    public function get_default_section_settings()
    {
        return [
            [
                "title" => __("Business Information", "otto-contracts"),
                "type" => "title",
                "id" => "general_settings",
            ],
            [
                "title" => __("Name", "otto-contracts"),
                "desc" => __(
                    "The name of your business. This will be used in the invoice, bill, and other documents.",
                    "otto-contracts",
                ),
                "id" => "eac_business_name",
                "type" => "text",
                "placeholder" => "e.g. XYZ Ltd.",
                "default" => esc_html(get_bloginfo("name")),
                "desc_tip" => true,
            ],
            [
                "title" => __("Email", "otto-contracts"),
                "desc" => __(
                    "The email address of your business. This will be used in the invoice, bill, and other documents.",
                    "otto-contracts",
                ),
                "id" => "eac_business_email",
                "type" => "email",
                "placeholder" => get_option("admin_email"),
                "default" => get_option("admin_email"),
                "desc_tip" => true,
            ],
            [
                "title" => __("Phone", "otto-contracts"),
                "desc" => __(
                    "The phone number of your business. This will be used in the invoice, bill, and other documents.",
                    "otto-contracts",
                ),
                "id" => "eac_business_phone",
                "type" => "text",
                "placeholder" => "e.g. +1 123 456 7890",
                "default" => "",
                "desc_tip" => true,
            ],
            [
                "title" => __("Logo", "otto-contracts"),
                "desc" => __(
                    "The logo of your business. This will be used in the invoice, bill, and other documents. Upload via Media Library.",
                    "otto-contracts",
                ),
                "id" => "eac_business_logo",
                "type" => "text",
                "placeholder" => "",
                "default" => "",
                "desc_tip" => true,
            ],
            [
                "title" => __("Tax Number", "otto-contracts"),
                "desc" => __(
                    "The tax number of your business. This will be used in the invoice, bill, and other documents.",
                    "otto-contracts",
                ),
                "id" => "eac_business_tax_number",
                "type" => "text",
                "placeholder" => "e.g. 123456789",
                "default" => "",
                "desc_tip" => true,
            ],
            [
                "title" => __("Financial Year Start", "otto-contracts"),
                "desc" => __(
                    "The start date of your financial year.",
                    "otto-contracts",
                ),
                "id" => "eac_year_start_date",
                "type" => "text",
                "placeholder" => "e.g. 01-01",
                "default" => "01-01",
                "desc_tip" => true,
                "class" => "eac_datepicker",
                "data-format" => "mm-dd",
            ],
            [
                "type" => "sectionend",
                "id" => "general_settings",
            ],
            [
                "title" => __("Business Address", "otto-contracts"),
                "type" => "title",
                "id" => "business_address",
            ],
            [
                "title" => __("Address", "otto-contracts"),
                "desc" => __(
                    "The street address of your business.",
                    "otto-contracts",
                ),
                "id" => "eac_business_address",
                "type" => "text",
                "placeholder" => "e.g. 123 Main Street",
                "default" => "",
                "desc_tip" => true,
            ],
            [
                "title" => __("City", "otto-contracts"),
                "desc" => __(
                    "The city in which your business is located. This will be used in the invoice, bill, and other documents.",
                    "otto-contracts",
                ),
                "id" => "eac_business_city",
                "type" => "text",
                "placeholder" => "e.g. Manhattan",
                "default" => "",
                "desc_tip" => true,
            ],
            [
                "title" => __("State", "otto-contracts"),
                "desc" => __(
                    "The state in which your business is located.",
                    "otto-contracts",
                ),
                "id" => "eac_business_state",
                "type" => "text",
                "placeholder" => "e.g. New York",
                "default" => "",
                "desc_tip" => true,
            ],
            [
                "title" => __("ZIP", "otto-contracts"),
                "desc" => __(
                    "The postcode or ZIP code of your business (if any). This will be used in the invoice, bill, and other documents.",
                    "otto-contracts",
                ),
                "id" => "eac_business_postcode",
                "type" => "text",
                "placeholder" => "e.g. 10001",
                "default" => "",
                "desc_tip" => true,
            ],
            [
                "title" => __("Country", "otto-contracts"),
                "desc" => __(
                    "The country in which your business is located.",
                    "otto-contracts",
                ),
                "id" => "eac_business_country",
                "type" => "select",
                "options" => I18nUtil::get_countries(),
                "class" => "eac_select2",
                "default" => "US",
                "placeholder" => __(
                    "Select a country&hellip;",
                    "otto-contracts",
                ),
                "desc_tip" => true,
            ],
            [
                "type" => "sectionend",
                "id" => "business_address",
            ],
            [
                "title" => __("Access Control", "otto-contracts"),
                "type" => "title",
                "id" => "access_control_settings",
                "desc" => __(
                    "Grant Banking and Tools access capabilities to selected roles.",
                    "otto-contracts",
                ),
            ],
            [
                "title" => __("Banking/Tools Role Access", "otto-contracts"),
                "id" => "eac_banking_tools_access_roles",
                "type" => "checkboxes",
                "options" => AdminSettings::get_role_options(),
                "default" => [],
                "desc" => __(
                    "Selected roles are granted Banking and Tools capabilities when settings are saved.",
                    "otto-contracts",
                ),
            ],
            [
                "type" => "sectionend",
                "id" => "access_control_settings",
            ],
        ];
    }

    
    public function get_currency_section_settings()
    {
        return [
            
            [
                "title" => __("Currency Settings", "otto-contracts"),
                "type" => "title",
                "id" => "currency_options",
            ],
            
            [
                "title" => __("Base Currency", "otto-contracts"),
                "desc" => __(
                    "The base currency of your business. Currency can not be changed once you have recorded any transaction.",
                    "otto-contracts",
                ),
                "id" => "eac_base_currency",
                "type" => "select",
                "default" => "USD",
                "class" => "eac_select2",
                "options" => wp_list_pluck(
                    eac_get_currencies(),
                    "formatted_name",
                    "code",
                ),
                "value" => get_option("eac_base_currency", "USD"),
                "desc_tip" => true,
            ],

            [
                "title" => __("Currency Position", "otto-contracts"),
                "desc" => __(
                    "The position of the currency symbol.",
                    "otto-contracts",
                ),
                "id" => "eac_currency_position",
                "type" => "select",
                "default" => "before",
                "options" => [
                    "before" => __("Before", "otto-contracts"),
                    "after" => __("After", "otto-contracts"),
                ],
                "desc_tip" => true,
            ],
            [
                "title" => __("Thousand Separator", "otto-contracts"),
                "desc" => __(
                    "The character used to separate thousands.",
                    "otto-contracts",
                ),
                "id" => "eac_thousand_separator",
                "type" => "text",
                "placeholder" => ",",
                "default" => ",",
                "desc_tip" => true,
            ],
            [
                "title" => __("Decimal Separator", "otto-contracts"),
                "desc" => __(
                    "The character used to separate decimals.",
                    "otto-contracts",
                ),
                "id" => "eac_decimal_separator",
                "type" => "text",
                "placeholder" => ".",
                "default" => ".",
                "desc_tip" => true,
            ],
            [
                "title" => __("Currency Precision", "otto-contracts"),
                "desc" => __(
                    "The number of decimal places to display.",
                    "otto-contracts",
                ),
                "id" => "eac_currency_precision",
                "type" => "number",
                "placeholder" => "2",
                "default" => 2,
                "desc_tip" => true,
            ],
            
            [
                "title" => __("Exchange Rates", "otto-contracts"),
                "id" => "eac_exchange_rates",
                "type" => "exchange_rates",
            ],

            [
                "type" => "sectionend",
                "id" => "currency_options",
            ],
        ];
    }
}
