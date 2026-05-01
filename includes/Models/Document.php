<?php

namespace Otto\Models;

use Otto\ByteKit\Models\Relations\BelongsTo;
use Otto\ByteKit\Models\Relations\HasMany;


class Document extends Model
{
    
    protected $table = "otto_documents";

    
    public $meta_type = "otto_document";

    
    protected $columns = [
        "id",
        "type",
        "status",
        "number",
        "reference",
        "issue_date",
        "due_date",
        "sent_date",
        "payment_date",
        "discount_value",
        "discount_type",
        "subtotal",
        "discount",
        "tax",
        "total",
        "currency",
        "exchange_rate",
        "contact_name",
        "contact_company",
        "contact_email",
        "contact_phone",
        "contact_address",
        "contact_city",
        "contact_state",
        "contact_postcode",
        "contact_country",
        "contact_tax_number",
        "note",
        "terms",
        "attachment_id",
        "contact_id",
        "parent_id",
        "author_id",
        "editable",
        "created_via",
        "uuid",
    ];

    
    protected $attributes = [
        "exchange_rate" => 1.0,
        "discount_type" => "fixed",
        "status" => "draft",
    ];

    
    protected $casts = [
        "number" => "string",
        "reference" => "string",
        "issue_date" => "datetime",
        "due_date" => "datetime",
        "sent_date" => "datetime",
        "payment_date" => "datetime",
        "discount_value" => "float",
        "subtotal" => "double",
        "discount" => "double",
        "tax" => "double",
        "total" => "double",
        "contact_id" => "int",
        "exchange_rate" => "double",
        "transaction_id" => "int",
        "attachment_id" => "int",
        "parent_id" => "int",
        "author_id" => "int",
        "editable" => "bool",
    ];

    
    protected $has_timestamps = true;

    

    
    protected function get_formatted_subtotal_attribute()
    {
        return eac_format_amount($this->subtotal, $this->currency);
    }

    
    protected function get_formatted_tax_attribute()
    {
        return eac_format_amount($this->tax, $this->currency);
    }

    
    protected function get_formatted_discount_attribute()
    {
        return eac_format_amount($this->discount, $this->currency);
    }

    
    protected function get_formatted_total_attribute()
    {
        return eac_format_amount($this->total, $this->currency);
    }

    
    protected function set_discount_type_attribute($type)
    {
        if (!in_array($type, ["fixed", "percentage"], true)) {
            $type = "fixed";
        }
        $this->attributes["discount_type"] = $type;
    }

    
    public function contact()
    {
        return $this->belongs_to(Contact::class, "contact_id");
    }

    
    public function parent()
    {
        return $this->belongs_to(self::class, "parent_id");
    }

    
    public function items()
    {
        return $this->has_many(DocumentItem::class, "document_id");
    }

    
    public function taxes()
    {
        return $this->has_many(DocumentTax::class, "document_id");
    }

    
    public function transactions()
    {
        return $this->belongs_to(Transaction::class, "transaction_id");
    }

    

    
    public function delete()
    {
        $return = parent::delete();
        if ($return) {
            $this->items()->delete();
            $this->taxes()->delete();
            $this->transactions()->delete();
        }

        return $return;
    }

    

    
    public function get_max_number()
    {
        global $wpdb;
        $number = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT number FROM {$wpdb->prefix}{$this->table} WHERE type = %s AND number IS NOT NULL AND number != '' ORDER BY number DESC", 
                esc_sql($this->type),
            ),
        );

        
        if (!empty($number)) {
            preg_match('/\d+$/', $number, $matches);
            $number = !empty($matches) ? $matches[0] : 0;
        }

        return (int) $number;
    }
}
