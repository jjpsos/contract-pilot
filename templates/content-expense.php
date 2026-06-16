<?php

defined('ABSPATH') || exit;

if (!isset($expense) || !is_object($expense)) {
    return;
}
?>
<div class="contract-pilot-document">
    <div class="contract-pilot-document__header">
        <div class="contract-pilot-document__title">
            <h1><?php esc_html_e('Expense', 'contract-pilot'); ?></h1>
            <p>
                <strong><?php esc_html_e('Expense #:', 'contract-pilot'); ?></strong>
                <?php echo esc_html(isset($expense->number) ? $expense->number : ''); ?>
            </p>
        </div>
    </div>

    <div class="contract-pilot-document__divider"></div>

    <div class="contract-pilot-document__summary">
        <table>
            <tbody>
            <tr>
                <th scope="row"><?php esc_html_e('Date', 'contract-pilot'); ?></th>
                <td>
                    <?php echo esc_html(
                        !empty($expense->payment_date)
                            ? wp_date(contract_pilot_date_format(), strtotime($expense->payment_date))
                            : 'N/A',
                    ); ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Amount', 'contract-pilot'); ?></th>
                <td><?php echo esc_html(
                    !empty($expense->formatted_amount)
                        ? $expense->formatted_amount
                        : contract_pilot_format_amount(
                            isset($expense->amount) ? $expense->amount : 0,
                            isset($expense->currency) ? $expense->currency : contract_pilot_base_currency(),
                        ),
                ); ?></td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Method', 'contract-pilot'); ?></th>
                <td><?php echo esc_html(
                    !empty($expense->payment_method_label)
                        ? $expense->payment_method_label
                        : (!empty($expense->payment_method) ? $expense->payment_method : 'N/A'),
                ); ?></td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Reference', 'contract-pilot'); ?></th>
                <td><?php echo esc_html(
                    !empty($expense->reference) ? $expense->reference : 'N/A',
                ); ?></td>
            </tr>
            <?php if (!empty($expense->status_label) || !empty($expense->status)) { ?>
                <tr>
                    <th scope="row"><?php esc_html_e('Status', 'contract-pilot'); ?></th>
                    <td><?php echo esc_html(
                        !empty($expense->status_label) ? $expense->status_label : $expense->status,
                    ); ?></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>

    <?php if (!empty($expense->note)) { ?>
        <div class="contract-pilot-document__note">
            <h3><?php esc_html_e('Notes', 'contract-pilot'); ?></h3>
            <?php echo wp_kses_post(wpautop($expense->note)); ?>
        </div>
    <?php } ?>
</div>
