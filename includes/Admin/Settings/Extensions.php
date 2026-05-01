<?php

namespace Otto\Admin\Settings;

defined("ABSPATH") || exit();


class Extensions extends Page
{
    
    public function __construct()
    {
        parent::__construct(
            "extensions",
            __("Extensions", "otto-contracts"),
        );
    }

    
    protected function get_own_sections()
    {
        return [];
    }
}
