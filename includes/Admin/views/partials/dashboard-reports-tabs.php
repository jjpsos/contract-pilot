<?php

defined('ABSPATH') || exit;

use Jjpsos\ContractPilot\Admin\Reports;

/**
 * Dashboard sales/expenses/profits report tabs.
 *
 * @var array<string, string> $contract_pilot_report_tabs
 * @var string                $contract_pilot_current_report_tab
 * @var string                $contract_pilot_reports_base_url
 */

if (
    empty($contract_pilot_report_tabs)
    || ! is_array($contract_pilot_report_tabs)
    || '' === $contract_pilot_reports_base_url
) {
    return;
}

?>
<div class="contract-pilot-card" style="margin-top: 0;">
    <div class="contract-pilot-card__body">
        <nav class="nav-tab-wrapper contract-pilot-navbar">
            <?php foreach ($contract_pilot_report_tabs as $contract_pilot_tab_key => $contract_pilot_tab_label) : ?>
                <a href="<?php echo esc_url(add_query_arg('dashboard_report_tab', $contract_pilot_tab_key, $contract_pilot_reports_base_url)); ?>" class="nav-tab <?php echo esc_attr($contract_pilot_current_report_tab === $contract_pilot_tab_key ? 'nav-tab-active' : ''); ?>">
                    <?php echo esc_html($contract_pilot_tab_label); ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <div style="margin-top: 12px;">
            <?php
            switch ($contract_pilot_current_report_tab) {
                case 'expenses':
                    Reports\Expenses::render();
                    break;
                case 'profits':
                    Reports\Profits::render();
                    break;
                case 'sales':
                default:
                    Reports\Sales::render();
                    break;
            }
            ?>
        </div>
    </div>
</div>
