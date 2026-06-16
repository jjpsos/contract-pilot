<?php

defined('ABSPATH') || exit;

/**
 * Account profile overview section.
 *
 * @var string $contract_pilot_chart_title
 * @var array  $contract_pilot_chart
 * @var string $contract_pilot_chart_canvas_id
 * @var string $contract_pilot_chart_currency
 * @var array  $contract_pilot_stats
 * @var int    $contract_pilot_stats_columns
 * @var string $contract_pilot_details_heading
 * @var array  $contract_pilot_attributes
 */

contract_pilot_render_admin_view(
    'partials/overview-chart',
    compact(
        'contract_pilot_chart_title',
        'contract_pilot_chart',
        'contract_pilot_chart_canvas_id',
        'contract_pilot_chart_currency',
    ),
);

contract_pilot_render_admin_view(
    'partials/stats-cards',
    compact('contract_pilot_stats', 'contract_pilot_stats_columns'),
);
?>
<h2><?php echo esc_html($contract_pilot_details_heading); ?></h2>
<?php
contract_pilot_render_admin_view(
    'partials/attributes-table',
    array( 'contract_pilot_attributes' => $contract_pilot_attributes ),
);
