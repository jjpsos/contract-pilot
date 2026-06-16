<?php

namespace Jjpsos\ContractPilot\Models;

use Jjpsos\ContractPilot\Database\Relations\BelongsTo;

class DocumentTax extends Model
{
    protected $table = "pilot_document_taxes";


    protected $columns = [
        "id",
        "document_id",
        "document_item_id",
        "tax_id",
        "name",
        "rate",
        "compound",
        "amount",
    ];


    protected $casts = [
        "id" => "int",
        "document_id" => "int",
        "document_item_id" => "int",
        "tax_id" => "int",
        "rate" => "double",
        "compound" => "bool",
        "amount" => "double",
    ];


    protected $appends = ["formatted_name"];


    protected $query_vars = [
        "orderby" => "id",
        "order" => "ASC",
    ];




    protected function get_formatted_name_attribute()
    {
        return $this->name . " (" . $this->rate . "%)";
    }


    public function item()
    {
        return $this->belongs_to(DocumentItem::class);
    }


    public function tax()
    {
        return $this->belongs_to(Tax::class);
    }


    public function document()
    {
        return $this->belongs_to(Document::class);
    }



    public function save()
    {
        if (empty($this->rate)) {
            return new \WP_Error(
                "missing_required",
                __("Tax rate is required.", "contract-pilot"),
            );
        }

        if (empty($this->tax_id)) {
            return new \WP_Error(
                "missing_required",
                __("Tax ID is required.", "contract-pilot"),
            );
        }

        if (empty($this->document_id)) {
            return new \WP_Error(
                "missing_required",
                __("Document ID is required.", "contract-pilot"),
            );
        }

        return parent::save();
    }
}
