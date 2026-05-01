<?php

namespace Otto\Controllers;

use Otto\Models\Bill;

defined( 'ABSPATH' ) || exit;


class Bills {

	
	public function get( $bill ) {
		return Bill::find( $bill );
	}

	

	
	public function insert( $data, $wp_error = true ) {
		return Bill::insert( $data, $wp_error );
	}

	
	public function delete( $id ) {
		$bill = $this->get( $id );
		if ( ! $bill ) {
			return false;
		}

		return $bill->delete();
	}

	
	public function query( $args = array(), $count = false ) {
		if ( $count ) {
			return Bill::count( $args );
		}

		return Bill::results( $args );
	}

	
	public function get_statuses() {
		$statuses = array(
			'draft'     => esc_html__( 'Draft', 'otto-contracts' ),
			'received'  => esc_html__( 'Received', 'otto-contracts' ),
			'partial'   => esc_html__( 'Partial', 'otto-contracts' ),
			'paid'      => esc_html__( 'Paid', 'otto-contracts' ),
			'overdue'   => esc_html__( 'Overdue', 'otto-contracts' ),
			'cancelled' => esc_html__( 'Cancelled', 'otto-contracts' ),
		);

		return apply_filters( 'eac_bill_statuses', $statuses );
	}

	
	public function get_columns() {
		$columns = array(
			'item'     => get_option( 'eac_bill_col_item_label', esc_html__( 'Service', 'otto-contracts' ) ),
			'quantity' => get_option( 'eac_bill_col_quantity_label', esc_html__( 'Quantity', 'otto-contracts' ) ),
			'price'    => get_option( 'eac_bill_col_price_label', esc_html__( 'Price', 'otto-contracts' ) ),
			'tax'      => get_option( 'eac_bill_col_tax_label', esc_html__( 'Tax', 'otto-contracts' ) ),
			'subtotal' => get_option( 'eac_bill_col_subtotal_label', esc_html__( 'Subtotal', 'otto-contracts' ) ),
		);

		return apply_filters( 'eac_bill_columns', $columns );
	}
}
