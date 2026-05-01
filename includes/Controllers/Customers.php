<?php

namespace Otto\Controllers;

use Otto\Models\Customer;

defined("ABSPATH") || exit();


class Customers
{
    
    public function get($customer)
    {
        return Customer::find($customer);
    }

    
    public function insert($data, $wp_error = true)
    {
        return Customer::insert($data, $wp_error);
    }

    
    public function delete($id)
    {
        $customer = $this->get($id);
        if (!$customer) {
            return false;
        }

        return $customer->delete();
    }

    
    public function query($args = [], $count = false)
    {
        if ($count) {
            return Customer::count($args);
        }

        return Customer::results($args);
    }
}
