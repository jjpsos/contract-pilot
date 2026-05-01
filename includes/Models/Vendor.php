<?php

namespace Otto\Models;

defined( 'ABSPATH' ) || exit;


class Vendor extends Contact {
	
	protected $object_type = 'vendor';

	
	public function __construct( $attributes = array() ) {
		$this->attributes['type'] = $this->get_object_type();
		$this->query_vars['type'] = $this->get_object_type();
		parent::__construct( $attributes );
	}

	

	
	
	public function save() {
		
		if ( ! empty( $this->email ) ) {
			$existing = $this->find( array( 'email' => $this->email ) );
			if ( ! empty( $existing ) && $existing->id !== $this->id ) {
				return new \WP_Error( 'duplicate', __( 'Vendor with same email already exists.', 'otto-contracts' ) );
			}
		}

		
		if ( ! empty( $this->phone ) ) {
			$existing = $this->find( array( 'phone' => $this->phone ) );
			if ( ! empty( $existing ) && $existing->id !== $this->id ) {
				return new \WP_Error( 'duplicate', __( 'Vendor with same phone already exists.', 'otto-contracts' ) );
			}
		}

		return parent::save();
	}

	

	
	public function get_edit_url() {
		return admin_url( 'admin.php?page=eac-purchases&tab=vendors&action=edit&id=' . $this->id );
	}

	
	public function get_view_url() {
		return admin_url( 'admin.php?page=eac-purchases&tab=vendors&action=view&id=' . $this->id );
	}
}
