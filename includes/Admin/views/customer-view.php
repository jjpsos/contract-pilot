<?php


use Otto\Models\Customer;

defined( 'ABSPATH' ) || exit;

$id       = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT );
$section  = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : ''; 
$customer = EAC()->customers->get( $id );
$sections = apply_filters(
	'eac_customer_view_sections',
	array(
		'overview' => array(
			'label' => __( 'Overview', 'otto-contracts' ),
			'icon'  => 'admin-settings',
		),
		'payments' => array(
			'label' => __( 'Payments', 'otto-contracts' ),
			'icon'  => 'money',
		),
		'invoices' => array(
			'label' => __( 'Contracts/Bills', 'otto-contracts' ),
			'icon'  => 'text-page',
		),
		'notes'    => array(
			'label' => __( 'Notes', 'otto-contracts' ),
			'icon'  => 'admin-comments',
		),
	)
);


$current_section = ! array_key_exists( $section, $sections ) ? current( array_keys( $sections ) ) : $section;
?>
<h1 class="wp-heading-inline">
	<?php esc_html_e( 'View Customer', 'otto-contracts' ); ?>
	<a href="<?php echo esc_attr( remove_query_arg( array( 'action', 'id' ) ) ); ?>" title="<?php esc_attr_e( 'Go back', 'otto-contracts' ); ?>">
		<span class="dashicons dashicons-undo"></span>
	</a>
</h1>

<div class="eac-card eac-profile-header">
	<div class="eac-profile-header__avatar">
		<?php echo get_avatar( $customer->email, 120 ); ?>
	</div>
	<div class="eac-profile-header__columns">
		<div class="eac-profile-header__column">
			<div class="eac-profile-header__title">
				<?php echo esc_html( $customer->name ); ?>
			</div>
			<?php if ( $customer->phone ) : ?>
				<p class="small"><a href="tel:<?php echo esc_attr( $customer->phone ); ?>"><?php echo esc_html( $customer->phone ); ?></a></p>
			<?php endif; ?>
			<?php if ( $customer->email ) : ?>
				<p class="small"><a href="mailto:<?php echo esc_attr( $customer->email ); ?>"><?php echo esc_html( $customer->email ); ?></a></p>
			<?php endif; ?>
			<p class="small">
				<?php ?>
				<?php printf( esc_html__( 'Since %s', 'otto-contracts' ), esc_html( wp_date( get_option( 'date_format' ), strtotime( $customer->date_created ) ) ) ); ?>
			</p>
		</div>
	</div>
	<?php if ( current_user_can( 'eac_edit_customers' ) ) : ?>
		<a class="eac-profile-header__edit" href="<?php echo esc_url( $customer->get_edit_url() ); ?>"><span class="dashicons dashicons-edit"></span></a>
	<?php endif; ?>
</div>

<div class="eac-profile-sections">
	<ul class="eac-profile-sections__nav" role="tablist">
		<?php foreach ( $sections as $key => $section ) : ?>
			<li id="<?php echo esc_attr( $key ); ?>-nav-item" class="eac-profile-sections__nav-item <?php echo $current_section === $key ? 'is-active' : ''; ?>" role="tab" aria-controls="<?php echo esc_attr( $key ); ?>">
				<a href="<?php echo esc_url( add_query_arg( 'section', $key ) ); ?>">
					<span class="dashicons dashicons-<?php echo esc_attr( $section['icon'] ); ?>"></span>
					<span class="label">
						<?php echo esc_html( $section['label'] ); ?>
					</span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
	<div class="eac-profile-sections__content">
		<?php
		
		do_action( 'eac_customer_profile_section_' . $current_section, $customer );
		?>
	</div>
	<br class="clear">
</div>
