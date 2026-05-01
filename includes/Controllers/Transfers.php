<?php

namespace Otto\Controllers;

use Otto\Models\Expense;
use Otto\Models\Payment;
use Otto\Models\Transfer;

defined("ABSPATH") || exit();


class Transfers
{
    
    public function get($transfer)
    {
        return Transfer::find($transfer);
    }

    
    public function insert($data, $wp_error = true)
    {
        return Transfer::insert($data, $wp_error);
    }

    
    public function delete($id)
    {
        $transfer = $this->get($id);
        if (!$transfer) {
            return false;
        }

        return $transfer->delete();
    }

    
    public function query($args = [], $count = false)
    {
        if ($count) {
            return Transfer::count($args);
        }

        return Transfer::results($args);
    }
}
