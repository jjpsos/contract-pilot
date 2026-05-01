<?php


defined("ABSPATH") || exit(); ?>
<table class="eac-exchange-rates">
	<thead>
	<tr>
		<th class="currency"><?php esc_html_e("Currency", "otto-contracts"); ?></th>
		<th class="rate"><?php esc_html_e("Rate", "otto-contracts"); ?></th>
		<td class="actions" width="1%"></td>
	</tr>
	</thead>
	<tbody>
	<?php foreach ($rates as $code => $rate): ?>
		<?php include __DIR__ . "/exchange-rate.php"; ?>
	<?php endforeach; ?>
	</tbody>
	<tfoot>
	<tr>
		<td colspan="3">
			<a href="#" class="button add" data-row="
			<?php
   ob_start();
   $rate = "";
   $code = "";
   require __DIR__ . "/exchange-rate.php";
   echo esc_attr(ob_get_clean());
   ?>
			">
				<?php esc_html_e("Add Exchange Rate", "otto-contracts"); ?>
			</a>
		</td>
	</tr>
</table>
