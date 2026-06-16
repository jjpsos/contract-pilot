<?php

namespace Jjpsos\ContractPilot\Admin\Settings;

defined("ABSPATH") || exit();


class Extensions extends Page
{
    public function __construct()
    {
        parent::__construct(
            "extensions",
            __("Extensions", "contract-pilot"),
        );
    }


    protected function get_own_sections()
    {
        return [];
    }
}
