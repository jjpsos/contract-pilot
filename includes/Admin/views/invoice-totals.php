<?php


use Otto\Models\Invoice;

defined("ABSPATH") || exit();
?>

<tr>
	<td class="col-label" colspan="<?php echo count($columns) -
     1; ?>"><?php esc_html_e("Discount", "otto-contracts"); ?></td>
	<td class="col-amount">
		<div class="eac-input-group">
			<select name="discount_type" id="discount_type" class="addon">
				<?php foreach (
        [
            "fixed" => '($)',
            "percentage" => "(%)",
        ]
        as $key => $label
    ): ?>
					<option value="<?php echo esc_attr($key); ?>" <?php selected(
    $key,
    $invoice->discount_type,
); ?>>
						<?php echo esc_html($label); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<input type="number" name="discount_value" id="discount_value" placeholder="0" style="text-align: right;width: auto;" value="<?php echo esc_attr(
       $invoice->discount_value,
   ); ?>"/>
		</div>
	</td>
</tr>

<tr>
	<td class="col-label" colspan="<?php echo count($columns) -
     1; ?>"><?php esc_html_e("Subtotal", "otto-contracts"); ?></td>
	<td class="col-amount"><?php echo esc_html(
     $invoice->formatted_subtotal,
 ); ?></td>
</tr>

<tr>
	<td class="col-label" colspan="<?php echo count($columns) - 1; ?>">
		<?php esc_html_e("Discount", "otto-contracts"); ?>
	</td>
	<td class="col-amount">
		<?php echo esc_html($invoice->formatted_discount); ?>
	</td>
</tr>

<?php if ($invoice->is_taxed()): ?>
	<?php if ("single" === get_option("eac_tax_total_display")): ?>
		<tr>
			<td class="col-label" colspan="<?php echo count($columns) - 1; ?>">
				<?php esc_html_e("Tax", "otto-contracts"); ?>
			</td>
			<td class="col-amount">
				<?php echo esc_html($invoice->formatted_tax); ?>
			</td>
		</tr>
	<?php else: ?>
		<?php foreach ($invoice->get_itemized_taxes() as $tax): ?>
			<tr>
				<td class="col-label" colspan="<?php echo count($columns) - 1; ?>">
					<?php echo esc_html($tax->formatted_name); ?>
				</td>
				<td class="col-amount">
					<?php echo esc_html(eac_format_amount($tax->amount, $invoice->currency)); ?>
				</td>
			</tr>
		<?php endforeach; ?>
	<?php endif; ?>
<?php endif; ?>

<tr>
	<td class="col-label" colspan="<?php echo count($columns) - 1; ?>">
		<?php esc_html_e("Total", "otto-contracts"); ?>
	</td>
	<td class="col-amount">
		<?php echo esc_html($invoice->formatted_total); ?>
	</td>
</tr>
