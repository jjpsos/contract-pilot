<?php

namespace Otto\Controllers;

use Otto\Models\Transaction;

defined("ABSPATH") || exit();


class Transactions
{
    
    public function get($transaction)
    {
        return Transaction::find($transaction);
    }

    
    public function insert($data, $wp_error = true)
    {
        return Transaction::insert($data, $wp_error);
    }

    
    public function delete($id)
    {
        $transaction = $this->get($id);
        if (!$transaction) {
            return false;
        }

        return $transaction->delete();
    }

    
    public function query($args = [], $count = false)
    {
        if ($count) {
            return Transaction::count($args);
        }

        return Transaction::results($args);
    }

    
    public function get_types()
    {
        return apply_filters("eac_transaction_types", [
            "payment" => esc_html__("Payment", "otto-contracts"),
            "expense" => esc_html__("Expense", "otto-contracts"),
        ]);
    }
}
