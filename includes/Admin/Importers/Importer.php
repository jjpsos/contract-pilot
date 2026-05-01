<?php

namespace Otto\Admin\Importers;

use Otto\Utilities\FileSystemUtil;

defined( 'ABSPATH' ) || exit;


abstract class Importer {
	
	protected $capability = 'eac_manage_import';

	
	protected $file = '';

	
	protected $rows = array();

	
	protected $position = 0;

	
	protected $start_time = 0;

	
	public function __construct( $file = '', $position = 0 ) {
		$this->file     = $file;
		$this->position = $position;
		if ( ! empty( $this->file ) && file_exists( $this->file ) ) {
			$this->rows = $this->parse_csv( $this->file );
		}
	}

	
	public function can_import() {
		return (bool) current_user_can( apply_filters( 'eac_import_capability', $this->capability ) );
	}

	
	public function import() {
		if ( ! $this->can_import() ) {
			return 0;
		}

		$this->start_time = time();
		$rows             = $this->rows;

		if ( ! empty( $this->position ) && $this->position < count( $rows ) ) {
			$rows = array_slice( $rows, $this->position, $this->position );
		}

		$imported = 0;
		foreach ( $rows as $row ) {
			++$this->position;
			if ( ! is_wp_error( $this->import_item( $row ) ) ) {
				++$imported;
			}
			if ( $this->time_exceeded() || $this->memory_exceeded() ) {
				break;
			}
		}

		return $imported;
	}

	
	public function get_percent_complete() {
		$count = count( $this->rows );
		if ( ! $count || ! $this->position ) {
			return 0;
		}

		$percent = absint( ( $this->position / $count ) * 100 );

		if ( $percent >= 100 ) {
			FileSystemUtil::delete( $this->file );
		}

		return $percent;
	}

	
	public function get_position() {
		return $this->position;
	}

	
	abstract protected function import_item( $data );

	
	protected function parse_csv( $file ) {
		if ( ! file_exists( $file ) ) {
			return array();
		}

		$rows = array_map( 'str_getcsv', FileSystemUtil::file( $file ) );
		array_walk(
			$rows,
			function ( &$a ) use ( $rows ) {
				
				$min     = min( count( $rows[0] ), count( $a ) );
				$headers = array_slice( $rows[0], 0, $min );
				if ( 'efbbbf' === substr( bin2hex( $headers[0] ), 0, 6 ) ) {
					$headers[0] = substr( $headers[0], 3 );
				}
				$values = array_slice( $a, 0, $min );
				$a      = array_combine( $headers, $values );
			}
		);
		array_shift( $rows );

		
		foreach ( $rows as &$row ) {
			$this->parse_row( $row );
		}

		return $rows;
	}

	
	protected function parse_row( &$row ) {
		$row = array_map( 'wp_unslash', $row );
		$row = array_map( 'trim', $row );
		foreach ( $row as &$value ) {
			if ( function_exists( 'mb_convert_encoding' ) ) {
				$encoding = mb_detect_encoding( $value, mb_detect_order(), true );
				if ( $encoding ) {
					$value = mb_convert_encoding( $value, 'UTF-8', $encoding );
				} else {
					$value = mb_convert_encoding( $value, 'UTF-8', 'UTF-8' );
				}
			} else {
				$value = wp_check_invalid_utf8( $value, true );
			}

			if ( in_array( mb_substr( $value, 0, 2 ), array( "'=", "'+", "'-", "'@" ), true ) ) {
				$value = mb_substr( $value, 1 );
			}
		}

		return $row;
	}

	
	protected function time_exceeded() {
		$finish = $this->start_time + 20; 
		$return = false;
		if ( time() >= $finish ) {
			$return = true;
		}

		return $return;
	}

	
	protected function memory_exceeded() {
		$memory_limit   = $this->get_memory_limit() * 0.9; 
		$current_memory = memory_get_usage( true );
		$return         = false;
		if ( $current_memory >= $memory_limit ) {
			$return = true;
		}

		return $return;
	}

	
	protected function get_memory_limit() {
		if ( function_exists( 'ini_get' ) ) {
			$memory_limit = ini_get( 'memory_limit' );
		} else {
			
			$memory_limit = '128M';
		}

		if ( ! $memory_limit || - 1 === intval( $memory_limit ) ) {
			
			$memory_limit = '32000M';
		}

		return intval( $memory_limit ) * 1024 * 1024;
	}
}
