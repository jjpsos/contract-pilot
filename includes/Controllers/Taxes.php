<?php

namespace Otto\Controllers;

use Otto\Models\Tax;

defined("ABSPATH") || exit();


class Taxes
{
    
    public function get($tax)
    {
        return Tax::find($tax);
    }

    
    public function insert($data, $wp_error = true)
    {
        return Tax::insert($data, $wp_error);
    }

    
    public function delete($id)
    {
        $tax = $this->get($id);
        if (!$tax) {
            return false;
        }

        return $tax->delete();
    }

    
    public function query($args = [], $count = false)
    {
        if ($count) {
            return Tax::count($args);
        }

        return Tax::results($args);
    }
}
