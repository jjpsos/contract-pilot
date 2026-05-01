<?php

namespace Otto\API;

use Otto\Models\Account;

defined("ABSPATH") || exit();


class Accounts extends Controller
{
    
    protected $rest_base = "accounts";

    
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
                            "Unique identifier for the account.",
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
        if (!current_user_can("eac_read_accounts")) {
            
            return new \WP_Error(
                "rest_forbidden_context",
                __(
                    "Sorry, you are not allowed to view accounts.",
                    "otto-contracts",
                ),
                ["status" => rest_authorization_required_code()],
            );
        }

        return true;
    }

    
    public function create_item_permissions_check($request)
    {
        if (!current_user_can("eac_edit_accounts")) {
            
            return new \WP_Error(
                "rest_forbidden_context",
                __(
                    "Sorry, you are not allowed to create account.",
                    "otto-contracts",
                ),
                ["status" => rest_authorization_required_code()],
            );
        }

        return true;
    }

    
    public function get_item_permissions_check($request)
    {
        $account = EAC()->accounts->get($request["id"]);

        if (empty($account) || !current_user_can("eac_read_accounts")) {
            
            return new \WP_Error(
                "rest_forbidden_context",
                __(
                    "Sorry, you are not allowed to view this account.",
                    "otto-contracts",
                ),
                ["status" => rest_authorization_required_code()],
            );
        }

        return true;
    }

    
    public function update_item_permissions_check($request)
    {
        $account = EAC()->accounts->get($request["id"]);

        if (empty($account) || !current_user_can("eac_edit_accounts")) {
            
            return new \WP_Error(
                "rest_forbidden_context",
                __(
                    "Sorry, you are not allowed to update this account.",
                    "otto-contracts",
                ),
                ["status" => rest_authorization_required_code()],
            );
        }

        return true;
    }

    
    public function delete_item_permissions_check($request)
    {
        $account = EAC()->accounts->get($request["id"]);

        if (empty($account) || !current_user_can("eac_delete_accounts")) {
            
            return new \WP_Error(
                "rest_forbidden_context",
                __(
                    "Sorry, you are not allowed to delete this account.",
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

        
        $args = apply_filters("eac_rest_account_query", $args, $request);
        $accounts = EAC()->accounts->query($args);
        $total = EAC()->accounts->query($args, true);
        $max_pages = ceil($total / (int) $args["per_page"]);

        $results = [];
        foreach ($accounts as $account) {
            $data = $this->prepare_item_for_response($account, $request);
            $results[] = $this->prepare_response_for_collection($data);
        }

        $response = rest_ensure_response($results);

        $response->header("X-WP-Total", (int) $total);
        $response->header("X-WP-TotalPages", (int) $max_pages);

        return $response;
    }

    
    public function get_item($request)
    {
        $account = EAC()->accounts->get($request["id"]);
        $data = $this->prepare_item_for_response($account, $request);

        return rest_ensure_response($data);
    }

    
    public function create_item($request)
    {
        if (!empty($request["id"])) {
            return new \WP_Error(
                "rest_exists",
                __("Cannot create existing account.", "otto-contracts"),
                ["status" => 400],
            );
        }

        $data = $this->prepare_item_for_database($request);
        if (is_wp_error($data)) {
            return $data;
        }

        $account = EAC()->accounts->insert($data);
        if (is_wp_error($account)) {
            return $account;
        }

        $response = $this->prepare_item_for_response($account, $request);
        $response = rest_ensure_response($response);

        $response->set_status(201);

        return $response;
    }

    
    public function update_item($request)
    {
        $account = EAC()->accounts->get($request["id"]);
        $data = $this->prepare_item_for_database($request);
        if (is_wp_error($data)) {
            return $data;
        }

        $account = $account->fill($data)->save();
        if (is_wp_error($account)) {
            return $account;
        }

        $response = $this->prepare_item_for_response($account, $request);
        $response = rest_ensure_response($response);

        return $response;
    }

    
    public function delete_item($request)
    {
        $account = EAC()->accounts->get($request["id"]);
        $request->set_param("context", "edit");
        $data = $this->prepare_item_for_response($account, $request);

        if (!$account->delete()) {
            return new \WP_Error(
                "rest_cannot_delete",
                __("The account cannot be deleted.", "otto-contracts"),
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
            switch ($key) {
                case "date_created":
                case "date_updated":
                    $value = $this->prepare_date_response($item->$key);
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
            "eac_rest_prepare_account",
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

        
        return apply_filters("eac_rest_pre_insert_account", $props, $request);
    }

    
    public function get_item_schema()
    {
        $schema = [
            '$schema' => "http://json-schema.org/draft-04/schema#",
            "title" => __("Account", "otto-contracts"),
            "type" => "object",
            "properties" => [
                "id" => [
                    "description" => __(
                        "Unique identifier for the account.",
                        "otto-contracts",
                    ),
                    "type" => "integer",
                    "context" => ["view", "embed", "edit"],
                    "readonly" => true,
                    "arg_options" => [
                        "sanitize_callback" => "intval",
                    ],
                ],
                "type" => [
                    "description" => __("Account type.", "otto-contracts"),
                    "type" => "string",
                    "enum" => ["bank", "card"],
                    "context" => ["view", "edit"],
                    "required" => true,
                    "arg_options" => [
                        "sanitize_callback" => "sanitize_text_field",
                    ],
                ],
                "name" => [
                    "description" => __("Account name.", "otto-contracts"),
                    "type" => "string",
                    "context" => ["view", "edit"],
                    "required" => true,
                ],
                "number" => [
                    "description" => __(
                        "Account number.",
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "context" => ["view", "edit"],
                    "required" => true,
                ],
                "balance" => [
                    "description" => __(
                        "Account balance.",
                        "otto-contracts",
                    ),
                    "type" => "number",
                    "context" => ["view", "edit"],
                    "readonly" => true,
                    "arg_options" => [
                        "sanitize_callback" => "floatval",
                    ],
                ],
                "currency" => [
                    "description" => __(
                        "Account currency code.",
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "context" => ["view", "edit"],
                    "required" => true,
                    "default" => eac_base_currency(),
                    "arg_options" => [
                        "sanitize_callback" => "sanitize_text_field",
                    ],
                ],
                "date_updated" => [
                    "description" => __(
                        "The date the account was last updated, in the site's timezone.",
                        "otto-contracts",
                    ),
                    "type" => "date-time",
                    "context" => ["view", "edit"],
                    "readonly" => true,
                ],
                "date_created" => [
                    "description" => __(
                        "The date the account was created, in the site's timezone.",
                        "otto-contracts",
                    ),
                    "type" => "date-time",
                    "context" => ["view", "edit"],
                    "readonly" => true,
                ],
            ],
        ];

        
        $schema = apply_filters("eac_rest_account_schema", $schema);

        return $this->add_additional_fields_schema($schema);
    }
}
