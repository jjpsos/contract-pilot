<?php


use Otto\Models\Expense;

defined( 'ABSPATH' ) || exit;

$id      = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT );
$expense = Expense::make( $id );

?>
<h1 class="wp-heading-inline">
	<?php if ( $expense->exists() ) : ?>
		<?php esc_html_e( 'Edit Expense', 'otto-contracts' ); ?>
		<a href="<?php echo esc_attr( admin_url( 'admin.php?page=eac-purchases&tab=expenses&action=add' ) ); ?>" class="button button-small">
			<?php esc_html_e( 'Add New', 'otto-contracts' ); ?>
		</a>
	<?php else : ?>
		<?php esc_html_e( 'Add Expense', 'otto-contracts' ); ?>
	<?php endif; ?>
	<a href="<?php echo esc_attr( remove_query_arg( array( 'action', 'id' ) ) ); ?>" title="<?php esc_attr_e( 'Go back', 'otto-contracts' ); ?>">
		<span class="dashicons dashicons-undo"></span>
	</a>
</h1>

<form id="eac-edit-expense" name="expense" method="post" action="<?php echo esc_html( admin_url( 'admin-post.php' ) ); ?>">

	<div class="eac-poststuff">
		<div class="column-1">
			<div class="eac-card">
				<div class="eac-card__header">
					<h3 class="eac-card__title"><?php esc_html_e( 'Expense Attributes', 'otto-contracts' ); ?></h3>
				</div>
				<div class="eac-card__body grid--fields">
					<?php
					eac_form_field(
						array(
							'label'       => __( 'Date', 'otto-contracts' ),
							'type'        => 'date',
							'name'        => 'payment_date',
							'default'     => eac_format_datetime(),
							'value'       => eac_format_datetime( $expense->payment_date ),
							'placeholder' => 'yyyy-mm-dd',
							'class'       => 'eac_datetimepicker',
							'required'    => true,
						)
					);

					eac_form_field(
						array(
							'label'       => __( 'Expense #', 'otto-contracts' ),
							'type'        => 'text',
							'name'        => 'expense_number',
							'value'       => $expense->number,
							'placeholder' => $expense->get_next_number(),
							'default'     => $expense->get_next_number(),
							'readonly'    => true,
							'required'    => true,
						)
					);

					eac_form_field(
						array(
							'label'            => __( 'Account', 'otto-contracts' ),
							'type'             => 'select',
							'name'             => 'account_id',
							'value'            => $expense->account_id,
							'options'          => array( $expense->account ),
							'option_value'     => 'id',
							'option_label'     => 'formatted_name',
							'class'            => 'eac_select2',
							'data-placeholder' => __( 'Select an account', 'otto-contracts' ),
							'data-action'      => 'eac_json_search',
							'data-type'        => 'account',
							'required'         => true,
							'suffix'           => sprintf(
								'<a class="addon" href="%s" target="_blank" title="%s"><span class="dashicons dashicons-plus"></span></a>',
								esc_url( admin_url( 'admin.php?page=eac-banking&tab=accounts&action=add' ) ),
								__( 'Add Account', 'otto-contracts' )
							),
							'tooltip'          => __( 'Select the account.', 'otto-contracts' ),
						)
					);

					eac_form_field(
						array(
							'label'         => __( 'Exchange Rate', 'otto-contracts' ),
							'name'          => 'exchange_rate',
							'value'         => $expense->exchange_rate,
							'default'       => 1,
							'placeholder'   => '1.00',
							'class'         => 'eac_exchange_rate',
							'required'      => true,
							'prefix'        => '1 ' . eac_base_currency() . ' = ',
							'attr-step'     => 'any',
							'readonly'      => eac_base_currency() === $expense->currency,
							'data-currency' => $expense->currency,
							'data-source'   => ':input[name="account_id"]',
						)
					);

					eac_form_field(
						array(
							'label'         => __( 'Amount', 'otto-contracts' ),
							'name'          => 'amount',
							'value'         => $expense->amount,
							'placeholder'   => '0.00',
							'class'         => 'eac_amount',
							'required'      => true,
							'tooltip'       => sprintf(
								
								__( 'Enter the amount in the currency of the selected account, use (%s) for decimal.', 'otto-contracts' ),
								get_option( 'eac_decimal_separator', '.' )
							),
							'data-currency' => $expense->currency,
							'data-source'   => ':input[name="account_id"]',
						)
					);

					eac_form_field(
						array(
							'label'            => __( 'Category', 'otto-contracts' ),
							'type'             => 'select',
							'name'             => 'category_id',
							'value'            => $expense->category_id,
							'options'          => array( $expense->category ),
							'option_value'     => 'id',
							'option_label'     => 'formatted_name',
							'class'            => 'eac_select2',
							'placeholder'      => __( 'Select category', 'otto-contracts' ),
							'data-placeholder' => __( 'Select category', 'otto-contracts' ),
							'data-action'      => 'eac_json_search',
							'data-type'        => 'category',
							'data-subtype'     => 'expense',
							'suffix'           => sprintf(
								'<a class="addon" href="%s" target="_blank" title="%s"><span class="dashicons dashicons-plus"></span></a>',
								esc_url( admin_url( 'admin.php?page=eac-settings&tab=categories&action=add&type=expense' ) ),
								__( 'Add Category', 'otto-contracts' )
							),
						)
					);

					eac_form_field(
						array(
							'label'       => __( 'Expense Method', 'otto-contracts' ),
							'type'        => 'select',
							'name'        => 'payment_method',
							'value'       => $expense->payment_method,
							'options'     => eac_get_payment_methods(),
							'placeholder' => __( 'Select &hellip;', 'otto-contracts' ),
						)
					);

					if ( $expense->bill_id ) {
						
						eac_form_field(
							array(
								'label'    => __( 'Contract', 'otto-contracts' ),
								'type'     => 'text',
								'name'     => 'bill',
								'value'    => $expense->bill->number,
								'readonly' => true,
							)
						);
						printf( '<input type="hidden" name="bill_id" value="%d">', esc_attr( $expense->document_id ) );
					}

					eac_form_field(
						array(
							'label'       => __( 'Reference', 'otto-contracts' ),
							'type'        => 'text',
							'name'        => 'reference',
							'value'       => $expense->reference,
							'placeholder' => __( 'Enter reference', 'otto-contracts' ),
						)
					);

					eac_form_field(
						array(
							'label'         => __( 'Note', 'otto-contracts' ),
							'type'          => 'textarea',
							'name'          => 'note',
							'value'         => $expense->note,
							'placeholder'   => __( 'Enter description', 'otto-contracts' ),
							'wrapper_class' => 'is--full',
						)
					);
					?>
				</div>
			</div>

			<?php
			
			do_action( 'eac_expense_edit_core_content', $expense );
			?>
		</div>
		<div class="column-2">

			<div class="eac-card">
				<div class="eac-card__header">
					<h3 class="eac-card__title"><?php esc_html_e( 'Actions', 'otto-contracts' ); ?></h3>
				</div>
				<div class="eac-card__body">
					<?php
					
					do_action( 'eac_expense_edit_misc_actions', $expense );
					?>
				</div>
				<div class="eac-card__footer">
					<?php if ( $expense->exists() ) : ?>
						<a class="del del_confirm" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'action', 'delete', $expense->get_edit_url() ), 'bulk-expenses' ) ); ?>">
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
					<?php eac_file_uploader( array( 'value' => $expense->attachment_id ) ); ?>
				</div>
			</div>

			<?php
			
			do_action( 'eac_expense_edit_sidebar_content', $expense );
			?>
		</div><!-- .column-2 -->
	</div><!-- .eac-poststuff -->

	<?php wp_nonce_field( 'eac_edit_expense' ); ?>
	<input type="hidden" name="action" value="eac_edit_expense"/>
	<input type="hidden" name="id" value="<?php echo esc_attr( $expense->id ); ?>"/>
</form>
