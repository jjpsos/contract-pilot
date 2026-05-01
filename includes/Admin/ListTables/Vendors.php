<?php

namespace Otto\Admin\ListTables;

use Otto\Models\Vendor;

defined( 'ABSPATH' ) || exit;


class Vendors extends ListTable {
	
	public function __construct( $args = array() ) {
		parent::__construct(
			wp_parse_args(
				$args,
				array(
					'singular' => 'vendor',
					'plural'   => 'vendors',
					'screen'   => get_current_screen(),
					'args'     => array(),
				)
			)
		);
		$this->base_url = admin_url( 'admin.php?page=eac-purchases&tab=vendors' );
	}

	
	public function prepare_items() {
		$this->process_actions();
		$per_page = $this->get_items_per_page( 'eac_vendors_per_page', 20 );
		$paged    = $this->get_pagenum();
		$search   = $this->get_request_search();
		$order_by = $this->get_request_orderby();
		$order    = $this->get_request_order();
		$args     = array(
			'limit'   => $per_page,
			'page'    => $paged,
			'search'  => $search,
			'orderby' => $order_by,
			'order'   => $order,
		);
		
		$args        = apply_filters( 'eac_vendors_table_query_args', $args );
		$this->items = EAC()->vendors->query( $args );
		$total       = EAC()->vendors->query( $args, true );

		$this->set_pagination_args(
			array(
				'total_items' => $total,
				'per_page'    => $per_page,
			)
		);
	}

	
	protected function bulk_delete( $ids ) {
		if ( ! current_user_can( 'eac_delete_vendors' ) ) { 
			EAC()->flash->error( __( 'You do not have permission to delete vendors.', 'otto-contracts' ) );
			return;
		}

		$performed = 0;
		foreach ( $ids as $id ) {
			if ( EAC()->vendors->delete( $id ) ) {
				++$performed;
			}
		}
		if ( ! empty( $performed ) ) {
			
			EAC()->flash->success( sprintf( __( '%s vendor(s) deleted successfully.', 'otto-contracts' ), number_format_i18n( $performed ) ) );
		}
	}

	
	public function no_items() {
		esc_html_e( 'No vendors found.', 'otto-contracts' );
	}

	
	protected function get_bulk_actions() {
		$actions = array();

		if ( current_user_can( 'eac_delete_vendors' ) ) { 
			$actions['delete'] = __( 'Delete', 'otto-contracts' );
		}

		return $actions;
	}

	
	protected function extra_tablenav( $which ) {
		static $has_items;
		if ( ! isset( $has_items ) ) {
			$has_items = $this->has_items();
		}

		if ( 'top' === $which ) {
			ob_start();
			$this->country_filter( 'active' );
			$output = ob_get_clean();
			if ( ! empty( $output ) && $this->has_items() ) {
				echo $output; 
				submit_button( __( 'Filter', 'otto-contracts' ), 'alignleft', 'filter_action', false );
			}
		}
	}

	
	public function get_columns() {
		return array(
			'cb'           => '<input type="checkbox" />',
			'name'         => __( 'Name', 'otto-contracts' ),
			'email'        => __( 'Email', 'otto-contracts' ),
			'phone'        => __( 'Phone', 'otto-contracts' ),
			'country'      => __( 'Country', 'otto-contracts' ),
			'date_created' => __( 'Date', 'otto-contracts' ),
		);
	}

	
	protected function get_sortable_columns() {
		return array(
			'name'         => array( 'name', false ),
			'email'        => array( 'email', false ),
			'phone'        => array( 'phone', false ),
			'country'      => array( 'country', false ),
			'due'          => array( 'due', false ),
			'date_created' => array( 'date_created', false ),
		);
	}

	
	public function get_primary_column_name() {
		return 'name';
	}

	
	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="id[]" value="%d"/>', esc_attr( $item->id ) );
	}

	
	public function column_name( $item ) {
		return sprintf(
			'<a class="row-title" href="%s">%s</a>',
			esc_url( $item->get_view_url() ),
			wp_kses_post( $item->name )
		);
	}

	
	public function column_country( $item ) {
		return $item->country_name ? esc_html( $item->country_name ) : '&mdash;';
	}

	
	public function column_date_created( $item ) {
		return $item->date_created ? eac_format_datetime( $item->date_created, eac_date_format() ) : '&mdash;';
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
				'<a href="%s" class="del">%s</a>',
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

		if ( ! current_user_can( 'eac_delete_vendors' ) ) { 
			unset( $actions['delete'] );
		}

		if ( ! current_user_can( 'eac_edit_vendors' ) ) { 
			unset( $actions['edit'] );
		}

		return $this->row_actions( $actions );
	}
}
