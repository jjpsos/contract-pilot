<?php

defined('ABSPATH') || exit;

/**
 * Overview line chart fragment.
 *
 * @var string $contract_pilot_chart_title
 * @var array  $contract_pilot_chart
 * @var string $contract_pilot_chart_canvas_id
 * @var string $contract_pilot_chart_currency
 */

if (empty($contract_pilot_chart_canvas_id)) {
    return;
}

?>
<h2 class="has--border"><?php echo esc_html($contract_pilot_chart_title); ?></h2>
<div class="contract-pilot-chart">
    <canvas class="contract-pilot-chart" id="<?php echo esc_attr($contract_pilot_chart_canvas_id); ?>" style="height: 300px;margin-bottom: 20px;" data-datasets="<?php echo esc_attr(wp_json_encode($contract_pilot_chart)); ?>" data-currency="<?php echo esc_attr($contract_pilot_chart_currency); ?>"></canvas>
</div>
