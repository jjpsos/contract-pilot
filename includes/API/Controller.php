<?php

namespace Otto\API;

defined("ABSPATH") || exit();


class Controller extends \WP_REST_Controller
{
    
    protected $namespace = "eac/v1";

    
    protected $rest_base = "";

    
    protected function get_normalized_rest_base()
    {
        return preg_replace("/\(.*\)\//i", "", $this->rest_base);
    }

    
    protected function get_schema_properties()
    {
        $schema = $this->get_item_schema();
        $properties = isset($schema["properties"]) ? $schema["properties"] : [];

        
        
        foreach (
            $this->get_additional_fields()
            as $field_name => $field_options
        ) {
            if (is_null($field_options["schema"])) {
                $properties[$field_name] = $field_options;
            }
        }

        return $properties;
    }

    
    protected function filter_response_fields_by_context($fields, $context)
    {
        if (empty($context)) {
            return $fields;
        }

        foreach ($fields as $name => $options) {
            if (
                !empty($options["context"]) &&
                !in_array($context, $options["context"], true)
            ) {
                unset($fields[$name]);
            }
        }

        return $fields;
    }

    
    protected function filter_response_fields_by_array($fields, $requested)
    {
        
        $requested = array_map("trim", $requested);

        
        if (in_array("id", $fields, true)) {
            $requested[] = "id";
        }

        
        $requested = array_unique($requested);

        
        return array_reduce(
            $requested,
            function ($response_fields, $field) use ($fields) {
                if (in_array($field, $fields, true)) {
                    $response_fields[] = $field;

                    return $response_fields;
                }

                
                $nested_fields = explode(".", $field);

                
                
                if (in_array($nested_fields[0], $fields, true)) {
                    $response_fields[] = $field;
                }

                return $response_fields;
            },
            [],
        );
    }

    
    public function get_fields_for_response($request)
    {
        
        $properties = $this->get_schema_properties();

        
        $properties = $this->filter_response_fields_by_context(
            $properties,
            $request["context"],
        );

        
        $fields = array_keys($properties);

        
        if (empty($request["_fields"])) {
            return $fields;
        }

        return $this->filter_response_fields_by_array(
            $fields,
            wp_parse_list($request["_fields"]),
        );
    }

    
    public function limit_object_to_requested_fields(
        $data,
        $fields,
        $prefix = ""
    ) {
        
        if (empty($fields)) {
            return $data;
        }

        foreach ($data as $key => $value) {
            
            if (is_numeric($key) && is_array($value)) {
                $data[$key] = $this->limit_object_to_requested_fields(
                    $value,
                    $fields,
                    $prefix,
                );
                continue;
            }

            
            $new_prefix = empty($prefix) ? $key : "$prefix.$key";

            
            if (
                !empty($key) &&
                !$this->is_field_included($new_prefix, $fields)
            ) {
                unset($data[$key]);
                continue;
            }

            if ("meta_data" !== $key && is_array($value)) {
                $data[$key] = $this->limit_object_to_requested_fields(
                    $value,
                    $fields,
                    $new_prefix,
                );
            }
        }

        return $data;
    }

    
    public function is_field_included($field, $fields)
    {
        if (in_array($field, $fields, true)) {
            return true;
        }

        foreach ($fields as $accepted_field) {
            
            
            if (strpos($accepted_field, "$field.") === 0) {
                return true;
            }
            
            
            if (strpos($field, "$accepted_field.") === 0) {
                return true;
            }
        }

        return false;
    }

    
    protected function filter_writable_props($schema)
    {
        return empty($schema["readonly"]);
    }

    
    protected function prepare_date_response($date = null)
    {
        
        if (!empty($date) || "0000-00-00 00:00:00" !== $date) {
            return mysql_to_rfc3339($date);
        }

        return null;
    }

    
    protected function prepare_date_for_database(
        $date = null,
        $format = "Y-m-d H:i:s"
    ) {
        $timestamp = null;
        if (is_numeric($date)) {
            $timestamp = (int) $date;
        } elseif (
            1 ===
            preg_match(
                '/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})(Z|((-|\+)\d{2}:\d{2}))$/',
                $date,
                $date_bits,
            )
        ) {
            $offset = !empty($date_bits[7])
                ? iso8601_timezone_to_offset($date_bits[7])
                : wc_timezone_offset();
            $timestamp =
                gmmktime(
                    $date_bits[4],
                    $date_bits[5],
                    $date_bits[6],
                    $date_bits[2],
                    $date_bits[3],
                    $date_bits[1],
                ) - $offset;
        } elseif (!empty($date) && false !== strtotime($date)) {
            $timestamp = get_gmt_from_date(
                gmdate("Y-m-d H:i:s", strtotime($date)),
                "U",
            );
        }

        return isset($timestamp)
            ? (new \DateTime("@{$timestamp}", new \DateTimeZone("UTC")))->format(
                $format,
            )
            : null;
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
            "search" => [
                "description" => __(
                    "Limit results to those matching a string.",
                    "otto-contracts",
                ),
                "type" => "string",
                "sanitize_callback" => "sanitize_text_field",
                "validate_callback" => "rest_validate_request_arg",
            ],
            "include" => [
                "description" => __(
                    "Limit result set to specific ids.",
                    "otto-contracts",
                ),
                "type" => "array",
                "items" => ["type" => "integer"],
                "default" => [],
                "sanitize_callback" => "wp_parse_id_list",
            ],
            "order" => [
                "description" => __(
                    "Order sort attribute ascending or descending.",
                    "otto-contracts",
                ),
                "type" => "string",
                "default" => "desc",
                "enum" => ["asc", "desc"],
                "validate_callback" => "rest_validate_request_arg",
            ],
            "orderby" => [
                "description" => __(
                    "Sort collection by object attribute.",
                    "otto-contracts",
                ),
                "type" => "string",
                "default" => "date_created",
                "validate_callback" => "rest_validate_request_arg",
            ],
            "offset" => [
                "description" => __(
                    "Offset the result set by a specific number of items.",
                    "otto-contracts",
                ),
                "type" => "integer",
                "sanitize_callback" => "absint",
                "validate_callback" => "rest_validate_request_arg",
            ],
        ];

        return $params;
    }

    
    public function get_endpoint_data($endpoint, $params = [], $method = "GET")
    {
        $request = new \WP_REST_Request($method, $endpoint);
        if ($params && "GET" === $method) {
            $request->set_query_params($params);
        } elseif ($params && "POST" === $method) {
            $request->set_body_params($params);
        }
        $response = rest_do_request($request);
        $server = rest_get_server();
        $json = wp_json_encode($server->response_to_data($response, false));

        return json_decode($json, true);
    }
}
