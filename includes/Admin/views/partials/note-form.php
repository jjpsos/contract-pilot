<?php

defined('ABSPATH') || exit;

/**
 * Add note form fragment.
 *
 * @var int         $contract_pilot_note_parent_id
 * @var string      $contract_pilot_note_parent_type
 * @var string|null $contract_pilot_note_capability
 */

if (empty($contract_pilot_note_parent_id) || '' === $contract_pilot_note_parent_type) {
    return;
}

$contract_pilot_note_capability = isset($contract_pilot_note_capability)
    ? $contract_pilot_note_capability
    : 'contract_pilot_edit_notes';

if (! current_user_can($contract_pilot_note_capability)) {
    return;
}

?>
<div class="contract-pilot-form-field">
    <label for="contract-pilot-note"><?php esc_html_e('Add Note', 'contract-pilot'); ?></label>
    <textarea id="contract-pilot-note" cols="30" rows="2" placeholder="<?php esc_attr_e('Enter Note', 'contract-pilot'); ?>"></textarea>
</div>
<button id="contract-pilot-add-note" type="button" class="button cp-mb-20" data-parent_id="<?php echo esc_attr($contract_pilot_note_parent_id); ?>" data-parent_type="<?php echo esc_attr($contract_pilot_note_parent_type); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('contract_pilot_add_note')); ?>">
    <?php esc_html_e('Add Note', 'contract-pilot'); ?>
</button>
