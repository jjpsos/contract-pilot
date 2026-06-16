<?php

defined('ABSPATH') || exit;

/**
 * Transfer add/edit screen.
 *
 * @var \Jjpsos\ContractPilot\Models\Transfer $transfer
 */

?><h1 class="wp-heading-inline">
    <?php if ($transfer->exists()) : ?>
        <?php esc_html_e('Edit Transfer', 'contract-pilot'); ?>
        <?php if (current_user_can('contract_pilot_edit_transfers')) : ?>
            <a href="<?php echo esc_attr(admin_url('admin.php?page=contract-pilot-banking&tab=transfers&action=add')); ?>" class="button button-small">
                <?php esc_html_e('Add New', 'contract-pilot'); ?>
            </a>
        <?php endif; ?>
    <?php else : ?>
        <?php esc_html_e('Add Transfer', 'contract-pilot'); ?>
    <?php endif; ?>
    <a href="<?php echo esc_attr(remove_query_arg(array( 'action', 'id' ))); ?>" title="<?php esc_attr_e('Go back', 'contract-pilot'); ?>">
        <span class="dashicons dashicons-undo"></span>
    </a>
</h1>


<form id="contract-pilot-edit-transfer" name="transfer" method="post" action="<?php echo esc_html(admin_url('admin-post.php')); ?>">
    <div class="contract-pilot-poststuff">
        <div class="column-1">

            <div class="contract-pilot-card">
                <div class="contract-pilot-card__header">
                    <h2 class="contract-pilot-card__title"><?php esc_html_e('Transfer Attributes', 'contract-pilot'); ?></h2>
                </div>
                <div class="contract-pilot-card__body grid--fields">
                    <?php
                    contract_pilot_form_field(
                        array(
                            'type'             => 'select',
                            'name'             => 'from_account_id',
                            'label'            => __('From Account', 'contract-pilot'),
                            'options'          => array( $transfer->expense ? $transfer->expense->account : null ),
                            'value'            => $transfer->expense ? $transfer->expense->account_id : null,
                            'class'            => 'contract_pilot_select2',
                            'required'         => true,
                            'tooltip'          => __('Select the account.', 'contract-pilot'),
                            'data-placeholder' => __('Select an account', 'contract-pilot'),
                            'data-action'      => 'contract_pilot_json_search',
                            'data-type'        => 'account',
                            'option_value'     => 'id',
                            'option_label'     => 'formatted_name',
                            'suffix'           => sprintf(
                                '<a class="addon" href="%s" target="_blank" title="%s"><span class="dashicons dashicons-plus"></span></a>',
                                esc_url(admin_url('admin.php?page=contract-pilot-banking&tab=accounts&action=add')),
                                __('Add Account', 'contract-pilot')
                            ),
                        )
                    );

                    contract_pilot_form_field(
                        array(
                            'type'             => 'select',
                            'name'             => 'to_account_id',
                            'label'            => __('To Account', 'contract-pilot'),
                            'options'          => array( $transfer->payment ? $transfer->payment->account : null ),
                            'value'            => $transfer->payment ? $transfer->payment->account_id : null,
                            'class'            => 'contract_pilot_select2',
                            'required'         => true,
                            'tooltip'          => __('Select the account.', 'contract-pilot'),
                            'data-placeholder' => __('Select an account', 'contract-pilot'),
                            'data-action'      => 'contract_pilot_json_search',
                            'data-type'        => 'account',
                            'option_value'     => 'id',
                            'option_label'     => 'formatted_name',
                            'suffix'           => sprintf(
                                '<a class="addon" href="%s" target="_blank" title="%s"><span class="dashicons dashicons-plus"></span></a>',
                                esc_url(admin_url('admin.php?page=contract-pilot-banking&tab=accounts&action=add')),
                                __('Add Account', 'contract-pilot')
                            ),
                        )
                    );

                    contract_pilot_form_field(
                        array(
                            'type'          => 'text',
                            'name'          => 'amount',
                            'label'         => __('Amount', 'contract-pilot'),
                            'placeholder'   => '0.00',
                            'value'         => $transfer->amount,
                            'required'      => true,
                            'data-currency' => $transfer->currency ? $transfer->currency : contract_pilot_base_currency(),
                            'class'         => 'contract_pilot_amount',
                            'data-source'   => ':input[name="from_account_id"]',
                        )
                    );

                    contract_pilot_form_field(
                        array(
                            'data_type'   => 'date',
                            'name'        => 'transfer_date',
                            'label'       => __('Date', 'contract-pilot'),
                            'placeholder' => 'YYYY-MM-DD',
                            'default'     => contract_pilot_format_datetime('now', 'Y-m-d'),
                            'value'       => contract_pilot_format_datetime($transfer->transfer_date, 'Y-m-d'),
                            'required'    => true,
                            'class'       => 'contract_pilot_datetimepicker',
                        )
                    );

                    contract_pilot_form_field(
                        array(
                            'type'        => 'select',
                            'name'        => 'payment_method',
                            'label'       => __('Payment Method', 'contract-pilot'),
                            'value'       => $transfer->payment_method,
                            'options'     => contract_pilot_get_payment_methods(),
                            'placeholder' => __('Select payment method', 'contract-pilot'),
                        )
                    );

                    contract_pilot_form_field(
                        array(
                            'type'        => 'text',
                            'name'        => 'reference',
                            'label'       => __('Reference', 'contract-pilot'),
                            'value'       => $transfer->reference,
                            'placeholder' => __('Enter reference', 'contract-pilot'),
                        )
                    );

                    contract_pilot_form_field(
                        array(
                            'type'          => 'textarea',
                            'name'          => 'note',
                            'label'         => __('Notes', 'contract-pilot'),
                            'value'         => $transfer->note,
                            'placeholder'   => __('Enter description', 'contract-pilot'),
                            'wrapper_class' => 'is--full',
                        )
                    );
                    ?>
                </div>
            </div>

            <?php

            do_action('contract_pilot_transfer_edit_core_content', $transfer);
            ?>
        </div><!-- .column-1 -->
        <div class="column-2">
            <div class="contract-pilot-card">
                <div class="contract-pilot-card__header">
                    <h3 class="contract-pilot-card__title"><?php esc_html_e('Save', 'contract-pilot'); ?></h3>
                </div>

                <?php if (has_action('contract_pilot_transfer_edit_misc_actions')) : ?>
                    <div class="contract-pilot-card__body">
                        <?php

                        do_action('contract_pilot_transfer_edit_misc_actions', $transfer);
                        ?>
                    </div>
                <?php endif; ?>

                <div class="contract-pilot-card__footer">
                    <?php if ($transfer->exists()) : ?>
                        <?php if (current_user_can('contract_pilot_delete_transfers')) : ?>
                            <a class="del del_confirm" href="<?php echo esc_url(wp_nonce_url(add_query_arg('action', 'delete', $transfer->get_edit_url()), 'bulk-transfers')); ?>">
                                <?php esc_html_e('Delete', 'contract-pilot'); ?>
                            </a>
                        <?php endif; ?>
                        <?php if (current_user_can('contract_pilot_edit_transfers')) : ?>
                            <button class="button button-primary"><?php esc_html_e('Update Transfer', 'contract-pilot'); ?></button>
                        <?php endif; ?>
                    <?php else : ?>
                        <?php if (current_user_can('contract_pilot_edit_transfers')) : ?>
                        <button class="button button-primary button-large cp-w-full"><?php esc_html_e('Save Transfer', 'contract-pilot'); ?></button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div><!-- .contract-pilot-card -->

            <?php

            do_action('contract_pilot_transfer_edit_sidebar_content', $transfer);
            ?>

        </div><!-- .column-2 -->
    </div>


    <?php wp_nonce_field('contract_pilot_edit_transfer'); ?>
    <input type="hidden" name="action" value="contract_pilot_edit_transfer"/>
    <input type="hidden" name="id" value="<?php echo esc_attr($transfer->id); ?>"/>
</form>
