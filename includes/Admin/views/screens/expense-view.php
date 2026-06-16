<?php

defined('ABSPATH') || exit;

/**
 * Expense read-only view.
 *
 * @var \Jjpsos\ContractPilot\Models\Expense $expense
 */

?><h1 class="wp-heading-inline">
    <?php esc_html_e('View Expense', 'contract-pilot'); ?>
    <?php if (current_user_can('contract_pilot_edit_expenses')) : ?>
        <a href="<?php echo esc_attr(admin_url('admin.php?page=contract-pilot-purchases&tab=expenses&action=add')); ?>" class="button button-small">
            <?php esc_html_e('Add New', 'contract-pilot'); ?>
        </a>
    <?php endif; ?>
    <a href="<?php echo esc_attr(remove_query_arg(array( 'action', 'id' ))); ?>" title="<?php esc_attr_e('Go back', 'contract-pilot'); ?>">
        <span class="dashicons dashicons-undo"></span>
    </a>
</h1>

<div class="contract-pilot-poststuff">

    <div class="column-1">
        <div class="contract-pilot-card"><?php contract_pilot_get_template('content-expense.php', array( 'expense' => $expense )); ?></div>
        <?php

        do_action('contract_pilot_expense_edit_core_content', $expense);
        ?>
    </div>

    <div class="column-2">
        <div class="contract-pilot-card">
            <div class="contract-pilot-card__header">
                <h2 class="contract-pilot-card__title"><?php esc_html_e('Actions', 'contract-pilot'); ?></h2>
                <?php if ($expense->editable && current_user_can('contract_pilot_edit_expenses')) : ?>
                    <a href="<?php echo esc_url($expense->get_edit_url()); ?>">
                        <?php esc_html_e('Edit', 'contract-pilot'); ?>
                    </a>
                <?php endif; ?>
            </div>
            <div class="contract-pilot-card__body">
                <?php

                do_action('contract_pilot_expense_view_misc_actions', $expense);
                ?>
                <a href="#" class="button button-small button-block contract_pilot_print_document" data-target=".contract-pilot-document">
                    <span class="dashicons dashicons-printer"></span> <?php esc_html_e('Print', 'contract-pilot'); ?>
                </a>
            </div>
            <div class="contract-pilot-card__footer">
                <?php if (current_user_can('contract_pilot_delete_expenses')) : ?>
                <a class="del del_confirm" href="<?php echo esc_url(wp_nonce_url(add_query_arg('action', 'delete', $expense->get_edit_url()), 'bulk-expenses')); ?>">
                    <?php esc_html_e('Delete', 'contract-pilot'); ?>
                </a>
                <?php endif; ?>
            </div>
        </div>

        <?php

        do_action('contract_pilot_expense_view_sidebar_content', $expense);
        ?>

    </div><!-- .column-2 -->

</div><!-- .contract-pilot-poststuff -->

