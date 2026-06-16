<?php

defined('ABSPATH') || exit;

/**
 * Invoice line items table body rows (edit screen / Ajax recalc).
 *
 * @var \Jjpsos\ContractPilot\Models\Invoice $invoice
 * @var array<string, string>              $columns
 */

if (! isset($invoice, $columns) || ! is_object($invoice) || ! is_array($columns)) {
    return;
}

?>

<?php if ($invoice->items) { ?>
    <?php foreach ($invoice->items as $contract_pilot_index => $contract_pilot_item) { ?>
        <tr class="contract-pilot-document-items__item" data-id="<?php echo esc_attr(
            $contract_pilot_item->id,
        ); ?>" data-index="<?php echo esc_attr($contract_pilot_index); ?>">
            <?php foreach ($columns as $contract_pilot_col_key => $contract_pilot_col_label) { ?>
                <td class="col-<?php echo esc_attr($contract_pilot_col_key); ?>">
                    <?php switch ($contract_pilot_col_key) {
                        case 'item':
                            printf(
                                '<input type="hidden" name="items[%1$s][id]" value="%1$s" />',
                                esc_attr($contract_pilot_index),
                            );
                            printf(
                                '<input type="hidden" name="items[%1$s][item_id]" value="%2$s" />',
                                esc_attr($contract_pilot_index),
                                esc_attr($contract_pilot_item->item_id),
                            );
                            printf(
                                '<input type="hidden" name="items[%1$s][type]" value="%2$s" />',
                                esc_attr($contract_pilot_index),
                                esc_attr($contract_pilot_item->type),
                            );
                            printf(
                                '<input type="hidden" name="items[%1$s][unit]" value="%2$s" />',
                                esc_attr($contract_pilot_index),
                                esc_attr($contract_pilot_item->unit),
                            );
                            printf(
                                '<input class="item-name" type="text" name="items[%1$s][name]" value="%2$s" placeholder="%3$s" autocomplete="off" data-lpignore="true" readonly/>',
                                esc_attr($contract_pilot_index),
                                esc_attr($contract_pilot_item->name),
                                esc_attr__('Name', 'contract-pilot'),
                            );
                            printf(
                                '<textarea class="item-description" name="items[%1$s][description]" placeholder="%2$s">%3$s</textarea>',
                                esc_attr($contract_pilot_index),
                                esc_attr__('Description', 'contract-pilot'),
                                esc_textarea($contract_pilot_item->description),
                            );
                            if ($invoice->is_taxed()) { ?>
                                <select class="item-taxes contract_pilot_select2" data-action="contract_pilot_json_search" data-type="tax" data-placeholder="<?php esc_attr_e(
                                    'Select a tax',
                                    'contract-pilot',
                                ); ?>" multiple>
                                    <?php if ($contract_pilot_item->taxes) {
                                        foreach ($contract_pilot_item->taxes as $contract_pilot_tax) {
                                            printf(
                                                '<option value="%1$s" selected>%2$s</option>',
                                                esc_attr($contract_pilot_tax->tax_id),
                                                esc_html($contract_pilot_tax->formatted_name),
                                            );
                                        }
                                    } ?>
                                </select>
                                <?php if ($contract_pilot_item->taxes) {
                                    foreach ($contract_pilot_item->taxes as $contract_pilot_tax_index => $contract_pilot_tax) {
                                        printf(
                                            '<input type="hidden" name="items[%1$s][taxes][%2$s][id]" value="%3$s" />',
                                            esc_attr($contract_pilot_index),
                                            esc_attr($contract_pilot_tax_index),
                                            esc_attr($contract_pilot_tax->id),
                                        );

                                        printf(
                                            '<input type="hidden" name="items[%1$s][taxes][%2$s][tax_id]" value="%3$s" />',
                                            esc_attr($contract_pilot_index),
                                            esc_attr($contract_pilot_tax_index),
                                            esc_attr($contract_pilot_tax->tax_id),
                                        );

                                        printf(
                                            '<input type="hidden" name="items[%1$s][taxes][%2$s][name]" value="%3$s" />',
                                            esc_attr($contract_pilot_index),
                                            esc_attr($contract_pilot_tax_index),
                                            esc_attr($contract_pilot_tax->name),
                                        );

                                        printf(
                                            '<input type="hidden" name="items[%1$s][taxes][%2$s][rate]" value="%3$s" />',
                                            esc_attr($contract_pilot_index),
                                            esc_attr($contract_pilot_tax_index),
                                            esc_attr($contract_pilot_tax->rate),
                                        );

                                        printf(
                                            '<input type="hidden" name="items[%1$s][taxes][%2$s][compound]" value="%3$s" />',
                                            esc_attr($contract_pilot_index),
                                            esc_attr($contract_pilot_tax_index),
                                            esc_attr($contract_pilot_tax->compound),
                                        );

                                        printf(
                                            '<input type="hidden" name="items[%1$s][taxes][%2$s][amount]" value="%3$s" />',
                                            esc_attr($contract_pilot_index),
                                            esc_attr($contract_pilot_tax_index),
                                            esc_attr($contract_pilot_tax->amount),
                                        );
                                    }
                                }
                            }

                            break;

                        case 'quantity':
                            printf(
                                '<input type="number" class="item-quantity" name="items[%1$s][quantity]" value="%2$s" placeholder="%3$s" step="any" required/>',
                                esc_attr($contract_pilot_index),
                                esc_attr($contract_pilot_item->quantity),
                                esc_attr__('Quantity', 'contract-pilot'),
                            );
                            break;

                        case 'price':
                            printf(
                                '<input type="number" class="item-price" name="items[%1$s][price]" value="%2$s" placeholder="%3$s" step="any" required/>',
                                esc_attr($contract_pilot_index),
                                esc_attr($contract_pilot_item->price),
                                esc_attr__('Price', 'contract-pilot'),
                            );
                            break;
                        case 'tax':
                            printf(
                                '<span class="item-tax">%s</span>',
                                esc_html(contract_pilot_format_amount($contract_pilot_item->tax, $invoice->currency)),
                            );
                            break;

                        case 'subtotal':
                            printf(
                                '<span class="item-subtotal">%s</span>',
                                esc_html(
                                    contract_pilot_format_amount($contract_pilot_item->subtotal, $invoice->currency),
                                ),
                            );
                            echo sprintf(
                                '<a href="#" class="remove-item" aria-label="%1$s" title="%1$s">x</a>',
                                esc_attr__('Remove item', 'contract-pilot'),
                            );
                            break;

                        case 'default':
                            do_action('contract_pilot_invoice_edit_item_column', $contract_pilot_col_key, $contract_pilot_item);
                            break;
                    } ?>
                </td>
            <?php } ?>
        </tr>
    <?php } ?>
<?php } else { ?>
    <tr class="contract-pilot-document-items__empty">
        <td colspan="<?php echo esc_attr(count($columns)); ?>">
            <?php esc_html_e('No services added yet.', 'contract-pilot'); ?>
        </td>
    </tr>
<?php } ?>
