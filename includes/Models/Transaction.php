<?php

namespace Jjpsos\ContractPilot\Models;

use Jjpsos\ContractPilot\Database\Relations\BelongsTo;
use Jjpsos\ContractPilot\Utilities\DatabaseUtil;

defined("ABSPATH") || exit();


class Transaction extends Model
{
    protected $table = "pilot_transactions";


    protected $meta_type = "pilot_transaction";


    protected $columns = [
        "id",
        "type",
        "number",
        "payment_date",
        "amount",
        "currency",
        "exchange_rate",
        "reference",
        "note",
        "payment_method",
        "account_id",
        "document_id",
        "contact_id",
        "category_id",
        "attachment_id",
        "author_id",
        "parent_id",
        "editable",
        "created_via",
        "uuid",
        "date_created",
        "date_updated",
    ];


    protected $attributes = [
        "created_via" => "manual",
    ];


    protected $casts = [
        "id" => "int",
        "type" => "sanitize_text",
        "number" => "sanitize_text",
        "payment_date" => "datetime",
        "amount" => "float",
        "currency" => "sanitize_text",
        "exchange_rate" => "double",
        "reference" => "sanitize_text",
        "note" => "sanitize_textarea",
        "payment_method" => "sanitize_text",
        "account_id" => "int",
        "document_id" => "int",
        "contact_id" => "int",
        "category_id" => "int",
        "attachment_id" => "int",
        "author_id" => "int",
        "parent_id" => "int",
        "editable" => "bool",
        "created_via" => "sanitize_text",
        "uuid" => "sanitize_text",
    ];


    protected $appends = ["formatted_number", "formatted_amount"];


    protected $has_timestamps = true;


    protected $searchable = ["number", "note"];


    public function __construct($attributes = [])
    {
        $this->attributes["uuid"] = wp_generate_uuid4();
        $this->attributes["currency"] = contract_pilot_base_currency();
        parent::__construct($attributes);
    }




    protected function get_formatted_number_attribute()
    {
        $number = empty($this->number)
            ? $this->get_next_number()
            : $this->number;
        $prefix = strtoupper(substr($this->type, 0, 3)) . "-";
        $next = str_pad($number, 4, "0", STR_PAD_LEFT);

        return $prefix . $next;
    }


    protected function get_formatted_amount_attribute()
    {
        return contract_pilot_format_amount($this->amount, $this->currency);
    }


    public function account()
    {
        return $this->belongs_to(Account::class);
    }


    public function category()
    {
        return $this->belongs_to(Category::class);
    }


    public function customer()
    {
        return $this->belongs_to(Customer::class, "contact_id");
    }


    public function contact()
    {
        return $this->belongs_to(Contact::class, "contact_id");
    }


    public function attachment()
    {
        return $this->belongs_to(Attachment::class, "attachment_id");
    }



    public function save()
    {

        if (empty($this->number)) {
            $this->number = $this->get_next_number();
        }

        if (empty($this->uuid)) {
            $this->uuid = wp_generate_uuid4();
        }

        if (empty($this->author_id) && is_user_logged_in()) {
            $this->author_id = get_current_user_id();
        }

        return parent::save();
    }



    public function get_max_number()
    {
        if ('pilot_transactions' !== $this->table) {
            return 0;
        }

        $number = DatabaseUtil::get_var_max_transaction_number($this->type);


        if (!empty($number)) {
            preg_match('/\d+$/', $number, $matches);
            $number = !empty($matches) ? $matches[0] : 0;
        }

        return (int) $number;
    }


    public function get_next_number()
    {
        $max = (int) $this->get_max_number();

        return $max + 1;
    }
}
