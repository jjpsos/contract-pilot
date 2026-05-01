<?php

defined("ABSPATH") || exit();

if (!isset($expense) || !is_object($expense)) {
    return;
}
?>
<div class="eac-document">
	<div class="eac-document__header">
		<div class="eac-document__title">
			<h1><?php esc_html_e("Expense", "otto-contracts"); ?></h1>
			<p>
				<strong><?php esc_html_e("Expense #:", "otto-contracts"); ?></strong>
				<?php echo esc_html(isset($expense->number) ? $expense->number : ""); ?>
			</p>
		</div>
	</div>

	<div class="eac-document__divider"></div>

	<div class="eac-document__summary">
		<table>
			<tbody>
			<tr>
				<th scope="row"><?php esc_html_e("Date", "otto-contracts"); ?></th>
				<td>
					<?php echo esc_html(
         !empty($expense->payment_date)
             ? wp_date(eac_date_format(), strtotime($expense->payment_date))
             : "N/A",
     ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e("Amount", "otto-contracts"); ?></th>
				<td><?php echo esc_html(
        !empty($expense->formatted_amount)
            ? $expense->formatted_amount
            : eac_format_amount(
                isset($expense->amount) ? $expense->amount : 0,
                isset($expense->currency) ? $expense->currency : eac_base_currency(),
            ),
    ); ?></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e("Method", "otto-contracts"); ?></th>
				<td><?php echo esc_html(
        !empty($expense->payment_method_label)
            ? $expense->payment_method_label
            : (!empty($expense->payment_method) ? $expense->payment_method : "N/A"),
    ); ?></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e("Reference", "otto-contracts"); ?></th>
				<td><?php echo esc_html(
        !empty($expense->reference) ? $expense->reference : "N/A",
    ); ?></td>
			</tr>
			<?php if (!empty($expense->status_label) || !empty($expense->status)): ?>
				<tr>
					<th scope="row"><?php esc_html_e("Status", "otto-contracts"); ?></th>
					<td><?php echo esc_html(
          !empty($expense->status_label) ? $expense->status_label : $expense->status,
      ); ?></td>
				</tr>
			<?php endif; ?>
			</tbody>
		</table>
	</div>

	<?php if (!empty($expense->note)): ?>
		<div class="eac-document__note">
			<h3><?php esc_html_e("Notes", "otto-contracts"); ?></h3>
			<?php echo wp_kses_post(wpautop($expense->note)); ?>
		</div>
	<?php endif; ?>
</div>
