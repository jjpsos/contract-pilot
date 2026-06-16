<?php

use Jjpsos\ContractPilot\Utilities\Idempotency;

defined('ABSPATH') || exit;

/**
 * Expense add/edit screen.
 *
 * @var \Jjpsos\ContractPilot\Models\Expense $expense
 */

?><h1 class="wp-heading-inline">
    <?php if ($expense->exists()) : ?>
        <?php esc_html_e('Edit Expense', 'contract-pilot'); ?>
        <a href="<?php echo esc_attr(admin_url('admin.php?page=contract-pilot-purchases&tab=expenses&action=add')); ?>" class="button button-small">
            <?php esc_html_e('Add New', 'contract-pilot'); ?>
        </a>
    <?php else : ?>
        <?php esc_html_e('Add Expense', 'contract-pilot'); ?>
    <?php endif; ?>
    <a href="<?php echo esc_attr(remove_query_arg(array( 'action', 'id' ))); ?>" title="<?php esc_attr_e('Go back', 'contract-pilot'); ?>">
        <span class="dashicons dashicons-undo"></span>
    </a>
</h1>

<form id="contract-pilot-edit-expense" name="expense" method="post" action="<?php echo esc_html(admin_url('admin-post.php')); ?>">

    <div class="contract-pilot-poststuff">
        <div class="column-1">
            <div class="contract-pilot-card">
                <div class="contract-pilot-card__header">
                    <h3 class="contract-pilot-card__title"><?php esc_html_e('Expense Attributes', 'contract-pilot'); ?></h3>
                </div>
                <div class="contract-pilot-card__body grid--fields">
                    <?php
                    contract_pilot_form_field(
                        array(
                            'label'       => __('Date', 'contract-pilot'),
                            'type'        => 'date',
                            'name'        => 'payment_date',
                            'default'     => contract_pilot_format_datetime('now', 'Y-m-d'),
                            'value'       => contract_pilot_format_datetime($expense->payment_date, 'Y-m-d'),
                            'placeholder' => 'yyyy-mm-dd',
                            'class'       => 'contract_pilot_datetimepicker',
                            'required'    => true,
                        )
                    );

                    contract_pilot_form_field(
                        array(
                            'label'       => __('Expense #', 'contract-pilot'),
                            'type'        => 'text',
                            'name'        => 'expense_number',
                            'value'       => $expense->number,
                            'placeholder' => $expense->get_next_number(),
                            'default'     => $expense->get_next_number(),
                            'readonly'    => true,
                            'required'    => true,
                        )
                    );

                    contract_pilot_form_field(
                        array(
                            'label'            => __('Account', 'contract-pilot'),
                            'type'             => 'select',
                            'name'             => 'account_id',
                            'value'            => $expense->account_id,
                            'options'          => array( $expense->account ),
                            'option_value'     => 'id',
                            'option_label'     => 'formatted_name',
                            'class'            => 'contract_pilot_select2',
                            'data-placeholder' => __('Select an account', 'contract-pilot'),
                            'data-action'      => 'contract_pilot_json_search',
                            'data-type'        => 'account',
                            'required'         => true,
                            'suffix'           => sprintf(
                                '<a class="addon" href="%s" target="_blank" title="%s"><span class="dashicons dashicons-plus"></span></a>',
                                esc_url(admin_url('admin.php?page=contract-pilot-banking&tab=accounts&action=add')),
                                __('Add Account', 'contract-pilot')
                            ),
                            'tooltip'          => __('Select the account.', 'contract-pilot'),
                        )
                    );

                    ?>
                    <input type="hidden" name="exchange_rate" value="1"/>
                    <?php
                    contract_pilot_form_field(
                        array(
                            'label'         => __('Amount', 'contract-pilot'),
                            'name'          => 'amount',
                            'value'         => $expense->amount,
                            'placeholder'   => '0.00',
                            'class'         => 'contract_pilot_amount',
                            'required'      => true,
                            'tooltip'       => sprintf(
                                /* translators: %s: decimal separator character */
                                __('Enter the amount in the currency of the selected account, use (%s) for decimal.', 'contract-pilot'),
                                get_option('contract_pilot_decimal_separator', '.')
                            ),
                            'data-currency' => $expense->currency,
                            'data-source'   => ':input[name="account_id"]',
                        )
                    );

                    contract_pilot_form_field(
                        array(
                            'label'            => __('Category', 'contract-pilot'),
                            'type'             => 'select',
                            'name'             => 'category_id',
                            'value'            => $expense->category_id,
                            'options'          => array( $expense->category ),
                            'option_value'     => 'id',
                            'option_label'     => 'formatted_name',
                            'class'            => 'contract_pilot_select2',
                            'placeholder'      => __('Select category', 'contract-pilot'),
                            'data-placeholder' => __('Select category', 'contract-pilot'),
                            'data-action'      => 'contract_pilot_json_search',
                            'data-type'        => 'category',
                            'data-subtype'     => 'expense',
                            'suffix'           => sprintf(
                                '<a class="addon" href="%s" target="_blank" title="%s"><span class="dashicons dashicons-plus"></span></a>',
                                esc_url(admin_url('admin.php?page=contract-pilot-settings&tab=categories&action=add&type=expense')),
                                __('Add Category', 'contract-pilot')
                            ),
                        )
                    );

                    contract_pilot_form_field(
                        array(
                            'label'       => __('Expense Method', 'contract-pilot'),
                            'type'        => 'select',
                            'name'        => 'payment_method',
                            'value'       => $expense->payment_method,
                            'options'     => contract_pilot_get_payment_methods(),
                            'placeholder' => __('Select &hellip;', 'contract-pilot'),
                        )
                    );

                    contract_pilot_form_field(
                        array(
                            'label'       => __('Reference', 'contract-pilot'),
                            'type'        => 'text',
                            'name'        => 'reference',
                            'value'       => $expense->reference,
                            'placeholder' => __('Enter reference', 'contract-pilot'),
                        )
                    );

                    contract_pilot_form_field(
                        array(
                            'label'         => __('Note', 'contract-pilot'),
                            'type'          => 'textarea',
                            'name'          => 'note',
                            'value'         => $expense->note,
                            'placeholder'   => __('Enter description', 'contract-pilot'),
                            'wrapper_class' => 'is--full',
                        )
                    );
                    ?>
                </div>
            </div>

            <?php

            do_action('contract_pilot_expense_edit_core_content', $expense);
            ?>
        </div>
        <div class="column-2">

            <div class="contract-pilot-card">
                <div class="contract-pilot-card__header">
                    <h3 class="contract-pilot-card__title"><?php esc_html_e('Actions', 'contract-pilot'); ?></h3>
                </div>
                <div class="contract-pilot-card__body">
                    <?php

                    do_action('contract_pilot_expense_edit_misc_actions', $expense);
                    ?>
                </div>
                <div class="contract-pilot-card__footer">
                    <?php if ($expense->exists()) : ?>
                        <a class="del del_confirm" href="<?php echo esc_url(wp_nonce_url(add_query_arg('action', 'delete', $expense->get_edit_url()), 'bulk-expenses')); ?>">
                            <?php esc_html_e('Delete', 'contract-pilot'); ?>
                        </a>
                        <button class="button button-primary"><?php esc_html_e('Update', 'contract-pilot'); ?></button>
                    <?php else : ?>
                        <button class="button button-primary button-block"><?php esc_html_e('Save', 'contract-pilot'); ?></button>
                    <?php endif; ?>
                </div>
            </div><!-- .contract-pilot-card -->

            <div class="contract-pilot-card">
                <div class="contract-pilot-card__header">
                    <h3 class="contract-pilot-card__title"><?php esc_html_e('Attachment', 'contract-pilot'); ?></h3>
                </div>
                <div class="contract-pilot-card__body">
                    <?php contract_pilot_file_uploader(array( 'value' => $expense->attachment_id )); ?>
                </div>
            </div>

            <?php

            do_action('contract_pilot_expense_edit_sidebar_content', $expense);
            ?>
        </div><!-- .column-2 -->
    </div><!-- .contract-pilot-poststuff -->

    <?php wp_nonce_field('contract_pilot_edit_expense'); ?>
    <input type="hidden" name="action" value="contract_pilot_edit_expense"/>
    <input type="hidden" name="id" value="<?php echo esc_attr($expense->id); ?>"/>
    <?php if (! $expense->exists()) : ?>
        <?php Idempotency::output_token_input('contract_pilot_edit_expense', 'create_expense'); ?>
    <?php endif; ?>
</form>
