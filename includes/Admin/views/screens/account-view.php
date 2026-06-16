<?php

defined('ABSPATH') || exit;

/**
 * Account profile view.
 *
 * @var \Jjpsos\ContractPilot\Models\Account $account
 * @var string                               $currency_symbol
 * @var array<string, array{label: string, icon: string}> $sections
 * @var string                               $current_section
 */

?><h1 class="wp-heading-inline">
    <?php esc_html_e('View Account', 'contract-pilot'); ?>
    <a href="<?php echo esc_attr(remove_query_arg(array( 'action', 'id' ))); ?>" title="<?php esc_attr_e('Go back', 'contract-pilot'); ?>">
        <span class="dashicons dashicons-undo"></span>
    </a>
</h1>


<div class="contract-pilot-card contract-pilot-profile-header">
    <div class="contract-pilot-profile-header__avatar">
        <div class="avatar cp-flex cp-items-center cp-justify-center cp-w-16 cp-h-16 cp-rounded-full cp-bg-blue-500 cp-text-white cp-text-2xl cp-font-bold">
        <?php echo esc_html($currency_symbol); ?>
        </div>
    </div>
    <div class="contract-pilot-profile-header__columns">
        <div class="contract-pilot-profile-header__column">
            <div class="contract-pilot-profile-header__title">
                <?php echo esc_html($account->name); ?>
            </div>
            <p class="small"><?php printf('%1$s %2$s', esc_html__('Balance:', 'contract-pilot'), esc_html($account->formatted_balance)); ?></p>
            <?php if ($account->number) : ?>
                <p class="small"><?php printf('%1$s %2$s', esc_html__('Account #:', 'contract-pilot'), esc_html($account->number)); ?></p>
            <?php endif; ?>
            <p class="small">
                <?php ?>
                <?php
                printf(
                    /* translators: %s: account creation date */
                    esc_html__('Since %s', 'contract-pilot'),
                    esc_html(wp_date(contract_pilot_date_format(), strtotime($account->date_created)))
                );
                ?>
            </p>
        </div>
    </div>
    <?php if (current_user_can('contract_pilot_edit_accounts')) : ?>
    <a class="contract-pilot-profile-header__edit" href="<?php echo esc_url($account->get_edit_url()); ?>"><span class="dashicons dashicons-edit"></span></a>
    <?php endif; ?>
</div>

<div class="contract-pilot-profile-sections">
    <ul class="contract-pilot-profile-sections__nav" role="tablist">
        <?php foreach ($sections as $contract_pilot_nav_key => $contract_pilot_section_nav) : ?>
            <li id="<?php echo esc_attr($contract_pilot_nav_key); ?>-nav-item" class="contract-pilot-profile-sections__nav-item <?php echo $current_section === $contract_pilot_nav_key ? 'is-active' : ''; ?>" role="tab" aria-controls="<?php echo esc_attr($contract_pilot_nav_key); ?>">
                <a href="<?php echo esc_url(add_query_arg('section', $contract_pilot_nav_key)); ?>">
                    <span class="dashicons dashicons-<?php echo esc_attr($contract_pilot_section_nav['icon']); ?>"></span>
                    <span class="label">
                        <?php echo esc_html($contract_pilot_section_nav['label']); ?>
                    </span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
    <div class="contract-pilot-profile-sections__content">
        <?php

        do_action('contract_pilot_account_profile_section_' . $current_section, $account);
        ?>
    </div>
    <br class="clear">
</div>
