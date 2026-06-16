<?php

defined('ABSPATH') || exit;

/**
 * Tax rate add/edit screen.
 *
 * @var \Jjpsos\ContractPilot\Models\Tax $tax
 */

?>
<h1 class="wp-heading-inline">
    <?php if ($tax->exists()) : ?>
        <?php esc_html_e('Edit Rate', 'contract-pilot'); ?>
        <a href="<?php echo esc_attr(admin_url('admin.php?page=contract-pilot-settings&tab=taxes&section=rates&action=add')); ?>" class="button button-small">
            <?php esc_html_e('Add New', 'contract-pilot'); ?>
        </a>
    <?php else : ?>
        <?php esc_html_e('Add Rate', 'contract-pilot'); ?>
    <?php endif; ?>
    <a href="<?php echo esc_attr(remove_query_arg(array( 'action', 'id' ))); ?>" title="<?php esc_attr_e('Go back', 'contract-pilot'); ?>">
        <span class="dashicons dashicons-undo"></span>
    </a>
</h1>

<form id="contract-pilot-edit-tax" name="tax" method="post" action="<?php echo esc_html(admin_url('admin-post.php')); ?>">
    <div class="contract-pilot-poststuff">
        <div class="column-1">

            <div class="contract-pilot-card">
                <div class="contract-pilot-card__header">
                    <h2 class="contract-pilot-card__title"><?php esc_html_e('Tax Attributes', 'contract-pilot'); ?></h2>
                </div>
                <div class="contract-pilot-card__body grid--fields">
                    <?php
                    contract_pilot_form_field(
                        array(
                            'id'          => 'name',
                            'label'       => __('Name', 'contract-pilot'),
                            'placeholder' => __('Enter tax rate name', 'contract-pilot'),
                            'value'       => $tax->name,
                            'required'    => true,
                        )
                    );
                    contract_pilot_form_field(
                        array(
                            'data_type'   => 'decimal',
                            'id'          => 'rate',
                            'label'       => __('Rate (%)', 'contract-pilot'),
                            'placeholder' => __('Enter tax rate', 'contract-pilot'),
                            'value'       => $tax->rate,
                            'required'    => true,
                            'type'        => 'number',
                            'attr-step'   => 'any',
                        )
                    );

                    contract_pilot_form_field(
                        array(
                            'id'       => 'compound',
                            'label'    => __('Compound', 'contract-pilot'),
                            'value'    => filter_var($tax->compound, FILTER_VALIDATE_BOOLEAN) ? 'yes' : 'no',
                            'required' => true,
                            'options'  => array(
                                'yes' => __('Yes', 'contract-pilot'),
                                'no'  => __('No', 'contract-pilot'),
                            ),
                            'type'     => 'select',
                        )
                    );
                    ?>
                </div>
            </div>

            <?php

            do_action('contract_pilot_tax_edit_core_content', $tax);
            ?>
        </div><!-- .column-1 -->

        <div class="column-2">
            <div id="contract-pilot-tax-actions" class="contract-pilot-card">
                <div class="contract-pilot-card__header">
                    <h3 class="contract-pilot-card__title"><?php esc_html_e('Actions', 'contract-pilot'); ?></h3>
                </div>
                <div class="contract-pilot-card__footer">
                    <?php if ($tax->exists()) : ?>
                        <a class="del del_confirm" href="<?php echo esc_url(wp_nonce_url(add_query_arg('action', 'delete', $tax->get_edit_url()), 'bulk-taxes')); ?>"><?php esc_html_e('Delete', 'contract-pilot'); ?></a>
                        <button class="button button-primary"><?php esc_html_e('Update Tax', 'contract-pilot'); ?></button>
                    <?php else : ?>
                        <button class="button button-primary button-block"><?php esc_html_e('Add Tax', 'contract-pilot'); ?></button>
                    <?php endif; ?>
                </div>
            </div><!-- .contract-pilot-card -->

            <?php

            do_action('contract_pilot_tax_edit_sidebar_content', $tax);
            ?>

        </div><!-- .column-2 -->

    </div><!-- .contract-pilot-poststuff -->
    <?php wp_nonce_field('contract_pilot_edit_tax'); ?>
    <input type="hidden" name="action" value="contract_pilot_edit_tax"/>
    <input type="hidden" name="id" value="<?php echo esc_attr($tax->id); ?>"/>
</form>
