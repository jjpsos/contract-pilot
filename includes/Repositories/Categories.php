<?php

namespace Jjpsos\ContractPilot\Repositories;

use Jjpsos\ContractPilot\Models\Category;

defined("ABSPATH") || exit();

/**
 * Repository-style facade for category records.
 *
 * Container: contract_pilot()->categories. Use for get(), query(), delete().
 * For admin save workflows, use {@see \Jjpsos\ContractPilot\Services\CategoryService}.
 */
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
            "item" => esc_html__("Service", "contract-pilot"),
            "payment" => esc_html__("Payment", "contract-pilot"),
            "expense" => esc_html__("Expense", "contract-pilot"),
        ];

        return apply_filters("contract_pilot_category_types", $types);
    }
}
