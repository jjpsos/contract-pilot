<?php

namespace Otto\Admin\Reports;

use Otto\Utilities\ReportsUtil;

defined("ABSPATH") || exit();


class Profits
{
    
    public static function render()
    {
        $year = !empty($_GET["year"]) ? absint($_GET["year"]) : wp_date("Y"); 
        $data = ReportsUtil::get_profits_report($year);
        ?>
		<div class="eac-section-header">
			<h3>
				<?php echo esc_html__("Otto Profits Report", "otto-contracts"); ?>
			</h3>
			<form class="ea-report-filters" method="get" action="">
				<input type="number" name="year" value="<?php echo esc_attr(
        $year,
    ); ?>" placeholder="<?php echo esc_attr__(
    "Year",
    "otto-contracts",
); ?>"/>
				<button type="submit" class="button">
					<?php echo esc_html__("Submit", "otto-contracts"); ?>
				</button>
				<input hidden="hidden" name="page" value="eac-reports"/>
				<input hidden="hidden" name="tab" value="profits"/>
			</form>
		</div>

		<div class="eac-stats stats--3">
			<div class="eac-stat">
				<div class="eac-stat__label"><?php esc_html_e(
        "Total Profit",
        "otto-contracts",
    ); ?></div>
				<div class="eac-stat__value"><?php echo esc_html(
        eac_format_amount($data["total_profit"]),
    ); ?></div>
			</div>
			<div class="eac-stat">
				<div class="eac-stat__label"><?php esc_html_e(
        "Monthly Avg.",
        "otto-contracts",
    ); ?></div>
				<div class="eac-stat__value"><?php echo esc_html(
        eac_format_amount($data["month_avg"]),
    ); ?></div>
			</div>
			<div class="eac-stat">
				<div class="eac-stat__label"><?php esc_html_e(
        "Profit - Aid",
        "otto-contracts",
    ); ?></div>
				<div class="eac-stat__value"><?php echo esc_html(
        eac_format_amount($data["profit_aid"]),
    ); ?></div>
			</div>
		</div>

		<div class="eac-card">
			<div class="eac-card__header">
				<h3 class="eac-card__title"><?php esc_html_e(
        "Profits by Months",
        "otto-contracts",
    ); ?></h3>
			</div>
			<div class="tw-overflow-x-auto">
				<table class="eac-table has--border">
					<thead>
					<tr>
						<th><?php esc_html_e("Month", "otto-contracts"); ?></th>
						<?php foreach (array_keys($data["profits"]) as $label): ?>
							<th><?php echo esc_html($label); ?></th>
						<?php endforeach; ?>
					</tr>
					</thead>
					<tbody>
					<tr>
						<th><?php esc_html_e("Payments", "otto-contracts"); ?></th>
						<?php foreach ($data["payments"] as $value): ?>
							<td><?php echo esc_html(eac_format_amount($value)); ?></td>
						<?php endforeach; ?>
					</tr>
					<tr>
						<th><?php esc_html_e("Expenses", "otto-contracts"); ?></th>
						<?php foreach ($data["expenses"] as $value): ?>
							<td><?php echo esc_html(eac_format_amount($value)); ?></td>
						<?php endforeach; ?>
					</tr>
					</tbody>
					<tfoot>
					<tr>
						<th><?php esc_html_e("Profit", "otto-contracts"); ?></th>
						<?php foreach ($data["profits"] as $value): ?>
							<th><?php echo esc_html(eac_format_amount($value)); ?></th>
						<?php endforeach; ?>
					</tr>
					</tfoot>
				</table>
			</div>
		</div>
		<?php
    }
}
