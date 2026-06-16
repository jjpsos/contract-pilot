<?php

/**
 * Fired when the plugin is uninstalled (deleted), not on deactivate.
 *
 * @package Jjpsos_Contract_Pilot
 */

defined('WP_UNINSTALL_PLUGIN') || exit;
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Uninstall performs intentional direct SQL cleanup for plugin-owned options/tables.

require_once __DIR__ . '/vendor/autoload.php';

use Jjpsos\ContractPilot\Utilities\DatabaseUtil;

( static function () {
    global $wpdb;

    if (! is_blog_installed()) {
        return;
    }

    remove_role('contract_pilot_auditor');
    remove_role('contract_pilot_accountant');
    remove_role('contract_pilot_manager');


    // Check if WP_UNINSTALL_PLUGIN constant is defined.
    // using parameterized queries to avoid SQL injection vulnerabilities.
    if (defined('WP_UNINSTALL_PLUGIN')) {
        wp_cache_delete('alloptions', 'options');
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                $wpdb->esc_like('contract_pilot_') . '%',
            ),
        );
    }

    wp_clear_scheduled_hook('contract_pilot_hourly_event');
    wp_clear_scheduled_hook('contract_pilot_daily_event');
    wp_clear_scheduled_hook('contract_pilot_weekly_event');

    DatabaseUtil::drop_plugin_tables();
} )();
