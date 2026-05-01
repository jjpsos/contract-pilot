<?php

namespace Otto;

defined("ABSPATH") || exit();


class Transactions
{
    
    public function __construct()
    {
        add_action("otto_accounting_payment_saved", [
            $this,
            "reset_report_cache",
        ]);
        add_action("otto_accounting_payment_deleted", [
            $this,
            "reset_report_cache",
        ]);
        add_action("otto_accounting_expense_saved", [
            $this,
            "reset_report_cache",
        ]);
        add_action("otto_accounting_expense_deleted", [
            $this,
            "reset_report_cache",
        ]);
    }

    
    public function reset_report_cache()
    {
        delete_transient("eac_payments_reports");
        delete_transient("eac_expenses_reports");
        delete_transient("eac_profit_reports");
    }
}
