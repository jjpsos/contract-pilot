<?php

defined('ABSPATH') || exit;

/**
 * Customer profile view.
 *
 * @var \Jjpsos\ContractPilot\Models\Customer $customer
 * @var array<string, array{label: string, icon: string}> $sections
 * @var string $current_section
 */

?><h1 class="wp-heading-inline">
    <?php esc_html_e('View Customer', 'contract-pilot'); ?>
    <a href="<?php echo esc_attr(remove_query_arg(array( 'action', 'id' ))); ?>" title="<?php esc_attr_e('Go back', 'contract-pilot'); ?>">
        <span class="dashicons dashicons-undo"></span>
    </a>
</h1>

<div class="contract-pilot-card contract-pilot-profile-header">
    <div class="contract-pilot-profile-header__avatar">
        <?php echo get_avatar($customer->email, 120); ?>
    </div>
    <div class="contract-pilot-profile-header__columns">
        <div class="contract-pilot-profile-header__column">
            <div class="contract-pilot-profile-header__title">
                <?php echo esc_html($customer->name); ?>
            </div>
            <?php if ($customer->phone) : ?>
                <p class="small"><a href="tel:<?php echo esc_attr($customer->phone); ?>"><?php echo esc_html($customer->phone); ?></a></p>
            <?php endif; ?>
            <?php if ($customer->email) : ?>
                <p class="small"><a href="mailto:<?php echo esc_attr($customer->email); ?>"><?php echo esc_html($customer->email); ?></a></p>
            <?php endif; ?>
            <p class="small">
                <?php ?>
                <?php
                printf(
                    /* translators: %s: customer registration date */
                    esc_html__('Since %s', 'contract-pilot'),
                    esc_html(wp_date(get_option('date_format'), strtotime($customer->date_created)))
                );
                ?>
            </p>
        </div>
    </div>
    <?php if (current_user_can('contract_pilot_edit_customers')) : ?>
        <a class="contract-pilot-profile-header__edit" href="<?php echo esc_url($customer->get_edit_url()); ?>"><span class="dashicons dashicons-edit"></span></a>
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

        do_action('contract_pilot_customer_profile_section_' . $current_section, $customer);
        ?>
    </div>
    <br class="clear">
</div>
