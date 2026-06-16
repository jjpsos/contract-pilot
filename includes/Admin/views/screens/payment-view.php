<?php

defined('ABSPATH') || exit;

/**
 * Payment read-only view.
 *
 * @var \Jjpsos\ContractPilot\Models\Payment $payment
 */

?>
<h1 class="wp-heading-inline">
    <?php esc_html_e('View Payment', 'contract-pilot'); ?>
    <?php if (current_user_can('contract_pilot_edit_payments')) : ?>
        <a href="<?php echo esc_attr(admin_url('admin.php?page=contract-pilot-sales&tab=payments&action=add')); ?>" class="button button-small">
            <?php esc_html_e('Add New', 'contract-pilot'); ?>
        </a>
    <?php endif; ?>
    <a href="<?php echo esc_attr(remove_query_arg(array( 'action', 'id' ))); ?>" title="<?php esc_attr_e('Go back', 'contract-pilot'); ?>">
        <span class="dashicons dashicons-undo"></span>
    </a>
</h1>

<div class="contract-pilot-poststuff">

    <div class="column-1">
        <div class="contract-pilot-card">
            <?php contract_pilot_get_template('content-payment.php', array( 'payment' => $payment )); ?>
        </div>
        <?php

        do_action('contract_pilot_payment_edit_core_content', $payment);
        ?>
    </div>

    <div class="column-2">
        <div class="contract-pilot-card">
            <div class="contract-pilot-card__header">
                <h2 class="contract-pilot-card__title"><?php esc_html_e('Actions', 'contract-pilot'); ?></h2>
                <?php if ($payment->editable && current_user_can('contract_pilot_edit_payments')) : ?>
                    <a href="<?php echo esc_url($payment->get_edit_url()); ?>">
                        <?php esc_html_e('Edit', 'contract-pilot'); ?>
                    </a>
                <?php endif; ?>
            </div>
            <div class="contract-pilot-card__body">
                <?php

                do_action('contract_pilot_payment_view_misc_actions', $payment);
                ?>
                <a href="#" class="button button-small button-block contract_pilot_print_document" data-target=".contract-pilot-document">
                    <span class="dashicons dashicons-printer"></span> <?php esc_html_e('Print', 'contract-pilot'); ?>
                </a>
            </div>
            <div class="contract-pilot-card__footer">
                <?php if (current_user_can('contract_pilot_delete_payments')) : ?>
                    <a class="del del_confirm" href="<?php echo esc_url(wp_nonce_url(add_query_arg('action', 'delete', $payment->get_edit_url()), 'bulk-payments')); ?>">
                        <?php esc_html_e('Delete', 'contract-pilot'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php

        do_action('contract_pilot_payment_view_sidebar_content', $payment);
        ?>

    </div><!-- .column-2 -->

</div><!-- .contract-pilot-poststuff -->

