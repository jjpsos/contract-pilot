<?php

namespace Otto\Controllers;

use Otto\Models\Term;

defined("ABSPATH") || exit();


class Terms
{
    
    public function get($term)
    {
        return Term::find($term);
    }

    
    public function insert($data, $wp_error = true)
    {
        return Term::insert($data, $wp_error);
    }

    
    public function delete($id)
    {
        $term = $this->get($id);
        if (!$term) {
            return false;
        }

        return $term->delete();
    }

    
    public function query($args = [], $count = false)
    {
        if ($count) {
            return Term::count($args);
        }

        return Term::results($args);
    }

    
    public function get_taxonomies()
    {
        $types = [
            "category" => __("Category", "otto-contracts"),
            "tax" => __("Tax", "otto-contracts"),
        ];

        return apply_filters("eac_term_taxonomies", $types);
    }
}
