<?php


namespace Otto\Admin\Exporters;

use Otto\Models\Document;

defined( 'ABSPATH' ) || exit();


/**
 * CSV export for {@see Document} rows (otto_documents).
 */
class Documents extends Exporter {

	public $export_type = 'documents';

	public function get_columns() {
		$hidden  = array( 'id', 'uuid', 'author_id', 'created_via' );
		$columns = array_values( array_diff( ( new Document() )->get_columns(), $hidden ) );
		foreach ( array( 'parent_document_number', 'parent_document_type' ) as $extra ) {
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

		$args = apply_filters( 'eac_export_documents_args', $args );

		$items       = Document::results( $args );
		$this->total = Document::count( $args );
		$rows        = array();

		foreach ( $items as $item ) {
			$row = array();
			foreach ( $this->get_columns() as $column ) {
				switch ( $column ) {
					case 'parent_document_number':
					case 'parent_document_type':
						$value = '';
						if ( ! empty( $item->parent_id ) ) {
							$p = Document::find( (int) $item->parent_id );
							if ( $p ) {
								$value = ( 'parent_document_number' === $column ) ? $p->number : $p->type;
							}
						}
						break;
					default:
						$value = isset( $item->{$column} ) ? $item->{$column} : null;
				}
				$row[ $column ] = $value;
			}
			if ( ! empty( $row ) ) {
				$dates = array(
					'issue_date',
					'due_date',
					'sent_date',
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
