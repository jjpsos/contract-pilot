<?php


use Otto\Models\Account;

defined( 'ABSPATH' ) || exit;


$id       = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT );
$section  = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : ''; 
$account  = EAC()->accounts->get( $id );
$sections = apply_filters(
	'eac_account_view_sections',
	array(
		'overview' => array(
			'label' => __( 'Overview', 'otto-contracts' ),
			'icon'  => 'admin-settings',
		),
		'payments' => array(
			'label' => __( 'Payments', 'otto-contracts' ),
			'icon'  => 'money',
		),
		'expenses' => array(
			'label' => __( 'Expenses', 'otto-contracts' ),
			'icon'  => 'money-alt',
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
	<?php esc_html_e( 'View Account', 'otto-contracts' ); ?>
	<a href="<?php echo esc_attr( remove_query_arg( array( 'action', 'id' ) ) ); ?>" title="<?php esc_attr_e( 'Go back', 'otto-contracts' ); ?>">
		<span class="dashicons dashicons-undo"></span>
	</a>
</h1>


<div class="eac-card eac-profile-header">
	<div class="eac-profile-header__avatar">
		<div class="avatar tw-flex tw-items-center tw-justify-center tw-w-16 tw-h-16 tw-rounded-full tw-bg-blue-500 tw-text-white tw-text-2xl tw-font-bold">
		<?php echo esc_html( EAC()->currencies->get_symbol( $account->currency ) ); ?>
		</div>
	</div>
	<div class="eac-profile-header__columns">
		<div class="eac-profile-header__column">
			<div class="eac-profile-header__title">
				<?php echo esc_html( $account->name ); ?>
			</div>
			<p class="small"><?php printf( '%1$s %2$s', esc_html__( 'Balance:', 'otto-contracts' ), esc_html( $account->formatted_balance ) ); ?></p>
			<?php if ( $account->number ) : ?>
				<p class="small"><?php printf( '%1$s %2$s', esc_html__( 'Account #:', 'otto-contracts' ), esc_html( $account->number ) ); ?></p>
			<?php endif; ?>
			<p class="small">
				<?php ?>
				<?php printf( esc_html__( 'Since %s', 'otto-contracts' ), esc_html( wp_date( eac_date_format(), strtotime( $account->date_created ) ) ) ); ?>
			</p>
		</div>
	</div>
	<?php if ( current_user_can( 'eac_edit_accounts' ) ) : ?>
	<a class="eac-profile-header__edit" href="<?php echo esc_url( $account->get_edit_url() ); ?>"><span class="dashicons dashicons-edit"></span></a>
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
		
		do_action( 'eac_account_profile_section_' . $current_section, $account );
		?>
	</div>
	<br class="clear">
</div>
