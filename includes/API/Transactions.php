<?php

namespace Otto\API;

defined("ABSPATH") || exit();


class Transactions extends Controller
{
    
    public function get_item_schema()
    {
        $schema = [
            '$schema' => "http://json-schema.org/draft-04/schema#",
            "title" => __("Transaction", "otto-contracts"),
            "type" => "object",
            "properties" => [
                "id" => [
                    "description" => __(
                        "Unique identifier for the transaction.",
                        "otto-contracts",
                    ),
                    "type" => "integer",
                    "context" => ["view", "embed", "edit"],
                    "readonly" => true,
                    "arg_options" => [
                        "sanitize_callback" => "intval",
                    ],
                ],
                "number" => [
                    "description" => __(
                        "Transaction number.",
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "context" => ["view", "embed", "edit"],
                ],
                "date" => [
                    "description" => __(
                        'The date the transaction took place, in the site\'s timezone.',
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "format" => "date-time",
                    "context" => ["view", "embed", "edit"],
                ],
                "amount" => [
                    "description" => __(
                        "Total amount of the transaction.",
                        "otto-contracts",
                    ),
                    "type" => "number",
                    "context" => ["view", "embed", "edit"],
                ],
                "reference" => [
                    "description" => __(
                        "Reference of the transaction.",
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "context" => ["view", "embed", "edit"],
                ],
                "note" => [
                    "description" => __(
                        "Note of the transaction.",
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "context" => ["view", "embed", "edit"],
                ],
                "account" => [
                    "description" => __(
                        "Account of the transaction.",
                        "otto-contracts",
                    ),
                    "type" => "object",
                    "context" => ["view", "embed", "edit"],
                    "properties" => [
                        "id" => [
                            "description" => __(
                                "Unique identifier for the account.",
                                "otto-contracts",
                            ),
                            "type" => "integer",
                            "context" => ["view", "embed", "edit"],
                            "readonly" => true,
                            "required" => true,
                            "arg_options" => [
                                "sanitize_callback" => "intval",
                            ],
                        ],
                        "name" => [
                            "description" => __(
                                "Account name.",
                                "otto-contracts",
                            ),
                            "type" => "string",
                            "context" => ["view", "embed", "edit"],
                        ],
                    ],
                ],
                "document" => [
                    "description" => __(
                        "Document of the transaction.",
                        "otto-contracts",
                    ),
                    "type" => "object",
                    "context" => ["view", "embed", "edit"],
                    "properties" => [
                        "id" => [
                            "description" => __(
                                "Unique identifier for the document.",
                                "otto-contracts",
                            ),
                            "type" => "integer",
                            "context" => ["view", "embed", "edit"],
                            "readonly" => true,
                            "required" => true,
                            "arg_options" => [
                                "sanitize_callback" => "intval",
                            ],
                        ],
                        "name" => [
                            "description" => __(
                                "Document name.",
                                "otto-contracts",
                            ),
                            "type" => "string",
                            "context" => ["view", "embed", "edit"],
                        ],
                    ],
                ],
                "category" => [
                    "description" => __(
                        "Category of the transaction.",
                        "otto-contracts",
                    ),
                    "type" => "object",
                    "context" => ["view", "embed", "edit"],
                    "properties" => [
                        "id" => [
                            "description" => __(
                                "Unique identifier for the category.",
                                "otto-contracts",
                            ),
                            "type" => "integer",
                            "context" => ["view", "embed", "edit"],
                            "readonly" => true,
                            "required" => true,
                            "arg_options" => [
                                "sanitize_callback" => "intval",
                            ],
                        ],
                        "name" => [
                            "description" => __(
                                "Category name.",
                                "otto-contracts",
                            ),
                            "type" => "string",
                            "context" => ["view", "embed", "edit"],
                        ],
                    ],
                ],
                "contact" => [
                    "description" => __(
                        "Contact of the transaction.",
                        "otto-contracts",
                    ),
                    "type" => "object",
                    "context" => ["view", "embed", "edit"],
                    "properties" => [
                        "id" => [
                            "description" => __(
                                "Unique identifier for the contact.",
                                "otto-contracts",
                            ),
                            "type" => "integer",
                            "context" => ["view", "embed", "edit"],
                            "readonly" => true,
                            "required" => true,
                            "arg_options" => [
                                "sanitize_callback" => "intval",
                            ],
                        ],
                        "name" => [
                            "description" => __(
                                "Contact name.",
                                "otto-contracts",
                            ),
                            "type" => "string",
                            "context" => ["view", "embed", "edit"],
                        ],
                    ],
                ],
                "mode" => [
                    "description" => __(
                        "Payment method of the transaction.",
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "enum" => array_keys(eac_get_payment_methods()),
                    "context" => ["view", "embed", "edit"],
                ],
                "attachment" => [
                    "description" => __(
                        "Attachment of the transaction.",
                        "otto-contracts",
                    ),
                    "type" => "object",
                    "context" => ["view", "embed", "edit"],
                    "properties" => [
                        "id" => [
                            "description" => __(
                                "Unique identifier for the attachment.",
                                "otto-contracts",
                            ),
                            "type" => "integer",
                            "context" => ["view", "embed", "edit"],
                            "readonly" => true,
                            "required" => true,
                            "arg_options" => [
                                "sanitize_callback" => "intval",
                            ],
                        ],
                        "url" => [
                            "description" => __(
                                "Attachment URL.",
                                "otto-contracts",
                            ),
                            "type" => "string",
                            "context" => ["view", "embed", "edit"],
                        ],
                    ],
                ],
                "currency" => [
                    "description" => __(
                        "Currency of the transaction.",
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "enum" => array_keys(eac_get_currencies()),
                    "context" => ["view", "embed", "edit"],
                ],
                "exchange_rate" => [
                    "description" => __(
                        "exchange_rate rate of the transaction.",
                        "otto-contracts",
                    ),
                    "type" => "number",
                    "context" => ["view", "embed", "edit"],
                ],
                "reconciled" => [
                    "description" => __(
                        "Whether the transaction is reconciled.",
                        "otto-contracts",
                    ),
                    "type" => "boolean",
                    "context" => ["view", "embed", "edit"],
                ],
                "parent_id" => [
                    "description" => __(
                        "Parent transaction ID.",
                        "otto-contracts",
                    ),
                    "type" => "integer",
                    "context" => ["view", "embed", "edit"],
                    "readonly" => true,
                    "arg_options" => [
                        "sanitize_callback" => "intval",
                    ],
                ],
                "uuid" => [
                    "description" => __(
                        "Unique identifier for the transaction.",
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "context" => ["view", "embed", "edit"],
                ],
                "date_updated" => [
                    "description" => __(
                        "The date the transaction was last modified, in the site's timezone.",
                        "otto-contracts",
                    ),
                    "type" => "date-time",
                    "context" => ["view", "embed", "edit"],
                    "readonly" => true,
                ],
                "date_created" => [
                    "description" => __(
                        "The date the transaction was created, in the site's timezone.",
                        "otto-contracts",
                    ),
                    "type" => "date-time",
                    "context" => ["view", "embed", "edit"],
                    "readonly" => true,
                ],
            ],
        ];

        
        $schema = apply_filters(
            "eac_rest_" . $this->rest_base . "_item_schema",
            $schema,
        );

        return $this->add_additional_fields_schema($schema);
    }
}
