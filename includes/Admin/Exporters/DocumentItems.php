<?php


namespace Otto\Admin\Exporters;

use Otto\Models\Document;
use Otto\Models\DocumentItem;

defined( 'ABSPATH' ) || exit();


/**
 * CSV export for {@see DocumentItem} rows (otto_document_items).
 */
class DocumentItems extends Exporter {

	public $export_type = 'document_items';

	public function get_columns() {
		$hidden  = array( 'id' );
		$columns = array_values( array_diff( ( new DocumentItem() )->get_columns(), $hidden ) );
		foreach ( array( 'document_number', 'document_type' ) as $extra ) {
			if ( ! in_array( $extra, $columns, true ) ) {
				$columns[] = $extra;
			}
		}

		return $columns;
	}

	public function get_rows() {
		$args = array(
			'orderby' => 'id',
			'order'   => 'ASC',
			'page'    => $this->page,
			'limit'   => $this->limit,
		);

		$args = apply_filters( 'eac_export_document_items_args', $args );

		$items       = DocumentItem::results( $args );
		$this->total = DocumentItem::count( $args );
		$rows        = array();

		foreach ( $items as $item ) {
			$row = array();
			foreach ( $this->get_columns() as $column ) {
				switch ( $column ) {
					case 'document_number':
					case 'document_type':
						$value = '';
						if ( ! empty( $item->document_id ) ) {
							$doc = Document::find( (int) $item->document_id );
							if ( $doc ) {
								$value = ( 'document_number' === $column ) ? $doc->number : $doc->type;
							}
						}
						break;
					default:
						$value = isset( $item->{$column} ) ? $item->{$column} : null;
				}
				$row[ $column ] = $value;
			}
			if ( ! empty( $row ) ) {
				$rows[] = $row;
			}
		}

		return $rows;
	}
}
