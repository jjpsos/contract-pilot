<?php

namespace Otto\Controllers;

use Otto\Models\Contact;

defined("ABSPATH") || exit();


class Contacts
{
    
    public function get($contact)
    {
        return Contact::find($contact);
    }

    
    public function insert($data, $wp_error = true)
    {
        return Contact::insert($data, $wp_error);
    }

    
    public function delete($id)
    {
        $contact = $this->get($id);
        if (!$contact) {
            return false;
        }

        return $contact->delete();
    }

    
    public function query($args = [], $count = false)
    {
        if ($count) {
            return Contact::count($args);
        }

        return Contact::results($args);
    }

    
    public function get_types()
    {
        $contact_types = [
            "customer" => esc_html__("Customer", "otto-contracts"),
            "vendor" => esc_html__("Vendor", "otto-contracts"),
        ];

        return apply_filters("eac_contact_types", $contact_types);
    }
}
