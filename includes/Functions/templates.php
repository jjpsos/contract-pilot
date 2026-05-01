<?php

defined( 'ABSPATH' ) || exit();


function eac_get_template_part( $slug, $name = null ) {
	$templates = array();
	if ( $name ) {
		$templates[] = "{$slug}-{$name}.php";
	}

	$templates[] = "{$slug}.php";

	
	$templates = apply_filters( 'eac_get_template_part', $templates, $slug, $name );

	foreach ( $templates as $template ) {
		$located = eac_locate_template( $template );

		if ( ! empty( $located ) ) {
			load_template( $located, false );
			break;
		}
	}
}


function eac_locate_template( $template_name, $template_path = '', $default_path = '' ) {
	if ( ! $template_path ) {
		$template_path = EAC()->get_template_path();
	}

	if ( ! $default_path ) {
		$default_path = EAC()->get_template_path();
	}

	
	$template = locate_template(
		array(
			trailingslashit( $template_path ) . $template_name,
			'eac/' . $template_name,
		)
	);

	
	if ( ! $template ) {
		$template = $default_path . $template_name;
	}

	
	return apply_filters( 'eac_locate_template', $template, $template_name, $template_path );
}


function eac_get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
	$template = eac_locate_template( $template_name, $template_path, $default_path );

	
	$filter_template = apply_filters( 'eac_get_template', $template, $template_name, $args, $template_path, $default_path );

	if ( $filter_template !== $template ) {
		if ( ! file_exists( $filter_template ) ) {
			$filter_template = $template;
		}
	}

	$action_args = array(
		'template_name' => $template_name,
		'template_path' => $template_path,
		'located'       => $template,
		'args'          => $args,
	);

	if ( ! empty( $args ) && is_array( $args ) ) {
		extract( $args ); 
	}

	do_action( 'otto_accounting_before_template_part', $action_args['template_name'], $action_args['template_path'], $action_args['located'], $action_args['args'] );

	include $action_args['located'];

	do_action( 'otto_accounting_after_template_part', $action_args['template_name'], $action_args['template_path'], $action_args['located'], $action_args['args'] );
}


function eac_get_template_html( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
	ob_start();
	eac_get_template( $template_name, $args, $template_path, $default_path );

	return ob_get_clean();
}


/**
 * Build invoice view data for template rendering.
 *
 * @param object $invoice Invoice model.
 * @return array<string, mixed>
 */
function eac_build_invoice_view_data( $invoice ) {
	$columns = EAC()->invoices->get_columns();
	if ( ! $invoice->is_taxed() ) {
		unset( $columns['tax'] );
	}

	$doc_title = eac_invoice_heading_status_label( $invoice );
	if ( '' === $doc_title ) {
		$doc_title = __( 'Contract', 'otto-contracts' );
	}

	$business = array(
		'logo'     => get_option( 'eac_business_logo', get_site_icon_url( 55 ) ),
		'phone'    => get_option( 'eac_business_phone' ),
		'email'    => get_option( 'eac_business_email', get_option( 'admin_email' ) ),
		'name'     => get_option( 'eac_business_name', get_bloginfo( 'name' ) ),
		'site_url' => site_url(),
	);

	$bill_from_address = eac_get_formatted_address(
		array(
			'name'       => get_option( 'eac_business_name', get_bloginfo( 'name' ) ),
			'address'    => get_option( 'eac_business_address' ),
			'city'       => get_option( 'eac_business_city' ),
			'state'      => get_option( 'eac_business_state' ),
			'postcode'   => get_option( 'eac_business_postcode' ),
			'country'    => get_option( 'eac_business_country' ),
			'email'      => get_option( 'eac_business_email' ),
			'phone'      => get_option( 'eac_business_phone' ),
			'tax_number' => get_option( 'eac_business_tax_number' ),
		)
	);

	$bill_to_address = eac_get_formatted_address(
		array(
			'name'       => $invoice->contact_name,
			'company'    => $invoice->contact_company,
			'address'    => $invoice->contact_address,
			'city'       => $invoice->contact_city,
			'state'      => $invoice->contact_state,
			'postcode'   => $invoice->contact_postcode,
			'country'    => $invoice->contact_country,
			'email'      => $invoice->contact_email,
			'phone'      => $invoice->contact_phone,
			'tax_number' => $invoice->contact_tax_number,
		)
	);

	$item_rows = array();
	$invoice_items = is_array( $invoice->items ) ? $invoice->items : array();
	foreach ( $invoice_items as $item ) {
		$cells = array();

		foreach ( $columns as $column_key => $column_label ) {
			switch ( $column_key ) {
				case 'item':
					$cells[ $column_key ] = array(
						'value'    => $item->name,
						'subvalue' => $item->description ? $item->description : '',
					);
					break;
				case 'quantity':
					$quantity_value = (string) $item->quantity;
					if ( $item->unit ) {
						$quantity_value .= ' x ' . $item->unit;
					}
					$cells[ $column_key ] = array(
						'value'    => $quantity_value,
						'subvalue' => '',
					);
					break;
				case 'price':
					$cells[ $column_key ] = array(
						'value'    => eac_format_amount( $item->price, $invoice->currency ),
						'subvalue' => '',
					);
					break;
				case 'tax':
					$cells[ $column_key ] = array(
						'value'    => eac_format_amount( $item->tax, $invoice->currency ),
						'subvalue' => '',
					);
					break;
				case 'subtotal':
					$cells[ $column_key ] = array(
						'value'    => eac_format_amount( $item->subtotal, $invoice->currency ),
						'subvalue' => '',
					);
					break;
			}
		}

		$item_rows[] = $cells;
	}

	$totals = array();
	$totals[] = array(
		'label'  => __( 'Subtotal', 'otto-contracts' ),
		'amount' => eac_format_amount( $invoice->subtotal, $invoice->currency ),
		'is_due' => false,
	);

	if ( $invoice->discount > 0 ) {
		$totals[] = array(
			'label'  => __( 'Discount', 'otto-contracts' ),
			'amount' => eac_format_amount( $invoice->discount, $invoice->currency ),
			'is_due' => false,
		);
	}

	if ( $invoice->is_taxed() ) {
		if ( 'single' === get_option( 'eac_tax_total_display' ) ) {
			$totals[] = array(
				'label'  => __( 'Tax', 'otto-contracts' ),
				'amount' => eac_format_amount( $invoice->tax, $invoice->currency ),
				'is_due' => false,
			);
		} else {
			foreach ( $invoice->get_itemized_taxes() as $tax ) {
				$totals[] = array(
					'label'  => $tax->formatted_name,
					'amount' => eac_format_amount( $tax->amount, $invoice->currency ),
					'is_due' => false,
				);
			}
		}
	}

	$totals[] = array(
		'label'  => __( 'Total', 'otto-contracts' ),
		'amount' => $invoice->formatted_total,
		'is_due' => false,
	);

	if ( $invoice->get_due_amount() > 0 ) {
		$totals[] = array(
			'label'  => __( 'Due', 'otto-contracts' ),
			'amount' => eac_format_amount( $invoice->get_due_amount(), $invoice->currency ),
			'is_due' => true,
		);
	}

	$payments = array();
	$invoice_payments = is_array( $invoice->payments ) ? $invoice->payments : array();
	foreach ( $invoice_payments as $payment ) {
		$payments[] = array(
			'url'    => is_admin() ? $payment->get_view_url() : $payment->get_public_url(),
			'number' => $payment->number,
			'date'   => $payment->payment_date
				? wp_date( get_option( 'date_format' ), strtotime( $payment->payment_date ) )
				: 'N/A',
			'method' => $payment->payment_method_label ? $payment->payment_method_label : 'N/A',
			'amount' => eac_format_amount(
				eac_convert_currency( $payment->amount, $payment->currency, $invoice->currency ),
				$invoice->currency
			),
		);
	}

	return array(
		'doc_title'         => $doc_title,
		'invoice_number'    => $invoice->number,
		'order_number'      => $invoice->order_number,
		'issue_date'        => $invoice->issue_date ? eac_format_datetime( $invoice->issue_date, eac_date_format() ) : '',
		'due_date'          => $invoice->due_date ? eac_format_datetime( $invoice->due_date, eac_date_format() ) : '',
		'business'          => $business,
		'bill_from_address' => $bill_from_address,
		'bill_to_address'   => $bill_to_address,
		'columns'           => $columns,
		'item_rows'         => $item_rows,
		'totals'            => $totals,
		'note'              => $invoice->note,
		'payments'          => $payments,
		'terms'             => $invoice->terms,
	);
}


function eac_header() {
	eac_get_template_part( 'header' );
}


function eac_footer() {
	eac_get_template_part( 'footer' );
}
