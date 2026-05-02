<?php


namespace Otto\Admin\Importers;


class Transfers extends Importer {
	
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
				'transfer_date',
				'date_created',
				'date_updated',
			)
		);
		$this->normalize_transaction_import_row(
			$data,
			array( 'expense_id', 'payment_id', 'from_account_id', 'to_account_id' )
		);

		return EAC()->transfers->insert( $data );
	}
}
