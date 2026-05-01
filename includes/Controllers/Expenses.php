<?php

namespace Otto\Controllers;

use Otto\Models\Expense;

defined( 'ABSPATH' ) || exit;


class Expenses {

	
	public function get( $expense ) {
		return Expense::find( $expense );
	}

	
	public function insert( $data, $wp_error = true ) {
		return Expense::insert( $data, $wp_error );
	}

	
	public function delete( $id ) {
		$expense = $this->get( $id );
		if ( ! $expense ) {
			return false;
		}

		return $expense->delete();
	}

	
	public function query( $args = array(), $count = false ) {
		if ( $count ) {
			return Expense::count( $args );
		}

		return Expense::results( $args );
	}
}
