<?php


namespace Otto\Admin\Importers;


class Expenses extends Importer {
	
	public function import_item( $data ) {
		$protected = array(
			'id',
			'type',
			'date_updated',
		);

		$data = array_diff_key( $data, array_flip( $protected ) );

		$this->normalize_import_datetime_fields(
			$data,
			array(
				'payment_date',
				'date_created',
				'date_updated',
			)
		);
		$this->normalize_transaction_import_row( $data );

		return EAC()->expenses->insert( $data );
	}
}
