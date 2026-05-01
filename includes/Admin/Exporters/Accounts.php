<?php


namespace Otto\Admin\Exporters;

use Otto\Models\Account;

defined( 'ABSPATH' ) || exit();



class Accounts extends Exporter {

	
	public $export_type = 'accounts';

	
	public function get_columns() {
		$hidden = array( 'id', 'user_id', 'parent_id', 'created_via' );

		return array_diff( ( new Account() )->get_columns(), $hidden );
	}

	
	public function get_rows() {
		$args = array(
			'orderby' => 'id',
			'order'   => 'ASC',
			'page'    => $this->page,
			'limit'   => $this->limit,
		);

		$args = apply_filters( 'eac_export_accounts_args', $args );

		$items = EAC()->accounts->query( $args );
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
