<?php

namespace Otto;

defined("ABSPATH") || exit();


class Crons
{
    
    public function __construct()
    {
        add_action("eac_hourly_event", [$this, "cleanup_scheduled_events"]);
    }

    
    public function cleanup_scheduled_events()
    {
        EAC()->queue()->cleanup();
    }
}
