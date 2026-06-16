<?php

namespace Jjpsos\ContractPilot\Models;

use Jjpsos\ContractPilot\Database\Relations\BelongsTo;
use Jjpsos\ContractPilot\Database\Relations\BelongsToMany;

class Item extends Model
{
    protected $table = "pilot_items";


    protected $meta_type = "pilot_item";


    protected $columns = [
        "id",
        "type",
        "name",
        "description",
        "unit",
        "price",
        "cost",
        "tax_ids",
        "category_id",
    ];


    protected $attributes = [
        "type" => "standard",
    ];


    protected $casts = [
        "type" => "sanitize_text",
        "name" => "sanitize_text",
        "description" => "sanitize_textarea",
        "unit" => "sanitize_text",
        "price" => "double",
        "cost" => "double",
        "tax_ids" => "id_list",
        "category_id" => "int",
    ];


    protected $appends = [
        "formatted_name",
        "formatted_price",
        "formatted_cost",
    ];


    protected $has_timestamps = true;


    protected $searchable = ["name", "description"];




    protected function set_item_type_attribute($value)
    {
        $this->attributes["type"] = array_key_exists(
            $value,
            contract_pilot()->items->get_types(),
        )
            ? $value
            : "standard";
    }


    protected function get_formatted_name_attribute()
    {
        return sprintf("%s (#%s)", $this->name, $this->id);
    }


    public function get_formatted_price_attribute()
    {
        return contract_pilot_format_amount($this->price);
    }


    protected function get_formatted_cost_attribute()
    {
        return contract_pilot_format_amount($this->cost);
    }


    public function category()
    {
        return $this->belongs_to(Category::class);
    }


    public function taxes()
    {
        return $this->belongs_to_many(Tax::class);
    }



    public function save()
    {
        if (empty($this->name)) {
            return new \WP_Error(
                "missing_required",
                __("Service name is required.", "contract-pilot"),
            );
        }
        if (empty($this->type)) {
            return new \WP_Error(
                "missing_required",
                __("Service type is required.", "contract-pilot"),
            );
        }

        if (empty($this->cost)) {
            $this->cost = $this->price;
        }

        return parent::save();
    }




    public function get_edit_url()
    {
        return admin_url(
            "admin.php?page=contract-pilot-items&tab=items&action=edit&id=" . $this->id,
        );
    }


    public function get_view_url()
    {
        return admin_url(
            "admin.php?page=contract-pilot-items&tab=items&action=view&id=" . $this->id,
        );
    }
}
