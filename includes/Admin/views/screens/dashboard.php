<?php

defined('ABSPATH') || exit;

/**
 * Contract Pilot dashboard screen.
 *
 * @var bool                             $contract_pilot_show_reports
 * @var array<string, string>            $contract_pilot_report_tabs
 * @var string                           $contract_pilot_current_report_tab
 * @var string                           $contract_pilot_reports_base_url
 * @var bool                             $contract_pilot_message_enabled
 * @var string                           $contract_pilot_contribution_url
 */

?>
<div class="wrap contract-pilot-wrap">
    <?php
    if (! empty($contract_pilot_show_reports)) {
        contract_pilot_render_admin_view(
            'partials/dashboard-reports-tabs',
            compact(
                'contract_pilot_report_tabs',
                'contract_pilot_current_report_tab',
                'contract_pilot_reports_base_url',
            ),
        );
    }

    contract_pilot_render_admin_view(
        'partials/dashboard-info-message',
        compact(
            'contract_pilot_message_enabled',
            'contract_pilot_contribution_url',
        ),
    );
    ?>
</div>
