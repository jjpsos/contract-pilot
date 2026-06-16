<?php

namespace Jjpsos\ContractPilot\Admin\Settings;

defined("ABSPATH") || exit();


class Purchases extends Page
{
    public function __construct()
    {
        parent::__construct("purchases", __("Purchases", "contract-pilot"));
    }


    protected function get_own_sections()
    {
        return [
            "" => __("Options", "contract-pilot"),
        ];
    }


    public function get_default_section_settings()
    {
        return [
            [
                "title" => __("Expense Settings", "contract-pilot"),
                "desc" => __(
                    "Customize how your expense number gets generated automatically when you create a new expense.",
                    "contract-pilot",
                ),
                "type" => "title",
                "id" => "expense_settings",
            ],
            [
                "title" => __("Number Prefix", "contract-pilot"),
                "desc" => __(
                    "The prefix of the expense number.",
                    "contract-pilot",
                ),
                "id" => "contract_pilot_expense_prefix",
                "type" => "text",
                "placeholder" => "e.g. EXP-",
                "default" => "EXP-",
                "desc_tip" => true,
            ],
            [
                "title" => __("Minimum Digits", "contract-pilot"),
                "desc" => __(
                    "The minimum digits of the expense number.",
                    "contract-pilot",
                ),
                "id" => "contract_pilot_expense_digits",
                "type" => "number",
                "placeholder" => "e.g. 4",
                "default" => 4,
                "desc_tip" => true,
            ],
            [
                "type" => "sectionend",
                "id" => "expense_settings",
            ],
        ];
    }
}
