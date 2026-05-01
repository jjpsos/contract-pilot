<?php


use Otto\Models\Invoice;

defined( 'ABSPATH' ) || exit;

$id            = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT );
$invoice       = EAC()->invoices->get( $id );
$mark_sent_url = wp_nonce_url(
	add_query_arg(
		array(
			'action' => 'eac_invoice_mark_sent',
			'id'     => $invoice->id,
		),
		admin_url( 'admin-post.php' )
	),
	'eac_invoice_action'
);
$mark_accept_url = wp_nonce_url(
	add_query_arg(
		array(
			'action' => 'eac_invoice_mark_accept',
			'id'     => $invoice->id,
		),
		admin_url( 'admin-post.php' )
	),
	'eac_invoice_action'
);

$eac_invoice_heading_sl = eac_invoice_heading_status_label( $invoice );
$eac_view_title         = '' !== $eac_invoice_heading_sl
	? sprintf(
		/* translators: %s: status label, e.g. Contract/Draft */
		__( 'View %s', 'otto-contracts' ),
		$eac_invoice_heading_sl
	)
	: __( 'View Contract', 'otto-contracts' );

?>
<h1 class="wp-heading-inline">
	<?php echo esc_html( $eac_view_title ); ?>
	<?php if ( current_user_can( 'eac_edit_invoices' ) ) : ?>
		<a href="<?php echo esc_attr( admin_url( 'admin.php?page=eac-sales&tab=invoices&action=add' ) ); ?>" class="button button-small">
			<?php esc_html_e( 'Add New', 'otto-contracts' ); ?>
		</a>
	<?php endif; ?>
	<a href="<?php echo esc_attr( remove_query_arg( array( 'action', 'id' ) ) ); ?>" title="<?php esc_attr_e( 'Go back', 'otto-contracts' ); ?>">
		<span class="dashicons dashicons-undo"></span>
	</a>
</h1>

<div class="eac-poststuff">

	<div class="column-1">
		<div class="eac-card"><?php eac_get_template( 'content-invoice.php', array( 'invoice' => $invoice ) ); ?></div>
		<?php
		
		do_action( 'eac_invoice_edit_core_content', $invoice );
		?>
	</div>

	<div class="column-2">
		<div class="eac-card">
			<div class="eac-card__header">
				<h2 class="eac-card__title"><?php esc_html_e( 'Actions', 'otto-contracts' ); ?></h2>
				<?php if ( $invoice->editable && current_user_can( 'eac_edit_invoices' ) ) : ?>
					<a href="<?php echo esc_url( $invoice->get_edit_url() ); ?>">
						<?php
						echo esc_html(
							'' !== $eac_invoice_heading_sl
								? sprintf(
									/* translators: %s: status label */
									__( 'Edit %s', 'otto-contracts' ),
									$eac_invoice_heading_sl
								)
								: __( 'Edit', 'otto-contracts' )
						);
						?>
					</a>
				<?php endif; ?>
			</div>

			<div class="eac-card__body">
				<?php if ( $invoice->is_status( 'draft' ) && current_user_can( 'eac_edit_invoices' ) ) : ?>
					<a href="<?php echo esc_url( $mark_sent_url ); ?>" class="button button-primary button-small button-block">
						<span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Mark Sent', 'otto-contracts' ); ?>
					</a>
				<?php elseif ( in_array( $invoice->status, array( 'sent', 'overdue' ), true ) && ! $invoice->is_paid() && current_user_can( 'eac_edit_invoices' ) ) : ?>
					<a href="<?php echo esc_url( $mark_accept_url ); ?>" class="button button-primary button-small button-block">
						<span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'Mark Accept', 'otto-contracts' ); ?>
					</a>
					<a href="#" class="button button-primary button-small button-block eac-add-invoice-payment" data-id="<?php echo esc_attr( $invoice->id ); ?>">
						<span class="dashicons dashicons-money-alt"></span> <?php esc_html_e( 'Add Payment', 'otto-contracts' ); ?>
					</a>
				<?php elseif ( ! $invoice->is_status( 'draft' ) && ! $invoice->is_paid() && current_user_can( 'eac_edit_invoices' ) ) : ?>
					<a href="#" class="button button-primary button-small button-block eac-add-invoice-payment" data-id="<?php echo esc_attr( $invoice->id ); ?>">
						<span class="dashicons dashicons-money-alt"></span> <?php esc_html_e( 'Add Payment', 'otto-contracts' ); ?>
					</a>
				<?php endif; ?>
				<a href="#" class="button button-small button-block eac_print_document" data-target=".eac-document">
					<span class="dashicons dashicons-printer"></span> <?php esc_html_e( 'Print', 'otto-contracts' ); ?>
				</a>

				<?php
				
				do_action( 'eac_invoice_view_misc_actions', $invoice );
				?>
			</div>

			<div class="eac-card__footer">
				<?php if ( current_user_can( 'eac_delete_invoices' ) ) : ?>
					<a class="del del_confirm" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'action', 'delete', $invoice->get_edit_url() ), 'bulk-contracts' ) ); ?>"><?php esc_html_e( 'Delete', 'otto-contracts' ); ?></a>
				<?php endif; ?>
			</div>
		</div>

		<div class="eac-card">
			<div class="eac-card__header">
				<h3 class="eac-card__title"><?php esc_html_e( 'Attachment', 'otto-contracts' ); ?></h3>
			</div>
			<div class="eac-card__body">
				<?php
				eac_file_uploader(
					array(
						'value'    => $invoice->attachment_id,
						'readonly' => true,
					)
				);
				?>
			</div>
		</div>

		<?php
		
		do_action( 'eac_invoice_view_sidebar_content', $invoice );
		?>

	</div><!-- .column-2 -->

</div><!-- .eac-poststuff -->

<script type="text/html" id="tmpl-eac-invoice-payment">
	<form>
		<div class="eac-modal-header">
			<h3><?php esc_html_e( 'Add Contract Payment', 'otto-contracts' ); ?></h3>
		</div>

		<div class="eac-modal-body">
			<div class="eac-form-field">
				<label for="payment_date"><?php esc_html_e( 'Payment Date', 'otto-contracts' ); ?>&nbsp;<abbr title="required"></abbr></label>
				<input type="text" name="payment_date" id="payment_date" value="<?php echo esc_attr( eac_format_datetime() ); ?>" class="eac_datetimepicker" required>
			</div>
			<div class="eac-form-field">
				<label for="account_id"><?php esc_html_e( 'Account', 'otto-contracts' ); ?>&nbsp;<abbr title="required"></abbr></label>
				<select name="account_id" id="account_id" class="eac_select2 account_id" data-action="eac_json_search" data-type="account" data-placeholder="<?php esc_html_e( 'Select an account', 'otto-contracts' ); ?>" required>
					<option value=""><?php esc_html_e( 'Select an account', 'otto-contracts' ); ?></option>
				</select>
			</div>
			<div class="eac-form-field">
				<label for="exchange_rate"><?php esc_html_e( 'Exchange Rate', 'otto-contracts' ); ?>&nbsp;<abbr title="required"></abbr></label>
				<input type="text" name="exchange_rate" id="exchange_rate" value="1.00" class="eac_exchange_rate" data-currency="<?php echo esc_attr( $invoice->currency ); ?>" required>
			</div>

			<div class="eac-form-field">
				<label for="amount"><?php esc_html_e( 'Amount', 'otto-contracts' ); ?>&nbsp;<abbr title="required"></abbr></label>
				<input type="text" name="amount" id="amount" class="eac_amount" value="<?php echo esc_attr( $invoice->get_due_amount() ); ?>" data-currncy="<?php echo esc_attr( $invoice->currency ); ?>" required>
			</div>

			<div class="eac-form-field">
				<label for="payment_method"><?php esc_html_e( 'Payment Method', 'otto-contracts' ); ?></label>
				<select name="payment_method" id="payment_method">
					<option value=""><?php esc_html_e( 'Select Payment Method', 'otto-contracts' ); ?></option>
					<?php foreach ( eac_get_payment_methods() as $key => $value ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $value ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="eac-form-field">
				<label for="reference"><?php esc_html_e( 'Reference', 'otto-contracts' ); ?></label>
				<input type="text" name="reference" id="reference">
			</div>

			<div class="eac-form-field">
				<label for="note"><?php esc_html_e( 'Description', 'otto-contracts' ); ?></label>
				<textarea name="note" id="note" rows="3"></textarea>
			</div>
		</div>

		<div class="eac-modal-footer">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Add Payment', 'otto-contracts' ); ?></button>
			<button type="button" class="button" data-modal-close><?php esc_html_e( 'Cancel', 'otto-contracts' ); ?></button>
		</div>

		<input type="hidden" name="currency" value="<?php echo esc_attr( $invoice->currency ); ?>">
		<input type="hidden" name="invoice_id" value="<?php echo esc_attr( $invoice->id ); ?>">
		<input type="hidden" name="customer_id" value="<?php echo esc_attr( $invoice->contact_id ); ?>">
	</form>
</script>
