<?php

namespace Jjpsos\ContractPilot\Models;

use Jjpsos\ContractPilot\Database\Relations\BelongsTo;
use Jjpsos\ContractPilot\Database\Relations\BelongsToMany;

defined("ABSPATH") || exit();


class Payment extends Transaction
{
    protected $object_type = "payment";


    protected $aliases = [
        "invoice_id" => "document_id",
        "customer_id" => "contact_id",
    ];


    protected $query_vars = [
        "type" => "payment",
        "search_columns" => ["id", "contact_id", "amount", "status", "date"],
    ];


    protected $transitionable = ["status"];


    public function __construct($attributes = null)
    {
        $this->attributes["type"] = $this->get_object_type();
        $this->query_vars["type"] = $this->get_object_type();
        parent::__construct($attributes);
    }




    protected function get_payment_method_label_attribute()
    {
        $modes = contract_pilot_get_payment_methods();

        return array_key_exists($this->payment_method, $modes)
            ? $modes[$this->payment_method]
            : $this->payment_method;
    }


    public function invoice()
    {
        return $this->belongs_to(Invoice::class, "document_id");
    }


    public function notes()
    {
        return $this->belongs_to_many(Note::class, "parent_id")->set(
            "parent_type",
            "payment",
        );
    }



    public function save()
    {
        if (empty($this->payment_date)) {
            return new \WP_Error(
                "missing_required",
                __("Payment date is required.", "contract-pilot"),
            );
        }
        if (empty($this->account_id)) {
            return new \WP_Error(
                "missing_required",
                __("Account is required.", "contract-pilot"),
            );
        }


        if (!$this->exists() || $this->is_dirty("account_id")) {
            $account = Account::find($this->account_id);
            if (!$account) {
                return new \WP_Error(
                    "invalid_account",
                    __("Invalid account.", "contract-pilot"),
                );
            }
            $this->currency = $account->currency;
        }

        return parent::save();
    }




    public function get_next_number()
    {
        $max = $this->get_max_number();
        $prefix = get_option(
            "contract_pilot_payment_prefix",
            strtoupper(substr($this->get_object_type(), 0, 3)) . "-",
        );
        $number = str_pad(
            $max + 1,
            get_option("contract_pilot_payment_digits", 4),
            "0",
            STR_PAD_LEFT,
        );

        return $prefix . $number;
    }


    public function get_edit_url()
    {
        return admin_url(
            "admin.php?page=contract-pilot-sales&tab=payments&action=edit&id=" . $this->id,
        );
    }


    public function get_view_url()
    {
        return admin_url(
            "admin.php?page=contract-pilot-sales&tab=payments&action=view&id=" . $this->id,
        );
    }


    public function get_public_url()
    {
        return site_url("contract-pilot/payment/?uuid=" . $this->uuid);
    }
}
