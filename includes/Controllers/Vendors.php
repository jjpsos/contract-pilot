<?php

namespace Otto\Controllers;

use Otto\Models\Vendor;

defined( 'ABSPATH' ) || exit;


class Vendors {

	
	public function get( $vendor ) {
		return Vendor::find( $vendor );
	}

	
	public function insert( $data, $wp_error = true ) {
		return Vendor::insert( $data, $wp_error );
	}

	
	public function delete( $id ) {
		$vendor = $this->get( $id );
		if ( ! $vendor ) {
			return false;
		}

		return $vendor->delete();
	}

	
	public function query( $args = array(), $count = false ) {
		if ( $count ) {
			return Vendor::count( $args );
		}

		return Vendor::results( $args );
	}
}
