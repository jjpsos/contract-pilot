<?php


namespace Otto\Admin\Importers;


class Items extends Importer {
	
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

		
		if ( ! empty( $data['category'] ) ) {
			$category = EAC()->categories->get(
				array(
					'name' => $data['category'],
					'type' => 'item',
				)
			);

			if ( ! $category ) {
				$category = EAC()->categories->insert(
					array(
						'name' => sanitize_text_field( $data['category'] ),
						'type' => 'item',
					)
				);
			}

			if ( $category ) {
				$data['category_id'] = $category->id;
			}
		}

		return EAC()->items->insert( $data );
	}
}
