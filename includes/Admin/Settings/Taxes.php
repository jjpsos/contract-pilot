<?php

namespace Jjpsos\ContractPilot\Admin\Settings;

defined("ABSPATH") || exit();


class Taxes extends Page
{
    public function __construct()
    {
        parent::__construct("taxes", __("Taxes", "contract-pilot"));
    }


    protected function get_own_sections()
    {
        return [
            "" => __("Options", "contract-pilot"),
            "rates" => __("Rates", "contract-pilot"),
        ];
    }


    public function get_default_section_settings()
    {
        return [
            [
                "title" => __("Tax options", "contract-pilot"),
                "type" => "title",
                "id" => "tax_options",
            ],
            [
                "title" => __("Enable Taxes", "contract-pilot"),
                "desc" => __(
                    "Enable tax rates and calculations.",
                    "contract-pilot",
                ),
                "id" => "contract_pilot_tax_enabled",
                "type" => "checkbox",
                "default" => "no",
            ],
            [
                "title" => __("Display tax totals", "contract-pilot"),
                "id" => "contract_pilot_tax_total_display",
                "type" => "select",
                "default" => "single",
                "desc_tip" => true,
                "options" => [
                    "single" => __("As a single total", "contract-pilot"),
                    "itemized" => __("Itemized", "contract-pilot"),
                ],
            ],

            [
                "type" => "sectionend",
                "id" => "tax_options",
            ],
        ];
    }
}
