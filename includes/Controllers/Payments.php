<?php

namespace Otto\Controllers;

use Otto\Models\Payment;

defined("ABSPATH") || exit();


class Payments
{
    
    public function get($payment)
    {
        return Payment::find($payment);
    }

    
    public function insert($data, $wp_error = true)
    {
        return Payment::insert($data, $wp_error);
    }

    
    public function delete($id)
    {
        $payment = $this->get($id);
        if (!$payment) {
            return false;
        }

        return $payment->delete();
    }

    
    public function query($args = [], $count = false)
    {
        if ($count) {
            return Payment::count($args);
        }

        return Payment::results($args);
    }
}
