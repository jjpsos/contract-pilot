<?php

namespace Otto\Admin;

defined( 'ABSPATH' ) || exit;


class Importers {

	
	public function __construct() {
		add_filter( 'eac_tools_page_tabs', array( __CLASS__, 'register_tabs' ), - 1 );
		add_action( 'eac_tools_page_import_content', array( __CLASS__, 'customers_import' ) );
		add_action( 'eac_tools_page_import_content', array( __CLASS__, 'vendors_import' ) );
		add_action( 'eac_tools_page_import_content', array( __CLASS__, 'categories_import' ) );
		add_action( 'eac_tools_page_import_content', array( __CLASS__, 'taxes_import' ) );
		add_action( 'eac_tools_page_import_content', array( __CLASS__, 'items_import' ) );
		add_action( 'eac_tools_page_import_content', array( __CLASS__, 'accounts_import' ) );
		add_action( 'eac_tools_page_import_content', array( __CLASS__, 'transfers_import' ) );
		add_action( 'eac_tools_page_import_content', array( __CLASS__, 'payments_import' ) );
		add_action( 'eac_tools_page_import_content', array( __CLASS__, 'expenses_import' ) );
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
				<h2 class="eac-card__title"><?php esc_html_e( 'Import Customers', 'otto-contracts' ); ?></h2>
			</div>
			<div class="eac-card__body">
				<form method="post" enctype="multipart/form-data" class="eac_importer" data-type="customers" data-nonce="<?php echo esc_attr( wp_create_nonce( 'eac_ajax_import' ) ); ?>">
					<p>
						<?php
						printf(
							
							esc_html__( 'Import customers from CSV file. Download a %1$s sample file %2$s to learn how to format the CSV file.', 'otto-contracts' ),
							'<a href="' . esc_url( EAC()->get_dir_url( 'samples/import/customers.csv' ) ) . '" download>',
							'</a>'
						);
						?>
					</p>
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
					<p>
						<?php
						printf(
						
							esc_html__( 'Import categories from CSV file. Download a %1$s sample file %2$s to learn how to format the CSV file.', 'otto-contracts' ),
							'<a href="' . esc_url( EAC()->get_dir_url( 'samples/import/categories.csv' ) ) . '" download>',
							'</a>'
						);
						?>
					</p>
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

	
	public static function vendors_import() {
		?>
		<div class="eac-card">
			<div class="eac-card__header">
				<h2 class="eac-card__title"><?php esc_html_e( 'Import Vendors', 'otto-contracts' ); ?></h2>
			</div>
			<div class="eac-card__body">
				<form method="post" enctype="multipart/form-data" class="eac_importer" data-type="vendors" data-nonce="<?php echo esc_attr( wp_create_nonce( 'eac_ajax_import' ) ); ?>">
					<p>
						<?php
						printf(
						
							esc_html__( 'Import vendors from CSV file. Download a %1$s sample file %2$s to learn how to format the CSV file.', 'otto-contracts' ),
							'<a href="' . esc_url( EAC()->get_dir_url( 'samples/import/vendors.csv' ) ) . '" download>',
							'</a>'
						);
						?>
					</p>
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
					<p>
						<?php
						printf(
						
							esc_html__( 'Import taxes from CSV file. Download a %1$s sample file %2$s to learn how to format the CSV file.', 'otto-contracts' ),
							'<a href="' . esc_url( EAC()->get_dir_url( 'samples/import/taxes.csv' ) ) . '" download>',
							'</a>'
						);
						?>
					</p>
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
					<p>
						<?php
						printf(
						
							esc_html__( 'Import services from CSV file. Download a %1$s sample file %2$s to learn how to format the CSV file.', 'otto-contracts' ),
							'<a href="' . esc_url( EAC()->get_dir_url( 'samples/import/items.csv' ) ) . '" download>',
							'</a>'
						);
						?>
					</p>
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
					<p>
						<?php
						printf(
						
							esc_html__( 'Import accounts from CSV file. Download a %1$s sample file %2$s to learn how to format the CSV file.', 'otto-contracts' ),
							'<a href="' . esc_url( EAC()->get_dir_url( 'samples/import/accounts.csv' ) ) . '" download>',
							'</a>'
						);
						?>
					</p>
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
					<p>
						<?php
						printf(
						
							esc_html__( 'Import transfers from CSV file. Download a %1$s sample file %2$s to learn how to format the CSV file.', 'otto-contracts' ),
							'<a href="' . esc_url( EAC()->get_dir_url( 'samples/import/transfers.csv' ) ) . '" download>',
							'</a>'
						);
						?>
					</p>
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
					<p>
						<?php
						printf(
						
							esc_html__( 'Import expenses from CSV file. Download a %1$s sample file %2$s to learn how to format the CSV file.', 'otto-contracts' ),
							'<a href="' . esc_url( EAC()->get_dir_url( 'samples/import/expenses.csv' ) ) . '" download>',
							'</a>'
						);
						?>
					</p>
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
					<p>
						<?php
						printf(
						
							esc_html__( 'Import payments from CSV file. Download a %1$s sample file %2$s to learn how to format the CSV file.', 'otto-contracts' ),
							'<a href="' . esc_url( EAC()->get_dir_url( 'samples/import/payments.csv' ) ) . '" download>',
							'</a>'
						);
						?>
					</p>
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
			case 'vendors':
				$importer = Importers\Vendors::class;
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
			default:
				
				$importer = apply_filters( "eac_ajax_{$type}_importer", null );
		}

		return $importer;
	}
}
