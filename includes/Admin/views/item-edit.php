<?php


use Otto\Models\Item;

defined( 'ABSPATH' ) || exit;

$id   = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT );
$item = Item::make( $id );
?>

<h1 class="wp-heading-inline">
	<?php if ( $item->exists() ) : ?>
		<?php esc_html_e( 'Edit Service', 'otto-contracts' ); ?>
		<?php if ( current_user_can( 'eac_edit_items' ) ) : ?>
			<a href="<?php echo esc_attr( admin_url( 'admin.php?page=eac-items&action=add' ) ); ?>" class="button button-small">
				<?php esc_html_e( 'Add New', 'otto-contracts' ); ?>
			</a>
		<?php endif; ?>
	<?php else : ?>
		<?php esc_html_e( 'Add Service', 'otto-contracts' ); ?>
	<?php endif; ?>
	<a href="<?php echo esc_attr( remove_query_arg( array( 'action', 'id' ) ) ); ?>" title="<?php esc_attr_e( 'Go back', 'otto-contracts' ); ?>">
		<span class="dashicons dashicons-undo"></span>
	</a>
</h1>

<form id="eac-edit-item" name="item" method="post" action="<?php echo esc_html( admin_url( 'admin-post.php' ) ); ?>">
	<div class="eac-poststuff">

		<div class="column-1">
			<div id="eac-item-data" class="eac-card">
				<div class="eac-card__header">
					<h3 class="eac-card__title"><?php esc_html_e( 'Service Attributes', 'otto-contracts' ); ?></h3>
				</div>
				<div class="eac-card__body grid--fields">
					<?php
					eac_form_field(
						array(
							'label'       => __( 'Name', 'otto-contracts' ),
							'type'        => 'text',
							'name'        => 'name',
							'value'       => $item->name,
							'placeholder' => __( 'Laptop', 'otto-contracts' ),
							'required'    => true,
						)
					);
					eac_form_field(
						array(
							'type'     => 'select',
							'name'     => 'type',
							'required' => true,
							'default'  => 'product',
							'label'    => __( 'Type', 'otto-contracts' ),
							'value'    => $item->type,
							'options'  => EAC()->items->get_types(),
							'tooltip'  => __( 'Select the item type: Standard for regular products eligible for discounts, or Fee for extra charges that do not support discounts.', 'otto-contracts' ),
						)
					);
					eac_form_field(
						array(
							'type'          => 'text',
							'name'          => 'price',
							'label'         => __( 'Price', 'otto-contracts' ),
							'value'         => $item->price,
							'placeholder'   => __( '10.00', 'otto-contracts' ),
							
							'tooltip'       => sprintf( __( 'Enter the price of the item in %s.', 'otto-contracts' ), eac_base_currency() ),
							'class'         => 'eac_amount',
							'data-currency' => eac_base_currency(),
						)
					);
					eac_form_field(
						array(
							'type'          => 'text',
							'name'          => 'cost',
							'label'         => __( 'Cost', 'otto-contracts' ),
							'value'         => $item->cost,
							'placeholder'   => __( '8.00', 'otto-contracts' ),
							
							'tooltip'       => sprintf( __( 'Enter the cost of the item in %s.', 'otto-contracts' ), eac_base_currency() ),
							'class'         => 'eac_amount',
							'data-currency' => eac_base_currency(),
						)
					);
					eac_form_field(
						array(
							'type'             => 'select',
							'name'             => 'category_id',
							'label'            => __( 'Category', 'otto-contracts' ),
							'value'            => $item->category_id,
							'options'          => array( $item->category ),
							'option_label'     => 'formatted_name',
							'option_value'     => 'id',
							'data-placeholder' => __( 'Select item category', 'otto-contracts' ),
							'class'            => 'eac_select2',
							'data-action'      => 'eac_json_search',
							'data-type'        => 'category',
							'data-subtype'     => 'item',
							'suffix'           => sprintf(
								'<a class="addon" href="%s" target="_blank" title="%s"><span class="dashicons dashicons-plus"></span></a>',
								esc_url( 'admin.php?page=eac-settings&tab=categories&action=add' ),
								__( 'Add Category', 'otto-contracts' )
							),
						)
					);
					eac_form_field(
						array(
							'type'        => 'select',
							'name'        => 'unit',
							'label'       => __( 'Unit', 'otto-contracts' ),
							'value'       => $item->unit,
							'options'     => EAC()->items->get_units(),
							'placeholder' => __( 'Select unit', 'otto-contracts' ),
							'class'       => 'eac-select2',
						)
					);
					
					eac_form_field(
						array(
							'type'         => 'select',
							'multiple'     => true,
							'name'         => 'tax_ids',
							'label'        => __( 'Taxes', 'otto-contracts' ),
							'value'        => $item->tax_ids,
							'options'      => $item->taxes,
							'option_label' => 'formatted_name',
							'option_value' => 'id',
							'class'        => 'eac_select2',
							'data-action'  => 'eac_json_search',
							'data-type'    => 'tax',
							'tooltip'      => __( 'The selected tax rates will be applied to this item.', 'otto-contracts' ),
							'suffix'       => sprintf(
								'<a class="addon" href="%s" target="_blank" title="%s"><span class="dashicons dashicons-plus"></span></a>',
								esc_url( 'admin.php?page=eac-settings&tab=taxes&section=rates&action=add' ),
								__( 'Add Tax', 'otto-contracts' )
							),
						)
					);

					eac_form_field(
						array(
							'type'          => 'textarea',
							'name'          => 'description',
							'label'         => __( 'Description', 'otto-contracts' ),
							'value'         => $item->description,
							'wrapper_class' => 'is--full',
						)
					);
					?>
				</div>
			</div><!-- .eac-card -->

			<?php
			
			do_action( 'eac_item_edit_core_content', $item );
			?>
		</div><!-- .column-1 -->

		<div class="column-2">
			<div id="eac-item-actions" class="eac-card">
				<div class="eac-card__header">
					<h3 class="eac-card__title"><?php esc_html_e( 'Actions', 'otto-contracts' ); ?></h3>
				</div>
				<?php if ( has_action( 'eac_item_edit_misc_actions' ) ) : ?>
					<div class="eac-card__body">
						<?php
						
						do_action( 'eac_item_edit_misc_actions', $item );
						?>
					</div>
				<?php endif; ?>
				<div class="eac-card__footer">
					<?php if ( $item->exists() && current_user_can( 'eac_delete_items' ) ) : ?>
						<a class="del del_confirm" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'action', 'delete', $item->get_edit_url() ), 'bulk-services' ) ); ?>"><?php esc_html_e( 'Delete', 'otto-contracts' ); ?></a>
						<button class="button button-primary"><?php esc_html_e( 'Update', 'otto-contracts' ); ?></button>
					<?php elseif ( current_user_can( 'eac_edit_items' ) ) : ?>
						<button class="button button-primary button-block"><?php esc_html_e( 'Save', 'otto-contracts' ); ?></button>
					<?php endif; ?>
				</div>
			</div><!-- .eac-card -->

			<?php
			
			do_action( 'eac_item_edit_sidebar_content', $item );
			?>

		</div><!-- .column-2 -->

	</div><!-- .eac-poststuff -->
	<?php wp_nonce_field( 'eac_edit_item' ); ?>
	<input type="hidden" name="action" value="eac_edit_item"/>
	<input type="hidden" name="id" value="<?php echo esc_attr( $item->id ); ?>"/>
</form>
