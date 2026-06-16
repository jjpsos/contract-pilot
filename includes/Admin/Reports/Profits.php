<?php

namespace Jjpsos\ContractPilot\Admin\Reports;

use Jjpsos\ContractPilot\Admin\Request;
use Jjpsos\ContractPilot\Utilities\ReportsUtil;

defined('ABSPATH') || exit;

class Profits
{
    public static function render()
    {
        $year = Request::get_int('year', (int) wp_date('Y'), 'contract_pilot_read_reports');
        $data = ReportsUtil::get_profits_report($year);
        ?>
        <div class="contract-pilot-section-header">
            <h3>
                <?php echo esc_html__('Contract Pilot Profits Report', 'contract-pilot'); ?>
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
                <input hidden="hidden" name="dashboard_report_tab" value="profits"/>
            </form>
        </div>

        <div class="contract-pilot-stats stats--3">
            <div class="contract-pilot-stat">
                <div class="contract-pilot-stat__label"><?php esc_html_e(
                    'Total Profit',
                    'contract-pilot',
                ); ?></div>
                <div class="contract-pilot-stat__value"><?php echo esc_html(
                    contract_pilot_format_amount($data['total_profit']),
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
                    'Profit - Aid',
                    'contract-pilot',
                ); ?></div>
                <div class="contract-pilot-stat__value"><?php echo esc_html(
                    contract_pilot_format_amount($data['profit_aid']),
                ); ?></div>
            </div>
        </div>

        <div class="contract-pilot-card">
            <div class="contract-pilot-card__header">
                <h3 class="contract-pilot-card__title"><?php esc_html_e(
                    'Profits by Months',
                    'contract-pilot',
                ); ?></h3>
            </div>
            <div class="cp-overflow-x-auto">
                <table class="contract-pilot-table has--border">
                    <thead>
                    <tr>
                        <th><?php esc_html_e('Month', 'contract-pilot'); ?></th>
                        <?php foreach (array_keys($data['profits']) as $label) { ?>
                            <th><?php echo esc_html($label); ?></th>
                        <?php } ?>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <th><?php esc_html_e('Payments', 'contract-pilot'); ?></th>
                        <?php foreach ($data['payments'] as $value) { ?>
                            <td><?php echo esc_html(contract_pilot_format_amount($value)); ?></td>
                        <?php } ?>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Expenses', 'contract-pilot'); ?></th>
                        <?php foreach ($data['expenses'] as $value) { ?>
                            <td><?php echo esc_html(contract_pilot_format_amount($value)); ?></td>
                        <?php } ?>
                    </tr>
                    </tbody>
                    <tfoot>
                    <tr>
                        <th><?php esc_html_e('Profit', 'contract-pilot'); ?></th>
                        <?php foreach ($data['profits'] as $value) { ?>
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
