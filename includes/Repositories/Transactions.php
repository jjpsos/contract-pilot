<?php

namespace Jjpsos\ContractPilot\Repositories;

use Jjpsos\ContractPilot\Models\Transaction;

defined("ABSPATH") || exit();

/**
 * Repository-style facade for transaction records.
 *
 * Not registered on the plugin container; instantiate or register if needed.
 * Same get/query/insert/delete pattern as other domain repositories.
 */
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
        return apply_filters("contract_pilot_transaction_types", [
            "payment" => esc_html__("Payment", "contract-pilot"),
            "expense" => esc_html__("Expense", "contract-pilot"),
        ]);
    }
}
