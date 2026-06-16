<?php

defined('ABSPATH') || exit;

/**
 * Service add/edit screen.
 *
 * @var \Jjpsos\ContractPilot\Models\Item $item
 * @var array<string, string>             $item_types
 * @var array<string, string>             $item_units
 */

?>
<h1 class="wp-heading-inline">
    <?php if ($item->exists()) : ?>
        <?php esc_html_e('Edit Service', 'contract-pilot'); ?>
        <?php if (current_user_can('contract_pilot_edit_items')) : ?>
            <a href="<?php echo esc_attr(admin_url('admin.php?page=contract-pilot-items&action=add')); ?>" class="button button-small">
                <?php esc_html_e('Add New', 'contract-pilot'); ?>
            </a>
        <?php endif; ?>
    <?php else : ?>
        <?php esc_html_e('Add Service', 'contract-pilot'); ?>
    <?php endif; ?>
    <a href="<?php echo esc_attr(remove_query_arg(array( 'action', 'id' ))); ?>" title="<?php esc_attr_e('Go back', 'contract-pilot'); ?>">
        <span class="dashicons dashicons-undo"></span>
    </a>
</h1>

<form id="contract-pilot-edit-item" name="item" method="post" action="<?php echo esc_html(admin_url('admin-post.php')); ?>">
    <div class="contract-pilot-poststuff">

        <div class="column-1">
            <div id="contract-pilot-item-data" class="contract-pilot-card">
                <div class="contract-pilot-card__header">
                    <h3 class="contract-pilot-card__title"><?php esc_html_e('Service Attributes', 'contract-pilot'); ?></h3>
                </div>
                <div class="contract-pilot-card__body service-attributes-grid">
                    <?php
                    contract_pilot_form_field(
                        array(
                            'label'         => __('Name', 'contract-pilot'),
                            'type'          => 'text',
                            'name'          => 'name',
                            'value'         => $item->name,
                            'placeholder'   => __('Laptop', 'contract-pilot'),
                            'required'      => true,
                            'wrapper_class' => 'is--service-name-row',
                        )
                    );
                    contract_pilot_form_field(
                        array(
                            'type'     => 'select',
                            'name'     => 'type',
                            'required' => true,
                            'default'  => 'product',
                            'label'    => __('Type', 'contract-pilot'),
                            'value'    => $item->type,
                            'options'  => $item_types,
                            'tooltip'  => __('Select the item type: Standard for regular products eligible for discounts, or Fee for extra charges that do not support discounts.', 'contract-pilot'),
                        )
                    );
                    contract_pilot_form_field(
                        array(
                            'type'          => 'text',
                            'name'          => 'price',
                            'label'         => __('Price', 'contract-pilot'),
                            'value'         => $item->price,
                            'placeholder'   => __('10.00', 'contract-pilot'),

                            'tooltip'       => sprintf(
                                /* translators: %s: base currency code */
                                __('Enter the price of the item in %s.', 'contract-pilot'),
                                contract_pilot_base_currency()
                            ),
                            'class'         => 'contract_pilot_amount',
                            'data-currency' => contract_pilot_base_currency(),
                        )
                    );
                    contract_pilot_form_field(
                        array(
                            'type'             => 'select',
                            'name'             => 'category_id',
                            'label'            => __('Category', 'contract-pilot'),
                            'value'            => $item->category_id,
                            'options'          => array( $item->category ),
                            'option_label'     => 'formatted_name',
                            'option_value'     => 'id',
                            'data-placeholder' => __('Select item category', 'contract-pilot'),
                            'class'            => 'contract_pilot_select2',
                            'data-action'      => 'contract_pilot_json_search',
                            'data-type'        => 'category',
                            'data-subtype'     => 'item',
                            'suffix'           => sprintf(
                                '<a class="addon" href="%s" target="_blank" title="%s"><span class="dashicons dashicons-plus"></span></a>',
                                esc_url('admin.php?page=contract-pilot-settings&tab=categories&action=add'),
                                __('Add Category', 'contract-pilot')
                            ),
                        )
                    );
                    contract_pilot_form_field(
                        array(
                            'type'        => 'select',
                            'name'        => 'unit',
                            'label'       => __('Unit', 'contract-pilot'),
                            'value'       => $item->unit,
                            'options'     => $item_units,
                            'placeholder' => __('Select unit', 'contract-pilot'),
                            'class'       => 'contract_pilot_select2',
                            'wrapper_class' => 'is--service-unit-row',
                        )
                    );

                    contract_pilot_form_field(
                        array(
                            'type'          => 'select',
                            'multiple'      => true,
                            'name'          => 'tax_ids',
                            'label'         => __('Taxes', 'contract-pilot'),
                            'value'         => $item->tax_ids,
                            'options'       => $item->taxes,
                            'option_label'  => 'formatted_name',
                            'option_value'  => 'id',
                            'class'         => 'contract_pilot_select2',
                            'data-action'   => 'contract_pilot_json_search',
                            'data-type'     => 'tax',
                            'tooltip'       => __('The selected tax rates will be applied to this item.', 'contract-pilot'),
                            'wrapper_class' => 'is--service-tax-row',
                            'suffix'        => sprintf(
                                '<a class="addon" href="%s" target="_blank" title="%s"><span class="dashicons dashicons-plus"></span></a>',
                                esc_url('admin.php?page=contract-pilot-settings&tab=taxes&section=rates&action=add'),
                                __('Add Tax', 'contract-pilot')
                            ),
                        )
                    );

                    contract_pilot_form_field(
                        array(
                            'type'          => 'textarea',
                            'name'          => 'description',
                            'label'         => __('Description', 'contract-pilot'),
                            'value'         => $item->description,
                            'rows'          => 5,
                            'wrapper_class' => 'is--service-description-row',
                        )
                    );
                    ?>
                </div>
            </div><!-- .contract-pilot-card -->

            <?php

            do_action('contract_pilot_item_edit_core_content', $item);
            ?>
        </div><!-- .column-1 -->

        <div class="column-2">
            <div id="contract-pilot-item-actions" class="contract-pilot-card">
                <div class="contract-pilot-card__header">
                    <h3 class="contract-pilot-card__title"><?php esc_html_e('Actions', 'contract-pilot'); ?></h3>
                </div>
                <?php if (has_action('contract_pilot_item_edit_misc_actions')) : ?>
                    <div class="contract-pilot-card__body">
                        <?php

                        do_action('contract_pilot_item_edit_misc_actions', $item);
                        ?>
                    </div>
                <?php endif; ?>
                <div class="contract-pilot-card__footer">
                    <?php if ($item->exists() && current_user_can('contract_pilot_delete_items')) : ?>
                        <a class="del del_confirm" href="<?php echo esc_url(wp_nonce_url(add_query_arg('action', 'delete', $item->get_edit_url()), 'bulk-services')); ?>"><?php esc_html_e('Delete', 'contract-pilot'); ?></a>
                        <button class="button button-primary"><?php esc_html_e('Update', 'contract-pilot'); ?></button>
                    <?php elseif (current_user_can('contract_pilot_edit_items')) : ?>
                        <button class="button button-primary button-block"><?php esc_html_e('Save', 'contract-pilot'); ?></button>
                    <?php endif; ?>
                </div>
            </div><!-- .contract-pilot-card -->

            <?php

            do_action('contract_pilot_item_edit_sidebar_content', $item);
            ?>

        </div><!-- .column-2 -->

    </div><!-- .contract-pilot-poststuff -->
    <?php wp_nonce_field('contract_pilot_edit_item'); ?>
    <input type="hidden" name="action" value="contract_pilot_edit_item"/>
    <input type="hidden" name="id" value="<?php echo esc_attr($item->id); ?>"/>
</form>
