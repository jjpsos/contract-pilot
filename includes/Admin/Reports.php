<?php

namespace Otto\Admin;

defined("ABSPATH") || exit();


class Reports
{
    
    public function __construct()
    {
        add_filter("eac_reports_page_tabs", [__CLASS__, "register_tabs"]);
        add_action("eac_reports_page_sales_content", [
            __CLASS__,
            "sales_report",
        ]);
        add_action("eac_reports_page_expenses_content", [
            __CLASS__,
            "expenses_report",
        ]);
        add_action("eac_reports_page_profits_content", [
            __CLASS__,
            "profits_report",
        ]);
        add_action("eac_reports_page_taxes_content", [
            __CLASS__,
            "taxes_report",
        ]);
    }

    
    public static function register_tabs($tabs)
    {
        if (current_user_can("eac_read_reports")) {
            
            $tabs["sales"] = __("Sales", "otto-contracts");
            $tabs["expenses"] = __("Expenses", "otto-contracts");
            $tabs["profits"] = __("Profits", "otto-contracts");
        }

        return $tabs;
    }

    
    public static function sales_report()
    {
        Reports\Sales::render();
    }

    
    public static function expenses_report()
    {
        Reports\Expenses::render();
    }

    
    public static function profits_report()
    {
        Reports\Profits::render();
    }

    
    public static function taxes_report()
    {
        Reports\Taxes::render();
    }
}
