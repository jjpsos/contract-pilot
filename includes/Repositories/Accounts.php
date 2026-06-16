<?php

namespace Jjpsos\ContractPilot\Repositories;

use Jjpsos\ContractPilot\Models\Account;

defined("ABSPATH") || exit();

/**
 * Repository-style facade for account records.
 *
 * Container: contract_pilot()->accounts. Use for get(), query(), delete(),
 * and lookups such as get_types(). Not for HTTP or admin UI. For admin save
 * workflows, use {@see \Jjpsos\ContractPilot\Services\AccountService}.
 */
class Accounts
{
    public function get($account)
    {
        return Account::find($account);
    }


    public function insert($data, $wp_error = true)
    {
        return Account::insert($data, $wp_error);
    }


    public function delete($id)
    {
        $account = $this->get($id);
        if (!$account) {
            return false;
        }

        return $account->delete();
    }


    public function query($args = [], $count = false)
    {
        if ($count) {
            return Account::count($args);
        }

        return Account::results($args);
    }


    public function get_types()
    {
        $account_types = [
            "bank" => __("Bank", "contract-pilot"),
            "card" => __("Card", "contract-pilot"),
        ];

        return apply_filters("contract_pilot_account_types", $account_types);
    }
}
