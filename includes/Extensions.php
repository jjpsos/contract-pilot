<?php

namespace Otto;

defined("ABSPATH") || exit();


class Extensions
{
    
    protected $extensions = [];

    
    public function __construct()
    {
        add_action("init", [$this, "register_extensions"]);
    }

    
    public function register_extensions() {}
}
