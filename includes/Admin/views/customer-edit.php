<?php


use Otto\Models\Customer;

defined( 'ABSPATH' ) || exit;

$id       = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT );
$customer = Customer::make( $id );
?>

<h1 class="wp-heading-inline">
	<?php if ( $customer->exists() ) : ?>
		<?php esc_html_e( 'Edit Customer', 'otto-contracts' ); ?>
		<a href="<?php echo esc_attr( admin_url( 'admin.php?page=eac-sales&tab=customers&action=add' ) ); ?>" class="button button-small">
			<?php esc_html_e( 'Add New', 'otto-contracts' ); ?>
		</a>
	<?php else : ?>
		<?php esc_html_e( 'Add Customer', 'otto-contracts' ); ?>
	<?php endif; ?>
	<a href="<?php echo esc_attr( remove_query_arg( array( 'action', 'id' ) ) ); ?>" title="<?php esc_attr_e( 'Go back', 'otto-contracts' ); ?>">
		<span class="dashicons dashicons-undo"></span>
	</a>
</h1>

<form id="eac-customer-form" name="customer" method="post" action="<?php echo esc_html( admin_url( 'admin-post.php' ) ); ?>">
	<div class="eac-poststuff">
		<div class="column-1">

			<!--Customer basic details-->
			<div class="eac-card">
				<div class="eac-card__header">
					<h2 class="eac-card__title"><?php esc_html_e( 'Basic Details', 'otto-contracts' ); ?></h2>
				</div>
				<div class="eac-card__body grid--fields">
					<?php
					eac_form_field(
						array(
							'id'          => 'name',
							'label'       => __( 'Name', 'otto-contracts' ),
							'placeholder' => __( 'John Doe', 'otto-contracts' ),
							'value'       => $customer->name,
							'required'    => true,
						)
					);
					eac_form_field(
						array(
							'id'           => 'currency',
							'type'         => 'select',
							'label'        => __( 'Currency Code', 'otto-contracts' ),
							'value'        => $customer->currency,
							'default'      => eac_base_currency(),
							'required'     => true,
							'class'        => 'eac_select2',
							'options'      => eac_get_currencies(),
							'option_value' => 'code',
							'option_label' => 'formatted_name',
						)
					);
					eac_form_field(
						array(
							'id'          => 'email',
							'type'        => 'email',
							'label'       => __( 'Email', 'otto-contracts' ),
							'placeholder' => __( 'john@company.com', 'otto-contracts' ),
							'value'       => $customer->email,
						)
					);
					eac_form_field(
						array(
							'id'          => 'phone',
							'label'       => __( 'Phone', 'otto-contracts' ),
							'placeholder' => __( '+1 123 456 7890', 'otto-contracts' ),
							'value'       => $customer->phone,
						)
					);
					?>
				</div>
			</div>

			<!--Customer Business details-->
			<div class="eac-card">
				<div class="eac-card__header">
					<h2 class="eac-card__title"><?php esc_html_e( 'Business Details', 'otto-contracts' ); ?></h2>
				</div>
				<div class="eac-card__body grid--fields">
					<?php
					eac_form_field(
						array(
							'id'          => 'company',
							'label'       => __( 'Company', 'otto-contracts' ),
							'placeholder' => __( 'XYZ Inc.', 'otto-contracts' ),
							'value'       => $customer->company,
						)
					);
					eac_form_field(
						array(
							'id'          => 'website',
							'type'        => 'url',
							'label'       => __( 'Website', 'otto-contracts' ),
							'placeholder' => __( 'https://example.com', 'otto-contracts' ),
							'value'       => $customer->website,
						)
					);
					eac_form_field(
						array(
							'id'          => 'tax_number',
							'label'       => __( 'Tax Number', 'otto-contracts' ),
							'placeholder' => __( '123456789', 'otto-contracts' ),
							'value'       => $customer->tax_number,
						)
					);
					?>
				</div>
			</div>

			<!--Customer Address details-->
			<div class="eac-card">
				<div class="eac-card__header">
					<h2 class="eac-card__title"><?php esc_html_e( 'Address Details', 'otto-contracts' ); ?></h2>
				</div>
				<div class="eac-card__body grid--fields">
					<?php
					eac_form_field(
						array(
							'id'          => 'address',
							'label'       => __( 'Address', 'otto-contracts' ),
							'placeholder' => __( '123 Main St', 'otto-contracts' ),
							'value'       => $customer->address,
						)
					);
					eac_form_field(
						array(
							'id'          => 'city',
							'label'       => __( 'City', 'otto-contracts' ),
							'placeholder' => __( 'New York', 'otto-contracts' ),
							'value'       => $customer->city,
						)
					);
					eac_form_field(
						array(
							'id'          => 'state',
							'label'       => __( 'State', 'otto-contracts' ),
							'placeholder' => __( 'NY', 'otto-contracts' ),
							'value'       => $customer->state,
						)
					);
					eac_form_field(
						array(
							'id'          => 'postcode',
							'label'       => __( 'Postal Code', 'otto-contracts' ),
							'placeholder' => __( '10001', 'otto-contracts' ),
							'value'       => $customer->postcode,
						)
					);
					eac_form_field(
						array(
							'type'        => 'select',
							'id'          => 'country',
							'label'       => __( 'Country', 'otto-contracts' ),
							'options'     => \Otto\Utilities\I18nUtil::get_countries(),
							'value'       => $customer->country,
							'class'       => 'eac-select2',
							'placeholder' => __( 'Select Country', 'otto-contracts' ),
						)
					);
					?>
				</div>
			</div>

			<?php
			
			do_action( 'eac_customer_edit_core_content', $customer );
			?>
		</div><!-- .column-1 -->

		<div class="column-2">
			<div class="eac-card">
				<div class="eac-card__header">
					<h2 class="eac-card__title"><?php esc_html_e( 'Actions', 'otto-contracts' ); ?></h2>
					<?php if ( $customer->exists() ) : ?>
						<a href="<?php echo esc_url( $customer->get_view_url() ); ?>">
							<?php esc_html_e( 'View', 'otto-contracts' ); ?>
						</a>
					<?php endif; ?>
				</div>
				<?php if ( has_action( 'eac_customer_misc_actions' ) ) : ?>
					<div class="eac-card__body">
						<?php
						
						do_action( 'eac_customer_edit_misc_actions', $customer );
						?>
					</div>
				<?php endif; ?>
				<div class="eac-card__footer">
					<?php if ( $customer->exists() ) : ?>
						<a class="eac_confirm_delete del" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'action', 'delete', admin_url( 'admin.php?page=eac-sales&tab=customers&id=' . $customer->id ) ), 'bulk-customers' ) ); ?>"><?php esc_html_e( 'Delete', 'otto-contracts' ); ?></a>
						<button class="button button-primary"><?php esc_html_e( 'Update Customer', 'otto-contracts' ); ?></button>
					<?php else : ?>
						<button class="button button-primary button-block"><?php esc_html_e( 'Add Customer', 'otto-contracts' ); ?></button>
					<?php endif; ?>
				</div>
			</div>

			<?php
			
			do_action( 'eac_customer_edit_sidebar_content', $customer );
			?>

		</div><!-- .column-2 -->

	</div><!-- .eac-poststuff -->

	<?php wp_nonce_field( 'eac_edit_customer' ); ?>
	<input type="hidden" name="action" value="eac_edit_customer"/>
	<input type="hidden" name="id" value="<?php echo esc_attr( $customer->id ); ?>"/>
</form>
