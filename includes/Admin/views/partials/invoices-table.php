<?php

defined('ABSPATH') || exit;

/**
 * Recent invoices table fragment.
 *
 * @var array $contract_pilot_invoices
 */

?>
<h2 class="has--border"><?php esc_html_e('Recent Invoices', 'contract-pilot'); ?></h2>
<table class="widefat fixed striped">
    <thead>
    <tr>
        <th><?php esc_html_e('Number', 'contract-pilot'); ?></th>
        <th><?php esc_html_e('Date', 'contract-pilot'); ?></th>
        <th><?php esc_html_e('Total', 'contract-pilot'); ?></th>
        <th><?php esc_html_e('Status', 'contract-pilot'); ?></th>
    </tr>
    </thead>
    <tbody>
    <?php if (! empty($contract_pilot_invoices)) { ?>
        <?php foreach ($contract_pilot_invoices as $contract_pilot_invoice) { ?>
            <tr>
                <td>
                    <a href="<?php echo esc_url($contract_pilot_invoice->get_view_url()); ?>">
                        <?php echo esc_html($contract_pilot_invoice->number); ?>
                    </a>
                </td>
                <td><?php echo esc_html($contract_pilot_invoice->issue_date); ?></td>
                <td><?php echo esc_html($contract_pilot_invoice->formatted_total); ?></td>
                <td><?php echo esc_html($contract_pilot_invoice->status_label); ?></td>
            </tr>
        <?php } ?>
    <?php } else { ?>
        <tr>
            <td colspan="4"><?php esc_html_e('No invoices found.', 'contract-pilot'); ?></td>
        </tr>
    <?php } ?>
    </tbody>
</table>
