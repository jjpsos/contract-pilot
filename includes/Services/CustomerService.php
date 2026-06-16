<?php

namespace Jjpsos\ContractPilot\Services;

use Jjpsos\ContractPilot\Models\Customer;

defined("ABSPATH") || exit();

/**
 * Application service for customer/client workflows.
 *
 * Request concerns (nonces, capabilities, flash messages, redirects) remain
 * in the admin layer; this service owns persistence orchestration.
 * For reads and listings, use contract_pilot()->customers.
 */
class CustomerService
{
    /**
     * Create or update a customer from sanitized input.
     *
     * @param array $data Sanitized customer fields.
     *
     * @return Customer|\WP_Error Saved customer on success, WP_Error on failure.
     */
    public function save(array $data)
    {
        return Customer::insert($data);
    }
}
