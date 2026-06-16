<?php

defined('ABSPATH') || exit;

/**
 * Notes card (sidebar on entity view/edit screens).
 *
 * @var array  $contract_pilot_notes
 * @var int    $contract_pilot_note_parent_id
 * @var string $contract_pilot_note_parent_type
 * @var string|null $contract_pilot_note_capability
 */

if (empty($contract_pilot_note_parent_id) || '' === $contract_pilot_note_parent_type) {
    return;
}

$contract_pilot_notes_data = compact(
    'contract_pilot_notes',
    'contract_pilot_note_parent_id',
    'contract_pilot_note_parent_type',
);

if (isset($contract_pilot_note_capability)) {
    $contract_pilot_notes_data['contract_pilot_note_capability'] = $contract_pilot_note_capability;
}

?>
<div class="contract-pilot-card">
    <div class="contract-pilot-card__header">
        <h3 class="contract-pilot-card__title"><?php esc_html_e('Notes', 'contract-pilot'); ?></h3>
    </div>
    <div class="contract-pilot-card__body">
        <?php
        contract_pilot_render_admin_view('partials/note-form', $contract_pilot_notes_data);
        contract_pilot_render_admin_view('partials/note-list', array( 'contract_pilot_notes' => $contract_pilot_notes ));
        ?>
    </div>
</div>
