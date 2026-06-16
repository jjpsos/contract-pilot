<?php

defined('ABSPATH') || exit;

/**
 * Customers list screen.
 *
 * @var \Jjpsos\ContractPilot\Admin\ListTables\Customers|null $contract_pilot_list_table
 */

if (! $contract_pilot_list_table) {
    return;
}
?>
    <h1 class="wp-heading-inline">
        <?php esc_html_e('Customers', 'contract-pilot'); ?>
        <?php if (current_user_can('contract_pilot_edit_customers')) { ?>
            <a href="<?php echo esc_attr(admin_url('admin.php?page=contract-pilot-sales&tab=customers&action=add')); ?>" class="button button-small">
                <?php esc_html_e('Add New', 'contract-pilot'); ?>
            </a>
        <?php } ?>
        <?php if ($contract_pilot_list_table->get_request_search()) { ?>
            <span class="subtitle"><?php echo esc_html(
                sprintf(
                    /* translators: %s: search query text. */
                    __('Search results for "%s"', 'contract-pilot'),
                    esc_html($contract_pilot_list_table->get_request_search()),
                ),
            ); ?></span>
        <?php } ?>
    </h1>
    <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>">
        <?php $contract_pilot_list_table->views(); ?>
        <?php $contract_pilot_list_table->search_box(__('Search', 'contract-pilot'), 'search'); ?>
        <?php $contract_pilot_list_table->display(); ?>
        <input type="hidden" name="page" value="contract-pilot-sales"/>
        <input type="hidden" name="tab" value="customers"/>
    </form>
