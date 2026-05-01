<?php


namespace Otto\Models;

defined("ABSPATH") || exit();


abstract class Model extends \Otto\ByteKit\Models\Model
{
    
    public function get_hook_prefix()
    {
        return "eac_" . $this->get_object_type();
    }
}
