<?php


use Otto\Models\Payment;

defined( 'ABSPATH' ) || exit;

$id      = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT );
$payment = EAC()->payments->get( $id );

?>

<h1 class="wp-heading-inline">
	<?php esc_html_e( 'View Payment', 'otto-contracts' ); ?>
	<?php if ( current_user_can( 'eac_edit_payments' ) ) : ?>
		<a href="<?php echo esc_attr( admin_url( 'admin.php?page=eac-sales&tab=payments&action=add' ) ); ?>" class="button button-small">
			<?php esc_html_e( 'Add New', 'otto-contracts' ); ?>
		</a>
	<?php endif; ?>
	<a href="<?php echo esc_attr( remove_query_arg( array( 'action', 'id' ) ) ); ?>" title="<?php esc_attr_e( 'Go back', 'otto-contracts' ); ?>">
		<span class="dashicons dashicons-undo"></span>
	</a>
</h1>

<div class="eac-poststuff">

	<div class="column-1">
		<div class="eac-card">
			<?php eac_get_template( 'content-payment.php', array( 'payment' => $payment ) ); ?>
		</div>
		<?php
		
		do_action( 'eac_payment_edit_core_content', $payment );
		?>
	</div>

	<div class="column-2">
		<div class="eac-card">
			<div class="eac-card__header">
				<h2 class="eac-card__title"><?php esc_html_e( 'Actions', 'otto-contracts' ); ?></h2>
				<?php if ( $payment->editable && current_user_can( 'eac_edit_payments' ) ) : ?>
					<a href="<?php echo esc_url( $payment->get_edit_url() ); ?>">
						<?php esc_html_e( 'Edit', 'otto-contracts' ); ?>
					</a>
				<?php endif; ?>
			</div>
			<div class="eac-card__body">
				<?php
				
				do_action( 'eac_payment_view_misc_actions', $payment );
				?>
				<a href="#" class="button button-small button-block eac_print_document" data-target=".eac-document">
					<span class="dashicons dashicons-printer"></span> <?php esc_html_e( 'Print', 'otto-contracts' ); ?>
				</a>
			</div>
			<div class="eac-card__footer">
				<?php if ( current_user_can( 'eac_delete_payments' ) ) : ?>
					<a class="del del_confirm" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'action', 'delete', $payment->get_edit_url() ), 'bulk-payments' ) ); ?>">
						<?php esc_html_e( 'Delete', 'otto-contracts' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>

		<?php
		
		do_action( 'eac_payment_view_sidebar_content', $payment );
		?>

	</div><!-- .column-2 -->

</div><!-- .eac-poststuff -->

