<?php


namespace Otto\Admin\Exporters;

use Otto\Models\Expense;

defined( 'ABSPATH' ) || exit();



class Expenses extends Exporter {

	
	public $export_type = 'expenses';

	
	public function get_columns() {
		$hidden = array( 'id', 'type', 'uuid', 'document_id', 'author_id', 'user_id', 'parent_id', 'created_via' );

		return array_diff( ( new Expense() )->get_columns(), $hidden );
	}

	
	public function get_rows() {
		$args = array(
			'orderby' => 'id',
			'order'   => 'ASC',
			'page'    => $this->page,
			'limit'   => $this->limit,
		);

		$args = apply_filters( 'eac_export_expenses_args', $args );

		$items       = EAC()->expenses->query( $args );
		$this->total = EAC()->expenses->query( $args, true );
		$rows        = array();

		foreach ( $items as $item ) {
			$row = array();
			foreach ( $this->get_columns() as $column ) {
				switch ( $column ) {
					default:
						$value = isset( $item->{$column} ) ? $item->{$column} : null;
				}

				$row[ $column ] = $value;
			}
			if ( ! empty( $row ) ) {
				$dates = array(
					'payment_date',
					'date_created',
					'date_updated',
				);

				foreach ( $dates as $date ) {
					if ( isset( $row[ $date ] ) && ! empty( $row[ $date ] ) ) {
						$row[ $date ] = eac_format_datetime( $row[ $date ] );
					}
				}

				$rows[] = $row;
			}
		}

		return $rows;
	}
}
