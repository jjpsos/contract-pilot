<?php

namespace Otto\Core;

defined("ABSPATH") || exit();


class Queue
{
    
    const GROUP = "otto-accounting";

    
    protected static $instance = null;

    
    final public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    
    public function add($hook, $args = [], $group = self::GROUP)
    {
        return $this->schedule_single(time(), $hook, $args, $group);
    }

    
    public function schedule_single(
        $timestamp,
        $hook,
        $args = [],
        $group = self::GROUP
    ) {
        return as_schedule_single_action($timestamp, $hook, $args, $group);
    }

    
    public function schedule_recurring(
        $timestamp,
        $interval_in_seconds,
        $hook,
        $args = [],
        $group = self::GROUP
    ) {
        return as_schedule_recurring_action(
            $timestamp,
            $interval_in_seconds,
            $hook,
            $args,
            $group,
        );
    }

    
    public function is_scheduled($hook, $args = [], $group = self::GROUP)
    {
        if (!function_exists("as_has_scheduled_action")) {
            return !is_null($this->get_next($hook, $args));
        }

        return as_has_scheduled_action($hook, $args, $group);
    }

    
    public function schedule_cron(
        $timestamp,
        $cron_schedule,
        $hook,
        $args = [],
        $group = self::GROUP
    ) {
        return as_schedule_cron_action(
            $timestamp,
            $cron_schedule,
            $hook,
            $args,
            $group,
        );
    }

    
    public function cancel($hook, $args = [], $group = self::GROUP)
    {
        as_unschedule_action($hook, $args, $group);
    }

    
    public function cancel_all($hook, $args = [], $group = self::GROUP)
    {
        as_unschedule_all_actions($hook, $args, $group);
    }

    
    public function get_next($hook, $args = null, $group = self::GROUP)
    {
        $next_timestamp = as_next_scheduled_action($hook, $args, $group);
        if (is_numeric($next_timestamp)) {
            return $next_timestamp;
        }

        return null;
    }

    
    public function search($args = [], $return_format = OBJECT)
    {
        return as_get_scheduled_actions($args, $return_format);
    }

    
    public function cleanup()
    {
        global $wpdb;
        $wpdb->query(
            "DELETE FROM {$wpdb->prefix}actionscheduler_logs WHERE action_id IN ( SELECT action_id FROM {$wpdb->prefix}actionscheduler_actions WHERE status = 'complete' )",
        );
        $wpdb->query(
            "DELETE FROM {$wpdb->prefix}actionscheduler_actions WHERE status = 'complete'",
        );
    }
}
