<?php


use Otto\Models\Note;

defined("ABSPATH") || exit();
?>

<ul class="eac-notes">
	<?php if (empty($notes)): ?>
		<li class="no-items">
			<p><?php esc_html_e("No notes found.", "otto-contracts"); ?></p>
		</li>
	<?php else: ?>
		<?php foreach ($notes as $note): ?>
			<?php include __DIR__ . "/note-item.php"; ?>
		<?php endforeach; ?>
	<?php endif; ?>
</ul>
