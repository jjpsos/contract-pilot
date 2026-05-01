<?php
/**
 * Fired when the plugin is uninstalled (deleted), not on deactivate.
 *
 * @package Otto_Contracts
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

if ( ! is_blog_installed() ) {
	return;
}

remove_role( 'eac_auditor' );
remove_role( 'eac_accountant' );
remove_role( 'eac_manager' );

// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Matches Installer::uninstall().
$wpdb->query(
	"DELETE FROM $wpdb->options WHERE option_name LIKE 'eac\_%';",
);

wp_clear_scheduled_hook( 'eac_hourly_event' );
wp_clear_scheduled_hook( 'eac_daily_event' );
wp_clear_scheduled_hook( 'eac_weekly_event' );

$tables = array(
	'otto_accounts',
	'otto_contactmeta',
	'otto_contacts',
	'otto_document_items',
	'otto_document_taxes',
	'otto_documentmeta',
	'otto_documents',
	'otto_items',
	'otto_itemmeta',
	'otto_notes',
	'otto_terms',
	'otto_termmeta',
	'otto_transactionmeta',
	'otto_transactions',
	'otto_transfers',
);

foreach ( $tables as $table ) {
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is allowlisted; prefix is from $wpdb.
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}{$table}" );
}
