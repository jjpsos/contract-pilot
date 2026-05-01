<?php


use Otto\Models\Note;

defined("ABSPATH") || exit();

$author = esc_html__("System", "otto-contracts");
if ($note->author_id) {
    $user_object = get_userdata($note->author_id);
    if ($user_object) {
        $author = !empty($user_object->display_name)
            ? $user_object->display_name
            : $user_object->user_login;
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
       wp_date(eac_date_time_format(), strtotime($note->date_created)),
   ); ?>
			<?php

?>
			<?php echo esc_html(
       sprintf(" " . __("by %s", "otto-contracts"), $author),
   ); ?>
		</abbr>
		<?php if (
      current_user_can("eac_delete_notes")
  ):
       ?>
			<a href="#" class="note__delete" data-nonce="<?php echo esc_attr(
       wp_create_nonce("eac_delete_note"),
   ); ?>" data-note_id="<?php echo esc_attr($note->id); ?>">
				<?php echo esc_html_x("Delete", "Delete", "otto-contracts"); ?>
			</a>
		<?php endif; ?>
	</div>
</li>
