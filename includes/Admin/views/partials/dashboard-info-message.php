<?php

defined('ABSPATH') || exit;

/**
 * Dashboard intro message card and visibility toggle.
 *
 * @var bool   $contract_pilot_message_enabled
 * @var string $contract_pilot_contribution_url
 */

if (! isset($contract_pilot_message_enabled, $contract_pilot_contribution_url)) {
    return;
}

?>
<div class="contract-pilot-card" style="margin-top: 8px;">
    <?php if ($contract_pilot_message_enabled) : ?>
        <div class="contract-pilot-card__header">
            <h3 class="contract-pilot-card__title"><?php esc_html_e('About Contract Pilot', 'contract-pilot'); ?></h3>
        </div>
    <?php endif; ?>
    <div class="contract-pilot-card__body">
        <?php if ($contract_pilot_message_enabled) : ?>
            <p style="margin: 0 0 8px;"><?php esc_html_e('Contract Pilot helps you manage contracts and related business records in WordPress.', 'contract-pilot'); ?></p>
            <p style="margin: 0 0 8px;">
                <?php esc_html_e('Go to', 'contract-pilot'); ?>
                <a href="https://www.softestate.net/contract-pilot/" target="_blank" rel="noopener noreferrer">https://www.softestate.net/contract-pilot/</a>
                <?php esc_html_e('for a showcase of advanced features.', 'contract-pilot'); ?>
            </p>
            <p style="margin: 0 0 8px;">
                <?php
                echo wp_kses(
                    sprintf(
                        /* translators: %1$s: contribution page URL (href), %2$s: same URL as visible link text */
                        __(
                            'Thank you for your aid, <a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>, to this open source project.',
                            'contract-pilot',
                        ),
                        esc_url($contract_pilot_contribution_url),
                        esc_html($contract_pilot_contribution_url),
                    ),
                    array(
                        'a' => array(
                            'href'   => true,
                            'target' => true,
                            'rel'    => true,
                        ),
                    ),
                );
                ?>
            </p>
        <?php endif; ?>
        <form method="post" action="">
            <?php wp_nonce_field('contract_pilot_toggle_dashboard_message'); ?>
            <label for="contract-pilot-dashboard-message-enabled">
                <input type="checkbox" id="contract-pilot-dashboard-message-enabled" name="contract_pilot_dashboard_message_enabled" value="yes" <?php checked($contract_pilot_message_enabled, true); ?> />
                <?php esc_html_e('Show dashboard intro message', 'contract-pilot'); ?>
            </label>
            <p style="margin: 8px 0 0;">
                <button type="submit" class="button" name="contract_pilot_dashboard_message_toggle_submit" value="1"><?php esc_html_e('Save', 'contract-pilot'); ?></button>
            </p>
        </form>
    </div>
</div>
