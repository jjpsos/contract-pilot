<?php

namespace Otto\Admin\Exporters;

use Otto\Utilities\FileSystemUtil;


abstract class Exporter {
	
	public $export_type = 'default';

	
	public $capability = 'eac_manage_export';

	
	protected $filename = '';

	
	protected $delimiter = ',';

	
	protected $enclosure = '"';

	
	protected $escape = "\0"; 

	
	protected $position = 0;

	
	protected $page = 1;

	
	protected $limit = 100;

	
	protected $total = 0;

	
	abstract protected function get_columns();

	
	abstract protected function get_rows();

	
	public function can_export() {
		return (bool) current_user_can( apply_filters( 'eac_export_capability', $this->capability ) );
	}


	
	public function set_filename( $filename ) {
		$this->filename = sanitize_file_name( str_replace( '.csv', '', $filename ) . '.csv' );
	}

	
	public function get_filename() {
		$date = wp_date( 'Ymdhis' );

		return sanitize_file_name( "{$this->export_type}-$date.csv" );
	}

	
	public function process_step( $step ) {
		$this->page    = absint( $step );
		$wp_filesystem = FileSystemUtil::get_fs();

		if ( 1 === $this->page ) {
			$wp_filesystem->delete( $this->get_file_path() );
		}

		$rows = $this->prepare_rows( $this->get_rows() );

		$file = $this->get_file();

		if ( 100 <= $this->get_percent_complete() ) {
			$file = chr( 239 ) . chr( 187 ) . chr( 191 ) . $this->get_column_headers() . $file;
		}

		$file .= $rows;
		$wp_filesystem->put_contents( $this->get_file_path(), $file, FS_CHMOD_FILE );
	}

	
	public function export() {
		$this->send_headers();
		$this->send_content( $this->get_file() );
		$wp_filesystem = FileSystemUtil::get_fs();
		$wp_filesystem->delete( $this->get_file_path() );
		die();
	}

	
	public function get_percent_complete() {
		return $this->total ? floor( ( $this->get_total_exported() / $this->total ) * 100 ) : 100;
	}

	
	public function get_total_exported() {
		return ( ( $this->page - 1 ) * $this->limit ) + $this->position;
	}

	
	protected function get_file_path() {
		$upload_dir = wp_upload_dir();

		return trailingslashit( $upload_dir['basedir'] ) . $this->get_filename();
	}

	
	protected function get_file() {
		$file          = '';
		$wp_filesystem = FileSystemUtil::get_fs();
		
		if ( $wp_filesystem->exists( $this->get_file_path() ) ) {
			$file = $wp_filesystem->get_contents( $this->get_file_path() );
		} else {
			
			$wp_filesystem->put_contents( $this->get_file_path(), $file, FS_CHMOD_FILE );
		}

		return $file;
	}

	
	protected function prepare_rows( $rows ) {
		$buffer = fopen( 'php://output', 'w' );
		ob_start();
		foreach ( $rows as $row ) {
			$this->prepare_row( $row, $buffer );
		}
		return ob_get_clean();
	}

	
	protected function prepare_row( $row, $buffer ) {
		$prepared = array();
		foreach ( $this->get_columns() as $column ) {
			if ( isset( $row[ $column ] ) ) {
				$prepared[] = $this->format_data( $row[ $column ] );
			} else {
				$prepared[] = '';
			}
		}

		fputcsv( $buffer, $prepared, $this->delimiter, $this->enclosure, $this->escape );

		++$this->position;
	}

	
	protected function format_data( $data ) {
		if ( ! is_scalar( $data ) ) {
			if ( is_a( $data, '\Otto\DateTime' ) ) {
				$data = $data->date( 'Y-m-d G:i:s' );
			} else {
				$data = ''; 
			}
		} elseif ( is_bool( $data ) ) {
			$data = $data ? 1 : 0;
		}

		$use_mb = function_exists( 'mb_convert_encoding' );

		if ( $use_mb ) {
			$encoding = mb_detect_encoding( $data, 'UTF-8, ISO-8859-1', true );
			$data     = 'UTF-8' === $encoding ? $data : mb_convert_encoding( $data, 'UTF-8', $encoding );
		}

		return $this->escape_data( $data );
	}

	
	protected function escape_data( $data ) {
		$active_content_triggers = array( '=', '+', '-', '@' );

		if ( in_array( mb_substr( $data, 0, 1 ), $active_content_triggers, true ) ) {
			$data = "'" . $data;
		}

		return $data;
	}

	
	protected function get_column_headers() {
		$columns    = $this->get_columns();
		$export_row = array();
		$buffer     = fopen( 'php://output', 'w' );
		ob_start();

		foreach ( $columns as $column_name ) {
			$export_row[] = $this->format_data( $column_name );
		}

		fputcsv( $buffer, $export_row, $this->delimiter, $this->enclosure, $this->escape );

		return ob_get_clean();
	}

	
	protected function send_headers() {
		ignore_user_abort( true );
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $this->get_filename() );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );
	}

	
	protected function send_content( $content ) {
		echo wp_kses_post( $content );
	}
}
