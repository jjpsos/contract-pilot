<?php

namespace Jjpsos\ContractPilot;

defined('ABSPATH') || exit();

class Crons
{
    public function __construct()
    {
        add_action('contract_pilot_hourly_event', [$this, 'cleanup_scheduled_events']);
    }

    public function cleanup_scheduled_events()
    {
        contract_pilot()->queue()->cleanup();
    }
}
