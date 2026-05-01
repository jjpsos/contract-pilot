<?php

namespace Otto\Models;

use Otto\ByteKit\Models\Relations\HasMany;

defined("ABSPATH") || exit();


class Category extends Term
{
    
    protected $object_type = "category";

    

    
    protected function set_type_attribute($type)
    {
        if (!array_key_exists($type, EAC()->categories->get_types())) {
            $type = "";
        }

        $this->attributes["type"] = sanitize_text_field($type);
    }

    
    protected function get_formatted_name_attribute()
    {
        return sprintf("%s (#%d)", $this->name, $this->id);
    }

    
    public function transactions()
    {
        return $this->has_many(Transaction::class);
    }

    
    public function payments()
    {
        return $this->has_many(Payment::class);
    }

    
    public function expenses()
    {
        return $this->has_many(Expense::class);
    }

    
    public function items()
    {
        return $this->has_many(Item::class);
    }

    
    
    public function save()
    {
        if (empty($this->name)) {
            return new \WP_Error(
                "missing_required",
                __("Category name is required.", "otto-contracts"),
            );
        }

        if (empty($this->type)) {
            return new \WP_Error(
                "missing_required",
                __("Category type is required.", "otto-contracts"),
            );
        }

        
        $existing = static::results([
            "type" => $this->type,
            "taxonomy" => $this->taxonomy,
            "name" => $this->name,
            "limit" => 1,
        ]);
        if (!empty($existing) && $existing[0]->id !== $this->id) {
            return new \WP_Error(
                "duplicate",
                __(
                    "Category with same name and type already exists.",
                    "otto-contracts",
                ),
            );
        }

        return parent::save();
    }

    

    
    public function get_edit_url()
    {
        return admin_url(
            "admin.php?page=eac-settings&tab=categories&action=edit&id=" .
                $this->id,
        );
    }
}
