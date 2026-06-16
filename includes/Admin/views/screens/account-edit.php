<?php

defined('ABSPATH') || exit;

/**
 * Account add/edit screen.
 *
 * @var \Jjpsos\ContractPilot\Models\Account $account
 * @var array<string, string>                $account_types
 */

?><div class="contract-pilot-section-header">
    <h1 class="wp-heading-inline">
        <?php if ($account->exists()) : ?>
            <?php esc_html_e('Edit Account', 'contract-pilot'); ?>
            <a href="<?php echo esc_attr(admin_url('admin.php?page=contract-pilot-banking&tab=accounts&action=add')); ?>" class="button button-small">
                <?php esc_html_e('Add New', 'contract-pilot'); ?>
            </a>
        <?php else : ?>
            <?php esc_html_e('Add Account', 'contract-pilot'); ?>
        <?php endif; ?>
        <a href="<?php echo esc_attr(remove_query_arg(array( 'action', 'id' ))); ?>" title="<?php esc_attr_e('Go back', 'contract-pilot'); ?>">
            <span class="dashicons dashicons-undo"></span>
        </a>
    </h1>
</div>

<form id="contract-pilot-edit-account" name="account" method="post" action="<?php echo esc_html(admin_url('admin-post.php')); ?>">
    <div class="contract-pilot-poststuff">
        <div class="column-1">
            <div class="contract-pilot-card">
                <div class="contract-pilot-card__header">
                    <h2 class="contract-pilot-card__title"><?php esc_html_e('Account Attributes', 'contract-pilot'); ?></h2>
                </div>

                <div class="contract-pilot-card__body grid--fields">
                    <?php
                    contract_pilot_form_field(
                        array(
                            'label'       => __('Name', 'contract-pilot'),
                            'type'        => 'text',
                            'name'        => 'name',
                            'value'       => $account->name,
                            'placeholder' => __('XYZ Saving Account', 'contract-pilot'),
                            'required'    => true,
                        )
                    );

                    contract_pilot_form_field(
                        array(
                            'label'       => __('Number', 'contract-pilot'),
                            'type'        => 'text',
                            'name'        => 'number',
                            'value'       => $account->number,
                            'placeholder' => __('1234567890', 'contract-pilot'),
                            'required'    => true,
                        )
                    );

                    contract_pilot_form_field(
                        array(
                            'label'       => __('Type', 'contract-pilot'),
                            'type'        => 'select',
                            'name'        => 'type',
                            'value'       => $account->type,
                            'options'     => $account_types,
                            'placeholder' => __('Select Type', 'contract-pilot'),
                            'required'    => true,
                        )
                    );

                    contract_pilot_form_field(
                        array(
                            'label'       => __('Currency', 'contract-pilot'),
                            'type'        => 'text',
                            'name'        => 'currency_display',
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
                    ?>
                </div><!-- .contract-pilot-card__body -->
            </div>
            <?php

            do_action('contract_pilot_account_edit_core_content', $account);
            ?>
        </div><!-- .column-1 -->
        <div class="column-2">
            <div class="contract-pilot-card">
                <div class="contract-pilot-card__header">
                    <h3 class="contract-pilot-card__title"><?php esc_html_e('Actions', 'contract-pilot'); ?></h3>
                </div>
                <div class="contract-pilot-card__body">
                    <?php

                    do_action('contract_pilot_account_edit_misc_actions', $account);
                    ?>
                </div>
                <div class="contract-pilot-card__footer">
                    <?php if ($account->exists()) : ?>
                        <a class="del del_confirm" href="<?php echo esc_url(wp_nonce_url(add_query_arg('action', 'delete', $account->get_edit_url()), 'bulk-accounts')); ?>">
                            <?php esc_html_e('Delete', 'contract-pilot'); ?>
                        </a>
                        <button class="button button-primary"><?php esc_html_e('Update Account', 'contract-pilot'); ?></button>
                    <?php else : ?>
                        <button class="button button-primary button-large button-block"><?php esc_html_e('Add Account', 'contract-pilot'); ?></button>
                    <?php endif; ?>
                </div>
            </div><!-- .contract-pilot-card -->

            <?php

            do_action('contract_pilot_account_edit_sidebar_content', $account);
            ?>

        </div><!-- .column-2 -->
    </div>


    <?php wp_nonce_field('contract_pilot_edit_account'); ?>
    <input type="hidden" name="action" value="contract_pilot_edit_account"/>
    <input type="hidden" name="id" value="<?php echo esc_attr($account->id); ?>"/>
</form>
