<?php

namespace Otto\Admin;

defined( 'ABSPATH' ) || exit;


class Exporters {

	
	public function __construct() {
		add_filter( 'eac_tools_page_tabs', array( __CLASS__, 'register_tabs' ), - 1 );
		add_action( 'admin_post_eac_download_export', array( __CLASS__, 'handle_csv_download' ) );
		add_action( 'eac_tools_page_export_content', array( __CLASS__, 'documents_export' ) );
		add_action( 'eac_tools_page_export_content', array( __CLASS__, 'document_items_export' ) );
		add_action( 'eac_tools_page_export_content', array( __CLASS__, 'customers_export' ) );
		add_action( 'eac_tools_page_export_content', array( __CLASS__, 'items_export' ) );
		add_action( 'eac_tools_page_export_content', array( __CLASS__, 'accounts_export' ) );
		add_action( 'eac_tools_page_export_content', array( __CLASS__, 'payments_export' ) );
		add_action( 'eac_tools_page_export_content', array( __CLASS__, 'expenses_export' ) );
		add_action( 'eac_tools_page_export_content', array( __CLASS__, 'transfers_export' ) );
		add_action( 'eac_tools_page_export_content', array( __CLASS__, 'categories_export' ) );
		add_action( 'eac_tools_page_export_content', array( __CLASS__, 'taxes_export' ) );
	}

	
	public static function register_tabs( $tabs ) {
		if ( current_user_can( 'eac_manage_export' ) || current_user_can( 'eac_banking_tools_access' ) || current_user_can( 'eac_manage_options' ) ) {
			$tabs['export'] = __( 'Export', 'otto-contracts' );
		}

		return $tabs;
	}


	
	public static function handle_csv_download() {
		check_admin_referer( 'eac_download_file' );
		$type     = isset( $_GET['type'] ) ? sanitize_key( $_GET['type'] ) : '';
		$filename = isset( $_GET['filename'] ) ? sanitize_text_field( wp_unslash( $_GET['filename'] ) ) : '';
		$exporter = self::get_exporter( $type );
		if ( ! $exporter || ! is_subclass_of( $exporter, Exporters\Exporter::class ) ) {
			wp_die( esc_html__( 'Invalid export type.', 'otto-contracts' ) );
		}
		$exporter = new $exporter();
		if ( ! $exporter->can_export() ) {
			wp_die( esc_html__( 'You do not have permission to export.', 'otto-contracts' ) );
		}

		if ( ! empty( $filename ) ) {
			$exporter->set_filename( $filename );
		}

		$exporter->export();
		exit;
	}

	
	public static function customers_export() {
		?>
		<div class="eac-card">
			<div class="eac-card__header">
				<h2 class="eac-card__title"><?php esc_html_e( 'Export Clients', 'otto-contracts' ); ?></h2>
			</div>
			<div class="eac-card__body">
				<form method="post" class="eac_exporter" data-type="customers" data-nonce="<?php echo esc_attr( wp_create_nonce( 'eac_ajax_export' ) ); ?>">
					<p><?php esc_html_e( 'Export clients from this site as CSV file.', 'otto-contracts' ); ?></p>
					<?php submit_button( esc_html__( 'Export', 'otto-contracts' ), 'secondary', null, false ); ?>
				</form>
			</div>
		</div>
		<?php
	}

	
	public static function categories_export() {
		?>
		<div class="eac-card">
			<div class="eac-card__header">
				<h2 class="eac-card__title"><?php esc_html_e( 'Export Categories', 'otto-contracts' ); ?></h2>
			</div>
			<div class="eac-card__body">
				<form method="post" class="eac_exporter" data-type="categories" data-nonce="<?php echo esc_attr( wp_create_nonce( 'eac_ajax_export' ) ); ?>">
					<p><?php esc_html_e( 'Export categories from this site as CSV file.', 'otto-contracts' ); ?></p>
					<?php submit_button( esc_html__( 'Export', 'otto-contracts' ), 'secondary', null, false ); ?>
				</form>
			</div>
		</div>
		<?php
	}

	
	public static function taxes_export() {
		?>
		<div class="eac-card">
			<div class="eac-card__header">
				<h2 class="eac-card__title"><?php esc_html_e( 'Export Taxes', 'otto-contracts' ); ?></h2>
			</div>
			<div class="eac-card__body">
				<form method="post" class="eac_exporter" data-type="taxes" data-nonce="<?php echo esc_attr( wp_create_nonce( 'eac_ajax_export' ) ); ?>">
					<p><?php esc_html_e( 'Export taxes from this site as CSV file.', 'otto-contracts' ); ?></p>
					<?php submit_button( esc_html__( 'Export', 'otto-contracts' ), 'secondary', null, false ); ?>
				</form>
			</div>
		</div>
		<?php
	}

	
	public static function items_export() {
		?>
		<div class="eac-card">
			<div class="eac-card__header">
				<h2 class="eac-card__title"><?php esc_html_e( 'Export Services', 'otto-contracts' ); ?></h2>
			</div>
			<div class="eac-card__body">
				<form method="post" class="eac_exporter" data-type="items" data-nonce="<?php echo esc_attr( wp_create_nonce( 'eac_ajax_export' ) ); ?>">
					<p><?php esc_html_e( 'Export services from this site as CSV file.', 'otto-contracts' ); ?></p>
					<?php submit_button( esc_html__( 'Export', 'otto-contracts' ), 'secondary', null, false ); ?>
				</form>
			</div>
		</div>
		<?php
	}

	
	public static function accounts_export() {
		?>
		<div class="eac-card">
			<div class="eac-card__header">
				<h2 class="eac-card__title"><?php esc_html_e( 'Export Accounts', 'otto-contracts' ); ?></h2>
			</div>
			<div class="eac-card__body">
				<form method="post" class="eac_exporter" data-type="accounts" data-nonce="<?php echo esc_attr( wp_create_nonce( 'eac_ajax_export' ) ); ?>">
					<p><?php esc_html_e( 'Export accounts from this site as CSV file.', 'otto-contracts' ); ?></p>
					<?php submit_button( esc_html__( 'Export', 'otto-contracts' ), 'secondary', null, false ); ?>
				</form>
			</div>
		</div>
		<?php
	}

	
	public static function transfers_export() {
		?>
		<div class="eac-card">
			<div class="eac-card__header">
				<h2 class="eac-card__title"><?php esc_html_e( 'Export Transfers', 'otto-contracts' ); ?></h2>
			</div>
			<div class="eac-card__body">
				<form method="post" class="eac_exporter" data-type="transfers" data-nonce="<?php echo esc_attr( wp_create_nonce( 'eac_ajax_export' ) ); ?>">
					<p><?php esc_html_e( 'Export transfers from this site as CSV file.', 'otto-contracts' ); ?></p>
					<?php submit_button( esc_html__( 'Export', 'otto-contracts' ), 'secondary', null, false ); ?>
				</form>
			</div>
		</div>
		<?php
	}

	
	public static function payments_export() {
		?>
		<div class="eac-card">
			<div class="eac-card__header">
				<h2 class="eac-card__title"><?php esc_html_e( 'Export Payments', 'otto-contracts' ); ?></h2>
			</div>
			<div class="eac-card__body">
				<form method="post" class="eac_exporter" data-type="payments" data-nonce="<?php echo esc_attr( wp_create_nonce( 'eac_ajax_export' ) ); ?>">
					<p><?php esc_html_e( 'Export payments from this site as CSV file.', 'otto-contracts' ); ?></p>
					<?php submit_button( esc_html__( 'Export', 'otto-contracts' ), 'secondary', null, false ); ?>
				</form>
			</div>
		</div>
		<?php
	}

	
	public static function expenses_export() {
		?>
		<div class="eac-card">
			<div class="eac-card__header">
				<h2 class="eac-card__title"><?php esc_html_e( 'Export Expenses', 'otto-contracts' ); ?></h2>
			</div>
			<div class="eac-card__body">
				<form method="post" class="eac_exporter" data-type="expenses" data-nonce="<?php echo esc_attr( wp_create_nonce( 'eac_ajax_export' ) ); ?>">
					<p><?php esc_html_e( 'Export expenses from this site as CSV file.', 'otto-contracts' ); ?></p>
					<?php submit_button( esc_html__( 'Export', 'otto-contracts' ), 'secondary', null, false ); ?>
				</form>
			</div>
		</div>
		<?php
	}

	
	public static function documents_export() {
		?>
		<div class="eac-card">
			<div class="eac-card__header">
				<h2 class="eac-card__title"><?php esc_html_e( 'Export Documents', 'otto-contracts' ); ?></h2>
			</div>
			<div class="eac-card__body">
				<form method="post" class="eac_exporter" data-type="documents" data-nonce="<?php echo esc_attr( wp_create_nonce( 'eac_ajax_export' ) ); ?>">
					<p><?php esc_html_e( 'Export documents (Contracts/Bills) headers as CSV.', 'otto-contracts' ); ?></p>
					<?php submit_button( esc_html__( 'Export', 'otto-contracts' ), 'secondary', null, false ); ?>
				</form>
			</div>
		</div>
		<?php
	}

	
	public static function document_items_export() {
		?>
		<div class="eac-card">
			<div class="eac-card__header">
				<h2 class="eac-card__title"><?php esc_html_e( 'Export Document Lines', 'otto-contracts' ); ?></h2>
			</div>
			<div class="eac-card__body">
				<form method="post" class="eac_exporter" data-type="document_items" data-nonce="<?php echo esc_attr( wp_create_nonce( 'eac_ajax_export' ) ); ?>">
					<p><?php esc_html_e( 'Export document line items (Contracts/Bills) as CSV.', 'otto-contracts' ); ?></p>
					<?php submit_button( esc_html__( 'Export', 'otto-contracts' ), 'secondary', null, false ); ?>
				</form>
			</div>
		</div>
		<?php
	}

	
	public static function get_exporter( $type ) {
		switch ( $type ) {
			case 'customers':
				$exporter = Exporters\Customers::class;
				break;
			case 'categories':
				$exporter = Exporters\Categories::class;
				break;
			case 'taxes':
				$exporter = Exporters\Taxes::class;
				break;
			case 'items':
				$exporter = Exporters\Items::class;
				break;
			case 'accounts':
				$exporter = Exporters\Accounts::class;
				break;
			case 'transfers':
				$exporter = Exporters\Transfers::class;
				break;
			case 'expenses':
				$exporter = Exporters\Expenses::class;
				break;
			case 'payments':
				$exporter = Exporters\Payments::class;
				break;
			case 'documents':
				$exporter = Exporters\Documents::class;
				break;
			case 'document_items':
				$exporter = Exporters\DocumentItems::class;
				break;
			default:
				
				$exporter = apply_filters( "eac_ajax_{$type}_exporter", null );
		}

		return $exporter;
	}
}
