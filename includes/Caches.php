<?php

namespace Otto;

defined("ABSPATH") || exit();


class Caches
{
    
    public function __construct()
    {
        add_action("eac_payment_saved", [$this, "clear_payment_cache"]);
        add_action("eac_payment_deleted", [$this, "clear_payment_cache"]);
        add_action("eac_expense_saved", [$this, "clear_expense_cache"]);
        add_action("eac_expense_deleted", [$this, "clear_expense_cache"]);
    }

    
    public function clear_payment_cache()
    {
        delete_transient("eac_payments_report");
        delete_transient("eac_profits_report");
    }

    
    public function clear_expense_cache()
    {
        delete_transient("eac_expenses_report");
        delete_transient("eac_profits_report");
    }
}
