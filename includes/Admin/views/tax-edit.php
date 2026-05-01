<?php


use Otto\Models\Tax;

defined( 'ABSPATH' ) || exit;

$id  = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT );
$tax = Tax::make( $id );

?>
<h1 class="wp-heading-inline">
	<?php if ( $tax->exists() ) : ?>
		<?php esc_html_e( 'Edit Rate', 'otto-contracts' ); ?>
		<a href="<?php echo esc_attr( admin_url( 'admin.php?page=eac-settings&tab=taxes&section=rates&action=add' ) ); ?>" class="button button-small">
			<?php esc_html_e( 'Add New', 'otto-contracts' ); ?>
		</a>
	<?php else : ?>
		<?php esc_html_e( 'Add Rate', 'otto-contracts' ); ?>
	<?php endif; ?>
	<a href="<?php echo esc_attr( remove_query_arg( array( 'action', 'id' ) ) ); ?>" title="<?php esc_attr_e( 'Go back', 'otto-contracts' ); ?>">
		<span class="dashicons dashicons-undo"></span>
	</a>
</h1>

<form id="eac-edit-tax" name="tax" method="post" action="<?php echo esc_html( admin_url( 'admin-post.php' ) ); ?>">
	<div class="eac-poststuff">
		<div class="column-1">

			<div class="eac-card">
				<div class="eac-card__header">
					<h2 class="eac-card__title"><?php esc_html_e( 'Tax Attributes', 'otto-contracts' ); ?></h2>
				</div>
				<div class="eac-card__body grid--fields">
					<?php
					eac_form_field(
						array(
							'id'          => 'name',
							'label'       => __( 'Name', 'otto-contracts' ),
							'placeholder' => __( 'Enter tax rate name', 'otto-contracts' ),
							'value'       => $tax->name,
							'required'    => true,
						)
					);
					eac_form_field(
						array(
							'data_type'   => 'decimal',
							'id'          => 'rate',
							'label'       => __( 'Rate (%)', 'otto-contracts' ),
							'placeholder' => __( 'Enter tax rate', 'otto-contracts' ),
							'value'       => $tax->rate,
							'required'    => true,
							'type'        => 'number',
							'attr-step'   => 'any',
						)
					);

					eac_form_field(
						array(
							'id'       => 'compound',
							'label'    => __( 'Compound', 'otto-contracts' ),
							'value'    => filter_var( $tax->compound, FILTER_VALIDATE_BOOLEAN ) ? 'yes' : 'no',
							'required' => true,
							'options'  => array(
								'yes' => __( 'Yes', 'otto-contracts' ),
								'no'  => __( 'No', 'otto-contracts' ),
							),
							'type'     => 'select',
						)
					);
					?>
				</div>
			</div>

			<?php
			
			do_action( 'eac_tax_edit_core_content', $tax );
			?>
		</div><!-- .column-1 -->

		<div class="column-2">
			<div id="eac-tax-actions" class="eac-card">
				<div class="eac-card__header">
					<h3 class="eac-card__title"><?php esc_html_e( 'Actions', 'otto-contracts' ); ?></h3>
				</div>
				<div class="eac-card__footer">
					<?php if ( $tax->exists() ) : ?>
						<a class="del del_confirm" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'action', 'delete', $tax->get_edit_url() ), 'bulk-taxes' ) ); ?>"><?php esc_html_e( 'Delete', 'otto-contracts' ); ?></a>
						<button class="button button-primary"><?php esc_html_e( 'Update Tax', 'otto-contracts' ); ?></button>
					<?php else : ?>
						<button class="button button-primary button-block"><?php esc_html_e( 'Add Tax', 'otto-contracts' ); ?></button>
					<?php endif; ?>
				</div>
			</div><!-- .eac-card -->

			<?php
			
			do_action( 'eac_tax_edit_sidebar_content', $tax );
			?>

		</div><!-- .column-2 -->

	</div><!-- .eac-poststuff -->
	<?php wp_nonce_field( 'eac_edit_tax' ); ?>
	<input type="hidden" name="action" value="eac_edit_tax"/>
	<input type="hidden" name="id" value="<?php echo esc_attr( $tax->id ); ?>"/>
</form>
