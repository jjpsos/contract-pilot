<?php

namespace Jjpsos\ContractPilot\Services;

use Jjpsos\ContractPilot\Models\Account;

defined("ABSPATH") || exit();

/**
 * Application service for banking account workflows.
 *
 * Request concerns (nonces, capabilities, flash messages, redirects) remain
 * in the admin layer; this service owns persistence orchestration.
 * For reads and listings, use contract_pilot()->accounts.
 */
class AccountService
{
    /**
     * Create or update an account from sanitized input.
     *
     * @param array $data Sanitized account fields.
     *
     * @return Account|\WP_Error Saved account on success, WP_Error on failure.
     */
    public function save(array $data)
    {
        return Account::insert($data);
    }
}
