<?php

namespace Otto\Models;

use Otto\Utilities\I18nUtil;

defined("ABSPATH") || exit();


class Contact extends Model
{
    
    public $table = "otto_contacts";

    
    public $meta_type = "otto_contact";

    
    protected $columns = [
        "id",
        "type",
        "name",
        "company",
        "email",
        "phone",
        "website",
        "address",
        "city",
        "state",
        "postcode",
        "country",
        "tax_number",
        "currency",
        "user_id",
        "created_via",
    ];

    
    protected $casts = [
        "id" => "int",
        "type" => "sanitize_text",
        "name" => "sanitize_text",
        "company" => "sanitize_text",
        "email" => "sanitize_email",
        "phone" => "sanitize_text",
        "website" => "sanitize_url",
        "address" => "sanitize_text",
        "city" => "sanitize_text",
        "state" => "sanitize_text",
        "postcode" => "sanitize_text",
        "country" => "sanitize_text",
        "tax_number" => "sanitize_text",
        "currency" => "sanitize_text",
        "user_id" => "int",
        "created_via" => "sanitize_text",
    ];

    
    protected $appends = ["formatted_name", "country_name"];

    
    protected $hidden = ["type"];

    
    protected $has_timestamps = true;

    
    protected $searchable = ["name", "company", "email", "phone", "address"];

    

    
    protected function get_country_name_attribute()
    {
        $countries = I18nUtil::get_countries();

        return isset($countries[$this->country])
            ? $countries[$this->country]
            : $this->country;
    }

    
    protected function get_formatted_name_attribute()
    {
        $company = $this->company ? " (" . $this->company . ")" : "";

        return $this->name . $company;
    }

    
    
    public function save()
    {
        if (empty($this->name)) {
            return new \WP_Error(
                "missing_required",
                __("Name is required.", "otto-contracts"),
            );
        }

        if (empty($this->currency)) {
            $this->set("currency", eac_base_currency());
        }

        if (empty($this->author_id) && is_user_logged_in()) {
            $this->author_id = get_current_user_id();
        }

        return parent::save();
    }

    
}
