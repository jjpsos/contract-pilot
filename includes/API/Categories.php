<?php

namespace Otto\API;

use Otto\Models\Category;

defined("ABSPATH") || exit();


class Categories extends Controller
{
    
    protected $rest_base = "categories";

    
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
                            "Unique identifier for the Category.",
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
        if (!current_user_can("eac_read_categories")) {
            
            return new \WP_Error(
                "rest_forbidden_context",
                __(
                    "Sorry, you are not allowed to view categories.",
                    "otto-contracts",
                ),
                ["status" => rest_authorization_required_code()],
            );
        }

        return true;
    }

    
    public function create_item_permissions_check($request)
    {
        if (!current_user_can("eac_edit_categories")) {
            
            return new \WP_Error(
                "rest_forbidden_context",
                __(
                    "Sorry, you are not allowed to create categories.",
                    "otto-contracts",
                ),
                ["status" => rest_authorization_required_code()],
            );
        }

        return true;
    }

    
    public function get_item_permissions_check($request)
    {
        $category = EAC()->categories->get($request["id"]);

        if (empty($category) || !current_user_can("eac_read_categories")) {
            
            return new \WP_Error(
                "rest_forbidden_context",
                __(
                    "Sorry, you are not allowed to view this category.",
                    "otto-contracts",
                ),
                ["status" => rest_authorization_required_code()],
            );
        }

        return true;
    }

    
    public function update_item_permissions_check($request)
    {
        $category = EAC()->categories->get($request["id"]);

        if (empty($category) || !current_user_can("eac_edit_categories")) {
            
            return new \WP_Error(
                "rest_forbidden_context",
                __(
                    "Sorry, you are not allowed to update this category.",
                    "otto-contracts",
                ),
                ["status" => rest_authorization_required_code()],
            );
        }

        return true;
    }

    
    public function delete_item_permissions_check($request)
    {
        $category = EAC()->categories->get($request["id"]);

        if (empty($category) || !current_user_can("eac_delete_categories")) {
            
            return new \WP_Error(
                "rest_forbidden_context",
                __(
                    "Sorry, you are not allowed to delete this category.",
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

        
        $args = apply_filters("eac_rest_category_query", $args, $request);

        $categories = EAC()->categories->query($args);
        $total = EAC()->categories->query($args, true);
        $max_pages = ceil($total / (int) $args["per_page"]);

        $results = [];
        foreach ($categories as $category) {
            $data = $this->prepare_item_for_response($category, $request);
            $results[] = $this->prepare_response_for_collection($data);
        }

        $response = rest_ensure_response($results);

        $response->header("X-WP-Total", (int) $total);
        $response->header("X-WP-TotalPages", (int) $max_pages);

        return $response;
    }

    
    public function get_item($request)
    {
        $category = EAC()->categories->get($request["id"]);
        $data = $this->prepare_item_for_response($category, $request);

        return rest_ensure_response($data);
    }

    
    public function create_item($request)
    {
        if (!empty($request["id"])) {
            return new \WP_Error(
                "rest_exists",
                __("Cannot create existing category.", "otto-contracts"),
                ["status" => 400],
            );
        }

        $data = $this->prepare_item_for_database($request);
        if (is_wp_error($data)) {
            return $data;
        }

        $category = EAC()->categories->insert($data);
        if (is_wp_error($category)) {
            return $category;
        }

        $response = $this->prepare_item_for_response($category, $request);
        $response = rest_ensure_response($response);

        $response->set_status(201);

        return $response;
    }

    
    public function update_item($request)
    {
        $category = EAC()->categories->get($request["id"]);
        $data = $this->prepare_item_for_database($request);
        if (is_wp_error($data)) {
            return $data;
        }

        $saved = $category->fill($data)->save();
        if (is_wp_error($saved)) {
            return $saved;
        }

        $response = $this->prepare_item_for_response($saved, $request);

        return rest_ensure_response($response);
    }

    
    public function delete_item($request)
    {
        $category = EAC()->categories->get($request["id"]);
        $request->set_param("context", "edit");
        $data = $this->prepare_item_for_response($category, $request);

        if (!EAC()->categories->delete($category->id)) {
            return new \WP_Error(
                "rest_cannot_delete",
                __("The category cannot be deleted.", "otto-contracts"),
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
                case "date_updated":
                case "crated_at":
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
            "eac_rest_prepare_category",
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
                    case "date_created":
                    case "date_updated":
                        $props[$prop] = $this->prepare_date_for_database(
                            $value,
                        );
                        break;
                    default:
                        $data[$prop] = $value;
                        break;
                }
            }
        }

        
        return apply_filters("eac_rest_pre_insert_category", $data, $request);
    }

    
    public function get_item_schema()
    {
        $schema = [
            '$schema' => "http://json-schema.org/draft-04/schema#",
            "title" => __("Category", "otto-contracts"),
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
                    "description" => __("Category name.", "otto-contracts"),
                    "type" => "string",
                    "context" => ["view", "edit"],
                    "required" => true,
                ],
                "type" => [
                    "description" => __("Category type.", "otto-contracts"),
                    "type" => "string",
                    "enum" => array_keys(EAC()->categories->get_types()),
                    "context" => ["view", "edit"],
                    "required" => true,
                ],
                "description" => [
                    "description" => __(
                        "Category description.",
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "context" => ["view", "edit"],
                ],
            ],
        ];

        
        $schema = apply_filters("eac_rest_category_item_schema", $schema);

        return $this->add_additional_fields_schema($schema);
    }
}
