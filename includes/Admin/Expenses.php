<?php

namespace Otto\Admin;

use Otto\Models\Expense;

defined( 'ABSPATH' ) || exit;


class Expenses {

	
	public function __construct() {
		add_filter( 'eac_purchases_page_tabs', array( __CLASS__, 'register_tabs' ) );
		add_action( 'admin_post_eac_edit_expense', array( __CLASS__, 'handle_edit' ) );
		add_action( 'admin_post_eac_update_expense', array( __CLASS__, 'handle_update' ) );
		add_action( 'eac_purchases_page_expenses_loaded', array( __CLASS__, 'page_loaded' ) );
		add_action( 'eac_purchases_page_expenses_content', array( __CLASS__, 'page_content' ) );
		add_action( 'eac_expense_view_sidebar_content', array( __CLASS__, 'expense_attachment' ) );
		add_action( 'eac_expense_view_sidebar_content', array( __CLASS__, 'expense_notes' ) );
	}

	
	public static function register_tabs( $tabs ) {
		if ( current_user_can( 'eac_read_expenses' ) ) { 
			$tabs['expenses'] = __( 'Expenses', 'otto-contracts' );
		}

		return $tabs;
	}

	
	public static function handle_edit() {
		check_admin_referer( 'eac_edit_expense' );

		if ( ! current_user_can( 'eac_edit_expenses' ) ) { 
			wp_die( esc_html__( 'You do not have permission to edit expenses.', 'otto-contracts' ) );
		}

		$referer = wp_get_referer();
		$data    = array(
			'id'             => isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0,
			'payment_date'   => isset( $_POST['payment_date'] ) ? get_gmt_from_date( sanitize_text_field( wp_unslash( $_POST['payment_date'] ) ) ) : '',
			'account_id'     => isset( $_POST['account_id'] ) ? absint( wp_unslash( $_POST['account_id'] ) ) : 0,
			'amount'         => isset( $_POST['amount'] ) ? floatval( wp_unslash( $_POST['amount'] ) ) : 0,
			'exchange_rate'  => isset( $_POST['exchange_rate'] ) ? floatval( wp_unslash( $_POST['exchange_rate'] ) ) : 1,
			'category_id'    => isset( $_POST['category_id'] ) ? absint( wp_unslash( $_POST['category_id'] ) ) : 0,
			'contact_id'     => isset( $_POST['contact_id'] ) ? absint( wp_unslash( $_POST['contact_id'] ) ) : 0,
			'attachment_id'  => isset( $_POST['attachment_id'] ) ? absint( wp_unslash( $_POST['attachment_id'] ) ) : 0,
			'payment_method' => isset( $_POST['payment_method'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_method'] ) ) : '',
			'reference'      => isset( $_POST['reference'] ) ? sanitize_text_field( wp_unslash( $_POST['reference'] ) ) : '',
			'note'           => isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '',
			'status'         => isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'active',
		);
		$expense = EAC()->expenses->insert( $data );
		if ( is_wp_error( $expense ) ) {
			EAC()->flash->error( $expense->get_error_message() );
		} else {
			EAC()->flash->success( __( 'Expense saved successfully.', 'otto-contracts' ) );
			$referer = add_query_arg( 'id', $expense->id, $referer );
			$referer = add_query_arg( 'action', 'edit', $referer );
			$referer = remove_query_arg( array( 'add' ), $referer );
		}

		wp_safe_redirect( $referer );
		exit;
	}

	
	public static function handle_update() {
		check_admin_referer( 'eac_update_expense' );

		if ( ! current_user_can( 'eac_edit_expenses' ) ) { 
			wp_die( esc_html__( 'You do not have permission to update expense.', 'otto-contracts' ) );
		}

		$referer        = wp_get_referer();
		$id             = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0;
		$status         = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';
		$attachment_id  = isset( $_POST['attachment_id'] ) ? absint( wp_unslash( $_POST['attachment_id'] ) ) : 0;
		$expense_action = isset( $_POST['payment_action'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_action'] ) ) : '';
		$expense        = EAC()->payments->get( $id );

		
		if ( ! $expense ) {
			EAC()->flash->error( __( 'Expense not found.', 'otto-contracts' ) );

			return;
		}

		
		if ( ! empty( $status ) && $status !== $expense->status ) {
			$expense->status = $status;
		}

		
		if ( $attachment_id !== $expense->attachment_id ) {
			$expense->attachment_id = $attachment_id;
		}

		if ( $expense->is_dirty() && $expense->save() ) {
			$ret = $expense->save();
			if ( is_wp_error( $ret ) ) {
				EAC()->flash->error( $ret->get_error_message() );
			} else {
				EAC()->flash->success( __( 'Expense updated successfully.', 'otto-contracts' ) );
			}
		}

		
		if ( ! empty( $expense_action ) ) {
			switch ( $expense_action ) {
				case 'send_receipt':
					
					break;
				default:
					
					do_action( 'eac_expense_action_' . $expense_action, $expense );
					break;
			}
		}

		wp_safe_redirect( $referer );
		exit;
	}

	
	public static function page_loaded( $action ) {
		global $eac_list_table;
		switch ( $action ) {
			case 'add':
				if ( ! current_user_can( 'eac_edit_expenses' ) ) { 
					wp_die( esc_html__( 'You do not have permission to add expenses.', 'otto-contracts' ) );
				}
				break;

			case 'view':
			case 'edit':
				$id = filter_input( INPUT_GET, 'id', FILTER_VALIDATE_INT );
				if ( ! EAC()->expenses->get( $id ) ) {
					wp_die( esc_html__( 'You attempted to retrieve a expense that does not exist. Perhaps it was deleted?', 'otto-contracts' ) );
				}
				if ( 'edit' === $action && ! current_user_can( 'eac_edit_expenses' ) ) { 
					wp_die( esc_html__( 'You do not have permission to edit expenses.', 'otto-contracts' ) );
				}
				break;

			default:
				$screen         = get_current_screen();
				$eac_list_table = new ListTables\Expenses();
				$eac_list_table->prepare_items();
				$screen->add_option(
					'per_page',
					array(
						'label'   => __( 'Number of items per page:', 'otto-contracts' ),
						'default' => 20,
						'option'  => 'eac_expenses_per_page',
					)
				);
				break;
		}
	}

	
	public static function page_content( $action ) {
		switch ( $action ) {
			case 'add':
			case 'edit':
			case 'view':
				include __DIR__ . '/views/expense-edit.php';
				break;
			default:
				include __DIR__ . '/views/expense-list.php';
				break;
		}
	}

	
	public static function expense_attachment( $expense ) {
		?>
		<div class="eac-card">
			<div class="eac-card__header">
				<h3 class="eac-card__title"><?php esc_html_e( 'Attachment', 'otto-contracts' ); ?></h3>
			</div>
			<div class="eac-card__body">
				<?php
				eac_file_uploader(
					array(
						'value'    => $expense->attachment_id,
						'readonly' => true,
					)
				);
				?>
			</div>
		</div>
		<?php
	}

	
	public static function expense_notes( $expense ) {
		
		if ( ! $expense ) {
			return;
		}
		$notes = EAC()->notes->query(
			array(
				'parent_id'   => $expense->id,
				'parent_type' => 'expense',
				'orderby'     => 'date_created',
				'order'       => 'DESC',
				'limit'       => 20,
			)
		);
		?>
		<div class="eac-card">
			<div class="eac-card__header">
				<h3 class="eac-card__title"><?php esc_html_e( 'Notes', 'otto-contracts' ); ?></h3>
			</div>
			<div class="eac-card__body">

				<?php if ( current_user_can( 'eac_edit_notes' ) ) : ?>
					<div class="eac-form-field">
						<label for="eac-note"><?php esc_html_e( 'Add Note', 'otto-contracts' ); ?></label>
						<textarea id="eac-note" cols="30" rows="2" placeholder="<?php esc_attr_e( 'Enter Note', 'otto-contracts' ); ?>"></textarea>
					</div>
					<button id="eac-add-note" type="button" class="button tw-mb-[20px]" data-parent_id="<?php echo esc_attr( $expense->id ); ?>" data-parent_type="expense" data-nonce="<?php echo esc_attr( wp_create_nonce( 'eac_add_note' ) ); ?>">
						<?php esc_html_e( 'Add Note', 'otto-contracts' ); ?>
					</button>
				<?php endif; ?>

				<?php include __DIR__ . '/views/note-list.php'; ?>
			</div>
		</div>
		<?php
	}
}
