<?php

namespace Otto\Controllers;

use Otto\Models\Account;

defined("ABSPATH") || exit();


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
            "bank" => __("Bank", "otto-contracts"),
            "card" => __("Card", "otto-contracts"),
        ];

        return apply_filters("eac_account_types", $account_types);
    }
}
