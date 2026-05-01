<?php

namespace Otto\API;

defined("ABSPATH") || exit();


class Contacts extends Controller
{
    
    public function get_item_schema()
    {
        $schema = [
            '$schema' => "http://json-schema.org/draft-04/schema#",
            "title" => __("Contact", "otto-contracts"),
            "type" => "object",
            "properties" => [
                "id" => [
                    "description" => __(
                        "Unique identifier for the category.",
                        "otto-contracts",
                    ),
                    "type" => "integer",
                    "context" => ["view", "embed", "edit"],
                    "readonly" => true,
                    "arg_options" => [
                        "sanitize_callback" => "intval",
                    ],
                ],
                "name" => [
                    "description" => __(
                        "Name of the contact.",
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "context" => ["view", "embed", "edit"],
                    "required" => true,
                    "arg_options" => [
                        "sanitize_callback" => "sanitize_text_field",
                    ],
                ],
                "company" => [
                    "description" => __(
                        "Company name of the contact.",
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "context" => ["view", "embed", "edit"],
                    "arg_options" => [
                        "sanitize_callback" => "sanitize_text_field",
                    ],
                ],
                "email" => [
                    "description" => __(
                        "Email address of the contact.",
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "format" => "email",
                    "context" => ["view", "embed", "edit"],
                    "arg_options" => [
                        "sanitize_callback" => "sanitize_email",
                    ],
                ],
                "phone" => [
                    "description" => __(
                        "Phone number of the contact.",
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "format" => "phone",
                    "context" => ["view", "embed", "edit"],
                    "arg_options" => [
                        "sanitize_callback" => "sanitize_text_field",
                    ],
                ],
                "website" => [
                    "description" => __(
                        "Website URL of the contact.",
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "format" => "uri",
                    "context" => ["view", "embed", "edit"],
                    "arg_options" => [
                        "sanitize_callback" => "esc_url_raw",
                    ],
                ],
                "address" => [
                    "description" => __(
                        "Address line 1 of the contact.",
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "context" => ["view", "embed", "edit"],
                    "arg_options" => [
                        "sanitize_callback" => "sanitize_text_field",
                    ],
                ],
                "city" => [
                    "description" => __(
                        "City of the contact.",
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "context" => ["view", "embed", "edit"],
                    "arg_options" => [
                        "sanitize_callback" => "sanitize_text_field",
                    ],
                ],
                "state" => [
                    "description" => __(
                        "State of the contact.",
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "context" => ["view", "embed", "edit"],
                    "arg_options" => [
                        "sanitize_callback" => "sanitize_key",
                    ],
                ],
                "postcode" => [
                    "description" => __(
                        "Postcode of the contact.",
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "format" => "postcode",
                    "context" => ["view", "embed", "edit"],
                    "arg_options" => [
                        "sanitize_callback" => "sanitize_text_field",
                    ],
                ],
                "country" => [
                    "description" => __(
                        "Country of the contact.",
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "enum" => array_keys(eac_get_countries()),
                    "context" => ["view", "embed", "edit"],
                    "arg_options" => [
                        "sanitize_callback" => "sanitize_text_field",
                    ],
                ],
                "tax_number" => [
                    "description" => __(
                        "Tax number of the contact.",
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "context" => ["view", "edit"],
                    "arg_options" => [
                        "sanitize_callback" => "sanitize_text_field",
                    ],
                ],
                "currency" => [
                    "description" => __(
                        "Currency code of the contact.",
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "default" => eac_base_currency(),
                    "context" => ["view", "edit"],
                    "arg_options" => [
                        "sanitize_callback" => "sanitize_text_field",
                    ],
                ],
                "user_id" => [
                    "description" => __(
                        "The ID of the user who created the contact.",
                        "otto-contracts",
                    ),
                    "type" => "integer",
                    "context" => ["view"],
                    "readonly" => true,
                ],
                "uuid" => [
                    "description" => __(
                        "Unique identifier for the resource.",
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "format" => "uuid",
                    "context" => ["view", "embed", "edit"],
                    "readonly" => true,
                ],
                "created_via" => [
                    "description" => __(
                        "The ID of the user who created the contact.",
                        "otto-contracts",
                    ),
                    "type" => "integer",
                    "context" => ["view"],
                    "readonly" => true,
                ],
                "date_updated" => [
                    "description" => __(
                        "The date the category was last updated, in the site's timezone.",
                        "otto-contracts",
                    ),
                    "type" => "date-time",
                    "context" => ["view", "edit"],
                    "readonly" => true,
                ],
                "date_created" => [
                    "description" => __(
                        "The date the category was created, in the site's timezone.",
                        "otto-contracts",
                    ),
                    "type" => "date-time",
                    "context" => ["view", "edit"],
                    "readonly" => true,
                ],
            ],
        ];

        return $this->add_additional_fields_schema($schema);
    }
}
