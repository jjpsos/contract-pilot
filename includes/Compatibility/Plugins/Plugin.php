<?php

namespace Otto\Compatibility\Plugins;

defined("ABSPATH") || exit();


abstract class Plugin
{
    
    final public function __construct()
    {
        if (!$this->is_active()) {
            return;
        }

        
        $this->register_events();
    }

    
    abstract public function is_active(): bool;

    
    abstract protected function register_events(): void;
}
