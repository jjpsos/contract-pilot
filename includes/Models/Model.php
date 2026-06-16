<?php

namespace Jjpsos\ContractPilot\Models;

defined("ABSPATH") || exit();


abstract class Model extends \Jjpsos\ContractPilot\Database\Model
{
    public function get_hook_prefix()
    {
        return "contract_pilot_" . $this->get_object_type();
    }
}
