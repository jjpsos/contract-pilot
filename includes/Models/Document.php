<?php

namespace Jjpsos\ContractPilot\Models;

use Jjpsos\ContractPilot\Database\Relations\BelongsTo;
use Jjpsos\ContractPilot\Database\Relations\HasMany;
use Jjpsos\ContractPilot\Utilities\DatabaseUtil;

class Document extends Model
{
    protected $table = "pilot_documents";


    public $meta_type = "pilot_document";


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
        return contract_pilot_format_amount($this->subtotal, $this->currency);
    }


    protected function get_formatted_tax_attribute()
    {
        return contract_pilot_format_amount($this->tax, $this->currency);
    }


    protected function get_formatted_discount_attribute()
    {
        return contract_pilot_format_amount($this->discount, $this->currency);
    }


    protected function get_formatted_total_attribute()
    {
        return contract_pilot_format_amount($this->total, $this->currency);
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




    /**
     * Persist and retry auto-generated numbers when unique collisions happen.
     *
     * @param bool $auto_generated_number
     * @param int $max_attempts
     * @return mixed
     */
    protected function save_with_number_retry(
        $auto_generated_number,
        $max_attempts = 3
    ) {
        $attempts = max(1, (int) $max_attempts);
        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            $result = parent::save();
            if (!is_wp_error($result)) {
                return $result;
            }

            if (!$this->is_duplicate_number_error($result)) {
                return $result;
            }

            if (!$auto_generated_number) {
                return new \WP_Error(
                    "duplicate_document_number",
                    __(
                        "A document with this number already exists. Please choose a different number.",
                        "contract-pilot",
                    ),
                );
            }

            if ($attempt >= $attempts) {
                return new \WP_Error(
                    "duplicate_document_number",
                    __(
                        "Could not generate a unique document number. Please try again.",
                        "contract-pilot",
                    ),
                );
            }

            $this->number = $this->get_next_number();
        }

        return parent::save();
    }

    /**
     * @param mixed $result
     * @return bool
     */
    protected function is_duplicate_number_error($result)
    {
        if (!is_wp_error($result)) {
            return false;
        }

        $code = (string) $result->get_error_code();
        if (!in_array($code, ["db_insert_error", "db_update_error"], true)) {
            return false;
        }

        $message = strtolower((string) $result->get_error_message());
        if (false === strpos($message, "duplicate entry")) {
            return false;
        }

        return false !== strpos($message, "uq_pilot_documents_type_number");
    }

    public function get_max_number()
    {
        if ('pilot_documents' !== $this->table) {
            return 0;
        }

        $number = DatabaseUtil::get_var_max_document_number($this->type);


        if (!empty($number)) {
            preg_match('/\d+$/', $number, $matches);
            $number = !empty($matches) ? $matches[0] : 0;
        }

        return (int) $number;
    }
}
