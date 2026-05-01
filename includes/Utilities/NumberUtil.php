<?php

namespace Otto\Utilities;

defined("ABSPATH") || exit();


class NumberUtil
{
    
    public static function round(
        $val,
        $precision = 0,
        $mode = PHP_ROUND_HALF_UP
    ) {
        if (!is_numeric($val)) {
            $val = floatval($val);
        }
        return round($val, $precision, $mode);
    }
}
