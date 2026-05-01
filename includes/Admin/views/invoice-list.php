<?php


defined("ABSPATH") || exit();
global $eac_list_table;
?>
	<h1 class="wp-heading-inline">
		<?php esc_html_e("Contracts", "otto-contracts"); ?>
		<?php if (
      current_user_can("eac_edit_invoices")
  ):
       ?>
			<a href="<?php echo esc_attr(
       admin_url("admin.php?page=eac-sales&tab=invoices&action=add"),
   ); ?>" class="button button-small">
				<?php esc_html_e("Add New", "otto-contracts"); ?>
			</a>
		<?php endif; ?>
		<?php if ($eac_list_table->get_request_search()): ?>
			<?php
      
      ?>
			<span class="subtitle"><?php echo esc_html(
       sprintf(
           __('Search results for "%s"', "otto-contracts"),
           esc_html($eac_list_table->get_request_search()),
       ),
   ); ?></span>
		<?php endif; ?>
	</h1>
	<form method="get" action="<?php echo esc_url(admin_url("admin.php")); ?>">
		<?php $eac_list_table->views(); ?>
		<?php $eac_list_table->search_box(
      __("Search", "otto-contracts"),
      "search",
  ); ?>
		<?php $eac_list_table->display(); ?>
		<input type="hidden" name="page" value="eac-sales"/>
		<input type="hidden" name="tab" value="invoices"/>
		<input type="hidden" name="status" value="<?php echo esc_attr(
      filter_input(INPUT_GET, "status", FILTER_SANITIZE_FULL_SPECIAL_CHARS),
  ); ?>"/>
	</form>
