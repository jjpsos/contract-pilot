<?php

namespace Jjpsos\ContractPilot\Repositories;

defined("ABSPATH") || exit();

/**
 * Facade for currency configuration and formatting helpers.
 *
 * Container: contract_pilot()->currencies. Symbol, precision, position,
 * thousand/decimal separators, etc.
 */
class Currencies
{
    public function get_symbol($currency = null)
    {
        return $this->get_config($currency)["symbol"];
    }


    public function get_name($currency = null)
    {
        return $this->get_config($currency)["formatted_name"];
    }


    public function get_precision($currency = null)
    {
        return $this->get_config($currency)["precision"];
    }


    public function get_position($currency = null)
    {
        return $this->get_config($currency)["position"];
    }


    public function get_thousand($currency = null)
    {
        return $this->get_config($currency)["thousand"];
    }


    public function get_decimal($currency = null)
    {
        return $this->get_config($currency)["decimal"];
    }


    public function get_rate($currency = null)
    {
        return $this->get_config($currency)["rate"];
    }


    public function get_config($currency = null)
    {
        $currencies = contract_pilot_get_currencies();
        return array_key_exists($currency, $currencies)
            ? $currencies[$currency]
            : $currencies[contract_pilot_base_currency()];
    }
}
