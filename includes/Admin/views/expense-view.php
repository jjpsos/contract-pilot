<?php


use Otto\Models\Expense;

defined( 'ABSPATH' ) || exit;

$id      = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT );
$expense = EAC()->expenses->get( $id );

?>
<h1 class="wp-heading-inline">
	<?php esc_html_e( 'View Expense', 'otto-contracts' ); ?>
	<?php if ( current_user_can( 'eac_edit_expenses' ) ) : ?>
		<a href="<?php echo esc_attr( admin_url( 'admin.php?page=eac-purchases&tab=expenses&action=add' ) ); ?>" class="button button-small">
			<?php esc_html_e( 'Add New', 'otto-contracts' ); ?>
		</a>
	<?php endif; ?>
	<a href="<?php echo esc_attr( remove_query_arg( array( 'action', 'id' ) ) ); ?>" title="<?php esc_attr_e( 'Go back', 'otto-contracts' ); ?>">
		<span class="dashicons dashicons-undo"></span>
	</a>
</h1>

<div class="eac-poststuff">

	<div class="column-1">
		<div class="eac-card"><?php eac_get_template( 'content-expense.php', array( 'expense' => $expense ) ); ?></div>
		<?php
		
		do_action( 'eac_expense_edit_core_content', $expense );
		?>
	</div>

	<div class="column-2">
		<div class="eac-card">
			<div class="eac-card__header">
				<h2 class="eac-card__title"><?php esc_html_e( 'Actions', 'otto-contracts' ); ?></h2>
				<?php if ( $expense->editable && current_user_can( 'eac_edit_expenses' ) ) : ?>
					<a href="<?php echo esc_url( $expense->get_edit_url() ); ?>">
						<?php esc_html_e( 'Edit', 'otto-contracts' ); ?>
					</a>
				<?php endif; ?>
			</div>
			<div class="eac-card__body">
				<?php
				
				do_action( 'eac_expense_view_misc_actions', $expense );
				?>
				<a href="#" class="button button-small button-block eac_print_document" data-target=".eac-document">
					<span class="dashicons dashicons-printer"></span> <?php esc_html_e( 'Print', 'otto-contracts' ); ?>
				</a>
			</div>
			<div class="eac-card__footer">
				<?php if ( current_user_can( 'eac_delete_expenses' ) ) : ?>
				<a class="del del_confirm" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'action', 'delete', $expense->get_edit_url() ), 'bulk-expenses' ) ); ?>">
					<?php esc_html_e( 'Delete', 'otto-contracts' ); ?>
				</a>
				<?php endif; ?>
			</div>
		</div>

		<?php
		
		do_action( 'eac_expense_view_sidebar_content', $expense );
		?>

	</div><!-- .column-2 -->

</div><!-- .eac-poststuff -->

