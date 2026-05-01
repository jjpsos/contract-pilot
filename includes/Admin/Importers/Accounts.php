<?php


namespace Otto\Admin\Importers;


class Accounts extends Importer {
	
	public function import_item( $data ) {
		$protected = array(
			'id',
			'date_updated',
		);

		$data  = array_diff_key( $data, array_flip( $protected ) );
		$dates = array(
			'date_created',
			'date_updated',
		);

		foreach ( $dates as $date ) {
			if ( isset( $data[ $date ] ) && ! empty( $data[ $date ] ) ) {
				$data[ $date ] = get_gmt_from_date( $data[ $date ] );
			}
		}

		return EAC()->accounts->insert( $data );
	}
}
