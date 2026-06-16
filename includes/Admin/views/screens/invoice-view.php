<?php

defined('ABSPATH') || exit;

/**
 * Contract/Bill read-only view.
 *
 * @var \Jjpsos\ContractPilot\Models\Invoice $invoice
 * @var string                               $mark_sent_url
 * @var string                               $mark_accept_url
 * @var string                               $contract_pilot_invoice_heading_sl
 * @var string                               $contract_pilot_view_title
 * @var array<string, string>                $payment_methods
 */

?><h1 class="wp-heading-inline">
    <?php echo esc_html($contract_pilot_view_title); ?>
    <?php if (current_user_can('contract_pilot_edit_invoices')) : ?>
        <a href="<?php echo esc_attr(admin_url('admin.php?page=contract-pilot-sales&tab=invoices&action=add')); ?>" class="button button-small">
            <?php esc_html_e('Add New', 'contract-pilot'); ?>
        </a>
    <?php endif; ?>
    <a href="<?php echo esc_attr(remove_query_arg(array( 'action', 'id' ))); ?>" title="<?php esc_attr_e('Go back', 'contract-pilot'); ?>">
        <span class="dashicons dashicons-undo"></span>
    </a>
</h1>

<div class="contract-pilot-poststuff">

    <div class="column-1">
        <div class="contract-pilot-card"><?php contract_pilot_get_template('content-invoice.php', array( 'invoice' => $invoice )); ?></div>
        <?php

        do_action('contract_pilot_invoice_edit_core_content', $invoice);
        ?>
    </div>

    <div class="column-2">
        <div class="contract-pilot-card">
            <div class="contract-pilot-card__header">
                <h2 class="contract-pilot-card__title"><?php esc_html_e('Actions', 'contract-pilot'); ?></h2>
                <?php if ($invoice->editable && current_user_can('contract_pilot_edit_invoices')) : ?>
                    <a href="<?php echo esc_url($invoice->get_edit_url()); ?>">
                        <?php
                        echo esc_html(
                            '' !== $contract_pilot_invoice_heading_sl
                                ? sprintf(
                                    /* translators: %s: status label */
                                    __('Edit %s', 'contract-pilot'),
                                    $contract_pilot_invoice_heading_sl
                                )
                                : __('Edit', 'contract-pilot')
                        );
                        ?>
                    </a>
                <?php endif; ?>
            </div>

            <div class="contract-pilot-card__body">
                <?php if ($invoice->is_status('draft') && current_user_can('contract_pilot_edit_invoices')) : ?>
                    <a href="<?php echo esc_url($mark_sent_url); ?>" class="button button-primary button-small button-block">
                        <span class="dashicons dashicons-yes"></span> <?php esc_html_e('Mark Sent', 'contract-pilot'); ?>
                    </a>
                <?php elseif (in_array($invoice->status, array( 'sent', 'overdue' ), true) && ! $invoice->is_paid() && current_user_can('contract_pilot_edit_invoices')) : ?>
                    <a href="<?php echo esc_url($mark_accept_url); ?>" class="button button-primary button-small button-block">
                        <span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e('Mark Accept', 'contract-pilot'); ?>
                    </a>
                    <a href="#" class="button button-primary button-small button-block contract-pilot-add-invoice-payment" data-id="<?php echo esc_attr($invoice->id); ?>">
                        <span class="dashicons dashicons-money-alt"></span> <?php esc_html_e('Add Payment', 'contract-pilot'); ?>
                    </a>
                <?php elseif (! $invoice->is_status('draft') && ! $invoice->is_paid() && current_user_can('contract_pilot_edit_invoices')) : ?>
                    <a href="#" class="button button-primary button-small button-block contract-pilot-add-invoice-payment" data-id="<?php echo esc_attr($invoice->id); ?>">
                        <span class="dashicons dashicons-money-alt"></span> <?php esc_html_e('Add Payment', 'contract-pilot'); ?>
                    </a>
                <?php endif; ?>
                <a href="#" class="button button-small button-block contract_pilot_print_document" data-target=".contract-pilot-document">
                    <span class="dashicons dashicons-printer"></span> <?php esc_html_e('Print', 'contract-pilot'); ?>
                </a>

                <?php

                do_action('contract_pilot_invoice_view_misc_actions', $invoice);
                ?>
            </div>

            <div class="contract-pilot-card__footer">
                <?php if (current_user_can('contract_pilot_delete_invoices')) : ?>
                    <a class="del del_confirm" href="<?php echo esc_url(wp_nonce_url(add_query_arg('action', 'delete', $invoice->get_edit_url()), 'bulk-contracts')); ?>"><?php esc_html_e('Delete', 'contract-pilot'); ?></a>
                <?php endif; ?>
            </div>
        </div>

        <?php

        do_action('contract_pilot_invoice_view_sidebar_content', $invoice);
        ?>

    </div><!-- .column-2 -->

</div><!-- .contract-pilot-poststuff -->

<script type="text/html" id="tmpl-contract-pilot-invoice-payment">
    <form>
        <div class="contract-pilot-modal-header">
            <h3><?php esc_html_e('Add Contract Payment', 'contract-pilot'); ?></h3>
        </div>

        <div class="contract-pilot-modal-body">
            <div class="contract-pilot-form-field">
                <label for="payment_date"><?php esc_html_e('Payment Date', 'contract-pilot'); ?>&nbsp;<abbr title="required"></abbr></label>
                <input type="text" name="payment_date" id="payment_date" value="<?php echo esc_attr(contract_pilot_format_datetime('now', 'Y-m-d')); ?>" class="contract_pilot_datetimepicker" required>
            </div>
            <div class="contract-pilot-form-field">
                <label for="account_id"><?php esc_html_e('Account', 'contract-pilot'); ?>&nbsp;<abbr title="required"></abbr></label>
                <select name="account_id" id="account_id" class="contract_pilot_select2 account_id" data-action="contract_pilot_json_search" data-type="account" data-placeholder="<?php esc_html_e('Select an account', 'contract-pilot'); ?>" required>
                    <option value=""><?php esc_html_e('Select an account', 'contract-pilot'); ?></option>
                </select>
            </div>
            <input type="hidden" name="exchange_rate" value="1"/>

            <div class="contract-pilot-form-field">
                <label for="amount"><?php esc_html_e('Amount', 'contract-pilot'); ?>&nbsp;<abbr title="required"></abbr></label>
                <input type="text" name="amount" id="amount" class="contract_pilot_amount" value="<?php echo esc_attr($invoice->get_due_amount()); ?>" data-currency="<?php echo esc_attr($invoice->currency); ?>" required>
            </div>

            <div class="contract-pilot-form-field">
                <label for="payment_method"><?php esc_html_e('Payment Method', 'contract-pilot'); ?></label>
                <select name="payment_method" id="payment_method">
                    <option value=""><?php esc_html_e('Select Payment Method', 'contract-pilot'); ?></option>
                    <?php foreach ($payment_methods as $contract_pilot_pm_key => $contract_pilot_pm_label) : ?>
                        <option value="<?php echo esc_attr($contract_pilot_pm_key); ?>"><?php echo esc_html($contract_pilot_pm_label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="contract-pilot-form-field">
                <label for="reference"><?php esc_html_e('Reference', 'contract-pilot'); ?></label>
                <input type="text" name="reference" id="reference">
            </div>

            <div class="contract-pilot-form-field">
                <label for="note"><?php esc_html_e('Description', 'contract-pilot'); ?></label>
                <textarea name="note" id="note" rows="3"></textarea>
            </div>
        </div>

        <div class="contract-pilot-modal-footer">
            <button type="submit" class="button button-primary"><?php esc_html_e('Add Payment', 'contract-pilot'); ?></button>
            <button type="button" class="button" data-modal-close><?php esc_html_e('Cancel', 'contract-pilot'); ?></button>
        </div>

        <input type="hidden" name="currency" value="<?php echo esc_attr($invoice->currency); ?>">
        <input type="hidden" name="invoice_id" value="<?php echo esc_attr($invoice->id); ?>">
        <input type="hidden" name="customer_id" value="<?php echo esc_attr($invoice->contact_id); ?>">
    </form>
</script>
