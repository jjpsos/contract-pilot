<?php

namespace Jjpsos\ContractPilot\Models;

defined("ABSPATH") || exit();


class Term extends Model
{
    protected $table = "pilot_terms";


    protected $meta_type = "pilot_term";


    protected $columns = [
        "id",
        "name",
        "description",
        "type",
        "taxonomy",
        "parent_id",
    ];


    protected $attributes = [];


    protected $casts = [
        "name" => "sanitize_text",
        "description" => "sanitize_textarea",
        "type" => "sanitize_key",
        "taxonomy" => "sanitize_key",
        "parent_id" => "int",
    ];


    protected $searchable = ["name", "description"];


    protected $has_timestamps = true;


    public function __construct($attributes = null)
    {
        $this->attributes["taxonomy"] = $this->get_object_type();
        $this->query_vars["taxonomy"] = $this->get_object_type();
        $this->hidden[] = "taxonomy";
        parent::__construct($attributes);
    }




    protected function set_taxonomy_attribute($value)
    {
        if (!array_key_exists($value, contract_pilot()->terms->get_taxonomies())) {
            $value = "";
        }

        $this->attributes["taxonomy"] = sanitize_text_field($value);
    }



    public function save()
    {
        if (empty($this->name)) {
            return new \WP_Error(
                "missing_required",
                __("Term name is required.", "contract-pilot"),
            );
        }
        if (empty($this->taxonomy)) {
            return new \WP_Error(
                "missing_required",
                __("Term type is required.", "contract-pilot"),
            );
        }

        return parent::save();
    }
}
