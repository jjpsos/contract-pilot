<?php

namespace Jjpsos\ContractPilot\Foundation;

defined('ABSPATH') || exit();

/**
 * Lightweight job queue backed by WordPress cron (no Action Scheduler).
 */
class Queue
{
    const GROUP = 'contract-pilot';

    /** @var self|null */
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
        unset($group);

        $args = $this->normalize_args($args);

        if ($this->is_scheduled($hook, $args)) {
            return false;
        }

        return wp_schedule_single_event((int) $timestamp, $hook, $args);
    }

    public function schedule_recurring(
        $timestamp,
        $interval_in_seconds,
        $hook,
        $args = [],
        $group = self::GROUP
    ) {
        unset($group);

        $args     = $this->normalize_args($args);
        $schedule = $this->interval_to_schedule((int) $interval_in_seconds);

        if (wp_next_scheduled($hook, $args)) {
            return false;
        }

        return wp_schedule_event((int) $timestamp, $schedule, $hook, $args);
    }

    public function is_scheduled($hook, $args = [], $group = self::GROUP)
    {
        unset($group);

        return (bool) wp_next_scheduled($hook, $this->normalize_args($args));
    }

    public function schedule_cron(
        $timestamp,
        $cron_schedule,
        $hook,
        $args = [],
        $group = self::GROUP
    ) {
        unset($group);

        $args = $this->normalize_args($args);

        if (wp_next_scheduled($hook, $args)) {
            return false;
        }

        return wp_schedule_event((int) $timestamp, $cron_schedule, $hook, $args);
    }

    public function cancel($hook, $args = [], $group = self::GROUP)
    {
        unset($group);

        $args = $this->normalize_args($args);

        while ($timestamp = wp_next_scheduled($hook, $args)) {
            wp_unschedule_event($timestamp, $hook, $args);
        }
    }

    public function cancel_all($hook, $args = [], $group = self::GROUP)
    {
        unset($group, $args);

        wp_clear_scheduled_hook($hook);
    }

    public function get_next($hook, $args = null, $group = self::GROUP)
    {
        unset($group);

        if (null === $args) {
            $next = wp_next_scheduled($hook);
        } else {
            $next = wp_next_scheduled($hook, $this->normalize_args($args));
        }

        return is_numeric($next) ? (int) $next : null;
    }

    /**
     * @param array<string, mixed> $args
     * @return array<int, mixed>
     */
    public function search($args = [], $return_format = OBJECT)
    {
        unset($return_format);

        $hook   = isset($args['hook']) ? (string) $args['hook'] : '';
        $events = [];
        $crons  = _get_cron_array();

        if (! is_array($crons)) {
            return $events;
        }

        foreach ($crons as $timestamp => $hooks) {
            if (! is_array($hooks)) {
                continue;
            }

            foreach ($hooks as $scheduled_hook => $instances) {
                if ($hook && $scheduled_hook !== $hook) {
                    continue;
                }

                if (0 !== strpos((string) $scheduled_hook, 'contract_pilot_')) {
                    continue;
                }

                if (! is_array($instances)) {
                    continue;
                }

                foreach ($instances as $instance) {
                    $events[] = (object) [
                        'hook'      => $scheduled_hook,
                        'timestamp' => (int) $timestamp,
                        'args'      => isset($instance['args']) ? $instance['args'] : [],
                    ];
                }
            }
        }

        return $events;
    }

    /**
     * Run any overdue Contract Pilot cron hooks (e.g. DB migrations when wp-cron is delayed).
     */
    public function dispatch_due_events()
    {
        if (defined('DOING_CRON') && DOING_CRON) {
            return;
        }

        $crons = _get_cron_array();

        if (! is_array($crons) || [] === $crons) {
            return;
        }

        $due = [];

        foreach ($crons as $timestamp => $hooks) {
            if ((int) $timestamp > time()) {
                break;
            }

            if (! is_array($hooks)) {
                continue;
            }

            foreach ($hooks as $hook => $instances) {
                if (0 !== strpos((string) $hook, 'contract_pilot_')) {
                    continue;
                }

                if (! is_array($instances)) {
                    continue;
                }

                foreach ($instances as $instance) {
                    $args = isset($instance['args']) && is_array($instance['args'])
                        ? $instance['args']
                        : [];

                    $due[] = [
                        'timestamp' => (int) $timestamp,
                        'hook'      => $hook,
                        'args'      => $args,
                    ];
                }
            }
        }

        foreach ($due as $event) {
            wp_unschedule_event($event['timestamp'], $event['hook'], $event['args']);
            $this->dispatch_due_hook($event['hook'], $event['args']);
        }
    }

    /**
     * Dispatch a due cron hook after validating the contract_pilot_ prefix.
     *
     * @since 1.0.0
     *
     * @param string               $hook Cron hook name.
     * @param array<string, mixed> $args Hook arguments.
     * @return void
     */
    protected function dispatch_due_hook($hook, $args)
    {
        if (0 !== strpos((string) $hook, 'contract_pilot_')) {
            return;
        }

        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- Only contract_pilot_ hooks are dispatched.
        do_action_ref_array($hook, array_values($args));
    }

    public function cleanup()
    {
        // WP cron removes events after they run; clear stale duplicates for our hooks.
        foreach ($this->get_contract_pilot_hooks() as $hook) {
            $this->dedupe_scheduled_hook($hook);
        }
    }

    /**
     * WordPress cron stores argument lists; preserve order for hook callbacks.
     *
     * @param array<int|string, mixed> $args
     * @return array<int, mixed>
     */
    protected function normalize_args($args)
    {
        if (! is_array($args)) {
            return [];
        }

        return array_values($args);
    }

    protected function interval_to_schedule($interval_in_seconds)
    {
        if ($interval_in_seconds <= HOUR_IN_SECONDS) {
            return 'hourly';
        }

        if ($interval_in_seconds <= DAY_IN_SECONDS) {
            return 'daily';
        }

        if ($interval_in_seconds <= WEEK_IN_SECONDS) {
            return 'weekly';
        }

        return 'monthly';
    }

    /**
     * @return string[]
     */
    protected function get_contract_pilot_hooks()
    {
        return [
            'contract_pilot_run_update_callback',
            'contract_pilot_update_db_version',
            'contract_pilot_hourly_event',
            'contract_pilot_daily_event',
            'contract_pilot_weekly_event',
        ];
    }

    protected function dedupe_scheduled_hook($hook)
    {
        $seen   = [];
        $crons  = _get_cron_array();

        if (! is_array($crons)) {
            return;
        }

        foreach ($crons as $timestamp => $hooks) {
            if (! isset($hooks[ $hook ]) || ! is_array($hooks[ $hook ])) {
                continue;
            }

            foreach ($hooks[ $hook ] as $instance) {
                $args = isset($instance['args']) && is_array($instance['args'])
                    ? $instance['args']
                    : [];
                $key  = wp_json_encode($args);

                if (isset($seen[ $key ])) {
                    wp_unschedule_event((int) $timestamp, $hook, $args);
                    continue;
                }

                $seen[ $key ] = true;
            }
        }
    }
}
