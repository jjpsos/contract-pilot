<?php


defined("ABSPATH") || exit();

global $eac_list_table;
?>
	<h1 class="wp-heading-inline">
		<?php esc_html_e("Taxes", "otto-contracts"); ?>
		<a href="<?php echo esc_attr(
      admin_url(
          "admin.php?page=eac-settings&tab=taxes&section=rates&action=add",
      ),
  ); ?>" class="button button-small">
			<?php esc_html_e("Add New", "otto-contracts"); ?>
		</a>
		<a href="<?php echo esc_attr(
      admin_url("admin.php?page=eac-tools"),
  ); ?>" class="button button-small">
			<?php esc_html_e("Import", "otto-contracts"); ?>
		</a>
		<?php if ($eac_list_table->get_request_search()): ?>
			<span class="subtitle"><?php echo esc_html(
       sprintf(
            __(
               'Search results for "%s"',
               "otto-contracts",
           ),
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
		<input type="hidden" name="page" value="eac-settings"/>
		<input type="hidden" name="tab" value="taxes"/>
		<input type="hidden" name="section" value="rates">
	</form>
