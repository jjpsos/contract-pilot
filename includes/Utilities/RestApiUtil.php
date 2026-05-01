<?php

namespace Otto\Utilities;

defined("ABSPATH") || exit();


class RestApiUtil
{
    
    public static function get_response($path, $args = [], $method = "GET")
    {
        $endpoint = get_rest_url(null, $path);
        $request = new \WP_REST_Request($method, $endpoint);
        if (!empty($args)) {
            if ("GET" === $method) {
                $request->set_query_params($args);
            } else {
                $request->set_body_params($args);
            }
        }

        $response = rest_do_request($request);
        $server = rest_get_server();
        $json = wp_json_encode($server->response_to_data($response, false));
        return json_decode($json, true);
    }
}
