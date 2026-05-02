<?php

namespace Otto\Models;

use Otto\ByteKit\Models\Relations\BelongsTo;

defined( 'ABSPATH' ) || exit;


class Transfer extends Model {
	
	protected $table = 'otto_transfers';

	
	protected $columns = array(
		'id',
		'expense_id',
		'payment_id',
		'transfer_date',
		'amount',
		'currency',
		'payment_method',
		'reference',
		'note',
	);

	
	protected $casts = array(
		'payment_id'     => 'int',
		'expense_id'     => 'int',
		'transfer_date'  => 'datetime',
		'amount'         => 'double',
		'currency'       => 'string',
		'payment_method' => 'string',
		'reference'      => 'string',
		'note'           => 'string',
	);

	
	protected $searchable = array(
		'reference',
		'amount',
		'date',
	);

	
	protected $has_timestamps = true;

	

	
	public function get_formatted_amount_attribute() {
		return eac_format_amount( $this->amount, $this->currency );
	}

	
	public function payment() {
		return $this->belongs_to( Payment::class, 'payment_id' );
	}

	
	public function expense() {
		return $this->belongs_to( Expense::class, 'expense_id' );
	}

	

	
	public function save() {
		$expense = Expense::make( $this->expense_id );
		$payment = Payment::make( $this->payment_id );

		$from_account_id = $this->from_account_id ? (int) $this->from_account_id : ( $expense->exists() && $expense->account_id ? (int) $expense->account_id : 0 );
		$to_account_id   = $this->to_account_id ? (int) $this->to_account_id : ( $payment->exists() && $payment->account_id ? (int) $payment->account_id : 0 );

		$from_account = Account::find( $from_account_id );
		$to_account   = Account::find( $to_account_id );

		
		if ( ! $from_account || ! $to_account ) {
			return new \WP_Error( 'invalid_account', __( 'Invalid account.', 'otto-contracts' ) );
		}

		
		if ( $from_account_id === $to_account_id ) {
			return new \WP_Error( 'same_account', __( 'From and to accounts cannot be the same.', 'otto-contracts' ) );
		}

		
		if ( ! is_numeric( $this->amount ) ) {
			return new \WP_Error( 'invalid_amount', __( 'Invalid amount.', 'otto-contracts' ) );
		}

		if ( ! empty( $this->from_exchange_rate ) ) {
			$from_rate = floatval( $this->from_exchange_rate );
		} elseif ( $expense->exists() && ! empty( $expense->exchange_rate ) ) {
			$from_rate = floatval( $expense->exchange_rate );
		} else {
			$from_rate = floatval( EAC()->currencies->get_rate( $from_account->currency ) );
		}

		if ( ! empty( $this->to_exchange_rate ) ) {
			$to_rate = floatval( $this->to_exchange_rate );
		} elseif ( $payment->exists() && ! empty( $payment->exchange_rate ) ) {
			$to_rate = floatval( $payment->exchange_rate );
		} else {
			$to_rate = floatval( EAC()->currencies->get_rate( $to_account->currency ) );
		}

		if ( empty( $this->transfer_date ) ) {
			$this->transfer_date = current_time( 'mysql' );
		}

		$expense->fill(
			array(
				'status'         => 'completed',
				'payment_date'   => $this->transfer_date,
				'amount'         => $this->amount,
				'currency'       => $from_account->currency,
				'exchange_rate'  => $from_rate,
				'reference'      => $this->reference,
				'note'           => $this->note,
				'payment_method' => $this->payment_method,
				'account_id'     => $from_account_id,
				'editable'       => false,
			)
		);

		$ret_val1 = $expense->save();
		if ( is_wp_error( $ret_val1 ) ) {
			return $ret_val1;
		}

		$amount = $this->amount;
		if ( $from_account->currency !== $to_account->currency ) {
			$amount = eac_convert_currency( $amount, $from_rate, $to_rate );
		}

		$payment->fill(
			array(
				'status'         => 'completed',
				'payment_date'   => $this->transfer_date,
				'amount'         => $amount,
				'currency'       => $to_account->currency,
				'exchange_rate'  => $to_rate,
				'reference'      => $this->reference,
				'note'           => $this->note,
				'payment_method' => $this->payment_method,
				'account_id'     => $to_account_id,
				'editable'       => false,
			)
		);

		$ret_val2 = $payment->save();
		if ( is_wp_error( $ret_val2 ) ) {
			return $ret_val2;
		}

		$this->fill(
			array(
				'expense_id' => $expense->id,
				'payment_id' => $payment->id,
				'currency'   => $from_account->currency,
			)
		);

		return parent::save();
	}

	
	public function delete() {
		$this->expense()->delete();
		$this->payment()->delete();

		return parent::delete();
	}

	

	
	public function get_edit_url() {
		return admin_url( 'admin.php?page=eac-banking&tab=transfers&action=edit&id=' . $this->id );
	}

	
	public function get_view_url() {
		return admin_url( 'admin.php?page=eac-banking&tab=transfers&action=view&id=' . $this->id );
	}
}
