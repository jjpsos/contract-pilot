<?php


defined("ABSPATH") || exit(); ?>
<tr>
	<td class="currency">
		<select name="eac_exchange_rates[<?php echo esc_attr($code); ?>]"  required>
			<option value=""><?php esc_html_e(
       "Select Currency",
       "otto-contracts",
   ); ?></option>
			<?php foreach ($currencies as $currency): ?>
				<option value="<?php echo esc_attr($currency["code"]); ?>" <?php selected(
    $code,
    $currency["code"],
); ?>><?php echo esc_html($currency["formatted_name"]); ?></option>
			<?php endforeach; ?>
		</select>
	</td>
	<td class="rate">
		<div>
			1 <?php echo esc_html(
       eac_base_currency(),
   ); ?> = <input type="number" step="any" name="eac_exchange_rates[<?php echo esc_attr(
     $code,
 ); ?>]" value="<?php echo esc_attr($rate); ?>" required>
		</div>
	</td>
	<td class="actions">
		<a href="#" class="remove">
			<span class="dashicons dashicons-trash"></span>
		</a>
	</td>
</tr>
