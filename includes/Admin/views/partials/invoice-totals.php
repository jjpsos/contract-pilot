<?php

defined('ABSPATH') || exit;

/**
 * Invoice totals footer rows (edit screen / Ajax recalc).
 *
 * @var \Jjpsos\ContractPilot\Models\Invoice $invoice
 * @var array<string, string>              $columns
 */

if (! isset($invoice, $columns) || ! is_object($invoice) || ! is_array($columns)) {
    return;
}

?>

<tr>
    <td class="col-label" colspan="<?php echo esc_attr(count($columns) - 1); ?>"><?php esc_html_e('Subtotal', 'contract-pilot'); ?></td>
    <td class="col-amount"><?php echo esc_html(
        $invoice->formatted_subtotal,
    ); ?></td>
</tr>

<?php if ($invoice->is_taxed()) { ?>
    <?php if ('single' === get_option('contract_pilot_tax_total_display')) { ?>
        <tr>
            <td class="col-label" colspan="<?php echo esc_attr(count($columns) - 1); ?>">
                <?php esc_html_e('Tax', 'contract-pilot'); ?>
            </td>
            <td class="col-amount">
                <?php echo esc_html($invoice->formatted_tax); ?>
            </td>
        </tr>
    <?php } else { ?>
        <?php foreach ($invoice->get_itemized_taxes() as $contract_pilot_tax) { ?>
            <tr>
                <td class="col-label" colspan="<?php echo esc_attr(count($columns) - 1); ?>">
                    <?php echo esc_html($contract_pilot_tax->formatted_name); ?>
                </td>
                <td class="col-amount">
                    <?php echo esc_html(contract_pilot_format_amount($contract_pilot_tax->amount, $invoice->currency)); ?>
                </td>
            </tr>
        <?php } ?>
    <?php } ?>
<?php } ?>

<tr>
    <td class="col-label" colspan="<?php echo esc_attr(count($columns) - 1); ?>">
        <?php esc_html_e('Total', 'contract-pilot'); ?>
    </td>
    <td class="col-amount">
        <?php echo esc_html($invoice->formatted_total); ?>
    </td>
</tr>
