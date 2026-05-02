<?php


namespace Otto\Admin\Importers;

use Otto\Models\Contact;
use Otto\Models\Customer;
use Otto\Models\Document;
use Otto\Models\Vendor;

/**
 * CSV import for {@see Document} rows (all document types; dispatches invoice/bill to their controllers).
 */
class Documents extends Importer {

	/**
	 * @return string[]
	 */
	protected function get_allowed_document_types() {
		$defaults = array( 'invoice', 'bill', 'receipt', 'contract' );

		return apply_filters( 'eac_import_documents_allowed_types', $defaults );
	}

	protected function resolve_document_contact( $type, array &$data ) {
		$email = isset( $data['contact_email'] ) ? sanitize_email( trim( (string) $data['contact_email'] ) ) : '';
		$cid   = isset( $data['contact_id'] ) ? absint( $data['contact_id'] ) : 0;

		if ( 'invoice' === $type ) {
			if ( $cid && Customer::find( $cid ) ) {
				return;
			}
			if ( '' !== $email ) {
				$found = Customer::results( array( 'email' => $email, 'limit' => 1 ) );
				if ( ! empty( $found ) ) {
					$data['contact_id'] = (int) $found[0]->id;

					return;
				}
			}
			unset( $data['contact_id'] );

			return;
		}

		if ( 'bill' === $type ) {
			if ( $cid && Vendor::find( $cid ) ) {
				return;
			}
			if ( '' !== $email ) {
				$found = Vendor::results( array( 'email' => $email, 'limit' => 1 ) );
				if ( ! empty( $found ) ) {
					$data['contact_id'] = (int) $found[0]->id;

					return;
				}
			}
			unset( $data['contact_id'] );

			return;
		}

		if ( $cid && Contact::find( $cid ) ) {
			return;
		}
		if ( '' !== $email ) {
			foreach ( array( Customer::class, Vendor::class ) as $class ) {
				$found = $class::results( array( 'email' => $email, 'limit' => 1 ) );
				if ( ! empty( $found ) ) {
					$data['contact_id'] = (int) $found[0]->id;

					return;
				}
			}
		}
		unset( $data['contact_id'] );
	}

	protected function resolve_document_parent( array &$data ) {
		$pnum = isset( $data['parent_document_number'] ) ? trim( wp_unslash( (string) $data['parent_document_number'] ) ) : '';
		$ptyp = isset( $data['parent_document_type'] ) ? sanitize_key( trim( wp_unslash( (string) $data['parent_document_type'] ) ) ) : '';
		unset( $data['parent_document_number'], $data['parent_document_type'] );

		if ( '' === $pnum || '' === $ptyp ) {
			return;
		}

		$found = Document::results(
			array(
				'type'   => $ptyp,
				'number' => $pnum,
				'limit'  => 1,
			)
		);
		if ( ! empty( $found ) ) {
			$data['parent_id'] = (int) $found[0]->id;
		}
	}

	protected function resolve_document_number_conflict( $type, array &$data ) {
		if ( empty( $data['number'] ) || '' === $type ) {
			return;
		}
		$dup = Document::results(
			array(
				'type'   => $type,
				'number' => (string) $data['number'],
				'limit'  => 1,
			)
		);
		if ( ! empty( $dup ) ) {
			unset( $data['number'] );
		}
	}

	/**
	 * @param string $type Document type.
	 * @param array  $data Row without key type.
	 * @return int|\WP_Error
	 */
	protected function insert_document_for_type( $type, array $data ) {
		switch ( $type ) {
			case 'invoice':
				return EAC()->invoices->insert( $data );
			case 'bill':
				return EAC()->bills->insert( $data );
			default:
				$data['type'] = $type;

				return Document::insert( $data );
		}
	}

	public function import_item( $data ) {
		$type = isset( $data['type'] ) ? sanitize_key( (string) $data['type'] ) : '';

		$allowed = $this->get_allowed_document_types();
		if ( ! is_array( $allowed ) ) {
			$allowed = array( 'invoice', 'bill', 'receipt', 'contract' );
		}
		$allowed = array_map( 'sanitize_key', $allowed );

		if ( '' === $type ) {
			return new \WP_Error(
				'eac_documents_import_missing_type',
				__( 'Each row must include a document type.', 'otto-contracts' )
			);
		}
		if ( ! in_array( $type, $allowed, true ) ) {
			return new \WP_Error(
				'eac_documents_import_invalid_type',
				__( 'This document type is not allowed for import.', 'otto-contracts' )
			);
		}

		$data = array_diff_key(
			$data,
			array_flip(
				array(
					'id',
					'date_updated',
					'uuid',
				)
			)
		);

		if ( ! empty( $data['parent_id'] ) ) {
			$pid = absint( $data['parent_id'] );
			$par = $pid ? Document::find( $pid ) : null;
			if ( ! $par ) {
				unset( $data['parent_id'] );
			}
		}

		$this->resolve_document_parent( $data );
		$this->resolve_document_contact( $type, $data );
		$this->resolve_document_number_conflict( $type, $data );

		$this->normalize_import_datetime_fields(
			$data,
			array(
				'issue_date',
				'due_date',
				'sent_date',
				'payment_date',
				'date_created',
				'date_updated',
			)
		);
		$this->normalize_document_import_row( $data );

		unset( $data['type'] );

		return $this->insert_document_for_type( $type, $data );
	}
}
