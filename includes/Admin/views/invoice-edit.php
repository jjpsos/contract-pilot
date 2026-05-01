<?php


use Otto\Models\Invoice;

defined( 'ABSPATH' ) || exit;

$id      = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT );
$invoice = Invoice::make( $id );
$columns = EAC()->invoices->get_columns();

if ( ! $invoice->is_taxed() ) {
	unset( $columns['tax'] );
}

$eac_invoice_heading_sl = $invoice->exists() ? eac_invoice_heading_status_label( $invoice ) : '';
$eac_edit_title         = '';
if ( $invoice->exists() ) {
	$eac_edit_title = '' !== $eac_invoice_heading_sl
		? sprintf(
			/* translators: %s: status label, e.g. Contract/Draft */
			__( 'Edit %s', 'otto-contracts' ),
			$eac_invoice_heading_sl
		)
		: __( 'Edit Contract', 'otto-contracts' );
}

defined( 'ABSPATH' ) || exit;
?>
<h1 class="wp-heading-inline">
	<?php if ( $invoice->exists() ) : ?>
		<?php echo esc_html( $eac_edit_title ); ?>
	<?php else : ?>
		<?php esc_html_e( 'Add Contract', 'otto-contracts' ); ?>
	<?php endif; ?>
	<a href="<?php echo esc_attr( remove_query_arg( array( 'action', 'id' ) ) ); ?>" title="<?php esc_attr_e( 'Go back', 'otto-contracts' ); ?>">
		<span class="dashicons dashicons-undo"></span>
	</a>
</h1>

<form id="eac-edit-invoice" name="invoice" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<div class="eac-poststuff">

		<div class="column-1">
			<div class="eac-card eac-document-overview">
				<div class="eac-card__faked document-details tw-grid tw-grid-cols-2 tw-gap-x-[15px]">
					<div class="">
						<?php
						eac_form_field(
							array(
								'label'            => __( 'Client', 'otto-contracts' ),
								'type'             => 'select',
								'name'             => 'contact_id',
								'options'          => array( $invoice->customer ),
								'value'            => $invoice->customer_id,
								'required'         => true,
								'class'            => 'eac_select2',
								'option_value'     => 'id',
								'option_label'     => 'formatted_name',
								'data-placeholder' => __( 'Select a client', 'otto-contracts' ),
								'data-action'      => 'eac_json_search',
								'data-type'        => 'customer',
							)
						);
						?>

						<div class="document-address">
							<?php require __DIR__ . '/invoice-address.php'; ?>
						</div>

					</div>

					<div class="tw-grid xs:tw-grid-cols-1 tw-grid-cols-2 tw-gap-x-[15px]">
						<?php
						eac_form_field(
							array(
								'label'             => esc_html__( 'Issue Date', 'otto-contracts' ),
								'name'              => 'issue_date',
								'type'              => 'text',
								'default'           => eac_format_datetime(),
								'value'             => eac_format_datetime( $invoice->issue_date ),
								'placeholder'       => 'YYYY-MM-DD',
								'required'          => true,
								'class'             => 'eac_datetimepicker',
								'attr-autocomplete' => 'off',
							)
						);
						eac_form_field(
							array(
								'label'             => esc_html__( 'Contract Number', 'otto-contracts' ),
								'name'              => 'number',
								'value'             => $invoice->number,
								'default'           => $invoice->get_next_number(),
								'type'              => 'text',
								'placeholder'       => 'INV-0001',
								'required'          => true,
								'readonly'          => true,
								'attr-autocomplete' => 'off',
							)
						);
						eac_form_field(
							array(
								'label'             => esc_html__( 'Due Date', 'otto-contracts' ),
								'name'              => 'due_date',
								'type'              => 'text',
								'default'           => eac_format_datetime(),
								'value'             => eac_format_datetime( $invoice->due_date ),
								'placeholder'       => 'YYYY-MM-DD',
								'class'             => 'eac_datetimepicker',
								'attr-autocomplete' => 'off',
							)
						);
						eac_form_field(
							array(
								'label'             => esc_html__( 'Order Number', 'otto-contracts' ),
								'name'              => 'order_number',
								'value'             => $invoice->reference,
								'type'              => 'text',
								'placeholder'       => 'REF-0001',
								'attr-autocomplete' => 'off',
							)
						);
						eac_form_field(
							array(
								'label'           => esc_html__( 'Currency', 'otto-contracts' ),
								'name'            => 'currency',
								'default'         => eac_base_currency(),
								'value'           => $invoice->currency,
								'type'            => 'select',
								'options'         => eac_get_currencies(),
								'option_value'    => 'code',
								'option_label'    => 'formatted_name',
								'placeholder'     => esc_html__( 'Select a currency', 'otto-contracts' ),
								'class'           => 'eac_select2',
								'data-allowClear' => 'false',
								'required'        => true,
							)
						);
						eac_form_field(
							array(
								'label'         => __( 'Exchange Rate', 'otto-contracts' ),
								'name'          => 'exchange_rate',
								'value'         => $invoice->exchange_rate,
								'default'       => 1,
								'placeholder'   => '1.00',
								'required'      => true,
								'prefix'        => '1 ' . eac_base_currency() . ' = ',
								'class'         => 'eac_exchange_rate',
								'attr-step'     => 'any',
								'readonly'      => eac_base_currency() === $invoice->currency,
								'data-currency' => $invoice->currency,
								'data-source'   => ':input[name="currency"]',
							)
						);
						?>
					</div>
				</div>

				<div class="document-items">
					<table class="eac-document-items">
						<thead class="eac-document-items__head">
						<tr>
							<?php foreach ( $columns as $key => $label ) : ?>
								<th class="col-<?php echo esc_attr( $key ); ?>">
									<?php echo esc_html( $label ); ?>
								</th>
							<?php endforeach; ?>
						</tr>
						</thead>
						<tbody class="eac-document-items__items">
						<?php require __DIR__ . '/invoice-items.php'; ?>
						</tbody>
						<tbody class="eac-document-items__toolbar">
						<tr>
							<td colspan="<?php echo esc_attr( count( $columns ) ); ?>">
								<select class="add-item eac_select2" data-action="eac_json_search" data-type="item" data-placeholder="<?php esc_attr_e( 'Select a service', 'otto-contracts' ); ?>"></select>
							</td>
						</tr>
						</tbody>
						<tfoot class="eac-document-items__totals">
						<?php require __DIR__ . '/invoice-totals.php'; ?>
						</tfoot>
					</table>
				</div><!-- .document-items -->

				<div class="document-footer">
					<?php
					eac_form_field(
						array(
							'label'       => __( 'Notes', 'otto-contracts' ),
							'name'        => 'note',
							'value'       => $invoice->note,
							'default'     => get_option( 'eac_invoice_note', '' ),
							'type'        => 'textarea',
							'placeholder' => __( 'Add notes', 'otto-contracts' ),
						)
					);

					
					eac_form_field(
						array(
							'label'       => __( 'Terms', 'otto-contracts' ),
							'name'        => 'terms',
							'value'       => $invoice->terms,
							'default'     => get_option( 'eac_invoice_terms', '' ),
							'type'        => 'textarea',
							'placeholder' => __( 'Add terms', 'otto-contracts' ),
						)
					);

					?>
				</div>

			</div>


			<?php
			
			do_action( 'eac_invoice_edit_core_content', $invoice );
			?>

		</div><!-- .column-1 -->

		<div class="column-2">
			<div class="eac-card">
				<div class="eac-card__header">
					<h3 class="eac-card__title"><?php esc_html_e( 'Actions', 'otto-contracts' ); ?></h3>
					<?php if ( $invoice->exists() ) : ?>
						<a href="<?php echo esc_url( $invoice->get_view_url() ); ?>">
							<?php esc_html_e( 'View', 'otto-contracts' ); ?>
						</a>
					<?php endif; ?>
				</div>
				<div class="eac-card__body">
					<?php
					$status_options = EAC()->invoices->get_statuses();
					if ( $invoice->status && ! isset( $status_options[ $invoice->status ] ) ) {
						$status_options[ $invoice->status ] = $invoice->status;
					}
					eac_form_field(
						array(
							'label'         => esc_html__( 'Status', 'otto-contracts' ),
							'name'          => 'status',
							'id'            => 'eac-invoice-status',
							'type'          => 'select',
							'options'       => $status_options,
							'value'         => $invoice->status,
							'class'         => 'widefat',
							'wrapper_class' => 'eac-invoice-status-field',
						)
					);
					$eac_status_label_display = isset( $invoice->status_label ) ? trim( (string) $invoice->status_label ) : '';
					if ( '' === $eac_status_label_display && $invoice->status ) {
						$eac_status_label_display = eac_invoice_status_label_for_status( $invoice->status );
					}
					?>
					<div class="eac-form-field eac-invoice-status-label-readonly" style="margin-top:12px;">
						<strong id="eac-invoice-status-label-heading" class="eac-field-label" style="display:block;margin-bottom:4px;font-size:11px;text-transform:uppercase;color:#646970;"><?php esc_html_e( 'Status label', 'otto-contracts' ); ?></strong>
						<p id="eac-invoice-status-label-display" class="description" style="margin:0;font-size:13px;" aria-labelledby="eac-invoice-status-label-heading">
							<?php echo '' !== $eac_status_label_display ? esc_html( $eac_status_label_display ) : '&mdash;'; ?>
						</p>
					</div>
					<script>
					(function () {
						var sel = document.getElementById('eac-invoice-status');
						var out = document.getElementById('eac-invoice-status-label-display');
						if (!sel || !out) { return; }
						var map = <?php echo wp_json_encode( eac_invoice_status_label_map() ); ?>;
						function sync() {
							var v = sel.value || '';
							var lbl = map[v] || map[v.toLowerCase()] || '';
							out.textContent = lbl || '\u2014';
						}
						sel.addEventListener('change', sync);
						sync();
					}());
					</script>
					<?php
					do_action( 'eac_invoice_edit_misc_actions', $invoice );
					?>
				</div>
				<div class="eac-card__footer">
					<?php if ( $invoice->exists() ) : ?>
						<a class="del del_confirm" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'action', 'delete', $invoice->get_edit_url() ), 'bulk-contracts' ) ); ?>">
							<?php esc_html_e( 'Delete', 'otto-contracts' ); ?>
						</a>
						<button class="button button-primary"><?php esc_html_e( 'Update', 'otto-contracts' ); ?></button>
					<?php else : ?>
						<button class="button button-primary button-block"><?php esc_html_e( 'Save', 'otto-contracts' ); ?></button>
					<?php endif; ?>
				</div>
			</div><!-- .eac-card -->

			<div class="eac-card">
				<div class="eac-card__header">
					<h3 class="eac-card__title"><?php esc_html_e( 'Attachment', 'otto-contracts' ); ?></h3>
				</div>
				<div class="eac-card__body">
					<?php eac_file_uploader( array( 'value' => $invoice->attachment_id ) ); ?>
				</div>
			</div>

			<?php
			
			do_action( 'eac_invoice_edit_sidebar_content', $invoice );
			?>

		</div><!-- .column-2 -->
	</div><!-- .eac-poststuff -->

	<input type="hidden" name="action" value="eac_edit_invoice"/>
	<input type="hidden" name="id" value="<?php echo esc_attr( $invoice->id ); ?>"/>
	<?php wp_nonce_field( 'eac_edit_invoice' ); ?>
</form>
