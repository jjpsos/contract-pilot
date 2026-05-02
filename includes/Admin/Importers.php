<?php

namespace Otto\Admin;

defined( 'ABSPATH' ) || exit;


class Importers {

	
	public function __construct() {
		add_filter( 'eac_tools_page_tabs', array( __CLASS__, 'register_tabs' ), - 1 );
		add_action( 'eac_tools_page_import_content', array( __CLASS__, 'documents_import' ) );
		add_action( 'eac_tools_page_import_content', array( __CLASS__, 'document_items_import' ) );
		add_action( 'eac_tools_page_import_content', array( __CLASS__, 'customers_import' ) );
		add_action( 'eac_tools_page_import_content', array( __CLASS__, 'items_import' ) );
		add_action( 'eac_tools_page_import_content', array( __CLASS__, 'accounts_import' ) );
		add_action( 'eac_tools_page_import_content', array( __CLASS__, 'payments_import' ) );
		add_action( 'eac_tools_page_import_content', array( __CLASS__, 'expenses_import' ) );
		add_action( 'eac_tools_page_import_content', array( __CLASS__, 'transfers_import' ) );
		add_action( 'eac_tools_page_import_content', array( __CLASS__, 'categories_import' ) );
		add_action( 'eac_tools_page_import_content', array( __CLASS__, 'taxes_import' ) );
	}

	
	public static function register_tabs( $tabs ) {
		if ( current_user_can( 'eac_manage_import' ) || current_user_can( 'eac_banking_tools_access' ) || current_user_can( 'eac_manage_options' ) ) {
			$tabs['import'] = __( 'Import', 'otto-contracts' );
		}

		return $tabs;
	}


	
	public static function customers_import() {
		?>
		<div class="eac-card">
			<div class="eac-card__header">
				<h2 class="eac-card__title"><?php esc_html_e( 'Import Clients', 'otto-contracts' ); ?></h2>
			</div>
			<div class="eac-card__body">
				<form method="post" enctype="multipart/form-data" class="eac_importer" data-type="customers" data-nonce="<?php echo esc_attr( wp_create_nonce( 'eac_ajax_import' ) ); ?>">
					<p><?php esc_html_e( 'Import clients from CSV file.', 'otto-contracts' ); ?></p>
					<div class="eac-form-field">
						<label for="file"><?php esc_html_e( 'Select file', 'otto-contracts' ); ?></label>
						<input type="file" name="file" id="file" accept="text/csv" required>
					</div>
					<?php submit_button( esc_html__( 'Import', 'otto-contracts' ), 'secondary', null, false ); ?>
				</form>
			</div>
		</div>
		<?php
	}

	
	public static function categories_import() {
		?>
		<div class="eac-card">
			<div class="eac-card__header">
				<h2 class="eac-card__title"><?php esc_html_e( 'Import Categories', 'otto-contracts' ); ?></h2>
			</div>
			<div class="eac-card__body">
				<form method="post" enctype="multipart/form-data" class="eac_importer" data-type="categories" data-nonce="<?php echo esc_attr( wp_create_nonce( 'eac_ajax_import' ) ); ?>">
					<p><?php esc_html_e( 'Import categories from CSV file.', 'otto-contracts' ); ?></p>
					<div class="eac-form-field">
						<label for="file"><?php esc_html_e( 'Select file', 'otto-contracts' ); ?></label>
						<input type="file" name="file" id="file" accept="text/csv" required>
					</div>
					<?php submit_button( esc_html__( 'Import', 'otto-contracts' ), 'secondary', null, false ); ?>
				</form>
			</div>
		</div>
		<?php
	}

	
	public static function taxes_import() {
		?>
		<div class="eac-card">
			<div class="eac-card__header">
				<h2 class="eac-card__title"><?php esc_html_e( 'Import Taxes', 'otto-contracts' ); ?></h2>
			</div>
			<div class="eac-card__body">
				<form method="post" enctype="multipart/form-data" class="eac_importer" data-type="taxes" data-nonce="<?php echo esc_attr( wp_create_nonce( 'eac_ajax_import' ) ); ?>">
					<p><?php esc_html_e( 'Import taxes from CSV file.', 'otto-contracts' ); ?></p>
					<div class="eac-form-field">
						<label for="file"><?php esc_html_e( 'Select file', 'otto-contracts' ); ?></label>
						<input type="file" name="file" id="file" accept="text/csv" required>
					</div>
					<?php submit_button( esc_html__( 'Import', 'otto-contracts' ), 'secondary', null, false ); ?>
				</form>
			</div>
		</div>
		<?php
	}

	
	public static function items_import() {
		?>
		<div class="eac-card">
			<div class="eac-card__header">
				<h2 class="eac-card__title"><?php esc_html_e( 'Import Services', 'otto-contracts' ); ?></h2>
			</div>
			<div class="eac-card__body">
				<form method="post" enctype="multipart/form-data" class="eac_importer" data-type="items" data-nonce="<?php echo esc_attr( wp_create_nonce( 'eac_ajax_import' ) ); ?>">
					<p><?php esc_html_e( 'Import services from CSV file.', 'otto-contracts' ); ?></p>
					<div class="eac-form-field">
						<label for="file"><?php esc_html_e( 'Select file', 'otto-contracts' ); ?></label>
						<input type="file" name="file" id="file" accept="text/csv" required>
					</div>
					<?php submit_button( esc_html__( 'Import', 'otto-contracts' ), 'secondary', null, false ); ?>
				</form>
			</div>
		</div>
		<?php
	}

	
	public static function accounts_import() {
		?>
		<div class="eac-card">
			<div class="eac-card__header">
				<h2 class="eac-card__title"><?php esc_html_e( 'Import Accounts', 'otto-contracts' ); ?></h2>
			</div>
			<div class="eac-card__body">
				<form method="post" enctype="multipart/form-data" class="eac_importer" data-type="accounts" data-nonce="<?php echo esc_attr( wp_create_nonce( 'eac_ajax_import' ) ); ?>">
					<p><?php esc_html_e( 'Import accounts from CSV file.', 'otto-contracts' ); ?></p>
					<div class="eac-form-field">
						<label for="file"><?php esc_html_e( 'Select file', 'otto-contracts' ); ?></label>
						<input type="file" name="file" id="file" accept="text/csv" required>
					</div>
					<?php submit_button( esc_html__( 'Import', 'otto-contracts' ), 'secondary', null, false ); ?>
				</form>
			</div>
		</div>
		<?php
	}

	
	public static function transfers_import() {
		?>
		<div class="eac-card">
			<div class="eac-card__header">
				<h2 class="eac-card__title"><?php esc_html_e( 'Import Transfers', 'otto-contracts' ); ?></h2>
			</div>
			<div class="eac-card__body">
				<form method="post" enctype="multipart/form-data" class="eac_importer" data-type="transfers" data-nonce="<?php echo esc_attr( wp_create_nonce( 'eac_ajax_import' ) ); ?>">
					<p><?php esc_html_e( 'Import transfers from CSV file.', 'otto-contracts' ); ?></p>
					<div class="eac-form-field">
						<label for="file"><?php esc_html_e( 'Select file', 'otto-contracts' ); ?></label>
						<input type="file" name="file" id="file" accept="text/csv" required>
					</div>
					<?php submit_button( esc_html__( 'Import', 'otto-contracts' ), 'secondary', null, false ); ?>
				</form>
			</div>
		</div>
		<?php
	}

	
	public static function expenses_import() {
		?>
		<div class="eac-card">
			<div class="eac-card__header">
				<h2 class="eac-card__title"><?php esc_html_e( 'Import Expenses', 'otto-contracts' ); ?></h2>
			</div>
			<div class="eac-card__body">
				<form method="post" enctype="multipart/form-data" class="eac_importer" data-type="expenses" data-nonce="<?php echo esc_attr( wp_create_nonce( 'eac_ajax_import' ) ); ?>">
					<p><?php esc_html_e( 'Import expenses from CSV file.', 'otto-contracts' ); ?></p>
					<div class="eac-form-field">
						<label for="file"><?php esc_html_e( 'Select file', 'otto-contracts' ); ?></label>
						<input type="file" name="file" id="file" accept="text/csv" required>
					</div>
					<?php submit_button( esc_html__( 'Import', 'otto-contracts' ), 'secondary', null, false ); ?>
				</form>
			</div>
		</div>
		<?php
	}

	
	public static function payments_import() {
		?>
		<div class="eac-card">
			<div class="eac-card__header">
				<h2 class="eac-card__title"><?php esc_html_e( 'Import Payments', 'otto-contracts' ); ?></h2>
			</div>
			<div class="eac-card__body">
				<form method="post" enctype="multipart/form-data" class="eac_importer" data-type="payments" data-nonce="<?php echo esc_attr( wp_create_nonce( 'eac_ajax_import' ) ); ?>">
					<p><?php esc_html_e( 'Import payments from CSV file.', 'otto-contracts' ); ?></p>
					<div class="eac-form-field">
						<label for="file"><?php esc_html_e( 'Select file', 'otto-contracts' ); ?></label>
						<input type="file" name="file" id="file" accept="text/csv" required>
					</div>
					<?php submit_button( esc_html__( 'Import', 'otto-contracts' ), 'secondary', null, false ); ?>
				</form>
			</div>
		</div>
		<?php
	}

	
	public static function documents_import() {
		?>
		<div class="eac-card">
			<div class="eac-card__header">
				<h2 class="eac-card__title"><?php esc_html_e( 'Import Documents', 'otto-contracts' ); ?></h2>
			</div>
			<div class="eac-card__body">
				<form method="post" enctype="multipart/form-data" class="eac_importer" data-type="documents" data-nonce="<?php echo esc_attr( wp_create_nonce( 'eac_ajax_import' ) ); ?>">
					<p><?php esc_html_e( 'Import document headers (Contracts/Bills) from CSV.', 'otto-contracts' ); ?></p>
					<div class="eac-form-field">
						<label for="file"><?php esc_html_e( 'Select file', 'otto-contracts' ); ?></label>
						<input type="file" name="file" id="file" accept="text/csv" required>
					</div>
					<?php submit_button( esc_html__( 'Import', 'otto-contracts' ), 'secondary', null, false ); ?>
				</form>
			</div>
		</div>
		<?php
	}

	
	public static function document_items_import() {
		?>
		<div class="eac-card">
			<div class="eac-card__header">
				<h2 class="eac-card__title"><?php esc_html_e( 'Import Document Lines', 'otto-contracts' ); ?></h2>
			</div>
			<div class="eac-card__body">
				<form method="post" enctype="multipart/form-data" class="eac_importer" data-type="document_items" data-nonce="<?php echo esc_attr( wp_create_nonce( 'eac_ajax_import' ) ); ?>">
					<p><?php esc_html_e( 'Import document line items (Contracts/Bills) from CSV.', 'otto-contracts' ); ?></p>
					<div class="eac-form-field">
						<label for="file"><?php esc_html_e( 'Select file', 'otto-contracts' ); ?></label>
						<input type="file" name="file" id="file" accept="text/csv" required>
					</div>
					<?php submit_button( esc_html__( 'Import', 'otto-contracts' ), 'secondary', null, false ); ?>
				</form>
			</div>
		</div>
		<?php
	}

	
	public static function get_importer( $type ) {
		switch ( $type ) {
			case 'customers':
				$importer = Importers\Customers::class;
				break;
			case 'categories':
				$importer = Importers\Categories::class;
				break;
			case 'taxes':
				$importer = Importers\Taxes::class;
				break;
			case 'items':
				$importer = Importers\Items::class;
				break;
			case 'accounts':
				$importer = Importers\Accounts::class;
				break;
			case 'transfers':
				$importer = Importers\Transfers::class;
				break;
			case 'expenses':
				$importer = Importers\Expenses::class;
				break;
			case 'payments':
				$importer = Importers\Payments::class;
				break;
			case 'documents':
				$importer = Importers\Documents::class;
				break;
			case 'document_items':
				$importer = Importers\DocumentItems::class;
				break;
			default:
				
				$importer = apply_filters( "eac_ajax_{$type}_importer", null );
		}

		return $importer;
	}
}
