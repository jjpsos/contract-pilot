<?php

namespace Jjpsos\ContractPilot;

use Jjpsos\ContractPilot\Models\Expense;
use Jjpsos\ContractPilot\Models\Payment;

defined("ABSPATH") || exit();


class Banking
{
    public function __construct()
    {
        add_action("contract_pilot_payment_inserted", [
            __CLASS__,
            "update_account_balance",
        ]);
        add_action("contract_pilot_payment_deleted", [
            __CLASS__,
            "update_account_balance",
        ]);
        add_action("contract_pilot_payment_updated", [
            __CLASS__,
            "update_account_balance",
        ]);
        add_action("contract_pilot_expense_inserted", [
            __CLASS__,
            "update_account_balance",
        ]);
        add_action("contract_pilot_expense_updated", [
            __CLASS__,
            "update_account_balance",
        ]);
        add_action("contract_pilot_expense_deleted", [
            __CLASS__,
            "update_account_balance",
        ]);
    }


    public static function update_account_balance($payment)
    {

        if (
            $payment->get_original("account_id") &&
            $payment->is_dirty("account_id")
        ) {
            $old_account = contract_pilot()->accounts->get(
                $payment->get_original("account_id"),
            );
            if ($old_account) {
                $old_account->update_balance();
            }
        }

        if ($payment->account_id > 0) {
            $account = contract_pilot()->accounts->get($payment->account_id);
            if ($account) {
                $account->update_balance();
            }
        }
    }
}
