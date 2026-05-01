<?php

namespace Otto\Models;

use Otto\ByteKit\Models\Relations\BelongsTo;
use Otto\ByteKit\Models\Relations\BelongsToMany;

defined( 'ABSPATH' ) || exit;


class Expense extends Transaction {
	
	protected $object_type = 'expense';

	
	protected $aliases = array(
		'bill_id'   => 'document_id',
		'vendor_id' => 'contact_id',
	);

	
	protected $query_vars = array(
		'type'           => 'payment',
		'search_columns' => array( 'id', 'contact_id', 'amount', 'payment_date' ),
	);

	
	public function __construct( $attributes = null ) {
		$this->attributes['type'] = $this->get_object_type();
		$this->query_vars['type'] = $this->get_object_type();
		parent::__construct( $attributes );
	}

	

	
	protected function get_payment_method_label_attribute() {
		$modes = eac_get_payment_methods();

		return array_key_exists( $this->payment_method, $modes ) ? $modes[ $this->payment_method ] : $this->payment_method;
	}

	
	public function bill() {
		return $this->belongs_to( Bill::class, 'document_id' );
	}

	
	public function notes() {
		return $this->belongs_to_many( Note::class, 'parent_id' )->set( 'parent_type', 'expense' );
	}

	
	
	public function save() {
		if ( empty( $this->payment_date ) ) {
			return new \WP_Error( 'missing_required', __( 'Expense date is required.', 'otto-contracts' ) );
		}
		if ( empty( $this->account_id ) ) {
			return new \WP_Error( 'missing_required', __( 'Account is required.', 'otto-contracts' ) );
		}

		
		if ( ! $this->exists() || $this->is_dirty( 'account_id' ) ) {
			$account = Account::find( $this->account_id );
			if ( ! $account ) {
				return new \WP_Error( 'invalid_account', __( 'Invalid account.', 'otto-contracts' ) );
			}
			$this->currency = $account->currency;
		}

		return parent::save();
	}

	

	
	public function get_next_number() {
		$max    = $this->get_max_number();
		$prefix = get_option( 'eac_expense_prefix', strtoupper( substr( $this->get_object_type(), 0, 3 ) ) . '-' );
		$number = str_pad( $max + 1, get_option( 'eac_expense_digits', 4 ), '0', STR_PAD_LEFT );

		return $prefix . $number;
	}

	
	public function get_edit_url() {
		return admin_url( 'admin.php?page=eac-purchases&tab=expenses&action=edit&id=' . $this->id );
	}

	
	public function get_view_url() {
		return admin_url( 'admin.php?page=eac-purchases&tab=expenses&action=view&id=' . $this->id );
	}

	
	public function get_public_url() {
		return site_url( 'eac/expense/?uuid=' . $this->uuid );
	}
}
