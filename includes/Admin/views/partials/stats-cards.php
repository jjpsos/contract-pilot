<?php

defined('ABSPATH') || exit;

/**
 * Stat cards grid fragment.
 *
 * @var array $contract_pilot_stats
 * @var int   $contract_pilot_stats_columns
 */

if (empty($contract_pilot_stats) || ! is_array($contract_pilot_stats)) {
    return;
}

if (empty($contract_pilot_stats_columns)) {
    $contract_pilot_stats_columns = count($contract_pilot_stats);
}

?>
<div class="contract-pilot-stats stats--<?php echo esc_attr($contract_pilot_stats_columns); ?>">
    <?php foreach ($contract_pilot_stats as $contract_pilot_stat) { ?>
        <div class="contract-pilot-stat">
            <div class="contract-pilot-stat__label"><?php echo esc_html($contract_pilot_stat['label']); ?></div>
            <div class="contract-pilot-stat__value">
                <?php echo esc_html($contract_pilot_stat['value']); ?>
                <?php if (isset($contract_pilot_stat['delta'])) { ?>
                    <?php $contract_pilot_delta_class = $contract_pilot_stat['delta'] > 0 ? 'is--positive' : 'is--negative'; ?>
                    <div class="contract-pilot-stat__delta <?php echo esc_attr($contract_pilot_delta_class); ?>">
                        <?php echo esc_html($contract_pilot_stat['delta']); ?>%
                    </div>
                <?php } ?>
            </div>
            <?php if (isset($contract_pilot_stat['meta'])) { ?>
                <div class="contract-pilot-stat__meta">
                    <span><?php echo wp_kses_post(
                        implode(' </span><span> ', $contract_pilot_stat['meta']),
                    ); ?></span>
                </div>
            <?php } ?>
        </div>
    <?php } ?>
</div>
