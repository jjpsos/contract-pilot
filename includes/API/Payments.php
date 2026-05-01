<?php

namespace Otto\API;

use Otto\Models\Payment;

defined("ABSPATH") || exit();


class Payments extends Transactions
{
    
    protected $rest_base = "payments";

    
    public function register_routes()
    {
        register_rest_route($this->namespace, "/" . $this->rest_base, [
            [
                "methods" => \WP_REST_Server::READABLE,
                "callback" => [$this, "get_items"],
                "permission_callback" => [$this, "get_items_permissions_check"],
                "args" => $this->get_collection_params(),
            ],
            [
                "methods" => \WP_REST_Server::CREATABLE,
                "callback" => [$this, "create_item"],
                "permission_callback" => [
                    $this,
                    "create_item_permissions_check",
                ],
                "args" => $this->get_endpoint_args_for_item_schema(
                    \WP_REST_Server::CREATABLE,
                ),
            ],
            "schema" => [$this, "get_public_item_schema"],
        ]);

        $get_item_args = [
            "context" => $this->get_context_param(["default" => "view"]),
        ];

        register_rest_route(
            $this->namespace,
            "/" . $this->rest_base . "/(?P<id>[\d]+)",
            [
                "args" => [
                    "id" => [
                        "description" => __(
                            "Unique identifier for the payment.",
                            "otto-contracts",
                        ),
                    ],
                ],
                [
                    "methods" => \WP_REST_Server::READABLE,
                    "callback" => [$this, "get_item"],
                    "permission_callback" => [
                        $this,
                        "get_item_permissions_check",
                    ],
                    "args" => $get_item_args,
                ],
                [
                    "methods" => \WP_REST_Server::EDITABLE,
                    "callback" => [$this, "update_item"],
                    "permission_callback" => [
                        $this,
                        "update_item_permissions_check",
                    ],
                    "args" => $this->get_endpoint_args_for_item_schema(
                        \WP_REST_Server::EDITABLE,
                    ),
                ],
                [
                    "methods" => \WP_REST_Server::DELETABLE,
                    "callback" => [$this, "delete_item"],
                    "permission_callback" => [
                        $this,
                        "delete_item_permissions_check",
                    ],
                ],
                "schema" => [$this, "get_public_item_schema"],
            ],
        );
    }

    
    public function get_items_permissions_check($request)
    {
        if (!current_user_can("eac_read_payments")) {
            
            return new \WP_Error(
                "rest_forbidden_context",
                __(
                    "Sorry, you are not allowed to view payments.",
                    "otto-contracts",
                ),
                ["status" => rest_authorization_required_code()],
            );
        }

        return true;
    }

    
    public function create_item_permissions_check($request)
    {
        if (!current_user_can("eac_edit_payments")) {
            
            return new \WP_Error(
                "rest_forbidden_context",
                __(
                    "Sorry, you are not allowed to create payments.",
                    "otto-contracts",
                ),
                ["status" => rest_authorization_required_code()],
            );
        }

        return true;
    }

    
    public function get_item_permissions_check($request)
    {
        $payment = EAC()->payments->get($request["id"]);

        if (empty($payment) || !current_user_can("eac_read_payments")) {
            
            return new \WP_Error(
                "rest_forbidden_context",
                __(
                    "Sorry, you are not allowed to view this payment.",
                    "otto-contracts",
                ),
                ["status" => rest_authorization_required_code()],
            );
        }

        return true;
    }

    
    public function update_item_permissions_check($request)
    {
        $payment = EAC()->payments->get($request["id"]);

        if (empty($payment) || !current_user_can("eac_edit_payments")) {
            
            return new \WP_Error(
                "rest_forbidden_context",
                __(
                    "Sorry, you are not allowed to update this payment.",
                    "otto-contracts",
                ),
                ["status" => rest_authorization_required_code()],
            );
        }

        return true;
    }

    
    public function delete_item_permissions_check($request)
    {
        $payment = EAC()->payments->get($request["id"]);

        if (empty($payment) || !current_user_can("eac_delete_payments")) {
            
            return new \WP_Error(
                "rest_forbidden_context",
                __(
                    "Sorry, you are not allowed to delete this payment.",
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

        
        $args = apply_filters("eac_rest_payment_query", $args, $request);

        $payments = EAC()->payments->query($args);
        $total = EAC()->payments->query($args, true);
        $max_pages = ceil($total / (int) $args["per_page"]);

        $results = [];
        foreach ($payments as $payment) {
            $data = $this->prepare_item_for_response($payment, $request);
            $results[] = $this->prepare_response_for_collection($data);
        }

        $response = rest_ensure_response($results);

        $response->header("X-WP-Total", (int) $total);
        $response->header("X-WP-TotalPages", (int) $max_pages);

        return $response;
    }

    
    public function get_item($request)
    {
        $payment = EAC()->payments->get($request["id"]);
        $data = $this->prepare_item_for_response($payment, $request);

        return rest_ensure_response($data);
    }

    
    public function create_item($request)
    {
        if (!empty($request["id"])) {
            return new \WP_Error(
                "rest_exists",
                __("Cannot create existing payment.", "otto-contracts"),
                ["status" => 400],
            );
        }

        $data = $this->prepare_item_for_database($request);
        if (is_wp_error($data)) {
            return $data;
        }

        $payment = EAC()->payments->insert($data);
        if (is_wp_error($payment)) {
            return $payment;
        }

        $response = $this->prepare_item_for_response($payment, $request);
        $response = rest_ensure_response($response);

        $response->set_status(201);

        return $response;
    }

    
    public function update_item($request)
    {
        $payment = EAC()->payments->get($request["id"]);
        $data = $this->prepare_item_for_database($request);
        if (is_wp_error($data)) {
            return $data;
        }

        $saved = $payment->fill($data)->save();
        if (is_wp_error($saved)) {
            return $saved;
        }

        $response = $this->prepare_item_for_response($saved, $request);

        return rest_ensure_response($response);
    }

    
    public function delete_item($request)
    {
        $payment = EAC()->payments->get($request["id"]);
        $request->set_param("context", "edit");
        $data = $this->prepare_item_for_response($payment, $request);

        if (!EAC()->payments->delete($payment->id)) {
            return new \WP_Error(
                "rest_cannot_delete",
                __("The payment cannot be deleted.", "otto-contracts"),
                ["status" => 500],
            );
        }

        $response = new \WP_REST_Response();
        $response->set_data([
            "deleted" => true,
            "previous" => $this->prepare_response_for_collection($data),
        ]);

        return $response;
    }

    
    public function prepare_item_for_response($item, $request)
    {
        $data = [];

        foreach (array_keys($this->get_schema_properties()) as $key) {
            $value = null;
            switch ($key) {
                case "category":
                    if (!empty($item->category)) {
                        $value = new \stdClass();
                        $properties = array_keys(
                            $this->get_schema_properties()[$key]["properties"],
                        );
                        foreach ($properties as $property) {
                            $value->$property = $item->category->$property;
                        }
                    }
                    break;
                case "account":
                    if (!empty($item->account)) {
                        $value = new \stdClass();
                        $properties = array_keys(
                            $this->get_schema_properties()[$key]["properties"],
                        );
                        foreach ($properties as $property) {
                            $value->$property = $item->account->$property;
                        }
                    }
                    break;
                case "bill":
                    if (!empty($item->bill)) {
                        $value = new \stdClass();
                        $properties = array_keys(
                            $this->get_schema_properties()[$key]["properties"],
                        );
                        foreach ($properties as $property) {
                            $value->$property = $item->bill->$property;
                        }
                    }
                    break;

                case "customer":
                    if (!empty($item->customer)) {
                        $value = new \stdClass();
                        $properties = array_keys(
                            $this->get_schema_properties()[$key]["properties"],
                        );
                        foreach ($properties as $property) {
                            $value->$property = $item->customer->$property;
                        }
                    }
                    break;

                case "date_updated":
                case "crated_at":
                case "date":
                    $value = $this->prepare_date_response($item->$key);
                    break;
                default:
                    $value = isset($item->$key) ? $item->$key : null;
                    break;
            }

            $data[$key] = $value;
        }

        $context = !empty($request["context"]) ? $request["context"] : "view";
        $data = $this->add_additional_fields_to_object($data, $request);
        $data = $this->filter_response_by_context($data, $context);
        $response = rest_ensure_response($data);

        
        return apply_filters(
            "eac_rest_prepare_payment",
            $response,
            $item,
            $request,
        );
    }

    
    protected function prepare_item_for_database($request)
    {
        $schema = $this->get_item_schema();
        $props = array_keys(
            array_filter($schema["properties"], [
                $this,
                "filter_writable_props",
            ]),
        );
        $data = [];
        foreach ($props as $prop) {
            if (isset($request[$prop])) {
                $value = $request[$prop];
                switch ($prop) {
                    case "category":
                        $category = EAC()->categories->get(
                            $request[$prop]["id"],
                        );
                        if (!$category) {
                            return new \WP_Error(
                                "rest_invalid_category",
                                __("Invalid category.", "otto-contracts"),
                                ["status" => 400],
                            );
                        }
                        $data["category_id"] = $category->id;
                        break;
                    case "category_id":
                        $category = EAC()->categories->get($request[$prop]);
                        if (!$category) {
                            return new \WP_Error(
                                "rest_invalid_category",
                                __("Invalid category.", "otto-contracts"),
                                ["status" => 400],
                            );
                        }
                        $data["category_id"] = $category->id;
                        break;
                    case "account":
                        $account = EAC()->accounts->get($request[$prop]["id"]);
                        if (!$account) {
                            return new \WP_Error(
                                "rest_invalid_account",
                                __("Invalid account.", "otto-contracts"),
                                ["status" => 400],
                            );
                        }
                        $data["account_id"] = $account->id;
                        break;

                    case "account_id":
                        $account = EAC()->accounts->get($request[$prop]);
                        if (!$account) {
                            return new \WP_Error(
                                "rest_invalid_account",
                                __("Invalid account.", "otto-contracts"),
                                ["status" => 400],
                            );
                        }
                        $data["account_id"] = $account->id;
                        break;

                    case "invoice":
                        $invoice = EAC()->invoices->get($request[$prop]["id"]);
                        if (!$invoice) {
                            return new \WP_Error(
                                "rest_invalid_invoice",
                                __("Invalid contract.", "otto-contracts"),
                                ["status" => 400],
                            );
                        }
                        $data["invoice_id"] = $invoice->id;
                        break;

                    case "customer":
                        $customer = EAC()->customers->get(
                            $request[$prop]["id"],
                        );
                        if (!$customer) {
                            return new \WP_Error(
                                "rest_invalid_customer",
                                __("Invalid customer.", "otto-contracts"),
                                ["status" => 400],
                            );
                        }
                        $data["customer_id"] = $customer->id;
                        break;

                    case "attachment":
                        $attachment_id = $request[$prop]["id"];
                        if (
                            !empty($attachment_id) &&
                            "attachment" === get_post_type($attachment_id)
                        ) {
                            $data["attachment_id"] = $attachment_id;
                        }
                        break;
                    case "issue_date":
                    case "due_date":
                    case "sent_date":
                    case "payment_date":
                    case "date_created":
                    case "date_updated":
                        $data[$prop] = $this->prepare_date_for_database($value);
                        break;
                    default:
                        $data[$prop] = $request[$prop];
                        break;
                }
            }
        }

        
        return apply_filters("eac_rest_pre_insert_payment", $data, $request);
    }

    
    public function get_item_schema()
    {
        $schema = [
            '$schema' => "http://json-schema.org/draft-04/schema#",
            "title" => __("Payment", "otto-contracts"),
            "type" => "object",
            "properties" => [
                "id" => [
                    "description" => __(
                        "Unique identifier for the payment.",
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
                        "Payment number.",
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "context" => ["view", "embed", "edit"],
                ],
                "payment_date" => [
                    "description" => __(
                        'The date the payment took place, in the site\'s timezone.',
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "format" => "string",
                    "context" => ["view", "embed", "edit"],
                ],
                "amount" => [
                    "description" => __(
                        "Total amount of the payment.",
                        "otto-contracts",
                    ),
                    "type" => "number",
                    "context" => ["view", "embed", "edit"],
                ],
                "formatted_amount" => [
                    "description" => __(
                        "Formatted total amount of the payment.",
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "context" => ["view", "embed"],
                ],
                "exchange_rate" => [
                    "description" => __(
                        "Exchange rate of the payment.",
                        "otto-contracts",
                    ),
                    "type" => "number",
                    "context" => ["view", "embed", "edit"],
                    "default" => 1,
                ],
                "reference" => [
                    "description" => __(
                        "Reference of the payment.",
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "context" => ["view", "embed", "edit"],
                ],
                "note" => [
                    "description" => __(
                        "Note of the payment.",
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "context" => ["view", "embed", "edit"],
                ],
                "payment_method" => [
                    "description" => __(
                        "Payment method of the payment.",
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "context" => ["view", "embed", "edit"],
                ],
                "account_id" => [
                    "description" => __(
                        "Account ID of the payment.",
                        "otto-contracts",
                    ),
                    "type" => "integer",
                    "context" => ["edit"],
                ],
                "account" => [
                    "description" => __(
                        "Account of the payment.",
                        "otto-contracts",
                    ),
                    "type" => "object",
                    "context" => ["view", "embed", "edit"],
                    "readonly" => true,
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
                "invoice_id" => [
                    "description" => __(
                        "Contract ID of the payment.",
                        "otto-contracts",
                    ),
                    "type" => "integer",
                    "context" => ["edit"],
                ],
                "invoice" => [
                    "description" => __(
                        "Contract of the payment.",
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
                            "arg_options" => [
                                "sanitize_callback" => "intval",
                            ],
                        ],
                        "name" => [
                            "description" => __(
                                "Contract name.",
                                "otto-contracts",
                            ),
                            "type" => "string",
                            "context" => ["view", "embed", "edit"],
                        ],
                    ],
                ],
                "customer_id" => [
                    "description" => __(
                        "Customer ID of the payment.",
                        "otto-contracts",
                    ),
                    "type" => "integer",
                    "context" => ["edit"],
                ],
                "customer" => [
                    "description" => __(
                        "Customer of the payment.",
                        "otto-contracts",
                    ),
                    "type" => "object",
                    "context" => ["view", "embed", "edit"],
                    "properties" => [
                        "id" => [
                            "description" => __(
                                "Unique identifier for the customer.",
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
                                "Vendor name.",
                                "otto-contracts",
                            ),
                            "type" => "string",
                            "context" => ["view", "embed", "edit"],
                        ],
                    ],
                ],
                "category_id" => [
                    "description" => __(
                        "Category ID of the payment.",
                        "otto-contracts",
                    ),
                    "type" => ["integer", "null", "string"],
                    "context" => ["edit"],
                    "sanitize_callback" => "intval",
                ],
                "category" => [
                    "description" => __(
                        "Category of the payment.",
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
                "attachment_id" => [
                    "description" => __(
                        "Attachment ID of the payment.",
                        "otto-contracts",
                    ),
                    "type" => "integer",
                    "context" => ["view", "embed", "edit"],
                ],
                "parent_id" => [
                    "description" => __(
                        "Parent payment ID.",
                        "otto-contracts",
                    ),
                    "type" => "integer",
                    "context" => ["view", "embed", "edit"],
                ],
                "editable" => [
                    "description" => __(
                        "Whether the payment is editable.",
                        "otto-contracts",
                    ),
                    "type" => "boolean",
                    "context" => ["view", "embed", "edit"],
                ],
                "uuid" => [
                    "description" => __(
                        "UUID of the payment.",
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "context" => ["view", "embed", "edit"],
                    "readonly" => true,
                ],
                "created_via" => [
                    "description" => __(
                        "Created via of the payment.",
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "context" => ["view", "embed", "edit"],
                ],
                "author_id" => [
                    "description" => __(
                        "Author ID of the payment.",
                        "otto-contracts",
                    ),
                    "type" => "integer",
                    "context" => ["view", "embed", "edit"],
                    "readonly" => true,
                ],
                "date_updated" => [
                    "description" => __(
                        "The date the payment was last updated, in the site's timezone.",
                        "otto-contracts",
                    ),
                    "type" => "date-time",
                    "context" => ["view", "edit"],
                    "readonly" => true,
                ],
                "date_created" => [
                    "description" => __(
                        "The date the payment was created, in the site's timezone.",
                        "otto-contracts",
                    ),
                    "type" => "date-time",
                    "context" => ["view", "edit"],
                    "readonly" => true,
                ],
            ],
        ];

        
        $schema = apply_filters("eac_rest_payment_item_schema", $schema);

        return $this->add_additional_fields_schema($schema);
    }
}
