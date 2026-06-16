<?php

namespace Jjpsos\ContractPilot\Admin\Reports;

use Jjpsos\ContractPilot\Admin\Request;
use Jjpsos\ContractPilot\Utilities\ReportsUtil;

defined('ABSPATH') || exit;

class Expenses
{
    public static function render()
    {
        $year = Request::get_int('year', (int) wp_date('Y'), 'contract_pilot_read_reports');
        $data = ReportsUtil::get_expenses_report($year);
        ?>
        <div class="contract-pilot-section-header">
            <h3>
                <?php echo esc_html__('Contract Pilot Expenses Report', 'contract-pilot'); ?>
            </h3>
            <form class="ea-report-filters" method="get" action="">
                <input type="number" name="year" value="<?php echo esc_attr(
                    $year,
                ); ?>" placeholder="<?php echo esc_attr__(
                    'Year',
                    'contract-pilot',
                ); ?>"/>
                <button type="submit" class="button">
                    <?php echo esc_html__('Submit', 'contract-pilot'); ?>
                </button>
                <input hidden="hidden" name="page" value="contract-pilot"/>
                <input hidden="hidden" name="dashboard_report_tab" value="expenses"/>
            </form>
        </div>

        <div class="contract-pilot-stats stats--3">
            <div class="contract-pilot-stat">
                <div class="contract-pilot-stat__label"><?php esc_html_e(
                    'Total Expense',
                    'contract-pilot',
                ); ?></div>
                <div class="contract-pilot-stat__value"><?php echo esc_html(
                    contract_pilot_format_amount($data['total_amount']),
                ); ?></div>
            </div>
            <div class="contract-pilot-stat">
                <div class="contract-pilot-stat__label"><?php esc_html_e(
                    'Monthly Avg.',
                    'contract-pilot',
                ); ?></div>
                <div class="contract-pilot-stat__value"><?php echo esc_html(
                    contract_pilot_format_amount($data['month_avg']),
                ); ?></div>
            </div>
            <div class="contract-pilot-stat">
                <div class="contract-pilot-stat__label"><?php esc_html_e(
                    'Monthly Aid',
                    'contract-pilot',
                ); ?></div>
                <div class="contract-pilot-stat__value"><?php echo esc_html(
                    contract_pilot_format_amount($data['monthly_aid']),
                ); ?></div>
            </div>
        </div>


        <div class="contract-pilot-card">
            <div class="contract-pilot-card__header">
                <h3 class="contract-pilot-card__title"><?php esc_html_e(
                    'Payments by Months',
                    'contract-pilot',
                ); ?></h3>
            </div>
            <div class="cp-overflow-x-auto">
                <table class="contract-pilot-table has--border">
                    <thead>
                    <tr>
                        <th><?php esc_html_e('Month', 'contract-pilot'); ?></th>
                        <?php foreach (array_keys($data['months']) as $label) { ?>
                            <th><?php echo esc_html($label); ?></th>
                        <?php } ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($data['categories'])) { ?>
                        <?php foreach ($data['categories'] as $category_id => $datum) { ?>
                            <tr>
                                <td>
                                    <?php
                     $term = contract_pilot()->categories->get($category_id);
                            $term_name = $term && $term->name ? $term->name : '&mdash;';
                            echo esc_html($term_name);
                            ?>
                                </td>
                                <?php foreach ($datum as $value) { ?>
                                    <td><?php echo esc_html(contract_pilot_format_amount($value)); ?></td>
                                <?php } ?>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="<?php echo count($data['months']) + 1; ?>">
                                <p>
                                    <?php esc_html_e('No data found', 'contract-pilot'); ?>
                                </p>
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                    <tfoot>
                    <tr>
                        <th><?php esc_html_e('Total', 'contract-pilot'); ?></th>
                        <?php foreach ($data['months'] as $value) { ?>
                            <th><?php echo esc_html(contract_pilot_format_amount($value)); ?></th>
                        <?php } ?>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <?php
    }
}
