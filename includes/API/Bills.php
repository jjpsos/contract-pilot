<?php

namespace Otto\API;

use Otto\Models\Bill;

defined("ABSPATH") || exit();


class Bills extends Documents
{
    
    protected $rest_base = "bills";

    
    public function get_items_permissions_check($request)
    {
        if (!current_user_can("eac_read_bills")) {
            
            return new \WP_Error(
                "rest_forbidden_context",
                __(
                    "Sorry, you are not allowed to view bills.",
                    "otto-contracts",
                ),
                ["status" => rest_authorization_required_code()],
            );
        }

        return true;
    }

    
    public function create_item_permissions_check($request)
    {
        if (!current_user_can("eac_edit_bills")) {
            
            return new \WP_Error(
                "rest_forbidden_context",
                __(
                    "Sorry, you are not allowed to create bills.",
                    "otto-contracts",
                ),
                ["status" => rest_authorization_required_code()],
            );
        }

        return true;
    }

    
    public function get_item_permissions_check($request)
    {
        $bill = EAC()->bills->get($request["id"]);

        if (empty($bill) || !current_user_can("eac_read_bills")) {
            
            return new \WP_Error(
                "rest_forbidden_context",
                __(
                    "Sorry, you are not allowed to view this bill.",
                    "otto-contracts",
                ),
                ["status" => rest_authorization_required_code()],
            );
        }

        return true;
    }

    
    public function update_item_permissions_check($request)
    {
        $bill = EAC()->bills->get($request["id"]);

        if (empty($bill) || !current_user_can("eac_edit_bills")) {
            
            return new \WP_Error(
                "rest_forbidden_context",
                __(
                    "Sorry, you are not allowed to update this bill.",
                    "otto-contracts",
                ),
                ["status" => rest_authorization_required_code()],
            );
        }

        return true;
    }

    
    public function delete_item_permissions_check($request)
    {
        $bill = EAC()->bills->get($request["id"]);

        if (empty($bill) || !current_user_can("eac_delete_bills")) {
            
            return new \WP_Error(
                "rest_forbidden_context",
                __(
                    "Sorry, you are not allowed to delete this bill.",
                    "otto-contracts",
                ),
                ["status" => rest_authorization_required_code()],
            );
        }

        return true;
    }

    
    public function get_items($request)
    {
        $params = $this->get_collection_params();
        $args = [];
        foreach ($params as $key => $value) {
            if (isset($request[$key])) {
                $args[$key] = $request[$key];
            }
        }

        
        $args = apply_filters("eac_rest_bill_query", $args, $request);
        $items = EAC()->bills->query($args);
        $total = EAC()->bills->query($args, true);
        $page = isset($request["page"]) ? absint($request["page"]) : 1;
        $max_pages = ceil($total / (int) $args["per_page"]);

        $results = [];
        foreach ($items as $item) {
            $data = $this->prepare_item_for_response($item, $request);
            $results[] = $this->prepare_response_for_collection($data);
        }

        $response = rest_ensure_response($results);

        $response->header("X-WP-Total", (int) $total);
        $response->header("X-WP-TotalPages", (int) $max_pages);

        $request_params = $request->get_query_params();
        $base = add_query_arg(
            urlencode_deep($request_params),
            rest_url(sprintf("%s/%s", $this->namespace, $this->rest_base)),
        );

        if ($page > 1) {
            $prev_page = $page - 1;

            if ($prev_page > $max_pages) {
                $prev_page = $max_pages;
            }

            $prev_link = add_query_arg("page", $prev_page, $base);
            $response->link_header("prev", $prev_link);
        }
        if ($max_pages > $page) {
            $next_page = $page + 1;
            $next_link = add_query_arg("page", $next_page, $base);

            $response->link_header("next", $next_link);
        }

        return $response;
    }

    
    public function get_item($request)
    {
        $bill = EAC()->bills->get($request["id"]);
        $data = $this->prepare_item_for_response($bill, $request);

        return rest_ensure_response($data);
    }

    
    public function prepare_item_for_response($item, $request)
    {
        $data = [];

        foreach (array_keys($this->get_schema_properties()) as $key) {
            switch ($key) {
                case "issue_date":
                case "due_date":
                case "sent_date":
                case "payment_date":
                case "date_created":
                case "date_updated":
                    $value = $this->prepare_date_response($item->$key);
                    break;
                case "due_amount":
                    $value = $item->get_due_amount();
                    break;
                case "contact":
                    if (!empty($item->contact)) {
                        $value = new \stdClass();
                        $properties = array_keys(
                            $this->get_schema_properties()[$key]["properties"],
                        );
                        foreach ($properties as $property) {
                            $value->$property = $item->contact->$property;
                        }
                    }
                    break;
                case "items":
                    $value = [];
                    foreach ($item->items as $item) {
                        $item_data = new \stdClass();
                        foreach (
                            array_keys(
                                $this->get_schema_properties()[$key]["items"][
                                    "properties"
                                ],
                            )
                            as $property
                        ) {
                            switch ($property) {
                                case "taxes":
                                    $taxes = [];
                                    foreach ($item->taxes as $tax) {
                                        $tax_data = new \stdClass();
                                        foreach (
                                            array_keys(
                                                $this->get_schema_properties()[
                                                    $key
                                                ]["items"]["properties"][
                                                    "taxes"
                                                ]["items"]["properties"],
                                            )
                                            as $tax_property
                                        ) {
                                            $tax_data->$tax_property =
                                                $tax->$tax_property;
                                        }
                                        $taxes[] = $tax_data;
                                    }
                                    $item_data->$property = $taxes;
                                    break;
                                default:
                                    $item_data->$property = $item->$property;
                                    break;
                            }
                        }
                        $value[] = $item_data;
                    }
                    break;
                case "attachment":
                    if (!empty($item->attachment)) {
                        $value = new \stdClass();
                        $properties = array_keys(
                            $this->get_schema_properties()[$key]["properties"],
                        );
                        foreach ($properties as $property) {
                            $value->$property = $item->attachment->$property;
                        }
                    }
                    break;
                default:
                    $value = $item->$key;
                    break;
            }

            $data[$key] = $value;
        }

        $context = !empty($request["context"]) ? $request["context"] : "view";
        $data = $this->add_additional_fields_to_object($data, $request);
        $data = $this->filter_response_by_context($data, $context);
        $response = rest_ensure_response($data);

        
        return apply_filters(
            "eac_rest_prepare_bill",
            $response,
            $item,
            $request,
        );
    }

    
    protected function prepare_item_for_database($request)
    {
        $schema = $this->get_item_schema();
        $data_keys = array_keys(
            array_filter($schema["properties"], [
                $this,
                "filter_writable_props",
            ]),
        );
        $props = [];
        
        foreach ($data_keys as $key) {
            $value = $request[$key];
            if (!is_null($value)) {
                switch ($key) {
                    case "issue_date":
                    case "due_date":
                    case "sent_date":
                    case "payment_date":
                    case "date_created":
                    case "date_updated":
                        $props[$key] = $this->prepare_date_for_database($value);
                        break;
                    default:
                        $props[$key] = $value;
                        break;
                }
            }
        }

        
        return apply_filters("eac_rest_pre_insert_bill", $props, $request);
    }

    
    public function get_item_schema()
    {
        $schema = [
            '$schema' => "http://json-schema.org/draft-04/schema#",
            "title" => __("Bill", "otto-contracts"),
            "type" => "object",
            "properties" => [
                "id" => [
                    "description" => __(
                        "Unique identifier for the bill.",
                        "otto-contracts",
                    ),
                    "type" => "integer",
                    "context" => ["view", "edit"],
                    "readonly" => true,
                ],
                "status" => [
                    "description" => __(
                        "Status of the bill.",
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "context" => ["view", "edit"],
                    "default" => "draft",
                ],
                "number" => [
                    "description" => __("Bill number.", "otto-contracts"),
                    "type" => "string",
                    "context" => ["view", "edit"],
                    "required" => true,
                ],
                "reference" => [
                    "description" => __(
                        "Bill reference.",
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "context" => ["view", "edit"],
                ],
                "issue_date" => [
                    "description" => __("Issue date.", "otto-contracts"),
                    "type" => "string",
                    "format" => "date-time",
                    "context" => ["view", "edit"],
                ],
                "due_date" => [
                    "description" => __("Due date.", "otto-contracts"),
                    "type" => "string",
                    "format" => "date-time",
                    "context" => ["view", "edit"],
                ],
                "sent_date" => [
                    "description" => __("Sent date.", "otto-contracts"),
                    "type" => "string",
                    "format" => "date-time",
                    "context" => ["view", "edit"],
                ],
                "payment_date" => [
                    "description" => __("Payment date.", "otto-contracts"),
                    "type" => "string",
                    "format" => "date-time",
                    "context" => ["view", "edit"],
                ],
                "discount_value" => [
                    "description" => __("Discount.", "otto-contracts"),
                    "type" => "number",
                    "context" => ["view", "edit"],
                ],
                "discount_type" => [
                    "description" => __("Discount type.", "otto-contracts"),
                    "type" => "string",
                    "enum" => ["fixed", "percentage"],
                    "context" => ["view", "edit"],
                ],
                "subtotal" => [
                    "description" => __("Subtotal.", "otto-contracts"),
                    "type" => "number",
                    "context" => ["view", "edit"],
                ],
                "discount" => [
                    "description" => __(
                        "Discount total.",
                        "otto-contracts",
                    ),
                    "type" => "number",
                    "context" => ["view", "edit"],
                ],
                "tax" => [
                    "description" => __("Tax total.", "otto-contracts"),
                    "type" => "number",
                    "context" => ["view", "edit"],
                ],
                "total" => [
                    "description" => __("Total.", "otto-contracts"),
                    "type" => "number",
                    "context" => ["view", "edit"],
                ],
                "due_amount" => [
                    "description" => __("Due total.", "otto-contracts"),
                    "type" => "float",
                    "context" => ["view", "edit"],
                ],
                "currency" => [
                    "description" => __("Currency code.", "otto-contracts"),
                    "type" => "string",
                    "context" => ["view", "edit"],
                ],
                "exchange_rate" => [
                    "description" => __("Exchange rate.", "otto-contracts"),
                    "type" => "number",
                    "context" => ["view", "edit"],
                ],
                "contact_name" => [
                    "description" => __("Contact name.", "otto-contracts"),
                    "type" => "string",
                    "context" => ["view", "edit"],
                ],
                "contact_company" => [
                    "description" => __(
                        "Contact company.",
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "context" => ["view", "edit"],
                ],
                "contact_email" => [
                    "description" => __("Contact email.", "otto-contracts"),
                    "type" => "string",
                    "context" => ["view", "edit"],
                ],
                "contact_phone" => [
                    "description" => __("Contact phone.", "otto-contracts"),
                    "type" => "string",
                    "context" => ["view", "edit"],
                ],
                "contact_address" => [
                    "description" => __(
                        "Contact address.",
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "context" => ["view", "edit"],
                ],
                "contact_city" => [
                    "description" => __("Contact city.", "otto-contracts"),
                    "type" => "string",
                    "context" => ["view", "edit"],
                ],
                "contact_state" => [
                    "description" => __("Contact state.", "otto-contracts"),
                    "type" => "string",
                    "context" => ["view", "edit"],
                ],
                "contact_postcode" => [
                    "description" => __(
                        "Contact postcode.",
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "context" => ["view", "edit"],
                ],
                "contact_country" => [
                    "description" => __(
                        "Contact country.",
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "context" => ["view", "edit"],
                ],
                "contact_tax_number" => [
                    "description" => __(
                        "Contact tax number.",
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "context" => ["view", "edit"],
                ],
                "note" => [
                    "description" => __("Note.", "otto-contracts"),
                    "type" => "string",
                    "context" => ["view", "edit"],
                ],
                "terms" => [
                    "description" => __("Terms.", "otto-contracts"),
                    "type" => "string",
                    "context" => ["view", "edit"],
                ],
                "attachment_id" => [
                    "description" => __("Attachment ID.", "otto-contracts"),
                    "type" => "integer",
                    "context" => ["edit"],
                ],
                "attachment" => [
                    "description" => __("Attachment.", "otto-contracts"),
                    "type" => "object",
                    "context" => ["view", "edit"],
                    "properties" => [
                        "id" => [
                            "description" => __(
                                "Attachment ID.",
                                "otto-contracts",
                            ),
                            "type" => "integer",
                            "context" => ["view", "edit"],
                        ],
                        "url" => [
                            "description" => __(
                                "Attachment URL.",
                                "otto-contracts",
                            ),
                            "type" => "string",
                            "context" => ["view", "edit"],
                        ],
                        "name" => [
                            "description" => __(
                                "Attachment name.",
                                "otto-contracts",
                            ),
                            "type" => "string",
                            "context" => ["view", "edit"],
                        ],
                    ],
                ],
                "contact_id" => [
                    "description" => __("Contact ID.", "otto-contracts"),
                    "type" => "integer",
                    "context" => ["edit"],
                ],
                "contact" => [
                    "description" => __("Contact.", "otto-contracts"),
                    "type" => "object",
                    "context" => ["view", "edit"],
                    "properties" => [
                        "id" => [
                            "description" => __(
                                "Contact ID.",
                                "otto-contracts",
                            ),
                            "type" => "integer",
                            "context" => ["view", "edit"],
                        ],
                        "name" => [
                            "description" => __(
                                "Contact name.",
                                "otto-contracts",
                            ),
                            "type" => "string",
                            "context" => ["view", "edit"],
                        ],
                    ],
                ],
                "payment_id" => [
                    "description" => __("Payment ID.", "otto-contracts"),
                    "type" => "integer",
                    "context" => ["edit"],
                ],
                "items" => [
                    "description" => __("Bill items.", "otto-contracts"),
                    "type" => "array",
                    "context" => ["view", "edit"],
                    "items" => [
                        "type" => "object",
                        "properties" => [
                            "id" => [
                                "description" => __(
                                    "Item ID.",
                                    "otto-contracts",
                                ),
                                "type" => "integer",
                                "context" => ["view", "edit"],
                                "readonly" => true,
                            ],
                            "name" => [
                                "description" => __(
                                    "Item name.",
                                    "otto-contracts",
                                ),
                                "type" => "string",
                                "context" => ["view", "edit"],
                            ],
                            "description" => [
                                "description" => __(
                                    "Item description.",
                                    "otto-contracts",
                                ),
                                "type" => "string",
                                "context" => ["view", "edit"],
                            ],
                            "item_id" => [
                                "description" => __(
                                    "Item ID.",
                                    "otto-contracts",
                                ),
                                "type" => "mixed",
                                "context" => ["view", "edit"],
                            ],
                            "quantity" => [
                                "description" => __(
                                    "Item quantity.",
                                    "otto-contracts",
                                ),
                                "type" => "integer",
                                "context" => ["view", "edit"],
                            ],
                            "unit" => [
                                "description" => __(
                                    "Item unit.",
                                    "otto-contracts",
                                ),
                                "type" => "string",
                                "context" => ["view", "edit"],
                            ],
                            "price" => [
                                "description" => __(
                                    "Item price per unit.",
                                    "otto-contracts",
                                ),
                                "type" => "number",
                                "context" => ["view", "edit"],
                            ],
                            "taxes" => [
                                "description" => __(
                                    "Item taxes.",
                                    "otto-contracts",
                                ),
                                "type" => "array",
                                "context" => ["view", "edit"],
                                "items" => [
                                    "type" => "object",
                                    "properties" => [
                                        "id" => [
                                            "description" => __(
                                                "Tax ID.",
                                                "otto-contracts",
                                            ),
                                            "type" => "integer",
                                            "context" => ["view", "edit"],
                                            "readonly" => true,
                                        ],
                                        "tax_id" => [
                                            "description" => __(
                                                "Tax ID.",
                                                "otto-contracts",
                                            ),
                                            "type" => "integer",
                                            "context" => ["view", "edit"],
                                        ],
                                        "name" => [
                                            "description" => __(
                                                "Tax name.",
                                                "otto-contracts",
                                            ),
                                            "type" => "string",
                                            "context" => ["view", "edit"],
                                        ],
                                        "rate" => [
                                            "description" => __(
                                                "Tax rate.",
                                                "otto-contracts",
                                            ),
                                            "type" => "number",
                                            "context" => ["view", "edit"],
                                        ],
                                        "amount" => [
                                            "description" => __(
                                                "Tax amount.",
                                                "otto-contracts",
                                            ),
                                            "type" => "number",
                                            "context" => ["view", "edit"],
                                        ],
                                        "compound" => [
                                            "description" => __(
                                                "Compound tax.",
                                                "otto-contracts",
                                            ),
                                            "type" => "boolean",
                                            "context" => ["view", "edit"],
                                        ],
                                    ],
                                ],
                            ],
                            "subtotal" => [
                                "description" => __(
                                    "Item subtotal.",
                                    "otto-contracts",
                                ),
                                "type" => "number",
                                "context" => ["view", "edit"],
                            ],
                            "discount" => [
                                "description" => __(
                                    "Item discount.",
                                    "otto-contracts",
                                ),
                                "type" => "number",
                                "context" => ["view", "edit"],
                            ],
                            "tax" => [
                                "description" => __(
                                    "Item tax.",
                                    "otto-contracts",
                                ),
                                "type" => "number",
                                "context" => ["view", "edit"],
                            ],
                            "total" => [
                                "description" => __(
                                    "Item total.",
                                    "otto-contracts",
                                ),
                                "type" => "number",
                                "context" => ["view", "edit"],
                            ],
                        ],
                    ],
                ],
                "editable" => [
                    "description" => __("Is editable.", "otto-contracts"),
                    "type" => "boolean",
                    "context" => ["view", "edit"],
                ],
                "created_via" => [
                    "description" => __("Created via.", "otto-contracts"),
                    "type" => "string",
                    "context" => ["view", "edit"],
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
                "date_updated" => [
                    "description" => __(
                        'The date the bill was last updated, in the site\'s timezone.',
                        "otto-contracts",
                    ),
                    "type" => "date-time",
                    "context" => ["view", "edit"],
                    "readonly" => true,
                ],
                "date_created" => [
                    "description" => __(
                        'The date the bill was created, in the site\'s timezone.',
                        "otto-contracts",
                    ),
                    "type" => "date-time",
                    "context" => ["view", "edit"],
                    "readonly" => true,
                ],
            ],
        ];

        return $schema;
    }
}
