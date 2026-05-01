<?php


use Otto\Models\Account;

defined( 'ABSPATH' ) || exit;

$id      = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT );
$account = Account::make( $id );

?>
<div class="eac-section-header">
	<h1 class="wp-heading-inline">
		<?php if ( $account->exists() ) : ?>
			<?php esc_html_e( 'Edit Account', 'otto-contracts' ); ?>
			<a href="<?php echo esc_attr( admin_url( 'admin.php?page=eac-banking&tab=accounts&action=add' ) ); ?>" class="button button-small">
				<?php esc_html_e( 'Add New', 'otto-contracts' ); ?>
			</a>
		<?php else : ?>
			<?php esc_html_e( 'Add Account', 'otto-contracts' ); ?>
		<?php endif; ?>
		<a href="<?php echo esc_attr( remove_query_arg( array( 'action', 'id' ) ) ); ?>" title="<?php esc_attr_e( 'Go back', 'otto-contracts' ); ?>">
			<span class="dashicons dashicons-undo"></span>
		</a>
	</h1>
</div>

<form id="eac-edit-account" name="account" method="post" action="<?php echo esc_html( admin_url( 'admin-post.php' ) ); ?>">
	<div class="eac-poststuff">
		<div class="column-1">
			<div class="eac-card">
				<div class="eac-card__header">
					<h2 class="eac-card__title"><?php esc_html_e( 'Account Attributes', 'otto-contracts' ); ?></h2>
				</div>

				<div class="eac-card__body grid--fields">
					<?php
					eac_form_field(
						array(
							'label'       => __( 'Name', 'otto-contracts' ),
							'type'        => 'text',
							'name'        => 'name',
							'value'       => $account->name,
							'placeholder' => __( 'XYZ Saving Account', 'otto-contracts' ),
							'required'    => true,
						)
					);

					eac_form_field(
						array(
							'label'       => __( 'Number', 'otto-contracts' ),
							'type'        => 'text',
							'name'        => 'number',
							'value'       => $account->number,
							'placeholder' => __( '1234567890', 'otto-contracts' ),
							'required'    => true,
						)
					);

					eac_form_field(
						array(
							'label'       => __( 'Type', 'otto-contracts' ),
							'type'        => 'select',
							'name'        => 'type',
							'value'       => $account->type,
							'options'     => EAC()->accounts->get_types(),
							'placeholder' => __( 'Select Type', 'otto-contracts' ),
							'required'    => true,
						)
					);

					eac_form_field(
						array(
							'label'        => __( 'Currency', 'otto-contracts' ),
							'type'         => 'select',
							'name'         => 'currency',
							'value'        => $account->currency,
							'default'      => eac_base_currency(),
							'class'        => 'eac_select2',
							'options'      => eac_get_currencies(),
							'option_label' => 'formatted_name',
							'option_value' => 'code',
							'placeholder'  => __( 'Select Currency', 'otto-contracts' ),
							'required'     => true,
						)
					);
					?>
				</div><!-- .eac-card__body -->
			</div>
			<?php
			
			do_action( 'eac_account_edit_core_content', $account );
			?>
		</div><!-- .column-1 -->
		<div class="column-2">
			<div class="eac-card">
				<div class="eac-card__header">
					<h3 class="eac-card__title"><?php esc_html_e( 'Actions', 'otto-contracts' ); ?></h3>
				</div>
				<div class="eac-card__body">
					<?php
					
					do_action( 'eac_account_edit_misc_actions', $account );
					?>
				</div>
				<div class="eac-card__footer">
					<?php if ( $account->exists() ) : ?>
						<a class="del del_confirm" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'action', 'delete', $account->get_edit_url() ), 'bulk-accounts' ) ); ?>">
							<?php esc_html_e( 'Delete', 'otto-contracts' ); ?>
						</a>
						<button class="button button-primary"><?php esc_html_e( 'Update Account', 'otto-contracts' ); ?></button>
					<?php else : ?>
						<button class="button button-primary button-large button-block"><?php esc_html_e( 'Add Account', 'otto-contracts' ); ?></button>
					<?php endif; ?>
				</div>
			</div><!-- .eac-card -->

			<?php
			
			do_action( 'eac_account_edit_sidebar_content', $account );
			?>

		</div><!-- .column-2 -->
	</div>


	<?php wp_nonce_field( 'eac_edit_account' ); ?>
	<input type="hidden" name="action" value="eac_edit_account"/>
	<input type="hidden" name="id" value="<?php echo esc_attr( $account->id ); ?>"/>
</form>
