<?php

namespace Jjpsos\ContractPilot\Models;

defined("ABSPATH") || exit();


class Customer extends Contact
{
    protected $object_type = "customer";


    public function __construct($attributes = [])
    {
        $this->attributes["type"] = $this->get_object_type();
        $this->query_vars["type"] = $this->get_object_type();
        parent::__construct($attributes);
    }





    public function save()
    {

        if (!empty($this->email)) {
            $existing = $this->find(["email" => $this->email]);
            if (!empty($existing) && $existing->id !== $this->id) {
                return new \WP_Error(
                    "duplicate",
                    __(
                        "Customer with same email already exists.",
                        "contract-pilot",
                    ),
                );
            }
        }


        if (!empty($this->phone)) {
            $existing = $this->find(["phone" => $this->phone]);
            if (!empty($existing) && $existing->id !== $this->id) {
                return new \WP_Error(
                    "duplicate",
                    __(
                        "Customer with same phone already exists.",
                        "contract-pilot",
                    ),
                );
            }
        }

        return parent::save();
    }




    public function update_amount_paid()
    {
        global $wpdb;
        // phpcs:ignore -- Aggregate paid amount roll-up from plugin transactions table.
        $amount = (float) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM( amount / exchange_rate)
			 FROM {$wpdb->prefix}pilot_transactions
			 WHERE contact_id = %d
			 AND type = 'payment'",
                $this->id,
            ),
        );
        $this->set_meta("total_paid", $amount);
        return !is_wp_error($this->save()) ? $amount : false;
    }


    public function get_edit_url()
    {
        return admin_url(
            "admin.php?page=contract-pilot-sales&tab=customers&action=edit&id=" .
                $this->id,
        );
    }


    public function get_view_url()
    {
        return admin_url(
            "admin.php?page=contract-pilot-sales&tab=customers&action=view&id=" .
                $this->id,
        );
    }
}
