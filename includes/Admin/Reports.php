<?php

namespace Jjpsos\ContractPilot\Admin;

defined("ABSPATH") || exit();


class Reports
{
    public function __construct()
    {
        add_filter("contract_pilot_reports_page_tabs", [__CLASS__, "register_tabs"]);
        add_action("contract_pilot_reports_page_sales_content", [
            __CLASS__,
            "sales_report",
        ]);
        add_action("contract_pilot_reports_page_expenses_content", [
            __CLASS__,
            "expenses_report",
        ]);
        add_action("contract_pilot_reports_page_profits_content", [
            __CLASS__,
            "profits_report",
        ]);
        add_action("contract_pilot_reports_page_taxes_content", [
            __CLASS__,
            "taxes_report",
        ]);
    }


    public static function register_tabs($tabs)
    {
        if (current_user_can("contract_pilot_read_reports")) {
            $tabs["sales"] = __("Sales Report", "contract-pilot");
            $tabs["expenses"] = __("Expenses Report", "contract-pilot");
            $tabs["profits"] = __("Profits Report", "contract-pilot");
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
