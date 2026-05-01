<?php

namespace Otto\Admin;

use Otto\Models\Vendor;
use Otto\Utilities\ReportsUtil;

defined( 'ABSPATH' ) || exit;


class Vendors {
	
	public function __construct() {
		add_filter( 'eac_purchases_page_tabs', array( __CLASS__, 'register_tabs' ) );
		add_action( 'admin_post_eac_edit_vendor', array( __CLASS__, 'handle_edit' ) );
		add_action( 'eac_purchases_page_vendors_loaded', array( __CLASS__, 'page_loaded' ) );
		add_action( 'eac_purchases_page_vendors_content', array( __CLASS__, 'page_content' ) );
		add_action( 'eac_vendor_profile_section_overview', array( __CLASS__, 'overview_section' ) );
		add_action( 'eac_vendor_profile_section_expenses', array( __CLASS__, 'expenses_section' ) );
		add_action( 'eac_vendor_profile_section_bills', array( __CLASS__, 'bills_section' ) );
		add_action( 'eac_vendor_profile_section_notes', array( __CLASS__, 'notes_section' ) );
	}

	
	public static function register_tabs( $tabs ) {
		return $tabs;
	}

	
	public static function handle_edit() {
		check_admin_referer( 'eac_edit_vendor' );
		if ( ! current_user_can( 'eac_edit_vendors' ) ) { 
			wp_die( esc_html__( 'You do not have permission to edit vendors.', 'otto-contracts' ) );
		}
		$referer = wp_get_referer();
		$data    = array(
			'id'         => isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '',
			'name'       => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
			'company'    => isset( $_POST['company'] ) ? sanitize_text_field( wp_unslash( $_POST['company'] ) ) : '',
			'email'      => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
			'phone'      => isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '',
			'website'    => isset( $_POST['website'] ) ? esc_url_raw( wp_unslash( $_POST['website'] ) ) : '',
			'address'    => isset( $_POST['address'] ) ? sanitize_text_field( wp_unslash( $_POST['address'] ) ) : '',
			'city'       => isset( $_POST['city'] ) ? sanitize_text_field( wp_unslash( $_POST['city'] ) ) : '',
			'state'      => isset( $_POST['state'] ) ? sanitize_text_field( wp_unslash( $_POST['state'] ) ) : '',
			'postcode'   => isset( $_POST['postcode'] ) ? sanitize_text_field( wp_unslash( $_POST['postcode'] ) ) : '',
			'country'    => isset( $_POST['country'] ) ? sanitize_text_field( wp_unslash( $_POST['country'] ) ) : '',
			'tax_number' => isset( $_POST['tax_number'] ) ? sanitize_text_field( wp_unslash( $_POST['tax_number'] ) ) : '',
			'currency'   => isset( $_POST['currency'] ) ? sanitize_text_field( wp_unslash( $_POST['currency'] ) ) : '',
		);

		$vendor = EAC()->vendors->insert( $data );

		if ( is_wp_error( $vendor ) ) {
			EAC()->flash->error( $vendor->get_error_message() );
		} else {
			EAC()->flash->success( __( 'Vendor saved successfully.', 'otto-contracts' ) );
			$referer = add_query_arg( 'id', $vendor->id, $referer );
			$referer = add_query_arg( 'action', 'view', $referer );
			$referer = remove_query_arg( array( 'add' ), $referer );
		}

		wp_safe_redirect( $referer );
		exit;
	}

	
	public static function page_loaded( $action ) {
		global $eac_list_table;
		switch ( $action ) {
			case 'add':
				if ( ! current_user_can( 'eac_edit_vendors' ) ) { 
					wp_die( esc_html__( 'You do not have permission to add vendors.', 'otto-contracts' ) );
				}
				break;

			case 'view':
			case 'edit':
				$id = filter_input( INPUT_GET, 'id', FILTER_VALIDATE_INT );
				if ( ! EAC()->vendors->get( $id ) ) {
					wp_die( esc_html__( 'You attempted to retrieve a vendor that does not exist. Perhaps it was deleted?', 'otto-contracts' ) );
				}
				if ( 'edit' === $action && ! current_user_can( 'eac_edit_vendors' ) ) { 
					wp_die( esc_html__( 'You do not have permission to edit vendors.', 'otto-contracts' ) );
				}
				break;

			default:
				$screen         = get_current_screen();
				$eac_list_table = new ListTables\Vendors();
				$eac_list_table->prepare_items();
				$screen->add_option(
					'per_page',
					array(
						'label'   => __( 'Number of items per page:', 'otto-contracts' ),
						'default' => 20,
						'option'  => 'eac_vendors_per_page',
					)
				);
				break;
		}
	}

	
	public static function page_content( $action ) {
		switch ( $action ) {
			case 'add':
			case 'edit':
				include __DIR__ . '/views/vendor-edit.php';
				break;

			case 'view':
				include __DIR__ . '/views/vendor-view.php';
				break;

			default:
				include __DIR__ . '/views/vendor-list.php';
				break;
		}
	}

	
	public static function overview_section( $vendor ) {
		global $wpdb;
		wp_enqueue_script( 'eac-chartjs' );
		$year_start_date = ReportsUtil::get_year_start_date();
		$year_end_date   = ReportsUtil::get_year_end_date();
		$date_column     = ReportsUtil::get_localized_time_sql( 't.payment_date' );
		
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT SUM(t.amount/t.exchange_rate) AS amount,
		        MONTH($date_column) AS month,
		        YEAR($date_column) AS year
		 		FROM {$wpdb->prefix}otto_transactions AS t
		        WHERE t.contact_id = %d
		   		AND t.type = 'expense'
		   		AND t.payment_date BETWEEN %s AND %s
		 		GROUP BY YEAR($date_column), MONTH($date_column)
		 		ORDER BY YEAR($date_column), MONTH($date_column)",
				$vendor->id,
				get_gmt_from_date( $year_start_date ),
				get_gmt_from_date( $year_end_date )
			)
		);
		

		$bill = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(total/exchange_rate) as total FROM {$wpdb->prefix}otto_documents WHERE contact_id = %d AND contact_id !='' AND type='bill' AND status != 'draft'",
				$vendor->id
			)
		);

		$paid = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(amount/exchange_rate) as total FROM {$wpdb->prefix}otto_transactions WHERE contact_id = %d AND contact_id != '' AND type='expense'",
				$vendor->id
			)
		);

		$due        = empty( $bill ) ? 0 : max( $bill - $paid, 0 );
		$chart_data = ReportsUtil::annualize_data( $results );
		$chart      = array(
			'type'     => 'line',
			'labels'   => array_keys( $chart_data ),
			'datasets' => array(
				array(
					'label'           => __( 'Payments', 'otto-contracts' ),
					'backgroundColor' => '#3644ff',
					'borderColor'     => '#3644ff',
					'fill'            => false,
					'data'            => array_values( $chart_data ),
				),
			),
		);
		$attributes = array(
			array(
				'label' => __( 'Name', 'otto-contracts' ),
				'value' => $vendor->name,
			),
			array(
				'label' => __( 'Company', 'otto-contracts' ),
				'value' => $vendor->company,
			),
			array(
				'label' => __( 'Email', 'otto-contracts' ),
				'value' => $vendor->email,
			),
			array(
				'label' => __( 'Phone', 'otto-contracts' ),
				'value' => $vendor->phone,
			),
			array(
				'label' => __( 'Website', 'otto-contracts' ),
				'value' => $vendor->website,
			),
			array(
				'label' => __( 'Address', 'otto-contracts' ),
				'value' => $vendor->address,
			),
			array(
				'label' => __( 'City', 'otto-contracts' ),
				'value' => $vendor->city,
			),
			array(
				'label' => __( 'State', 'otto-contracts' ),
				'value' => $vendor->state,
			),
			array(
				'label' => __( 'Postcode', 'otto-contracts' ),
				'value' => $vendor->postcode,
			),
			array(
				'label' => __( 'Country', 'otto-contracts' ),
				'value' => $vendor->country_name,
			),
			array(
				'label' => __( 'Tax Number', 'otto-contracts' ),
				'value' => $vendor->tax_number,
			),
			array(
				'label' => __( 'Currency', 'otto-contracts' ),
				'value' => $vendor->currency,
			),
			array(
				'label' => __( 'Created', 'otto-contracts' ),
				'value' => $vendor->date_created ? eac_format_datetime( $vendor->date_created, eac_date_format() ) : '&mdash;',
			),
			array(
				'label' => __( 'Updated', 'otto-contracts' ),
				'value' => $vendor->date_updated ? eac_format_datetime( $vendor->date_updated, eac_date_format() ) : '&mdash;',
			),
		);
		?>

		<h2 class="has--border"><?php esc_html_e( 'Overview', 'otto-contracts' ); ?></h2>

		<div class="eac-chart">
			<canvas class="eac-chart" id="eac-customer-chart" style="height: 300px;margin-bottom: 20px;" data-datasets="<?php echo esc_attr( wp_json_encode( $chart ) ); ?>" data-currency="<?php echo esc_attr( EAC()->currencies->get_symbol( eac_base_currency() ) ); ?>"></canvas>
		</div>
		<div class="eac-stats stats--2">
			<div class="eac-stat">
				<div class="eac-stat__label"><?php esc_html_e( 'Due', 'otto-contracts' ); ?></div>
				<div class="eac-stat__value"><?php echo esc_html( eac_format_amount( $due ) ); ?></div>
			</div>
			<div class="eac-stat">
				<div class="eac-stat__label"><?php esc_html_e( 'Paid', 'otto-contracts' ); ?></div>
				<div class="eac-stat__value"><?php echo esc_html( eac_format_amount( $paid ) ); ?></div>
			</div>
		</div>

		<h2><?php esc_html_e( 'Details', 'otto-contracts' ); ?></h2>
		<table class="eac-table is--striped is--bordered">
			<tbody>
			<?php foreach ( $attributes as $attribute ) : ?>
				<tr>
					<th><?php echo esc_html( $attribute['label'] ); ?></th>
					<td><?php echo esc_html( empty( $attribute['value'] ) ? '&mdash;' : $attribute['value'] ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	
	public static function expenses_section( $vendor ) {
		$expenses = EAC()->expenses->query(
			array(
				'contact_id' => $vendor->id,
				'limit'      => 20,
				'orderby'    => 'payment_date',
				'order'      => 'DESC',
			)
		);
		?>
		<h2 class="has--border"><?php esc_html_e( 'Recent Expenses', 'otto-contracts' ); ?></h2>
		<table class="widefat fixed striped">
			<thead>
			<tr>
				<th><?php esc_html_e( 'Number', 'otto-contracts' ); ?></th>
				<th><?php esc_html_e( 'Date', 'otto-contracts' ); ?></th>
				<th><?php esc_html_e( 'Reference', 'otto-contracts' ); ?></th>
				<th><?php esc_html_e( 'Amount', 'otto-contracts' ); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php if ( $expenses ) : ?>
				<?php foreach ( $expenses as $expense ) : ?>
					<tr>
						<td>
							<a href="<?php echo esc_url( $expense->get_view_url() ); ?>">
								<?php echo esc_html( $expense->number ); ?>
							</a>
						</td>
						<td><?php echo esc_html( wp_date( eac_date_format(), strtotime( $expense->payment_date ) ) ); ?></td>
						<td><?php echo esc_html( $expense->reference ? $expense->reference : '-' ); ?></td>
						<td><?php echo esc_html( $expense->formatted_amount ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr>
					<td colspan="4"><?php esc_html_e( 'No expenses found.', 'otto-contracts' ); ?></td>
				</tr>
			<?php endif; ?>
			</tbody>
		</table>
		<?php
	}

	
	public static function bills_section( $vendor ) {
		$bills = EAC()->bills->query(
			array(
				'contact_id' => $vendor->id,
				'orderby'    => 'date_created',
				'order'      => 'DESC',
				'limit'      => 20,
			)
		);
		?>
		<h2 class="has--border"><?php esc_html_e( 'Recent Bills', 'otto-contracts' ); ?></h2>
		<table class="widefat fixed striped">
			<thead>
			<tr>
				<th><?php esc_html_e( 'Number', 'otto-contracts' ); ?></th>
				<th><?php esc_html_e( 'Date', 'otto-contracts' ); ?></th>
				<th><?php esc_html_e( 'Amount', 'otto-contracts' ); ?></th>
				<th><?php esc_html_e( 'Status', 'otto-contracts' ); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php if ( $bills ) : ?>
				<?php foreach ( $bills as $bill ) : ?>
					<tr>
						<td>
							<a href="<?php echo esc_url( $bill->get_view_url() ); ?>">
								<?php echo esc_html( $bill->number ); ?>
							</a>
						</td>
						<td><?php echo esc_html( wp_date( eac_date_format(), strtotime( $bill->issue_date ) ) ); ?></td>
						<td><?php echo esc_html( $bill->formatted_total ); ?></td>
						<td><?php echo esc_html( $bill->status_label ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr>
					<td colspan="4"><?php esc_html_e( 'No bills found.', 'otto-contracts' ); ?></td>
				</tr>
			<?php endif; ?>
		</table>
		<?php
	}

	
	public static function notes_section( $vendor ) {
		$notes = EAC()->notes->query(
			array(
				'parent_id'   => $vendor->id,
				'parent_type' => 'vendor',
				'orderby'     => 'date_created',
				'order'       => 'DESC',
				'limit'       => 20,
			)
		);
		?>
		<h2 class="has--border"><?php esc_html_e( 'Notes', 'otto-contracts' ); ?></h2>

		<?php if ( current_user_can( 'eac_edit_notes' ) ) : ?>
			<div class="eac-form-field">
				<label for="eac-note"><?php esc_html_e( 'Add Note', 'otto-contracts' ); ?></label>
				<textarea id="eac-note" cols="30" rows="2" placeholder="<?php esc_attr_e( 'Enter Note', 'otto-contracts' ); ?>"></textarea>
			</div>
			<button id="eac-add-note" type="button" class="button tw-mb-[20px]" data-parent_id="<?php echo esc_attr( $vendor->id ); ?>" data-parent_type="vendor" data-nonce="<?php echo esc_attr( wp_create_nonce( 'eac_add_note' ) ); ?>">
				<?php esc_html_e( 'Add Note', 'otto-contracts' ); ?>
			</button>
		<?php endif; ?>

		<?php include __DIR__ . '/views/note-list.php'; ?>
		<?php
	}
}
