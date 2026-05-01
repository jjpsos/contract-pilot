<?php

namespace Otto\API;

use Otto\Models\Note;

defined("ABSPATH") || exit();


class Notes extends Controller
{
    
    protected $rest_base = "notes";

    
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
                            "Unique identifier for the note.",
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
        if (!current_user_can("manage_accounting")) {
            
            return new \WP_Error(
                "rest_forbidden_context",
                __(
                    "Sorry, you are not allowed to view notes.",
                    "otto-contracts",
                ),
                ["status" => rest_authorization_required_code()],
            );
        }

        return true;
    }

    
    public function create_item_permissions_check($request)
    {
        if (!current_user_can("manage_accounting")) {
            
            return new \WP_Error(
                "rest_forbidden_context",
                __(
                    "Sorry, you are not allowed to create notes.",
                    "otto-contracts",
                ),
                ["status" => rest_authorization_required_code()],
            );
        }

        return true;
    }

    
    public function get_item_permissions_check($request)
    {
        $note = EAC()->notes->get($request["id"]);

        if (empty($note) || !current_user_can("manage_accounting")) {
            
            return new \WP_Error(
                "rest_forbidden_context",
                __(
                    "Sorry, you are not allowed to view this note.",
                    "otto-contracts",
                ),
                ["status" => rest_authorization_required_code()],
            );
        }

        return true;
    }

    
    public function update_item_permissions_check($request)
    {
        $note = EAC()->notes->get($request["id"]);

        if (empty($note) || !current_user_can("manage_accounting")) {
            
            return new \WP_Error(
                "rest_forbidden_context",
                __(
                    "Sorry, you are not allowed to update this note.",
                    "otto-contracts",
                ),
                ["status" => rest_authorization_required_code()],
            );
        }

        return true;
    }

    
    public function delete_item_permissions_check($request)
    {
        $note = EAC()->notes->get($request["id"]);

        if (empty($note) || !current_user_can("manage_accounting")) {
            
            return new \WP_Error(
                "rest_forbidden_context",
                __(
                    "Sorry, you are not allowed to delete this note.",
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

        
        $args = apply_filters("eac_rest_note_query", $args, $request);

        $notes = EAC()->notes->query($args);
        $total = EAC()->notes->query($args, true);
        $max_pages = ceil($total / (int) $args["per_page"]);

        $results = [];
        foreach ($notes as $note) {
            $data = $this->prepare_item_for_response($note, $request);
            $results[] = $this->prepare_response_for_collection($data);
        }

        $response = rest_ensure_response($results);

        $response->header("X-WP-Total", (int) $total);
        $response->header("X-WP-TotalPages", (int) $max_pages);

        return $response;
    }

    
    public function get_item($request)
    {
        $note = EAC()->notes->get($request["id"]);
        $data = $this->prepare_item_for_response($note, $request);

        return rest_ensure_response($data);
    }

    
    public function create_item($request)
    {
        if (!empty($request["id"])) {
            return new \WP_Error(
                "rest_exists",
                __("Cannot create existing note.", "otto-contracts"),
                ["status" => 400],
            );
        }

        $data = $this->prepare_item_for_database($request);
        if (is_wp_error($data)) {
            return $data;
        }

        $note = EAC()->notes->insert($data);
        if (is_wp_error($note)) {
            return $note;
        }

        $response = $this->prepare_item_for_response($note, $request);
        $response = rest_ensure_response($response);

        $response->set_status(201);

        return $response;
    }

    
    public function update_item($request)
    {
        $note = EAC()->notes->get($request["id"]);
        $data = $this->prepare_item_for_database($request);
        if (is_wp_error($data)) {
            return $data;
        }

        $saved = $note->fill($data)->save();
        if (is_wp_error($saved)) {
            return $saved;
        }

        $response = $this->prepare_item_for_response($saved, $request);

        return rest_ensure_response($response);
    }

    
    public function delete_item($request)
    {
        $note = EAC()->notes->get($request["id"]);
        $request->set_param("context", "edit");
        $data = $this->prepare_item_for_response($note, $request);

        if (!$note->delete()) {
            return new \WP_Error(
                "rest_cannot_delete",
                __("The note cannot be deleted.", "otto-contracts"),
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
            "eac_rest_prepare_note",
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
                switch ($prop) {
                    default:
                        $data[$prop] = $request[$prop];
                        break;
                }
            }
        }

        
        return apply_filters("eac_rest_pre_insert_note", $data, $request);
    }

    
    public function get_item_schema()
    {
        $schema = [
            '$schema' => "http://json-schema.org/draft-04/schema#",
            "title" => __("Note", "otto-contracts"),
            "type" => "object",
            "properties" => [
                "id" => [
                    "description" => __(
                        "Unique identifier for the note.",
                        "otto-contracts",
                    ),
                    "type" => "integer",
                    "context" => ["view", "embed", "edit"],
                    "readonly" => true,
                    "arg_options" => [
                        "sanitize_callback" => "intval",
                    ],
                ],
                "parent_id" => [
                    "description" => __("Parent ID.", "otto-contracts"),
                    "type" => "integer",
                    "context" => ["view", "embed", "edit"],
                    "required" => true,
                ],
                "parent_type" => [
                    "description" => __("Parent type.", "otto-contracts"),
                    "type" => "string",
                    "context" => ["view", "embed", "edit"],
                    "required" => true,
                ],
                "content" => [
                    "description" => __(
                        "Content of the note.",
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "context" => ["view", "embed", "edit"],
                ],
                "author_id" => [
                    "description" => __("Creator ID.", "otto-contracts"),
                    "type" => "integer",
                    "context" => ["view", "embed", "edit"],
                ],
                "date_updated" => [
                    "description" => __(
                        "The date the note was last updated, in the site's timezone.",
                        "otto-contracts",
                    ),
                    "type" => "date-time",
                    "context" => ["view", "edit"],
                    "readonly" => true,
                ],
                "date_created" => [
                    "description" => __(
                        "The date the note was created, in the site's timezone.",
                        "otto-contracts",
                    ),
                    "type" => "date-time",
                    "context" => ["view", "edit"],
                    "readonly" => true,
                ],
            ],
        ];

        
        $schema = apply_filters("eac_rest_note_item_schema", $schema);

        return $this->add_additional_fields_schema($schema);
    }
}
