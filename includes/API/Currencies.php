<?php

namespace Otto\API;

defined("ABSPATH") || exit();


class Currencies extends Controller
{
    
    protected $rest_base = "currencies";

    
    public function register_routes()
    {
        register_rest_route($this->namespace, "/" . $this->rest_base, [
            [
                "methods" => \WP_REST_Server::READABLE,
                "callback" => [$this, "get_items"],
                "permission_callback" => "__return_true",
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
            "/" . $this->rest_base . "/(?P<code>[A-Z]{3})",
            [
                "args" => [
                    "id" => [
                        "description" => __(
                            "Currency code.",
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
                "schema" => [$this, "get_public_item_schema"],
            ],
        );
    }

    
    public function get_items_permissions_check($request)
    {
        if (!current_user_can("eac_manage_currency")) {
            
            return new \WP_Error(
                "rest_forbidden_context",
                __(
                    "Sorry, you are not allowed to view currencies.",
                    "otto-contracts",
                ),
                ["status" => rest_authorization_required_code()],
            );
        }

        return true;
    }

    
    public function create_item_permissions_check($request)
    {
        if (!current_user_can("eac_manage_currency")) {
            
            return new \WP_Error(
                "rest_forbidden_context",
                __(
                    "Sorry, you are not allowed to create currencies.",
                    "otto-contracts",
                ),
                ["status" => rest_authorization_required_code()],
            );
        }

        return true;
    }

    
    public function get_item_permissions_check($request)
    {
        if (!current_user_can("eac_manage_currency")) {
            
            return new \WP_Error(
                "rest_forbidden_context",
                __(
                    "Sorry, you are not allowed to view this currency.",
                    "otto-contracts",
                ),
                ["status" => rest_authorization_required_code()],
            );
        }

        return true;
    }

    
    public function update_item_permissions_check($request)
    {
        if (!current_user_can("eac_manage_currency")) {
            
            return new \WP_Error(
                "rest_forbidden_context",
                __(
                    "Sorry, you are not allowed to update this currency.",
                    "otto-contracts",
                ),
                ["status" => rest_authorization_required_code()],
            );
        }

        return true;
    }

    
    public function delete_item_permissions_check($request)
    {
        if (!current_user_can("eac_manage_currency")) {
            
            return new \WP_Error(
                "rest_forbidden_context",
                __(
                    "Sorry, you are not allowed to delete this currency.",
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
        foreach ($params as $key => $value) {
            if (isset($request[$key])) {
                $args[$key] = $request[$key];
            }
        }

        
        $args = apply_filters("eac_rest_currency_query", $args, $request);
        $currencies = eac_get_currencies();
        $per_page = empty($args["per_page"]) ? 20 : $args["per_page"];
        $page = empty($args["page"]) ? 1 : $args["page"];
        $offset = ($page - 1) * $per_page;

        $total = count($currencies);
        $results =
            $total > $offset
                ? array_slice($currencies, $offset, $per_page)
                : [];

        $data = [];
        foreach ($results as $currency) {
            $data[] = $this->prepare_item_for_response($currency, $request);
        }

        $response = rest_ensure_response($data);
        $response->header("X-Total-Count", $total);
        $response->header("X-Total-Pages", ceil($total / (int) $per_page));

        return $response;
    }

    
    public function get_item($request)
    {
        $code = strtoupper($request["code"]);
        $currencies = eac_get_currencies();
        $currency = isset($currencies[$code]) ? $currencies[$code] : null;
        if (empty($currency)) {
            return new \WP_Error(
                "rest_currency_invalid",
                __("Invalid currency code.", "otto-contracts"),
                ["status" => 404],
            );
        }

        $response = $this->prepare_item_for_response($currency, $request);

        return rest_ensure_response($response);
    }

    
    public function create_item($request)
    {
        $option = get_option("eac_currencies", []);
        $code = strtoupper($request["code"]);
        if (isset($option[$code])) {
            return new \WP_Error(
                "rest_currency_exists",
                __("Cannot create existing currency.", "otto-contracts"),
                ["status" => 400],
            );
        }

        $data = $this->prepare_item_for_database($request);
        if (is_wp_error($data)) {
            return $data;
        }

        $option[$code] = $data;
        update_option("eac_currencies", $option);

        $response = $this->prepare_item_for_response($option[$code], $request);

        return rest_ensure_response($response);
    }

    
    public function update_item($request)
    {
        $currencies = eac_get_currencies();
        $code = strtoupper($request["code"]);
        if (empty($code) || !isset($currencies[$code])) {
            return new \WP_Error(
                "rest_currency_invalid",
                __("Invalid currency code.", "otto-contracts"),
                ["status" => 404],
            );
        }

        $currency = $currencies[$code];
        $data = $this->prepare_item_for_database($request);
        $options = get_option("eac_currencies", []);
        $options[$code] = [
            "rate" => isset($data["rate"])
                ? floatval($data["rate"])
                : $currency["rate"],
            "precision" => isset($data["precision"])
                ? intval($data["precision"])
                : $currency["precision"],
            "decimal_separator" => isset($data["decimal_separator"])
                ? $data["decimal_separator"]
                : $currency["decimal_separator"],
            "thousand_separator" => isset($data["thousand_separator"])
                ? $data["thousand_separator"]
                : $currency["thousand_separator"],
            "position" => isset($data["position"])
                ? $data["position"]
                : $currency["position"],
        ];

        update_option("eac_currencies", $options);

        $response = $this->prepare_item_for_response($options[$code], $request);

        return rest_ensure_response($response);
    }

    
    public function delete_item($request)
    {
        $currencies = eac_get_currencies();
        $code = strtoupper($request["code"]);
        if (empty($code) || !isset($currencies[$code])) {
            return new \WP_Error(
                "rest_currency_invalid",
                __("Invalid currency code.", "otto-contracts"),
                ["status" => 404],
            );
        }

        $options = get_option("eac_currencies", []);
        unset($options[$code]);
        update_option("eac_currencies", $options);

        return new \WP_REST_Response(null, 204);
    }

    
    public function prepare_item_for_response($item, $request)
    {
        return $item;
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

        
        return apply_filters("eac_rest_pre_insert_currency", $props, $request);
    }

    
    public function get_collection_params()
    {
        $params = [
            "context" => $this->get_context_param(),
            "page" => [
                "description" => __(
                    "Current page of the collection.",
                    "otto-contracts",
                ),
                "type" => "integer",
                "default" => 1,
                "sanitize_callback" => "absint",
                "validate_callback" => "rest_validate_request_arg",
                "minimum" => 1,
            ],
            "per_page" => [
                "description" => __(
                    "Maximum number of items to be returned in result set.",
                    "otto-contracts",
                ),
                "type" => "integer",
                "default" => 10,
                "minimum" => 1,
                "maximum" => 100,
                "sanitize_callback" => "absint",
                "validate_callback" => "rest_validate_request_arg",
            ],
        ];

        return $params;
    }

    
    public function get_item_schema()
    {
        $schema = [
            '$schema' => "http://json-schema.org/draft-04/schema#",
            "title" => __("currency", "otto-contracts"),
            "type" => "object",
            "properties" => [
                "code" => [
                    "description" => __("currency code.", "otto-contracts"),
                    "type" => "string",
                    "context" => ["view", "embed", "edit"],
                    "readonly" => true,
                    "required" => true,
                ],
                "name" => [
                    "description" => __("currency name.", "otto-contracts"),
                    "type" => "string",
                    "context" => ["view", "edit"],
                    "readonly" => true,
                ],
                "rate" => [
                    "description" => __(
                        "currency exchange rate.",
                        "otto-contracts",
                    ),
                    "type" => "number",
                    "context" => ["view", "edit"],
                    "required" => true,
                ],
                "precision" => [
                    "description" => __(
                        "currency decimals.",
                        "otto-contracts",
                    ),
                    "type" => "integer",
                    "context" => ["view", "edit"],
                    "required" => true,
                ],
                "symbol" => [
                    "description" => __(
                        "currency symbol.",
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "context" => ["view", "edit"],
                    "readonly" => true,
                ],
                "decimal_separator" => [
                    "description" => __(
                        "currency decimal separator.",
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "context" => ["view", "edit"],
                    "required" => true,
                ],
                "thousand_separator" => [
                    "description" => __(
                        "currency thousand separator.",
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "context" => ["view", "edit"],
                    "required" => true,
                ],
                "position" => [
                    "description" => __(
                        "currency position.",
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "enum" => ["before", "after"],
                    "context" => ["view", "edit"],
                    "required" => true,
                ],
            ],
        ];

        
        $schema = apply_filters("eac_rest_currency_item_schema", $schema);

        return $this->add_additional_fields_schema($schema);
    }
}
