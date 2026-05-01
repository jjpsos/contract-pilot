<?php

namespace Otto\API;

use Otto\Models\Tax;

defined("ABSPATH") || exit();


class Taxes extends Controller
{
    
    protected $rest_base = "taxes";

    
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
                            "Unique identifier for the tax.",
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
        if (!current_user_can("eac_read_taxes")) {
            
            return new \WP_Error(
                "rest_forbidden_context",
                __(
                    "Sorry, you are not allowed to view taxes.",
                    "otto-contracts",
                ),
                ["status" => rest_authorization_required_code()],
            );
        }

        return true;
    }

    
    public function create_item_permissions_check($request)
    {
        if (!current_user_can("eac_edit_taxes")) {
            
            return new \WP_Error(
                "rest_forbidden_context",
                __(
                    "Sorry, you are not allowed to create taxes.",
                    "otto-contracts",
                ),
                ["status" => rest_authorization_required_code()],
            );
        }

        return true;
    }

    
    public function get_item_permissions_check($request)
    {
        $tax = EAC()->taxes->get($request["id"]);

        if (empty($tax) || !current_user_can("eac_read_taxes")) {
            
            return new \WP_Error(
                "rest_forbidden_context",
                __(
                    "Sorry, you are not allowed to view this tax.",
                    "otto-contracts",
                ),
                ["status" => rest_authorization_required_code()],
            );
        }

        return true;
    }

    
    public function update_item_permissions_check($request)
    {
        $tax = EAC()->taxes->get($request["id"]);

        if (empty($tax) || !current_user_can("eac_edit_taxes")) {
            
            return new \WP_Error(
                "rest_forbidden_context",
                __(
                    "Sorry, you are not allowed to update this tax.",
                    "otto-contracts",
                ),
                ["status" => rest_authorization_required_code()],
            );
        }

        return true;
    }

    
    public function delete_item_permissions_check($request)
    {
        $tax = EAC()->taxes->get($request["id"]);

        if (empty($tax) || !current_user_can("eac_delete_taxes")) {
            
            return new \WP_Error(
                "rest_forbidden_context",
                __(
                    "Sorry, you are not allowed to delete this tax.",
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

        
        $args = apply_filters("eac_rest_tax_query", $args, $request);
        $taxes = EAC()->taxes->query($args);
        $total = EAC()->taxes->query($args, true);
        $max_pages = ceil($total / (int) $args["per_page"]);

        $results = [];
        foreach ($taxes as $tax) {
            $data = $this->prepare_item_for_response($tax, $request);
            $results[] = $this->prepare_response_for_collection($data);
        }

        $response = rest_ensure_response($results);

        $response->header("X-WP-Total", (int) $total);
        $response->header("X-WP-TotalPages", (int) $max_pages);

        return $response;
    }

    
    public function get_item($request)
    {
        $tax = EAC()->taxes->get($request["id"]);
        $data = $this->prepare_item_for_response($tax, $request);

        return rest_ensure_response($data);
    }

    
    public function create_item($request)
    {
        if (!empty($request["id"])) {
            return new \WP_Error(
                "rest_account_exists",
                __("Cannot create existing tax.", "otto-contracts"),
                ["status" => 400],
            );
        }

        $data = $this->prepare_item_for_database($request);
        if (is_wp_error($data)) {
            return $data;
        }

        $tax = EAC()->taxes->insert($data);
        if (is_wp_error($tax)) {
            return $tax;
        }

        $response = $this->prepare_item_for_response($tax, $request);
        $response = rest_ensure_response($response);

        $response->set_status(201);
        $response->header(
            "Location",
            rest_url(
                sprintf(
                    "%s/%s/%d",
                    $this->namespace,
                    $this->rest_base,
                    $tax->id,
                ),
            ),
        );

        return $response;
    }

    
    public function update_item($request)
    {
        $tax = EAC()->taxes->get($request["id"]);
        $data = $this->prepare_item_for_database($request);
        if (is_wp_error($data)) {
            return $data;
        }

        $saved = $tax->fill($data)->save();
        if (is_wp_error($saved)) {
            return $saved;
        }

        $response = $this->prepare_item_for_response($tax, $request);

        return rest_ensure_response($response);
    }

    
    public function delete_item($request)
    {
        $tax = EAC()->taxes->get($request["id"]);
        $request->set_param("context", "edit");
        $data = $this->prepare_item_for_response($tax, $request);

        if (!$tax->delete()) {
            return new \WP_Error(
                "rest_cannot_delete",
                __("The tax cannot be deleted.", "otto-contracts"),
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
            "eac_rest_prepare_tax",
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
                    default:
                        $props[$key] = $value;
                        break;
                }
            }
        }

        
        return apply_filters("eac_rest_pre_insert_tax", $props, $request);
    }

    
    public function get_item_schema()
    {
        $schema = [
            '$schema' => "http://json-schema.org/draft-04/schema#",
            "title" => __("Tax", "otto-contracts"),
            "type" => "object",
            "properties" => [
                "id" => [
                    "description" => __(
                        "Unique identifier for the tax.",
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
                    "description" => __("Tax name.", "otto-contracts"),
                    "type" => "string",
                    "context" => ["view", "edit"],
                    "required" => true,
                ],
                "formatted_name" => [
                    "description" => __(
                        "Formatted tax name.",
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "context" => ["view"],
                    "readonly" => true,
                ],
                "rate" => [
                    "description" => __("Tax rate.", "otto-contracts"),
                    "type" => "number",
                    "context" => ["view", "edit"],
                    "required" => true,
                ],
                "compound" => [
                    "description" => __(
                        "Whether the tax is compound.",
                        "otto-contracts",
                    ),
                    "type" => "boolean",
                    "context" => ["view", "edit"],
                    "required" => true,
                    "default" => false,
                ],
            ],
        ];

        
        $schema = apply_filters("eac_rest_tax_item_schema", $schema);

        return $this->add_additional_fields_schema($schema);
    }

    
    public function get_collection_params()
    {
        $params = parent::get_collection_params();

        $params["orderby"]["default"] = "name";

        
        return apply_filters("eac_rest_tax_collection_params", $params);
    }
}
