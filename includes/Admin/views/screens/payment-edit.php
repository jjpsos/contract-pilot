<?php

use Jjpsos\ContractPilot\Utilities\Idempotency;

defined('ABSPATH') || exit;

/**
 * Payment add/edit screen.
 *
 * @var \Jjpsos\ContractPilot\Models\Payment $payment
 */

?><h1 class="wp-heading-inline">
    <?php if ($payment->exists()) : ?>
        <?php esc_html_e('Edit Payment', 'contract-pilot'); ?>
        <a href="<?php echo esc_attr(admin_url('admin.php?page=contract-pilot-sales&tab=payments&action=add')); ?>" class="button button-small">
            <?php esc_html_e('Add New', 'contract-pilot'); ?>
        </a>
    <?php else : ?>
        <?php esc_html_e('Add Payment', 'contract-pilot'); ?>
    <?php endif; ?>
    <a href="<?php echo esc_attr(remove_query_arg(array( 'action', 'id' ))); ?>" title="<?php esc_attr_e('Go back', 'contract-pilot'); ?>">
        <span class="dashicons dashicons-undo"></span>
    </a>
</h1>

<form id="contract-pilot-edit-payment" name="payment" method="post" action="<?php echo esc_html(admin_url('admin-post.php')); ?>">

    <div class="contract-pilot-poststuff">
        <div class="column-1">
            <div class="contract-pilot-card">
                <div class="contract-pilot-card__header">
                    <h3 class="contract-pilot-card__title"><?php esc_html_e('Payment Attributes', 'contract-pilot'); ?></h3>
                </div>
                <div class="contract-pilot-card__body grid--fields">
                    <?php
                    contract_pilot_form_field(
                        array(
                            'label'       => __('Date', 'contract-pilot'),
                            'type'        => 'date',
                            'name'        => 'payment_date',
                            'default'     => contract_pilot_format_datetime('now', 'Y-m-d'),
                            'value'       => contract_pilot_format_datetime($payment->payment_date, 'Y-m-d'),
                            'placeholder' => 'yyyy-mm-dd',
                            'class'       => 'contract_pilot_datetimepicker',
                            'required'    => true,
                        )
                    );

                    contract_pilot_form_field(
                        array(
                            'label'       => __('Payment #', 'contract-pilot'),
                            'type'        => 'text',
                            'name'        => 'payment_number',
                            'value'       => $payment->number,
                            'placeholder' => $payment->get_next_number(),
                            'default'     => $payment->get_next_number(),
                            'readonly'    => true,
                            'required'    => true,
                        )
                    );

                    contract_pilot_form_field(
                        array(
                            'label'            => __('Account', 'contract-pilot'),
                            'type'             => 'select',
                            'name'             => 'account_id',
                            'value'            => $payment->account_id,
                            'options'          => array( $payment->account ),
                            'option_value'     => 'id',
                            'option_label'     => 'formatted_name',
                            'class'            => 'contract_pilot_select2',
                            'placeholder'      => __('Select an account', 'contract-pilot'),
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
                            'value'         => $payment->amount,
                            'placeholder'   => '0.00',
                            'class'         => 'contract_pilot_amount',
                            'required'      => true,
                            'tooltip'       => sprintf(
                                /* translators: %s: decimal separator character */
                                __('Enter the amount in the currency of the selected account, use (%s) for decimal.', 'contract-pilot'),
                                get_option('contract_pilot_decimal_separator', '.')
                            ),
                            'data-currency' => $payment->currency,
                            'data-source'   => ':input[name="account_id"]',
                        )
                    );

                    contract_pilot_form_field(
                        array(
                            'label'            => __('Category', 'contract-pilot'),
                            'type'             => 'select',
                            'name'             => 'category_id',
                            'value'            => $payment->category_id,
                            'options'          => array( $payment->category ),
                            'option_value'     => 'id',
                            'option_label'     => 'formatted_name',
                            'class'            => 'contract_pilot_select2',
                            'placeholder'      => __('Select category', 'contract-pilot'),
                            'data-placeholder' => __('Select category', 'contract-pilot'),
                            'data-action'      => 'contract_pilot_json_search',
                            'data-type'        => 'category',
                            'data-subtype'     => 'payment',
                            'suffix'           => sprintf(
                                '<a class="addon" href="%s" target="_blank" title="%s"><span class="dashicons dashicons-plus"></span></a>',
                                esc_url(admin_url('admin.php?page=contract-pilot-settings&tab=categories&action=add')),
                                __('Add Category', 'contract-pilot')
                            ),
                        )
                    );

                    contract_pilot_form_field(
                        array(
                            'label'            => __('Customer', 'contract-pilot'),
                            'type'             => 'select',
                            'name'             => 'contact_id',
                            'value'            => $payment->customer_id,
                            'options'          => array( $payment->customer ),
                            'option_value'     => 'id',
                            'option_label'     => 'formatted_name',
                            'class'            => 'contract_pilot_select2',
                            'data-placeholder' => __('Select a customer', 'contract-pilot'),
                            'data-action'      => 'contract_pilot_json_search',
                            'data-type'        => 'customer',
                            'suffix'           => sprintf(
                                '<a class="addon" href="%s" target="_blank" title="%s"><span class="dashicons dashicons-plus"></span></a>',
                                esc_url(admin_url('admin.php?page=contract-pilot-sales&tab=customers&action=add')),
                                __('Add Customer', 'contract-pilot')
                            ),
                            'tooltip'          => __('Select the customer.', 'contract-pilot'),
                        )
                    );

                    contract_pilot_form_field(
                        array(
                            'label'       => __('Payment Method', 'contract-pilot'),
                            'type'        => 'select',
                            'name'        => 'payment_method',
                            'value'       => $payment->payment_method,
                            'options'     => contract_pilot_get_payment_methods(),
                            'placeholder' => __('Select &hellip;', 'contract-pilot'),
                        )
                    );

                    if ($payment->invoice_id) {
                        contract_pilot_form_field(
                            array(
                                'label'    => __('Contract', 'contract-pilot'),
                                'type'     => 'text',
                                'name'     => 'invoice',
                                'value'    => $payment->invoice->number,
                                'readonly' => true,
                            )
                        );
                        printf('<input type="hidden" name="invoice_id" value="%d">', esc_attr($payment->document_id));
                    }

                    contract_pilot_form_field(
                        array(
                            'label'       => __('Reference', 'contract-pilot'),
                            'type'        => 'text',
                            'name'        => 'reference',
                            'value'       => $payment->reference,
                            'placeholder' => __('Enter reference', 'contract-pilot'),
                        )
                    );

                    contract_pilot_form_field(
                        array(
                            'label'         => __('Note', 'contract-pilot'),
                            'type'          => 'textarea',
                            'name'          => 'note',
                            'value'         => $payment->note,
                            'placeholder'   => __('Enter note', 'contract-pilot'),
                            'wrapper_class' => 'is--full',
                        )
                    );
                    ?>
                </div>
            </div>

            <?php

            do_action('contract_pilot_payment_edit_core_content', $payment);
            ?>
        </div>
        <div class="column-2">

            <div class="contract-pilot-card">
                <div class="contract-pilot-card__header">
                    <h3 class="contract-pilot-card__title"><?php esc_html_e('Actions', 'contract-pilot'); ?></h3>
                    <?php if ($payment->exists()) : ?>
                        <a href="<?php echo esc_url($payment->get_view_url()); ?>">
                            <?php esc_html_e('View', 'contract-pilot'); ?>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="contract-pilot-card__body">
                    <?php

                    do_action('contract_pilot_payment_edit_misc_actions', $payment);
                    ?>
                </div>
                <div class="contract-pilot-card__footer">
                    <?php if ($payment->exists()) : ?>
                        <a class="del del_confirm" href="<?php echo esc_url(wp_nonce_url(add_query_arg('action', 'delete', $payment->get_edit_url()), 'bulk-payments')); ?>">
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
                    <?php contract_pilot_file_uploader(array( 'value' => $payment->attachment_id )); ?>
                </div>
            </div>

            <?php

            do_action('contract_pilot_payment_edit_sidebar_content', $payment);
            ?>
        </div><!-- .column-2 -->
    </div><!-- .contract-pilot-poststuff -->

    <?php wp_nonce_field('contract_pilot_edit_payment'); ?>
    <input type="hidden" name="action" value="contract_pilot_edit_payment"/>
    <input type="hidden" name="id" value="<?php echo esc_attr($payment->id); ?>"/>
    <?php if (! $payment->exists()) : ?>
        <?php Idempotency::output_token_input('contract_pilot_edit_payment', 'create_payment'); ?>
    <?php endif; ?>
</form>
