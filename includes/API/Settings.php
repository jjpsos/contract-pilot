<?php

namespace Otto\API;

defined("ABSPATH") || exit();


class Settings extends Controller
{
    
    protected $rest_base = "settings";

    
    public function register_routes()
    {
        register_rest_route($this->namespace, "/" . $this->rest_base, [
            [
                "methods" => \WP_REST_Server::READABLE,
                "callback" => [$this, "get_items"],
                "permission_callback" => [$this, "get_items_permissions_check"],
            ],
            "schema" => [$this, "get_public_item_schema"],
        ]);
    }

    
    public function get_items($request)
    {
        $groups = apply_filters("eac_settings_groups", []);
        if (empty($groups)) {
            return new \WP_Error(
                "rest_setting_groups_empty",
                __(
                    "No setting groups have been registered.",
                    "otto-contracts",
                ),
                ["status" => 500],
            );
        }

        $defaults = $this->group_defaults();
        $filtered_groups = [];
        foreach ($groups as $group) {
            $sub_groups = [];
            foreach ($groups as $_group) {
                if (
                    !empty($_group["parent_id"]) &&
                    $group["id"] === $_group["parent_id"]
                ) {
                    $sub_groups[] = $_group["id"];
                }
            }
            $group["sub_groups"] = $sub_groups;

            $group = wp_parse_args($group, $defaults);
            if (!is_null($group["id"]) && !is_null($group["label"])) {
                $group_obj = $this->filter_group($group);
                $group_data = $this->prepare_item_for_response(
                    $group_obj,
                    $request,
                );
                $group_data = $this->prepare_response_for_collection(
                    $group_data,
                );

                $filtered_groups[] = $group_data;
            }
        }

        $response = rest_ensure_response($filtered_groups);

        return $response;
    }

    
    protected function prepare_links($group_id)
    {
        $base = "/" . $this->namespace . "/" . $this->rest_base;
        $links = [
            "options" => [
                "href" => rest_url(trailingslashit($base) . $group_id),
            ],
        ];

        return $links;
    }

    
    public function prepare_item_for_response($item, $request)
    {
        $context = empty($request["context"]) ? "view" : $request["context"];
        $data = $this->add_additional_fields_to_object($item, $request);
        $data = $this->filter_response_by_context($data, $context);

        $response = rest_ensure_response($data);

        $response->add_links($this->prepare_links($item["id"]));

        return $response;
    }

    
    public function filter_group($group)
    {
        return array_intersect_key(
            $group,
            array_flip(
                array_filter(array_keys($group), [$this, "allowed_group_keys"]),
            ),
        );
    }

    
    public function allowed_group_keys($key)
    {
        return in_array(
            $key,
            ["id", "label", "description", "parent_id", "sub_groups"],
            true,
        );
    }

    
    protected function group_defaults()
    {
        return [
            "id" => null,
            "label" => null,
            "description" => "",
            "parent_id" => "",
            "sub_groups" => [],
        ];
    }

    
    public function get_items_permissions_check($request)
    {
        if (!current_user_can("settings", "read")) {
            
            return new \WP_Error(
                "eac_rest_cannot_view",
                __("Sorry, you cannot list resources.", "otto-contracts"),
                ["status" => rest_authorization_required_code()],
            );
        }

        return true;
    }

    
    public function get_item_schema()
    {
        $schema = [
            '$schema' => "http://json-schema.org/draft-04/schema#",
            "title" => "setting_group",
            "type" => "object",
            "properties" => [
                "id" => [
                    "description" => __(
                        "A unique identifier that can be used to link settings together.",
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "context" => ["view"],
                    "readonly" => true,
                ],
                "label" => [
                    "description" => __(
                        "A human readable label for the setting used in interfaces.",
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "context" => ["view"],
                    "readonly" => true,
                ],
                "description" => [
                    "description" => __(
                        "A human readable description for the setting used in interfaces.",
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "context" => ["view"],
                    "readonly" => true,
                ],
                "parent_id" => [
                    "description" => __(
                        "ID of parent grouping.",
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "context" => ["view"],
                    "readonly" => true,
                ],
                "sub_groups" => [
                    "description" => __(
                        "IDs for settings sub groups.",
                        "otto-contracts",
                    ),
                    "type" => "string",
                    "context" => ["view"],
                    "readonly" => true,
                ],
            ],
        ];

        return $this->add_additional_fields_schema($schema);
    }
}
