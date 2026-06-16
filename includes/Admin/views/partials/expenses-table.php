<?php

defined('ABSPATH') || exit;

/**
 * Recent expenses table fragment.
 *
 * @var array $contract_pilot_expenses
 */

?>
<h2 class="has--border"><?php esc_html_e('Recent Expenses', 'contract-pilot'); ?></h2>
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
    <?php if (! empty($contract_pilot_expenses)) { ?>
        <?php foreach ($contract_pilot_expenses as $contract_pilot_expense) { ?>
            <tr>
                <td>
                    <a href="<?php echo esc_url($contract_pilot_expense->get_view_url()); ?>">
                        <?php echo esc_html($contract_pilot_expense->number); ?>
                    </a>
                </td>
                <td><?php echo esc_html(
                    wp_date(contract_pilot_date_format(), strtotime($contract_pilot_expense->payment_date)),
                ); ?></td>
                <td><?php echo esc_html(
                    $contract_pilot_expense->reference ? $contract_pilot_expense->reference : '&mdash;',
                ); ?></td>
                <td><?php echo esc_html($contract_pilot_expense->formatted_amount); ?></td>
            </tr>
        <?php } ?>
    <?php } else { ?>
        <tr>
            <td colspan="4"><?php esc_html_e('No expenses found.', 'contract-pilot'); ?></td>
        </tr>
    <?php } ?>
    </tbody>
</table>
