<?php

defined('ABSPATH') || exit;

/**
 * Read-only attachment card fragment.
 *
 * @var int $contract_pilot_attachment_id
 */

?>
<div class="contract-pilot-card">
    <div class="contract-pilot-card__header">
        <h3 class="contract-pilot-card__title"><?php esc_html_e('Attachment', 'contract-pilot'); ?></h3>
    </div>
    <div class="contract-pilot-card__body">
        <?php
        contract_pilot_file_uploader(
            array(
                'value'    => $contract_pilot_attachment_id,
                'readonly' => true,
            )
        );
        ?>
    </div>
</div>
