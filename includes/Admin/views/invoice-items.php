<?php


use Otto\Models\Invoice;

defined("ABSPATH") || exit();
?>

<?php if ($invoice->items): ?>
	<?php foreach ($invoice->items as $index => $item): ?>
		<tr class="eac-document-items__item" data-id="<?php echo esc_attr(
      $item->id,
  ); ?>" data-index="<?php echo esc_attr($index); ?>">
			<?php foreach ($columns as $column => $label): ?>
				<td class="col-<?php echo esc_attr($column); ?>">
					<?php switch ($column) {
         case "item":
             printf(
                 '<input type="hidden" name="items[%1$s][id]" value="%1$s" />',
                 esc_attr($index),
             );
             printf(
                 '<input type="hidden" name="items[%1$s][item_id]" value="%2$s" />',
                 esc_attr($index),
                 esc_attr($item->item_id),
             );
             printf(
                 '<input type="hidden" name="items[%1$s][type]" value="%2$s" />',
                 esc_attr($index),
                 esc_attr($item->type),
             );
             printf(
                 '<input type="hidden" name="items[%1$s][unit]" value="%2$s" />',
                 esc_attr($index),
                 esc_attr($item->unit),
             );
             printf(
                 '<input class="item-name" type="text" name="items[%1$s][name]" value="%2$s" placeholder="%3$s" autocomplete="off" data-lpignore="true" readonly/>',
                 esc_attr($index),
                 esc_attr($item->name),
                 esc_attr__("Name", "otto-contracts"),
             );
             printf(
                 '<textarea class="item-description" name="items[%1$s][description]" placeholder="%2$s">%3$s</textarea>',
                 esc_attr($index),
                 esc_attr__("Description", "otto-contracts"),
                 esc_textarea($item->description),
             );
             if ($invoice->is_taxed()) { ?>
								<select class="item-taxes eac_select2" data-action="eac_json_search" data-type="tax" data-placeholder="<?php esc_attr_e(
            "Select a tax",
            "otto-contracts",
        ); ?>" multiple>
									<?php if ($item->taxes) {
             foreach ($item->taxes as $tax) {
                 printf(
                     '<option value="%1$s" selected>%2$s</option>',
                     esc_attr($tax->tax_id),
                     esc_html($tax->formatted_name),
                 );
             }
         } ?>
								</select>
								<?php if ($item->taxes) {
            foreach ($item->taxes as $ti => $tax) {
                printf(
                    '<input type="hidden" name="items[%1$s][taxes][%2$s][id]" value="%3$s" />',
                    esc_attr($index),
                    esc_attr($ti),
                    esc_attr($tax->id),
                );

                printf(
                    '<input type="hidden" name="items[%1$s][taxes][%2$s][tax_id]" value="%3$s" />',
                    esc_attr($index),
                    esc_attr($ti),
                    esc_attr($tax->tax_id),
                );

                printf(
                    '<input type="hidden" name="items[%1$s][taxes][%2$s][name]" value="%3$s" />',
                    esc_attr($index),
                    esc_attr($ti),
                    esc_attr($tax->name),
                );

                printf(
                    '<input type="hidden" name="items[%1$s][taxes][%2$s][rate]" value="%3$s" />',
                    esc_attr($index),
                    esc_attr($ti),
                    esc_attr($tax->rate),
                );

                printf(
                    '<input type="hidden" name="items[%1$s][taxes][%2$s][compound]" value="%3$s" />',
                    esc_attr($index),
                    esc_attr($ti),
                    esc_attr($tax->compound),
                );

                printf(
                    '<input type="hidden" name="items[%1$s][taxes][%2$s][amount]" value="%3$s" />',
                    esc_attr($index),
                    esc_attr($ti),
                    esc_attr($tax->amount),
                );
            }
        }}

             break;

         case "quantity":
             printf(
                 '<input type="number" class="item-quantity" name="items[%1$s][quantity]" value="%2$s" placeholder="%3$s" step="any" required/>',
                 esc_attr($index),
                 esc_attr($item->quantity),
                 esc_attr__("Quantity", "otto-contracts"),
             );
             break;

         case "price":
             printf(
                 '<input type="number" class="item-price" name="items[%1$s][price]" value="%2$s" placeholder="%3$s" step="any" required/>',
                 esc_attr($index),
                 esc_attr($item->price),
                 esc_attr__("Price", "otto-contracts"),
             );
             break;
         case "tax":
             printf(
                 '<span class="item-tax">%s</span>',
                 esc_html(eac_format_amount($item->tax, $invoice->currency)),
             );
             break;

         case "subtotal":
             printf(
                 '<span class="item-subtotal">%s</span>',
                 esc_html(
                     eac_format_amount($item->subtotal, $invoice->currency),
                 ),
             );
             echo '<a href="#" class="remove-item"><span class="dashicons dashicons-trash"></span></a>';
             break;

         case "default":
             
             do_action("eac_invoice_edit_item_column", $column, $item);
             break;
     } ?>
				</td>
			<?php endforeach; ?>
		</tr>
	<?php endforeach; ?>
<?php else: ?>
	<tr class="eac-document-items__empty">
		<td colspan="<?php echo esc_attr(count($columns)); ?>">
			<?php esc_html_e("No services added yet.", "otto-contracts"); ?>
		</td>
	</tr>
<?php endif; ?>
