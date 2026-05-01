<?php

namespace Otto;

defined("ABSPATH") || exit();


class Currencies
{
    
    public function __construct()
    {
        add_filter("eac_currencies", [__CLASS__, "add_exchange_rates"]);
    }

    
    public static function add_exchange_rates($currencies)
    {
        $exchange_rates = get_option("eac_exchange_rates", []);
        if (is_array($exchange_rates) && !empty($exchange_rates)) {
            foreach ($exchange_rates as $code => $exchange_rate) {
                $currencies[$code]["rate"] = $exchange_rate;
            }
        }

        return $currencies;
    }
}
