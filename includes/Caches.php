<?php

namespace Jjpsos\ContractPilot;

use Jjpsos\ContractPilot\Utilities\DatabaseUtil;
use Jjpsos\ContractPilot\Utilities\ReportsUtil;

defined("ABSPATH") || exit();


class Caches
{
    public function __construct()
    {
        add_action("contract_pilot_invoice_saved", [$this, "clear_document_transaction_caches"]);
        add_action("contract_pilot_invoice_deleted", [$this, "clear_document_transaction_caches"]);
        add_action("contract_pilot_document_saved", [$this, "clear_document_transaction_caches"]);
        add_action("contract_pilot_document_deleted", [$this, "clear_document_transaction_caches"]);
        add_action("contract_pilot_payment_saved", [$this, "clear_payment_cache"]);
        add_action("contract_pilot_payment_deleted", [$this, "clear_payment_cache"]);
        add_action("contract_pilot_expense_saved", [$this, "clear_expense_cache"]);
        add_action("contract_pilot_expense_deleted", [$this, "clear_expense_cache"]);
    }


    public function clear_payment_cache()
    {
        $this->clear_document_transaction_caches();
    }


    public function clear_expense_cache()
    {
        $this->clear_document_transaction_caches();
    }

    /**
     * Invalidate cached number lookups and reporting snapshots after writes.
     *
     * @return void
     */
    public function clear_document_transaction_caches()
    {
        DatabaseUtil::invalidate_query_cache();
        ReportsUtil::flush_report_caches();
    }
}
