<?php

namespace Jjpsos\ContractPilot\Admin\Settings;

use Jjpsos\ContractPilot\Utilities\I18nUtil;

defined("ABSPATH") || exit();


class General extends Page
{
    public function __construct()
    {
        parent::__construct("general", __("General", "contract-pilot"));
    }


    protected function get_own_sections()
    {
        return [
            "" => __("General", "contract-pilot"),
            "currency" => __("Currency", "contract-pilot"),
        ];
    }


    public function get_default_section_settings()
    {
        return [
            [
                "title" => __("Business Information", "contract-pilot"),
                "type" => "title",
                "id" => "general_settings",
            ],
            [
                "title" => __("Name", "contract-pilot"),
                "desc" => __(
                    "The name of your business. This will be used in the invoice, bill, and other documents.",
                    "contract-pilot",
                ),
                "id" => "contract_pilot_business_name",
                "type" => "text",
                "placeholder" => "e.g. XYZ Ltd.",
                "default" => esc_html(get_bloginfo("name")),
                "desc_tip" => true,
            ],
            [
                "title" => __("Email", "contract-pilot"),
                "desc" => __(
                    "The email address of your business. This will be used in the invoice, bill, and other documents.",
                    "contract-pilot",
                ),
                "id" => "contract_pilot_business_email",
                "type" => "email",
                "placeholder" => get_option("admin_email"),
                "default" => get_option("admin_email"),
                "desc_tip" => true,
            ],
            [
                "title" => __("Phone", "contract-pilot"),
                "desc" => __(
                    "The phone number of your business. This will be used in the invoice, bill, and other documents.",
                    "contract-pilot",
                ),
                "id" => "contract_pilot_business_phone",
                "type" => "text",
                "placeholder" => "e.g. +1 123 456 7890",
                "default" => "",
                "desc_tip" => true,
            ],
            [
                "title" => __("Logo", "contract-pilot"),
                "desc" => __(
                    "The logo of your business. This will be used in the invoice, bill, and other documents. Upload via Media Library.",
                    "contract-pilot",
                ),
                "id" => "contract_pilot_business_logo",
                "type" => "text",
                "placeholder" => "",
                "default" => "",
                "desc_tip" => true,
            ],
            [
                "title" => __("Tax Number", "contract-pilot"),
                "desc" => __(
                    "The tax number of your business. This will be used in the invoice, bill, and other documents.",
                    "contract-pilot",
                ),
                "id" => "contract_pilot_business_tax_number",
                "type" => "text",
                "placeholder" => "e.g. 123456789",
                "default" => "",
                "desc_tip" => true,
            ],
            [
                "title" => __("Financial Year Start", "contract-pilot"),
                "desc" => __(
                    "The start date of your financial year.",
                    "contract-pilot",
                ),
                "id" => "contract_pilot_year_start_date",
                "type" => "text",
                "placeholder" => "e.g. 01-01",
                "default" => "01-01",
                "desc_tip" => true,
                "class" => "contract_pilot_datepicker",
                "data-format" => "mm-dd",
            ],
            [
                "type" => "sectionend",
                "id" => "general_settings",
            ],
            [
                "title" => __("Business Address", "contract-pilot"),
                "type" => "title",
                "id" => "business_address",
            ],
            [
                "title" => __("Address", "contract-pilot"),
                "desc" => __(
                    "The street address of your business.",
                    "contract-pilot",
                ),
                "id" => "contract_pilot_business_address",
                "type" => "text",
                "placeholder" => "e.g. 123 Main Street",
                "default" => "",
                "desc_tip" => true,
            ],
            [
                "title" => __("City", "contract-pilot"),
                "desc" => __(
                    "The city in which your business is located. This will be used in the invoice, bill, and other documents.",
                    "contract-pilot",
                ),
                "id" => "contract_pilot_business_city",
                "type" => "text",
                "placeholder" => "e.g. Manhattan",
                "default" => "",
                "desc_tip" => true,
            ],
            [
                "title" => __("State", "contract-pilot"),
                "desc" => __(
                    "The state in which your business is located.",
                    "contract-pilot",
                ),
                "id" => "contract_pilot_business_state",
                "type" => "text",
                "placeholder" => "e.g. New York",
                "default" => "",
                "desc_tip" => true,
            ],
            [
                "title" => __("ZIP", "contract-pilot"),
                "desc" => __(
                    "The postcode or ZIP code of your business (if any). This will be used in the invoice, bill, and other documents.",
                    "contract-pilot",
                ),
                "id" => "contract_pilot_business_postcode",
                "type" => "text",
                "placeholder" => "e.g. 10001",
                "default" => "",
                "desc_tip" => true,
            ],
            [
                "title" => __("Country", "contract-pilot"),
                "desc" => __(
                    "The country in which your business is located.",
                    "contract-pilot",
                ),
                "id" => "contract_pilot_business_country",
                "type" => "select",
                "options" => I18nUtil::get_countries(),
                "class" => "contract_pilot_select2",
                "default" => "US",
                "placeholder" => __(
                    "Select a country&hellip;",
                    "contract-pilot",
                ),
                "desc_tip" => true,
            ],
            [
                "type" => "sectionend",
                "id" => "business_address",
            ],
        ];
    }


    public function get_currency_section_settings()
    {
        return [

            [
                "title" => __("Currency Settings", "contract-pilot"),
                "type" => "title",
                "id" => "currency_options",
            ],

            [
                "title" => __("Base Currency", "contract-pilot"),
                "desc" => __(
                    "The base currency of your business. Currency can not be changed once you have recorded any transaction.",
                    "contract-pilot",
                ),
                "id" => "contract_pilot_base_currency",
                "type" => "select",
                "default" => "USD",
                "class" => "contract_pilot_select2",
                "options" => contract_pilot_allowed_base_currencies(),
                "value" => get_option("contract_pilot_base_currency", "USD"),
                "desc_tip" => true,
            ],

            [
                "title" => __("Currency Position", "contract-pilot"),
                "desc" => __(
                    "The position of the currency symbol.",
                    "contract-pilot",
                ),
                "id" => "contract_pilot_currency_position",
                "type" => "select",
                "default" => "before",
                "options" => [
                    "before" => __("Before", "contract-pilot"),
                    "after" => __("After", "contract-pilot"),
                ],
                "desc_tip" => true,
            ],
            [
                "title" => __("Thousand Separator", "contract-pilot"),
                "desc" => __(
                    "The character used to separate thousands.",
                    "contract-pilot",
                ),
                "id" => "contract_pilot_thousand_separator",
                "type" => "text",
                "placeholder" => ",",
                "default" => ",",
                "desc_tip" => true,
            ],
            [
                "title" => __("Decimal Separator", "contract-pilot"),
                "desc" => __(
                    "The character used to separate decimals.",
                    "contract-pilot",
                ),
                "id" => "contract_pilot_decimal_separator",
                "type" => "text",
                "placeholder" => ".",
                "default" => ".",
                "desc_tip" => true,
            ],
            [
                "title" => __("Currency Precision", "contract-pilot"),
                "desc" => __(
                    "The number of decimal places to display.",
                    "contract-pilot",
                ),
                "id" => "contract_pilot_currency_precision",
                "type" => "number",
                "placeholder" => "2",
                "default" => 2,
                "desc_tip" => true,
            ],

            [
                "type" => "sectionend",
                "id" => "currency_options",
            ],
        ];
    }
}
