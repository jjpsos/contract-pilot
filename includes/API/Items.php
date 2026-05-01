<?php

namespace Otto\API;

use Otto\Models\Item;

defined("ABSPATH") || exit();


class Items extends Controller
{
    
    protected $rest_base = "items";

    
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
                            "Unique identifier for the item.",
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
        if (!current_user_can("eac_read_items")) {
            
            return new \WP_Error(
                "rest_forbidden_context",
                __(
                    "Sorry, you are not allowed to view items.",
                    "otto-contracts",
                ),
                ["status" => rest_authorization_required_code()],
            );
        }

        return true;
    }

    
    public function create_item_permissions_check($request)
    {
        if (!current_user_can("eac_edit_items")) {
            
            return new \WP_Error(
                "rest_forbidden_context",
                __(
                    "Sorry, you are not allowed to create items.",
                    "otto-contracts",
                ),
                ["status" => rest_authorization_required_code()],
            );
        }

        return true;
    }

    
    public function get_item_permissions_check($request)
    {
        $item = EAC()->items->get($request["id"]);

        if (empty($item) || !current_user_can("eac_read_items")) {
            
            return new \WP_Error(
                "rest_forbidden_context",
                __(
                    "Sorry, you are not allowed to view this item.",
                    "otto-contracts",
                ),
                ["status" => rest_authorization_required_code()],
            );
        }

        return true;
    }

    
    public function update_item_permissions_check($request)
    {
        $item = EAC()->items->get($request["id"]);

        if (empty($item) || !current_user_can("eac_edit_items")) {
            
            return new \WP_Error(
                "rest_forbidden_context",
                __(
                    "Sorry, you are not allowed to update this item.",
                    "otto-contracts",
                ),
                ["status" => rest_authorization_required_code()],
            );
        }

        return true;
    }

    
    public function delete_item_permissions_check($request)
    {
        $item = EAC()->items->get($request["id"]);

        if (empty($item) || !current_user_can("eac_delete_items")) {
            
            return new \WP_Error(
                "rest_forbidden_context",
                __(
                    "Sorry, you are not allowed to delete this item.",
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

        
        $args = apply_filters("eac_rest_item_query", $args, $request);
        $items = EAC()->items->query($args);
        $total = EAC()->items->query($args, true);
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
        $item = EAC()->items->get($request["id"]);
        $data = $this->prepare_item_for_response($item, $request);

        return rest_ensure_response($data);
    }

    
    public function create_item($request)
    {
        if (!empty($request["id"])) {
            return new \WP_Error(
                "rest_exists",
                __("Cannot create existing service.", "otto-contracts"),
                ["status" => 400],
            );
        }

        $data = $this->prepare_item_for_database($request);
        if (is_wp_error($data)) {
            return $data;
        }

        $item = EAC()->items->insert($data);
        if (is_wp_error($item)) {
            return $item;
        }

        $response = $this->prepare_item_for_response($item, $request);
        $response = rest_ensure_response($response);

        $response->set_status(201);
        return $response;
    }

    
    public function update_item($request)
    {
        $item = EAC()->items->get($request["id"]);
        $data = $this->prepare_item_for_database($request);
        if (is_wp_error($data)) {
            return $data;
        }

        $item = $item->fill($data)->save();
        if (is_wp_error($item)) {
            return $item;
        }

        $response = $this->prepare_item_for_response($item, $request);
        $response = rest_ensure_response($response);

        return $response;
    }

    
    public function delete_item($request)
    {
        $item = EAC()->items->get($request["id"]);
        $request->set_param("context", "edit");
        $data = $this->prepare_item_for_response($item, $request);

        if (!$item->delete()) {
            return new \WP_Error(
                "rest_cannot_delete",
                __("The service cannot be deleted.", "otto-contracts"),
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
                case "category":
                    $value = $item->category_id
                        ? $this->get_endpoint_data(
                            sprintf(
                                "/%s/categories/%d",
                                $this->namespace,
                                $item->category_id,
                            ),
                        )
                        : null;
                    break;
                case "taxes":
                    $value = [];
                    if (!empty($item->taxes)) {
                        $properties = array_keys(
                            $this->get_schema_properties()[$key]["items"],
                        );
                        $value = array_map(function ($tax) use ($properties) {
                            return array_intersect_key(
                                $tax->to_array(),
                                array_flip($properties),
                            );
                        }, $item->taxes);
                    }
                    break;
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
            "eac_rest_prepare_item",
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

        
        return apply_filters("eac_rest_pre_insert_item", $props, $request);
    }

    
    public function get_item_schema()
    {
        $schema = [
            '$schema' => "http://json-schema.org/draft-04/schema#",
            "title" => __("Service", "otto-contracts"),
            "type" => "object",
            "properties" => [
                "id" => [
                    "description" => __(
                        "Unique identifier for the service.",
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
                    "description" => __("Service type.", "otto-contracts"),
                    "type" => "string",
                    "enum" => array_keys(EAC()->items->get_types()),
                    "context" => ["view", "edit"],
                    "required" => true,
                ],
                "name" => [
                    "description" => __("Service name.", "otto-contracts"),
                    "type" => "string",
                    "context" => ["view", "edit"],
                    "required" => true,
                ],
                "description" => [
                    "description" => __(
                        "Service description.",
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "context" => ["view", "edit"],
                ],
                "unit" => [
                    "description" => __(
                        "Measurement unit for the .",
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "context" => ["view", "edit"],
                ],
                "price" => [
                    "description" => __("Service price.", "otto-contracts"),
                    "type" => "number",
                    "context" => ["view", "edit"],
                    "required" => true,
                ],
                "cost" => [
                    "description" => __("Service cost.", "otto-contracts"),
                    "type" => "number",
                    "context" => ["view", "edit"],
                ],
                "taxes" => [
                    "description" => __(
                        "Taxes for the item.",
                        "otto-contracts",
                    ),
                    "type" => "array",
                    "context" => ["view", "edit"],
                    "items" => [
                        "id" => [
                            "type" => "integer",
                            "context" => ["view", "edit"],
                        ],
                        "name" => [
                            "type" => "string",
                            "context" => ["view", "edit"],
                        ],
                        "formatted_name" => [
                            "type" => "string",
                            "context" => ["view", "edit"],
                            "readonly" => true,
                        ],
                        "rate" => [
                            "type" => "number",
                            "context" => ["view", "edit"],
                        ],
                        "compound" => [
                            "type" => "boolean",
                            "context" => ["view", "edit"],
                            "default" => false,
                        ],
                    ],
                ],
                "category_id" => [
                    "description" => __(
                        "Category ID for the item.",
                        "otto-contracts",
                    ),
                    "type" => "integer",
                    "context" => ["view", "edit"],
                ],
                "date_updated" => [
                    "description" => __(
                        "The date the item was last updated, in the site's timezone.",
                        "otto-contracts",
                    ),
                    "type" => "date-time",
                    "context" => ["view", "edit"],
                    "readonly" => true,
                ],
                "date_created" => [
                    "description" => __(
                        "The date the item was created, in the site's timezone.",
                        "otto-contracts",
                    ),
                    "type" => "date-time",
                    "context" => ["view", "edit"],
                    "readonly" => true,
                ],
            ],
        ];

        
        $schema = apply_filters("eac_rest_item_schema", $schema);

        return $this->add_additional_fields_schema($schema);
    }

    
    public function get_collection_params()
    {
        $params = parent::get_collection_params();
        $params["type"] = [
            "description" => __(
                "Limit result set to items of a particular type.",
                "otto-contracts",
            ),
            "type" => "string",
            "enum" => array_keys(EAC()->items->get_types()),
            "default" => "",
            "sanitize_callback" => "sanitize_key",
        ];
        $params["status"] = [
            "description" => __(
                "Limit result set to items of a particular status.",
                "otto-contracts",
            ),
            "type" => "string",
            "enum" => ["active", "inactive"],
            "default" => "",
            "sanitize_callback" => "sanitize_key",
        ];

        return $params;
    }
}
