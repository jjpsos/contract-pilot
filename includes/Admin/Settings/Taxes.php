<?php

namespace Otto\Admin\Settings;

defined("ABSPATH") || exit();


class Taxes extends Page
{
    
    public function __construct()
    {
        parent::__construct("taxes", __("Taxes", "otto-contracts"));
    }

    
    protected function get_own_sections()
    {
        return [
            "" => __("Options", "otto-contracts"),
            "rates" => __("Rates", "otto-contracts"),
        ];
    }

    
    public function get_default_section_settings()
    {
        return [
            [
                "title" => __("Tax options", "otto-contracts"),
                "type" => "title",
                "id" => "tax_options",
            ],
            [
                "title" => __("Enable Taxes", "otto-contracts"),
                "desc" => __(
                    "Enable tax rates and calculations.",
                    "otto-contracts",
                ),
                "id" => "eac_tax_enabled",
                "type" => "checkbox",
                "default" => "no",
            ],
            [
                "title" => __("Display tax totals", "otto-contracts"),
                "id" => "eac_tax_total_display",
                "type" => "select",
                "default" => "single",
                "desc_tip" => true,
                "options" => [
                    "single" => __("As a single total", "otto-contracts"),
                    "itemized" => __("Itemized", "otto-contracts"),
                ],
            ],

            [
                "type" => "sectionend",
                "id" => "tax_options",
            ],
        ];
    }
}
