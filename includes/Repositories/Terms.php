<?php

namespace Jjpsos\ContractPilot\Repositories;

use Jjpsos\ContractPilot\Models\Term;

defined("ABSPATH") || exit();

/**
 * Repository-style facade for term records.
 *
 * Container: contract_pilot()->terms. Use for get(), query(), insert(), delete().
 * Not registered separately from invoice terms UI; no TermService yet.
 */
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
            "category" => __("Category", "contract-pilot"),
            "tax" => __("Tax", "contract-pilot"),
        ];

        return apply_filters("contract_pilot_term_taxonomies", $types);
    }
}
