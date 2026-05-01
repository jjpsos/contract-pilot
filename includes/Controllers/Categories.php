<?php

namespace Otto\Controllers;

use Otto\Models\Category;

defined("ABSPATH") || exit();


class Categories
{
    
    public function get($category)
    {
        return Category::find($category);
    }

    
    public function insert($data, $wp_error = true)
    {
        return Category::insert($data, $wp_error);
    }

    
    public function delete($id)
    {
        $category = $this->get($id);
        if (!$category) {
            return false;
        }

        return $category->delete();
    }

    
    public function query($args = [], $count = false)
    {
        if ($count) {
            return Category::count($args);
        }

        return Category::results($args);
    }

    
    public function get_types()
    {
        $types = [
            "item" => esc_html__("Service", "otto-contracts"),
            "payment" => esc_html__("Payment", "otto-contracts"),
            "expense" => esc_html__("Expense", "otto-contracts"),
        ];

        return apply_filters("eac_category_types", $types);
    }
}
