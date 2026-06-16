<?php

defined('ABSPATH') || exit;

/**
 * Customer add/edit screen.
 *
 * @var \Jjpsos\ContractPilot\Models\Customer $customer
 * @var array<string, string>                 $countries
 */

?>
<h1 class="wp-heading-inline">
    <?php if ($customer->exists()) : ?>
        <?php esc_html_e('Edit Customer', 'contract-pilot'); ?>
        <a href="<?php echo esc_attr(admin_url('admin.php?page=contract-pilot-sales&tab=customers&action=add')); ?>" class="button button-small">
            <?php esc_html_e('Add New', 'contract-pilot'); ?>
        </a>
    <?php else : ?>
        <?php esc_html_e('Add Customer', 'contract-pilot'); ?>
    <?php endif; ?>
    <a href="<?php echo esc_attr(remove_query_arg(array( 'action', 'id' ))); ?>" title="<?php esc_attr_e('Go back', 'contract-pilot'); ?>">
        <span class="dashicons dashicons-undo"></span>
    </a>
</h1>

<form id="contract-pilot-customer-form" name="customer" method="post" action="<?php echo esc_html(admin_url('admin-post.php')); ?>">
    <div class="contract-pilot-poststuff">
        <div class="column-1">

            <!--Customer basic details-->
            <div class="contract-pilot-card">
                <div class="contract-pilot-card__header">
                    <h2 class="contract-pilot-card__title"><?php esc_html_e('Basic Details', 'contract-pilot'); ?></h2>
                </div>
                <div class="contract-pilot-card__body grid--fields">
                    <?php
                    contract_pilot_form_field(
                        array(
                            'id'          => 'name',
                            'label'       => __('Name', 'contract-pilot'),
                            'placeholder' => __('John Doe', 'contract-pilot'),
                            'value'       => $customer->name,
                            'required'    => true,
                        )
                    );
                    contract_pilot_form_field(
                        array(
                            'id'          => 'currency_display',
                            'name'        => 'currency_display',
                            'type'        => 'text',
                            'label'       => __('Currency Code', 'contract-pilot'),
                            'value'       => contract_pilot_base_currency(),
                            'placeholder' => '',
                            'required'    => false,
                            'readonly'    => true,
                            'disabled'    => true,
                        )
                    );
                    ?>
                    <input type="hidden" name="currency" value="<?php echo esc_attr(contract_pilot_base_currency()); ?>"/>
                    <?php
                    contract_pilot_form_field(
                        array(
                            'id'          => 'email',
                            'type'        => 'email',
                            'label'       => __('Email', 'contract-pilot'),
                            'placeholder' => __('john@company.com', 'contract-pilot'),
                            'value'       => $customer->email,
                        )
                    );
                    contract_pilot_form_field(
                        array(
                            'id'          => 'phone',
                            'label'       => __('Phone', 'contract-pilot'),
                            'placeholder' => __('+1 123 456 7890', 'contract-pilot'),
                            'value'       => $customer->phone,
                        )
                    );
                    ?>
                </div>
            </div>

            <!--Customer Business details-->
            <div class="contract-pilot-card">
                <div class="contract-pilot-card__header">
                    <h2 class="contract-pilot-card__title"><?php esc_html_e('Business Details', 'contract-pilot'); ?></h2>
                </div>
                <div class="contract-pilot-card__body grid--fields">
                    <?php
                    contract_pilot_form_field(
                        array(
                            'id'          => 'company',
                            'label'       => __('Company', 'contract-pilot'),
                            'placeholder' => __('XYZ Inc.', 'contract-pilot'),
                            'value'       => $customer->company,
                        )
                    );
                    contract_pilot_form_field(
                        array(
                            'id'          => 'website',
                            'type'        => 'url',
                            'label'       => __('Website', 'contract-pilot'),
                            'placeholder' => __('https://example.com', 'contract-pilot'),
                            'value'       => $customer->website,
                        )
                    );
                    contract_pilot_form_field(
                        array(
                            'id'          => 'tax_number',
                            'label'       => __('Tax Number', 'contract-pilot'),
                            'placeholder' => __('123456789', 'contract-pilot'),
                            'value'       => $customer->tax_number,
                        )
                    );
                    ?>
                </div>
            </div>

            <!--Customer Address details-->
            <div class="contract-pilot-card">
                <div class="contract-pilot-card__header">
                    <h2 class="contract-pilot-card__title"><?php esc_html_e('Address Details', 'contract-pilot'); ?></h2>
                </div>
                <div class="contract-pilot-card__body grid--fields">
                    <?php
                    contract_pilot_form_field(
                        array(
                            'id'          => 'address',
                            'label'       => __('Address', 'contract-pilot'),
                            'placeholder' => __('123 Main St', 'contract-pilot'),
                            'value'       => $customer->address,
                        )
                    );
                    contract_pilot_form_field(
                        array(
                            'id'          => 'city',
                            'label'       => __('City', 'contract-pilot'),
                            'placeholder' => __('New York', 'contract-pilot'),
                            'value'       => $customer->city,
                        )
                    );
                    contract_pilot_form_field(
                        array(
                            'id'          => 'state',
                            'label'       => __('State', 'contract-pilot'),
                            'placeholder' => __('NY', 'contract-pilot'),
                            'value'       => $customer->state,
                        )
                    );
                    contract_pilot_form_field(
                        array(
                            'id'          => 'postcode',
                            'label'       => __('Postal Code', 'contract-pilot'),
                            'placeholder' => __('10001', 'contract-pilot'),
                            'value'       => $customer->postcode,
                        )
                    );
                    contract_pilot_form_field(
                        array(
                            'type'        => 'select',
                            'id'          => 'country',
                            'label'       => __('Country', 'contract-pilot'),
                            'options'     => $countries,
                            'value'       => $customer->country,
                            'class'       => 'contract-pilot-select2',
                            'placeholder' => __('Select Country', 'contract-pilot'),
                        )
                    );
                    ?>
                </div>
            </div>

            <?php

            do_action('contract_pilot_customer_edit_core_content', $customer);
            ?>
        </div><!-- .column-1 -->

        <div class="column-2">
            <div class="contract-pilot-card">
                <div class="contract-pilot-card__header">
                    <h2 class="contract-pilot-card__title"><?php esc_html_e('Actions', 'contract-pilot'); ?></h2>
                    <?php if ($customer->exists()) : ?>
                        <a href="<?php echo esc_url($customer->get_view_url()); ?>">
                            <?php esc_html_e('View', 'contract-pilot'); ?>
                        </a>
                    <?php endif; ?>
                </div>
                <?php if (has_action('contract_pilot_customer_misc_actions')) : ?>
                    <div class="contract-pilot-card__body">
                        <?php

                        do_action('contract_pilot_customer_edit_misc_actions', $customer);
                        ?>
                    </div>
                <?php endif; ?>
                <div class="contract-pilot-card__footer">
                    <?php if ($customer->exists()) : ?>
                        <a class="contract_pilot_confirm_delete del" href="<?php echo esc_url(wp_nonce_url(add_query_arg('action', 'delete', admin_url('admin.php?page=contract-pilot-sales&tab=customers&id=' . $customer->id)), 'bulk-customers')); ?>"><?php esc_html_e('Delete', 'contract-pilot'); ?></a>
                        <button class="button button-primary"><?php esc_html_e('Update Customer', 'contract-pilot'); ?></button>
                    <?php else : ?>
                        <button class="button button-primary button-block"><?php esc_html_e('Add Customer', 'contract-pilot'); ?></button>
                    <?php endif; ?>
                </div>
            </div>

            <?php

            do_action('contract_pilot_customer_edit_sidebar_content', $customer);
            ?>

        </div><!-- .column-2 -->

    </div><!-- .contract-pilot-poststuff -->

    <?php wp_nonce_field('contract_pilot_edit_customer'); ?>
    <input type="hidden" name="action" value="contract_pilot_edit_customer"/>
    <input type="hidden" name="id" value="<?php echo esc_attr($customer->id); ?>"/>
</form>
