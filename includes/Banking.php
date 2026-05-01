<?php

namespace Otto;

use Otto\Models\Expense;
use Otto\Models\Payment;

defined("ABSPATH") || exit();


class Banking
{
    
    public function __construct()
    {
        add_action("eac_payment_inserted", [
            __CLASS__,
            "update_account_balance",
        ]);
        add_action("eac_payment_deleted", [
            __CLASS__,
            "update_account_balance",
        ]);
        add_action("eac_payment_updated", [
            __CLASS__,
            "update_account_balance",
        ]);
        add_action("eac_expense_inserted", [
            __CLASS__,
            "update_account_balance",
        ]);
        add_action("eac_expense_updated", [
            __CLASS__,
            "update_account_balance",
        ]);
        add_action("eac_expense_deleted", [
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
            $old_account = EAC()->accounts->get(
                $payment->get_original("account_id"),
            );
            if ($old_account) {
                $old_account->update_balance();
            }
        }

        if ($payment->account_id > 0) {
            $account = EAC()->accounts->get($payment->account_id);
            if ($account) {
                $account->update_balance();
            }
        }
    }
}
