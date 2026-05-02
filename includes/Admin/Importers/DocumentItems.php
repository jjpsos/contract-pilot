<?php


namespace Otto\Admin\Importers;

use Otto\Models\Document;
use Otto\Models\DocumentItem;
use Otto\Models\Item;

/**
 * CSV import for {@see DocumentItem} rows.
 */
class DocumentItems extends Importer {

	/**
	 * @param array $data Import row (by reference).
	 * @return true|\WP_Error
	 */
	protected function resolve_line_document_id( array &$data ) {
		$dnum = isset( $data['document_number'] ) ? trim( wp_unslash( (string) $data['document_number'] ) ) : '';
		$dtyp = isset( $data['document_type'] ) ? sanitize_key( trim( wp_unslash( (string) $data['document_type'] ) ) ) : '';
		unset( $data['document_number'], $data['document_type'] );

		if ( '' !== $dnum && '' !== $dtyp ) {
			$found = Document::results(
				array(
					'type'   => $dtyp,
					'number' => $dnum,
					'limit'  => 1,
				)
			);
			if ( ! empty( $found ) ) {
				$data['document_id'] = (int) $found[0]->id;

				return true;
			}
		}

		if ( ! empty( $data['document_id'] ) ) {
			$id = absint( $data['document_id'] );
			if ( $id && Document::find( $id ) ) {
				$data['document_id'] = $id;

				return true;
			}
		}

		return new \WP_Error(
			'eac_document_items_import_document',
			__( 'Each line must include a valid document_id, or document_number and document_type for an existing document.', 'otto-contracts' )
		);
	}

	protected function resolve_line_item_id( array &$data ) {
		if ( empty( $data['item_id'] ) ) {
			return;
		}
		$item_id = absint( $data['item_id'] );
		if ( ! $item_id || ! Item::find( $item_id ) ) {
			unset( $data['item_id'] );
		} else {
			$data['item_id'] = $item_id;
		}
	}

	protected function maybe_refresh_parent_document_totals( $document_id ) {
		$doc = Document::find( (int) $document_id );
		if ( ! $doc ) {
			return;
		}
		if ( 'invoice' === $doc->type ) {
			$invoice = EAC()->invoices->get( $doc->id );
			if ( $invoice ) {
				$invoice->calculate_totals();
				$invoice->save();
			}
		} elseif ( 'bill' === $doc->type ) {
			$bill = EAC()->bills->get( $doc->id );
			if ( $bill ) {
				$bill->calculate_totals();
				$bill->save();
			}
		}
	}

	public function import_item( $data ) {
		$data = array_diff_key( $data, array_flip( array( 'id' ) ) );

		$res = $this->resolve_line_document_id( $data );
		if ( is_wp_error( $res ) ) {
			return $res;
		}

		$this->resolve_line_item_id( $data );

		if ( empty( $data['name'] ) || ! is_string( $data['name'] ) ) {
			return new \WP_Error(
				'eac_document_items_import_name',
				__( 'Each line must include a name.', 'otto-contracts' )
			);
		}
		$data['name'] = sanitize_text_field( $data['name'] );

		$this->normalize_document_item_import_row( $data );

		$doc_id = isset( $data['document_id'] ) ? (int) $data['document_id'] : 0;
		$result = DocumentItem::insert( $data );
		if ( ! is_wp_error( $result ) && $doc_id ) {
			$this->maybe_refresh_parent_document_totals( $doc_id );
		}

		return $result;
	}
}
