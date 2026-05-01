<?php

namespace Otto\API;

use Otto\Utilities\I18nUtil;

defined("ABSPATH") || exit();


class Utilities extends Controller
{
    
    protected $rest_base = "utilities";

    
    public function register_routes()
    {
        register_rest_route(
            $this->namespace,
            "/" . $this->rest_base . "/currencies",
            [
                "methods" => "GET",
                "callback" => [$this, "get_currencies"],
                "permission_callback" => function () {
                    return current_user_can("manage_accounting"); 
                },
            ],
        );
        
        register_rest_route(
            $this->namespace,
            "/" . $this->rest_base . "/countries",
            [
                "methods" => "GET",
                "callback" => [$this, "get_countries"],
                "permission_callback" => function () {
                    return current_user_can("manage_accounting"); 
                },
            ],
        );
    }

    
    public function get_currencies($request)
    {
        $currencies = I18nUtil::get_currencies();

        return rest_ensure_response($currencies);
    }

    
    public function get_countries($request)
    {
        $countries = I18nUtil::get_countries();

        return rest_ensure_response($countries);
    }
}
