<?php

namespace Otto;

use Otto\Utilities\ReportsUtil;

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
        ReportsUtil::flush_report_caches();
    }

    
    public function clear_expense_cache()
    {
        ReportsUtil::flush_report_caches();
    }
}
