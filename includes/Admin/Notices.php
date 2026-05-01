<?php

namespace Otto\Admin;

defined( 'ABSPATH' ) || exit;


class Notices {

	
	public function __construct() {
		add_action( 'admin_init', array( $this, 'admin_notices' ) );
	}

	
	public function admin_notices() {
		$installed_time = absint( get_option( 'eac_install_date' ) );
		$current_time   = absint( wp_date( 'U' ) );

		
		$black_friday_end_time = strtotime( '2025-12-05 00:00:00' );
		if ( $current_time < $black_friday_end_time ) {
			EAC()->notices->add(
				array(
					'message'     => __DIR__ . '/views/notices/black-friday.php',
					'dismissible' => false,
					'notice_id'   => 'eac_black-friday_promo_2025',
					'style'       => 'border-left-color: #000000;',
					'class'       => 'notice-black-friday',
				)
			);
		}

		
		if ( $installed_time && $current_time > ( $installed_time + ( 5 * DAY_IN_SECONDS ) ) ) {
			EAC()->notices->add(
				array(
					'message'     => __DIR__ . '/views/notices/review.php',
					'dismissible' => false,
					'notice_id'   => 'eac_review',
					'style'       => 'border-left-color: #77B82E;',
				)
			);
		}
	}
}
