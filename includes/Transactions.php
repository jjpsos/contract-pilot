<?php

namespace Jjpsos\ContractPilot;

defined("ABSPATH") || exit();


class Transactions
{
    public function __construct()
    {
        add_action("contract_pilot_payment_saved", [
            $this,
            "reset_report_cache",
        ]);
        add_action("contract_pilot_payment_deleted", [
            $this,
            "reset_report_cache",
        ]);
        add_action("contract_pilot_expense_saved", [
            $this,
            "reset_report_cache",
        ]);
        add_action("contract_pilot_expense_deleted", [
            $this,
            "reset_report_cache",
        ]);
    }


    public function reset_report_cache()
    {
        delete_transient("contract_pilot_payments_reports");
        delete_transient("contract_pilot_expenses_reports");
        delete_transient("contract_pilot_profit_reports");
    }
}
