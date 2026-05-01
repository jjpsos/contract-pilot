<?php

namespace Otto\Admin\ListTables;

use Otto\Models\Expense;
use Otto\Utilities\ReportsUtil;

defined( 'ABSPATH' ) || exit;


class Expenses extends ListTable {
	
	public function __construct( $args = array() ) {
		parent::__construct(
			wp_parse_args(
				$args,
				array(
					'singular' => 'expense',
					'plural'   => 'expenses',
					'screen'   => get_current_screen(),
					'args'     => array(),
				)
			)
		);
		$this->base_url = admin_url( 'admin.php?page=eac-purchases&tab=expenses' );
	}

	
	public function prepare_items() {
		$this->process_actions();
		$per_page    = $this->get_items_per_page( 'eac_expenses_per_page', 20 );
		$paged       = $this->get_pagenum();
		$search      = $this->get_request_search();
		$order_by    = $this->get_request_orderby();
		$order       = $this->get_request_order();
		$account_id  = filter_input( INPUT_GET, 'account_id', FILTER_VALIDATE_INT );
		$category_id = filter_input( INPUT_GET, 'category_id', FILTER_VALIDATE_INT );
		$contact_id  = filter_input( INPUT_GET, 'vendor_id', FILTER_VALIDATE_INT );
		$year_month  = filter_input( INPUT_GET, 'm', FILTER_VALIDATE_INT );
		$args        = array(
			'limit'       => $per_page,
			'page'        => $paged,
			'search'      => $search,
			'orderby'     => $order_by,
			'order'       => $order,
			'status'      => $this->get_request_status(),
			'account_id'  => $account_id,
			'category_id' => $category_id,
			'contact_id'  => $contact_id,
		);

		if ( ! empty( $year_month ) && preg_match( '/^[0-9]{6}$/', $year_month ) ) {
			$year                          = (int) substr( $year_month, 0, 4 );
			$month                         = (int) substr( $year_month, 4, 2 );
			$start                         = get_gmt_from_date( "$year-$month-01 00:00:00" );
			$end                           = get_gmt_from_date( date_create( "$year-$month" )->format( 'Y-m-t 23:59:59' ) );
			$args['payment_date__between'] = array( $start, $end );
		}

		
		$args = apply_filters( 'eac_expenses_table_query_args', $args );

		$this->items = Expense::results( $args );
		$total       = Expense::count( $args );

		$this->set_pagination_args(
			array(
				'total_items' => $total,
				'per_page'    => $per_page,
			)
		);
	}

	
	protected function bulk_delete( $ids ) {
		if ( ! current_user_can( 'eac_delete_expenses' ) ) { 
			EAC()->flash->error( __( 'You do not have permission to delete expenses.', 'otto-contracts' ) );
			return;
		}

		$performed = 0;
		foreach ( $ids as $id ) {
			if ( EAC()->expenses->delete( $id ) ) {
				++$performed;
			}
		}
		if ( ! empty( $performed ) ) {
			
			EAC()->flash->success( sprintf( __( '%s expense(s) deleted successfully.', 'otto-contracts' ), number_format_i18n( $performed ) ) );
		}
	}

	
	public function no_items() {
		esc_html_e( 'No expenses found.', 'otto-contracts' );
	}

	
	protected function get_bulk_actions() {
		$actions = array();

		if ( current_user_can( 'eac_delete_expenses' ) ) { 
			$actions['delete'] = __( 'Delete', 'otto-contracts' );
		}

		return $actions;
	}

	
	protected function extra_tablenav( $which ) {
		global $wpdb;
		static $has_items;
		if ( ! isset( $has_items ) ) {
			$has_items = $this->has_items();
		}
		echo '<div class="alignleft actions">';
		if ( 'top' === $which ) {
			$date_column = ReportsUtil::get_localized_time_sql( 'payment_date' );
			$months      = $wpdb->get_results(
			
				$wpdb->prepare(
					"SELECT DISTINCT YEAR( $date_column ) AS year, MONTH( $date_column ) AS month
					FROM {$wpdb->prefix}otto_transactions
					WHERE type = %s AND payment_date IS NOT NULL
					ORDER BY payment_date DESC",
					'expense'
				)
			
			);

			$this->date_filter( $months );
			$this->contact_filter( 'vendor' );
			$this->account_filter();
			$this->category_filter( 'expense' );
			submit_button( __( 'Filter', 'otto-contracts' ), '', 'filter_action', false );
		}
		echo '</div>';
	}

	
	public function get_columns() {
		return array(
			'cb'         => '<input type="checkbox" />',
			'number'     => __( 'Expense #', 'otto-contracts' ),
			'date'       => __( 'Date', 'otto-contracts' ),
			'account_id' => __( 'Account', 'otto-contracts' ),
			'vendor_id'  => __( 'Vendor', 'otto-contracts' ),
			'bill_id'    => __( 'Bill', 'otto-contracts' ),
			'reference'  => __( 'Reference', 'otto-contracts' ),
			'amount'     => __( 'Amount', 'otto-contracts' ),
		);
	}

	
	protected function get_sortable_columns() {
		return array(
			'date'       => array( 'payment_date', true ),
			'number'     => array( 'number', false ),
			'account_id' => array( 'account_id', false ),
			'bill_id'    => array( 'bill_id', false ),
			'vendor_id'  => array( 'vendor_id', false ),
			'reference'  => array( 'reference', false ),
			'amount'     => array( 'amount', false ),
		);
	}

	
	public function get_primary_column_name() {
		return 'number';
	}

	
	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="id[]" value="%d"/>', esc_attr( $item->id ) );
	}

	
	public function column_number( $item ) {
		return sprintf(
			'<a class="row-title" href="%s">%s</a>',
			esc_url( $item->get_view_url() ),
			wp_kses_post( $item->number )
		);
	}

	
	public function column_date( $item ) {
		return $item->payment_date ? eac_format_datetime( $item->payment_date, eac_date_format() ) : '&mdash;';
	}

	
	public function column_account_id( $item ) {
		$account  = $item->account ? sprintf( '<a href="%s">%s</a>', esc_url( $item->account->get_view_url() ), wp_kses_post( $item->account->name ) ) : '&mdash;';
		$metadata = $item->account && $item->account->number ? ucfirst( $item->account->number ) : '';

		return sprintf( '%s%s', $account, $this->column_metadata( $metadata ) );
	}

	
	public function column_bill_id( $item ) {
		$bill     = '&mdash;';
		$metadata = '';
		if ( $item->bill ) {
			$bill = sprintf( '<a href="%s">%s</a>', esc_url( $item->bill->get_view_url() ), wp_kses_post( $item->bill->number ) );
		}

		return sprintf( '%s', empty( $this->column_metadata( $metadata ) ) ? $bill : $this->column_metadata( $metadata ) );
	}

	
	public function column_vendor_id( $item ) {
		$customer = $item->vendor ? sprintf( '<a href="%s">%s</a>', esc_url( $item->vendor->get_view_url() ), wp_kses_post( $item->vendor->name ) ) : '&mdash;';
		$metadata = $item->vendor && $item->vendor->company ? $item->vendor->company : '';

		return sprintf( '%s%s', $customer, $this->column_metadata( $metadata ) );
	}

	
	public function column_amount( $item ) {
		return esc_html( $item->formatted_amount );
	}

	
	protected function handle_row_actions( $item, $column_name, $primary ) {
		if ( $primary !== $column_name ) {
			return null;
		}
		$actions = array(
			'edit'   => sprintf(
				'<a href="%s">%s</a>',
				esc_url( $item->get_edit_url() ),
				__( 'Edit', 'otto-contracts' )
			),
			'delete' => sprintf(
				'<a href="%s" class="del del_confirm">%s</a>',
				esc_url(
					wp_nonce_url(
						add_query_arg(
							array(
								'action' => 'delete',
								'id'     => $item->id,
							),
							$this->base_url
						),
						'bulk-' . $this->_args['plural']
					)
				),
				__( 'Delete', 'otto-contracts' )
			),
		);

		if ( ! current_user_can( 'eac_delete_expenses' ) ) { 
			unset( $actions['delete'] );
		}

		if ( ! $item->editable || ! current_user_can( 'eac_edit_expenses' ) ) { 
			unset( $actions['edit'] );
		}

		return $this->row_actions( $actions );
	}
}
