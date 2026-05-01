<?php


namespace Otto\Admin\Exporters;

use Otto\Models\Tax;

defined( 'ABSPATH' ) || exit();



class Taxes extends Exporter {

	
	public $export_type = 'taxes';

	
	public function get_columns() {
		$hidden    = array( 'id', 'description', 'type', 'taxonomy', 'parent_id', 'date_created', 'date_updated' );
		$columns   = array_diff( ( new Tax() )->get_columns(), $hidden );
		$columns[] = 'rate';
		$columns[] = 'compound';
		$columns[] = 'date_created';
		$columns[] = 'date_updated';

		return $columns;
	}

	
	public function get_rows() {
		$args = array(
			'orderby' => 'id',
			'order'   => 'ASC',
			'page'    => $this->page,
			'limit'   => $this->limit,
		);

		$args = apply_filters( 'eac_export_taxes_args', $args );

		$items = EAC()->taxes->query( $args );
		$rows  = array();

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
