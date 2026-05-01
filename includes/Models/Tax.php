<?php

namespace Otto\Models;


class Tax extends Term
{
    
    protected $object_type = "tax";

    
    protected $appends = ["rate", "compound"];

    
    public function __construct($attributes = null)
    {
        $this->hidden[] = "type";
        $this->hidden[] = "parent_id";
        $this->casts = array_merge($this->casts, [
            "rate" => "float",
            "compound" => "bool",
        ]);
        parent::__construct($attributes);
    }

    

    
    protected function get_rate_attribute()
    {
        return $this->get_meta("rate");
    }

    
    protected function get_compound_attribute()
    {
        return $this->cast("compound", $this->get_meta("compound"));
    }

    
    protected function set_rate_attribute($value)
    {
        $this->set_meta("rate", $value);
    }

    
    protected function set_compound_attribute($value)
    {
        $this->set_meta("compound", $this->cast("compound", $value));
    }

    
    protected function get_formatted_name_attribute()
    {
        return sprintf('%1$s (%2$s%%)', $this->name, $this->rate);
    }

    
    
    public function save()
    {
        if (empty($this->name)) {
            return new \WP_Error(
                "missing_required",
                __("Tax name is required.", "otto-contracts"),
            );
        }
        if (empty($this->rate)) {
            return new \WP_Error(
                "missing_required",
                __("Tax rate is required.", "otto-contracts"),
            );
        }

        return parent::save();
    }

    
    
    public function get_edit_url()
    {
        return admin_url(
            "admin.php?page=eac-settings&tab=taxes&section=rates&action=edit&id=" .
                $this->id,
        );
    }
}
