<?php

defined('ABSPATH') || exit;

/**
 * Single note list item.
 *
 * @var \Jjpsos\ContractPilot\Models\Note|null $note
 */

if (! isset($note) || ! is_object($note)) {
    return;
}

$contract_pilot_author = esc_html__('System', 'contract-pilot');
if ($note->author_id) {
    $contract_pilot_user_object = get_userdata($note->author_id);
    if ($contract_pilot_user_object) {
        $contract_pilot_author = ! empty($contract_pilot_user_object->display_name)
            ? $contract_pilot_user_object->display_name
            : $contract_pilot_user_object->user_login;
    }
}
?>
<li class="note" id="note-<?php echo esc_attr($note->id); ?>">
    <div class="note__content">
        <?php echo wp_kses_post(
            wpautop(wptexturize(make_clickable($note->content))),
        ); ?>
    </div>
    <div class="note__meta">
        <abbr class="exact-date" title="<?php echo esc_attr($note->date_created); ?>">
            <?php echo esc_html(
                wp_date(contract_pilot_date_time_format(), strtotime($note->date_created)),
            ); ?>
            <?php echo esc_html(
                ' '
                . sprintf(
                    /* translators: %s: note author name */
                    __('by %s', 'contract-pilot'),
                    $contract_pilot_author
                )
            ); ?>
        </abbr>
        <?php if (current_user_can('contract_pilot_delete_notes')) { ?>
            <a href="#" class="note__delete" data-nonce="<?php echo esc_attr(
                wp_create_nonce('contract_pilot_delete_note'),
            ); ?>" data-note_id="<?php echo esc_attr($note->id); ?>">
                <?php echo esc_html_x('Delete', 'Delete', 'contract-pilot'); ?>
            </a>
        <?php } ?>
    </div>
</li>
