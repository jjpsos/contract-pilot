<?php

namespace Otto\Controllers;

use Otto\Models\Item;

defined("ABSPATH") || exit();


class Items
{
    
    public function get($item)
    {
        return Item::find($item);
    }

    
    public function insert($data, $wp_error = true)
    {
        return Item::insert($data, $wp_error);
    }

    
    public function delete($id)
    {
        $item = $this->get($id);
        if (!$item) {
            return false;
        }

        return $item->delete();
    }

    
    public function query($args = [], $count = false)
    {
        if ($count) {
            return Item::count($args);
        }

        return Item::results($args);
    }

    
    public function get_types()
    {
        return apply_filters("eac_item_types", [
            "standard" => __("Standard", "otto-contracts"),
            "fee" => __("Fee", "otto-contracts"),
        ]);
    }

    
    public function get_units()
    {
        return apply_filters("eac_item_units", [
            "box" => __("Box", "otto-contracts"),
            "cm" => __("Centimeter", "otto-contracts"),
            "day" => __("Day", "otto-contracts"),
            "doz" => __("Dozen", "otto-contracts"),
            "ft" => __("Feet", "otto-contracts"),
            "gm" => __("Gram", "otto-contracts"),
            "hr" => __("Hour", "otto-contracts"),
            "inch" => __("Inch", "otto-contracts"),
            "kg" => __("Kilogram", "otto-contracts"),
            "km" => __("Kilometer", "otto-contracts"),
            "l" => __("Liter", "otto-contracts"),
            "lb" => __("Pound", "otto-contracts"),
            "m" => __("Meter", "otto-contracts"),
            "mg" => __("Milligram", "otto-contracts"),
            "mile" => __("Mile", "otto-contracts"),
            "min" => __("Minute", "otto-contracts"),
            "mm" => __("Millimeter", "otto-contracts"),
            "month" => __("Month", "otto-contracts"),
            "oz" => __("Ounce", "otto-contracts"),
            "pc" => __("Piece", "otto-contracts"),
            "sec" => __("Second", "otto-contracts"),
            "unit" => __("Unit", "otto-contracts"),
            "week" => __("Week", "otto-contracts"),
            "year" => __("Year", "otto-contracts"),
        ]);
    }
}
