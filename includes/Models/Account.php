<?php

namespace Otto\Models;

use Otto\ByteKit\Models\Relations\HasMany;

defined("ABSPATH") || exit();


class Account extends Model
{
    
    protected $table = "otto_accounts";

    
    protected $columns = [
        "id",
        "type",
        "name",
        "number",
        "balance",
        "currency",
    ];

    
    protected $attributes = [
        "type" => "bank",
    ];

    
    protected $casts = [
        "id" => "int",
        "type" => "sanitize_text",
        "name" => "sanitize_text",
        "number" => "sanitize_text",
        "balance" => "double",
        "currency" => "sanitize_text",
    ];

    
    protected $appends = ["formatted_name", "formatted_balance"];

    
    protected $has_timestamps = true;

    
    protected $searchable = ["name", "number", "currency"];

    
    public function __construct($attributes = null)
    {
        $this->attributes["currency"] = eac_base_currency();
        parent::__construct($attributes);
    }

    

    
    protected function get_formatted_balance_attribute()
    {
        return eac_format_amount($this->balance, $this->currency);
    }

    
    protected function get_formatted_name_attribute()
    {
        $name = sprintf("%s (%s)", $this->name, $this->currency);
        $number = $this->number;

        return $number ? sprintf("%s - %s", $number, $name) : $name;
    }

    
    public function transactions()
    {
        return $this->has_many(Transaction::class);
    }

    
    
    public function save()
    {
        if (empty($this->name)) {
            return new \WP_Error(
                "missing_required",
                __("Account name is required.", "otto-contracts"),
            );
        }
        if (empty($this->number)) {
            return new \WP_Error(
                "missing_required",
                __("Account number rate is required.", "otto-contracts"),
            );
        }
        if (empty($this->currency)) {
            return new \WP_Error(
                "missing_required",
                __("Currency code is required.", "otto-contracts"),
            );
        }

        return parent::save();
    }

    
    public function update_balance()
    {
        global $wpdb;
        $balance = (float) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(CASE WHEN type='payment' then amount WHEN type='expense' then - amount END) as total FROM {$wpdb->prefix}otto_transactions WHERE account_id=%d",
                $this->id,
            ),
        );

        if ($balance !== $this->balance) {
            $this->balance = $balance;
            $this->save();
        }
    }

    

    
    public function get_edit_url()
    {
        return admin_url(
            "admin.php?page=eac-banking&tab=accounts&action=edit&id=" .
                $this->id,
        );
    }

    
    public function get_view_url()
    {
        return admin_url(
            "admin.php?page=eac-banking&tab=accounts&action=view&id=" .
                $this->id,
        );
    }
}
