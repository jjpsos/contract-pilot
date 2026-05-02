<?php


namespace Otto\Admin\Importers;

use Otto\Models\Document;

class Payments extends Importer {

	/**
	 * Set document_id from CSV: prefer document_number + document_type when they resolve,
	 * otherwise keep a valid document_id / invoice_id, or clear invalid foreign keys.
	 *
	 * @param array $data Import row (by reference).
	 */
	protected function resolve_payment_document_from_import_row( array &$data ) {
		$doc_number = isset( $data['document_number'] ) ? trim( wp_unslash( (string) $data['document_number'] ) ) : '';
		$doc_type   = isset( $data['document_type'] ) ? strtolower( trim( wp_unslash( (string) $data['document_type'] ) ) ) : '';
		unset( $data['document_number'], $data['document_type'] );

		if ( '' !== $doc_number && '' !== $doc_type ) {
			$doc_type = sanitize_key( $doc_type );
			$found    = Document::results(
				array(
					'type'   => $doc_type,
					'number' => $doc_number,
					'limit'  => 1,
				)
			);
			if ( ! empty( $found ) ) {
				$data['document_id'] = (int) $found[0]->id;
				unset( $data['invoice_id'] );

				return;
			}
		}

		foreach ( array( 'document_id', 'invoice_id' ) as $key ) {
			if ( ! isset( $data[ $key ] ) || '' === $data[ $key ] || null === $data[ $key ] ) {
				continue;
			}
			$id = absint( $data[ $key ] );
			if ( $id && Document::find( $id ) ) {
				$data['document_id'] = $id;
				unset( $data['invoice_id'] );

				return;
			}
			unset( $data[ $key ] );
		}
	}

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
		$this->resolve_payment_document_from_import_row( $data );
		$this->normalize_transaction_import_row( $data );

		return EAC()->payments->insert( $data );
	}
}
