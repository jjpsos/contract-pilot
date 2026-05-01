<?php

namespace Otto\Utilities;

defined("ABSPATH") || exit();


class ColorsUtil
{
    
    public static function rgb_from_hex($color)
    {
        $color = str_replace("#", "", $color ?? "000");
        
        $color = preg_replace('~^(.)(.)(.)$~', '$1$1$2$2$3$3', $color);

        $rgb = [];
        $rgb["R"] = hexdec($color[0] . $color[1]);
        $rgb["G"] = hexdec($color[2] . $color[3]);
        $rgb["B"] = hexdec($color[4] . $color[5]);

        return $rgb;
    }

    
    public static function darken($color, $factor = 30)
    {
        $base = self::rgb_from_hex($color);
        $color = "#";

        foreach ($base as $k => $v) {
            $amount = $v / 100;
            $amount = NumberUtil::round($amount * $factor);
            $new_decimal = $v - $amount;

            $new_hex_component = dechex($new_decimal);
            if (strlen($new_hex_component) < 2) {
                $new_hex_component = "0" . $new_hex_component;
            }
            $color .= $new_hex_component;
        }

        return $color;
    }

    
    public static function lighten($color, $factor = 30)
    {
        $base = self::rgb_from_hex($color);
        $color = "#";

        foreach ($base as $k => $v) {
            $amount = 255 - $v;
            $amount = $amount / 100;
            $amount = NumberUtil::round($amount * $factor);
            $new_decimal = $v + $amount;

            $new_hex_component = dechex($new_decimal);
            if (strlen($new_hex_component) < 2) {
                $new_hex_component = "0" . $new_hex_component;
            }
            $color .= $new_hex_component;
        }

        return $color;
    }

    
    public static function is_lighter($color)
    {
        $hex = str_replace("#", "", $color ?? "");

        $c_r = hexdec(substr($hex, 0, 2));
        $c_g = hexdec(substr($hex, 2, 2));
        $c_b = hexdec(substr($hex, 4, 2));

        $brightness = ($c_r * 299 + $c_g * 587 + $c_b * 114) / 1000;

        return $brightness > 155;
    }

    
    public static function light_or_dark(
        $color,
        $dark = "#000000",
        $light = "#FFFFFF"
    ) {
        return self::is_lighter($color) ? $dark : $light;
    }
}
