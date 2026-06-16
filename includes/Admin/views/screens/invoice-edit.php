<?php

use Jjpsos\ContractPilot\Utilities\Idempotency;

defined('ABSPATH') || exit;

/**
 * Contract/Bill add/edit screen.
 *
 * @var \Jjpsos\ContractPilot\Models\Invoice $invoice
 * @var array<string, string>                $columns
 * @var string                               $contract_pilot_invoice_heading_sl
 * @var string                               $contract_pilot_edit_title
 * @var array<string, string>                $status_options
 * @var string                               $contract_pilot_status_label_display
 */

?><h1 class="wp-heading-inline">
    <?php if ($invoice->exists()) : ?>
        <?php echo esc_html($contract_pilot_edit_title); ?>
    <?php else : ?>
        <?php esc_html_e('Add Contract', 'contract-pilot'); ?>
    <?php endif; ?>
    <a href="<?php echo esc_attr(remove_query_arg(array( 'action', 'id' ))); ?>" title="<?php esc_attr_e('Go back', 'contract-pilot'); ?>">
        <span class="dashicons dashicons-undo"></span>
    </a>
</h1>

<form id="contract-pilot-edit-invoice" name="invoice" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <div class="contract-pilot-poststuff">

        <div class="column-1">
            <div class="contract-pilot-card contract-pilot-document-overview">
                <div class="contract-pilot-card__faked document-details cp-grid cp-grid-cols-2 cp-gap-x-15">
                    <div class="">
                        <?php
                        contract_pilot_form_field(
                            array(
                                'label'            => __('Client', 'contract-pilot'),
                                'type'             => 'select',
                                'name'             => 'contact_id',
                                'options'          => array( $invoice->customer ),
                                'value'            => $invoice->customer_id,
                                'required'         => true,
                                'class'            => 'contract_pilot_select2',
                                'option_value'     => 'id',
                                'option_label'     => 'formatted_name',
                                'data-placeholder' => __('Select a client', 'contract-pilot'),
                                'data-action'      => 'contract_pilot_json_search',
                                'data-type'        => 'customer',
                            )
                        );
                        ?>

                        <div class="document-address">
                            <?php contract_pilot_render_admin_view('partials/invoice-address', ['invoice' => $invoice]); ?>
                        </div>

                    </div>

                    <div class="cp-grid cp-grid-cols-2 cp-grid-responsive cp-gap-x-15">
                        <?php
                        contract_pilot_form_field(
                            array(
                                'label'             => esc_html__('Issue Date', 'contract-pilot'),
                                'name'              => 'issue_date',
                                'type'              => 'text',
                                'default'           => contract_pilot_format_datetime('now', 'Y-m-d'),
                                'value'             => contract_pilot_format_datetime($invoice->issue_date, 'Y-m-d'),
                                'placeholder'       => 'YYYY-MM-DD',
                                'required'          => true,
                                'class'             => 'contract_pilot_datetimepicker',
                                'attr-autocomplete' => 'off',
                            )
                        );
                        contract_pilot_form_field(
                            array(
                                'label'             => esc_html__('Contract Number', 'contract-pilot'),
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
                        contract_pilot_form_field(
                            array(
                                'label'             => esc_html__('Due Date', 'contract-pilot'),
                                'name'              => 'due_date',
                                'type'              => 'text',
                                'default'           => contract_pilot_format_datetime('now', 'Y-m-d'),
                                'value'             => contract_pilot_format_datetime($invoice->due_date, 'Y-m-d'),
                                'placeholder'       => 'YYYY-MM-DD',
                                'class'             => 'contract_pilot_datetimepicker',
                                'attr-autocomplete' => 'off',
                            )
                        );
                        contract_pilot_form_field(
                            array(
                                'label'             => esc_html__('Order Number', 'contract-pilot'),
                                'name'              => 'order_number',
                                'value'             => $invoice->reference,
                                'type'              => 'text',
                                'placeholder'       => 'REF-0001',
                                'attr-autocomplete' => 'off',
                            )
                        );
                        ?>
                        <input type="hidden" name="currency" value="<?php echo esc_attr(contract_pilot_base_currency()); ?>"/>
                        <input type="hidden" name="exchange_rate" value="1"/>
                    </div>
                </div>

                <div class="document-items">
                    <table class="contract-pilot-document-items">
                        <thead class="contract-pilot-document-items__head">
                        <tr>
                            <?php foreach ($columns as $contract_pilot_col_key => $contract_pilot_col_label) : ?>
                                <th class="col-<?php echo esc_attr($contract_pilot_col_key); ?>">
                                    <?php echo esc_html($contract_pilot_col_label); ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                        </thead>
                        <tbody class="contract-pilot-document-items__items">
                        <?php contract_pilot_render_admin_view('partials/invoice-items', [
                            'invoice' => $invoice,
                            'columns' => $columns,
                        ]); ?>
                        </tbody>
                        <tbody class="contract-pilot-document-items__toolbar">
                        <tr>
                            <td colspan="<?php echo esc_attr(count($columns)); ?>">
                                <select class="add-item contract_pilot_select2" data-action="contract_pilot_json_search" data-type="item" data-allow-clear="true" data-placeholder="<?php esc_attr_e('Select a service', 'contract-pilot'); ?>"></select>
                            </td>
                        </tr>
                        </tbody>
                        <tfoot class="contract-pilot-document-items__totals">
                        <?php contract_pilot_render_admin_view('partials/invoice-totals', [
                            'invoice' => $invoice,
                            'columns' => $columns,
                        ]); ?>
                        </tfoot>
                    </table>
                </div><!-- .document-items -->

                <div class="document-footer">
                    <?php
                    contract_pilot_form_field(
                        array(
                            'label'       => __('Notes', 'contract-pilot'),
                            'name'        => 'note',
                            'value'       => $invoice->note,
                            'default'     => get_option('contract_pilot_invoice_note', ''),
                            'type'        => 'textarea',
                            'placeholder' => __('Add notes', 'contract-pilot'),
                        )
                    );


                    contract_pilot_form_field(
                        array(
                            'label'       => __('Terms', 'contract-pilot'),
                            'name'        => 'terms',
                            'value'       => $invoice->terms,
                            'default'     => get_option('contract_pilot_invoice_terms', ''),
                            'type'        => 'textarea',
                            'placeholder' => __('Add terms', 'contract-pilot'),
                        )
                    );

                    ?>
                </div>

            </div>


            <?php

            do_action('contract_pilot_invoice_edit_core_content', $invoice);
            ?>

        </div><!-- .column-1 -->

        <div class="column-2">
            <div class="contract-pilot-card">
                <div class="contract-pilot-card__header">
                    <h3 class="contract-pilot-card__title"><?php esc_html_e('Actions', 'contract-pilot'); ?></h3>
                    <?php if ($invoice->exists()) : ?>
                        <a href="<?php echo esc_url($invoice->get_view_url()); ?>">
                            <?php esc_html_e('View', 'contract-pilot'); ?>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="contract-pilot-card__body">
                    <?php
                    contract_pilot_form_field(
                        array(
                            'label'         => esc_html__('Status', 'contract-pilot'),
                            'name'          => 'status',
                            'id'            => 'contract-pilot-invoice-status',
                            'type'          => 'select',
                            'options'       => $status_options,
                            'value'         => $invoice->status,
                            'class'         => 'widefat',
                            'wrapper_class' => 'contract-pilot-invoice-status-field',
                        )
                    );
                    ?>
                    <div class="contract-pilot-form-field contract-pilot-invoice-status-label-readonly" style="margin-top:12px;">
                        <strong id="contract-pilot-invoice-status-label-heading" class="contract-pilot-field-label" style="display:block;margin-bottom:4px;font-size:11px;text-transform:uppercase;color:#646970;"><?php esc_html_e('Status label', 'contract-pilot'); ?></strong>
                        <p id="contract-pilot-invoice-status-label-display" class="description" style="margin:0;font-size:13px;" aria-labelledby="contract-pilot-invoice-status-label-heading">
                            <?php echo '' !== $contract_pilot_status_label_display ? esc_html($contract_pilot_status_label_display) : '&mdash;'; ?>
                        </p>
                    </div>
                    <?php
                    do_action('contract_pilot_invoice_edit_misc_actions', $invoice);
                    ?>
                </div>
                <div class="contract-pilot-card__footer">
                    <?php if ($invoice->exists()) : ?>
                        <a class="del del_confirm" href="<?php echo esc_url(wp_nonce_url(add_query_arg('action', 'delete', $invoice->get_edit_url()), 'bulk-contracts')); ?>">
                            <?php esc_html_e('Delete', 'contract-pilot'); ?>
                        </a>
                        <button class="button button-primary"><?php esc_html_e('Update', 'contract-pilot'); ?></button>
                    <?php else : ?>
                        <button class="button button-primary button-block"><?php esc_html_e('Save', 'contract-pilot'); ?></button>
                    <?php endif; ?>
                </div>
            </div><!-- .contract-pilot-card -->

            <?php

            do_action('contract_pilot_invoice_edit_sidebar_content', $invoice);
            ?>

        </div><!-- .column-2 -->
    </div><!-- .contract-pilot-poststuff -->

    <input type="hidden" name="action" value="contract_pilot_edit_invoice"/>
    <input type="hidden" name="id" value="<?php echo esc_attr($invoice->id); ?>"/>
    <?php if (! $invoice->exists()) : ?>
        <?php Idempotency::output_token_input('contract_pilot_edit_invoice', 'create_invoice'); ?>
    <?php endif; ?>
    <?php wp_nonce_field('contract_pilot_edit_invoice'); ?>
</form>
