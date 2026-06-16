<?php

defined('ABSPATH') || exit;

/**
 * Recent payments table fragment.
 *
 * @var array $contract_pilot_payments
 */

?>
<h2 class="has--border"><?php esc_html_e('Recent Payments', 'contract-pilot'); ?></h2>
<table class="widefat fixed striped">
    <thead>
    <tr>
        <th><?php esc_html_e('Number', 'contract-pilot'); ?></th>
        <th><?php esc_html_e('Date', 'contract-pilot'); ?></th>
        <th><?php esc_html_e('Reference', 'contract-pilot'); ?></th>
        <th><?php esc_html_e('Amount', 'contract-pilot'); ?></th>
    </tr>
    </thead>
    <tbody>
    <?php if (! empty($contract_pilot_payments)) { ?>
        <?php foreach ($contract_pilot_payments as $contract_pilot_payment) { ?>
            <tr>
                <td>
                    <a href="<?php echo esc_url($contract_pilot_payment->get_view_url()); ?>">
                        <?php echo esc_html($contract_pilot_payment->number); ?>
                    </a>
                </td>
                <td><?php echo esc_html(
                    $contract_pilot_payment->payment_date
                        ? wp_date(contract_pilot_date_format(), strtotime($contract_pilot_payment->payment_date))
                        : '&mdash;',
                ); ?></td>
                <td><?php echo esc_html(
                    $contract_pilot_payment->reference ? $contract_pilot_payment->reference : '&mdash;',
                ); ?></td>
                <td><?php echo esc_html($contract_pilot_payment->formatted_amount); ?></td>
            </tr>
        <?php } ?>
    <?php } else { ?>
        <tr>
            <td colspan="4"><?php esc_html_e('No payments found.', 'contract-pilot'); ?></td>
        </tr>
    <?php } ?>
    </tbody>
</table>
