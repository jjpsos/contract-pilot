<?php

defined('ABSPATH') || exit;

/**
 * Notes list fragment.
 *
 * @var array $contract_pilot_notes
 */

?>
<ul class="contract-pilot-notes">
    <?php if (empty($contract_pilot_notes)) : ?>
        <li class="no-items">
            <p><?php esc_html_e('No notes found.', 'contract-pilot'); ?></p>
        </li>
    <?php else : ?>
        <?php foreach ($contract_pilot_notes as $contract_pilot_note) : ?>
            <?php contract_pilot_render_admin_view('partials/note-item', array( 'note' => $contract_pilot_note )); ?>
        <?php endforeach; ?>
    <?php endif; ?>
</ul>
