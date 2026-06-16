<?php

namespace Jjpsos\ContractPilot\Models;

use Jjpsos\ContractPilot\Database\Relations\BelongsTo;
use Jjpsos\ContractPilot\Database\Relations\HasMany;

class DocumentItem extends Model
{
    protected $table = "pilot_document_items";


    protected $columns = [
        "id",
        "document_id",
        "item_id",
        "type",
        "name",
        "description",
        "unit",
        "price",
        "quantity",
        "subtotal",
        "discount",
        "tax",
        "total",
    ];


    protected $attributes = [
        "type" => "standard",
        "quantity" => 1,
    ];


    protected $casts = [
        "id" => "int",
        "item_id" => "int",
        "document_id" => "int",
        "price" => "double",
        "quantity" => "double",
        "subtotal" => "double",
        "discount" => "double",
        "tax" => "double",
        "total" => "double",
    ];


    protected $query_vars = [
        "orderby" => "id",
        "order" => "ASC",
    ];






    public function taxes()
    {
        return $this->has_many(DocumentTax::class, "document_item_id");
    }


    public function item()
    {
        return $this->belongs_to(Item::class);
    }


    public function document()
    {
        return $this->belongs_to(Document::class);
    }




    public function save()
    {
        if (empty($this->type)) {
            return new \WP_Error(
                "required_missing",
                __("Product type is required.", "contract-pilot"),
            );
        }

        if (empty($this->quantity)) {
            return new \WP_Error(
                "required_missing",
                __("Product quantity is required.", "contract-pilot"),
            );
        }

        if (empty($this->document_id)) {
            return new \WP_Error(
                "required_missing",
                __("Document ID is required.", "contract-pilot"),
            );
        }

        return parent::save();
    }


    public function delete()
    {
        $this->taxes()->delete();

        return parent::delete();
    }




    public function set_taxes($taxes)
    {
        $this->taxes()->delete();
        $this->taxes = [];
        foreach ($taxes as $tax_data) {
            if (!is_array($tax_data) || empty($tax_data)) {
                continue;
            }
            $tax_data["tax_id"] = isset($tax_data["tax_id"])
                ? absint($tax_data["tax_id"])
                : 0;
            $tax = contract_pilot()->taxes->get($tax_data["tax_id"]);


            if (!$tax) {
                continue;
            }
            $tax_data = wp_parse_args($tax_data, [
                "name" => $tax->name,
                "rate" => $tax->rate,
            ]);
            $doc_tax = DocumentTax::make($tax_data);
            $doc_tax->tax_id = $tax->id;
            $doc_tax->document_id = $this->document_id;
            $doc_tax->document_item_id = $this->id;
            $doc_tax->amount = 0;
            if ($this->has_tax($doc_tax->tax_id)) {
                continue;
            }

            $this->taxes = array_merge($this->taxes, [$doc_tax]);
        }


        $disc_subtotal = max(0, $this->subtotal - $this->discount);
        $simple_tax = 0;
        $compound_tax = 0;

        foreach ($this->taxes as $item_tax) {
            $item_tax->amount = $item_tax->compound
                ? 0
                : ($disc_subtotal * $item_tax->rate) / 100;
            $simple_tax += $item_tax->compound ? 0 : $item_tax->amount;
        }

        foreach ($this->taxes as $item_tax) {
            if ($item_tax->compound) {
                $item_tax->amount =
                    (($disc_subtotal + $simple_tax) * $item_tax->rate) / 100;
                $compound_tax += $item_tax->amount;
            }
        }

        $this->tax = $simple_tax + $compound_tax;

        return $this;
    }




    public function has_tax($tax_id)
    {
        foreach ($this->taxes as $tax) {
            if ($tax->tax_id === $tax_id) {
                return true;
            }
        }

        return false;
    }
}
