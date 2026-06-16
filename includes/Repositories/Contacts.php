<?php

namespace Jjpsos\ContractPilot\Repositories;

use Jjpsos\ContractPilot\Models\Contact;

defined("ABSPATH") || exit();

/**
 * Repository-style facade for contact records.
 *
 * Not registered on the plugin container; instantiate or register if needed.
 * Same get/query/insert/delete pattern as other domain repositories.
 */
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
            "customer" => esc_html__("Customer", "contract-pilot"),
        ];

        return apply_filters("contract_pilot_contact_types", $contact_types);
    }
}
