<?php

namespace Jjpsos\ContractPilot\Repositories;

use Jjpsos\ContractPilot\Models\Customer;

defined("ABSPATH") || exit();

/**
 * Repository-style facade for customer (client) records.
 *
 * Container: contract_pilot()->customers. Use for get(), query(), delete().
 * For admin save workflows, use {@see \Jjpsos\ContractPilot\Services\CustomerService}.
 */
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
