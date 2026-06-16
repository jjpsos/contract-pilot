<?php

namespace Jjpsos\ContractPilot\Repositories;

defined('ABSPATH') || exit;

/**
 * Facade for business profile settings stored in WordPress options.
 *
 * Container: contract_pilot()->business. Read accessors for name, email,
 * address, logo, and related settings — not an MVC controller.
 */
class Business
{
    public function get_name()
    {
        $v = get_option('contract_pilot_business_name', '');
        return '' !== $v ? $v : get_option('contract_pilot_company_name', '');
    }


    public function get_email()
    {
        $v = get_option('contract_pilot_business_email', '');
        return '' !== $v ? $v : get_option('contract_pilot_company_email', '');
    }


    public function get_phone()
    {
        $v = get_option('contract_pilot_business_phone', '');
        return '' !== $v ? $v : get_option('contract_pilot_company_phone', '');
    }


    public function get_address()
    {
        $v = get_option('contract_pilot_business_address', '');
        return '' !== $v ? $v : get_option('contract_pilot_company_address', '');
    }


    public function get_city()
    {
        $v = get_option('contract_pilot_business_city', '');
        return '' !== $v ? $v : get_option('contract_pilot_company_city', '');
    }


    public function get_state()
    {
        return get_option('contract_pilot_business_state', '');
    }


    public function get_postcode()
    {
        $v = get_option('contract_pilot_business_postcode', '');
        return '' !== $v ? $v : get_option('contract_pilot_business_zip', '');
    }


    public function get_country()
    {
        return get_option('contract_pilot_business_country', '');
    }


    public function get_logo()
    {
        return get_option('contract_pilot_business_logo', '');
    }


    public function get_currency()
    {
        $currency = get_option('contract_pilot_base_currency', 'USD');

        return empty($currency) ? 'USD' : $currency;
    }


    public function get_tax_number()
    {
        return get_option('contract_pilot_business_tax_number', '');
    }


    public function get_year_start_date($year = '')
    {
        if (empty($year)) {
            $year = wp_date('Y');
        }

        $year_start = get_option('contract_pilot_year_start_date', '01-01');
        $dates      = explode('-', $year_start);
        $month      = ! empty($dates[0]) ? $dates[0] : '01';
        $day        = ! empty($dates[1]) ? $dates[1] : '01';
        $year       = empty($year) ? (int) wp_date('Y') : absint($year);

        return wp_date('Y-m-d', mktime(0, 0, 0, $month, $day, $year));
    }


    public function get_year_end_date($year = '')
    {
        if (empty($year)) {
            $year = wp_date('Y');
        }

        $start_date = $this->get_year_start_date($year);


        if (wp_date('Y') === $year) {
            return contract_pilot_format_datetime();
        }

        return wp_date('Y-m-d', strtotime($start_date . ' +1 year -1 day'));
    }
}
