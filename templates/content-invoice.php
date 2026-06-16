<?php

defined('ABSPATH') || exit;

(static function ($view_tpl, $invoice_tpl) {
    if ((!is_array($view_tpl) || [] === $view_tpl) && is_object($invoice_tpl)) {
        $view_tpl = contract_pilot_build_invoice_view_data($invoice_tpl);
    }

    $view = is_array($view_tpl) ? $view_tpl : [];
    $business = isset($view['business']) && is_array($view['business']) ? $view['business'] : [];
    $columns = isset($view['columns']) && is_array($view['columns']) ? $view['columns'] : [];
    $item_rows = isset($view['item_rows']) && is_array($view['item_rows']) ? $view['item_rows'] : [];
    $totals = isset($view['totals']) && is_array($view['totals']) ? $view['totals'] : [];
    $payments = isset($view['payments']) && is_array($view['payments']) ? $view['payments'] : [];

    $doc_title = isset($view['doc_title']) ? $view['doc_title'] : '';
    $invoice_number = isset($view['invoice_number']) ? $view['invoice_number'] : '';
    $order_number = isset($view['order_number']) ? $view['order_number'] : '';
    $issue_date = isset($view['issue_date']) ? $view['issue_date'] : '';
    $due_date = isset($view['due_date']) ? $view['due_date'] : '';
    $bill_from_address = isset($view['bill_from_address']) ? $view['bill_from_address'] : '';
    $bill_to_address = isset($view['bill_to_address']) ? $view['bill_to_address'] : '';
    $note = isset($view['note']) ? $view['note'] : '';
    $terms = isset($view['terms']) ? $view['terms'] : '';

    $business_logo = isset($business['logo']) ? $business['logo'] : '';
    $business_phone = isset($business['phone']) ? $business['phone'] : '';
    $business_email = isset($business['email']) ? $business['email'] : '';
    $business_name = isset($business['name']) ? $business['name'] : '';
    $business_site_url = isset($business['site_url']) ? $business['site_url'] : '';
    ?>
<div class="contract-pilot-document">
    <div class="contract-pilot-document__header">
        <?php if ($business_logo && filter_var($business_logo, FILTER_VALIDATE_URL)) { ?>
            <div class="contract-pilot-document__logo">
                <img src="<?php echo esc_url($business_logo); ?>" alt="<?php esc_attr_e(
                    'Logo',
                    'contract-pilot',
                ); ?>"/>
            </div>
        <?php } ?>
        <div class="contract-pilot-document__info">
            <?php if (!empty($business_name)) { ?>
                <h2><?php echo esc_html($business_name); ?></h2>
            <?php } ?>
            <?php if (!empty($business_phone)) { ?>
                <p><?php echo esc_html($business_phone); ?></p>
            <?php } ?>
            <?php if (!empty($business_email)) { ?>
                <p><?php echo esc_html($business_email); ?></p>
            <?php } ?>
            <p>
                <?php echo esc_html($business_site_url); ?>
            </p>
        </div>
        <div class="contract-pilot-document__title">
            <h1><?php echo esc_html($doc_title); ?></h1>
            <p>
                <strong><?php esc_html_e('Contract #:', 'contract-pilot'); ?></strong>
                <?php echo esc_html($invoice_number); ?>
            </p>
            <?php if ($order_number) { ?>
                <p>
                    <strong><?php esc_html_e('Order:', 'contract-pilot'); ?></strong>
                    <?php echo esc_html($order_number); ?>
                </p>
            <?php } ?>
            <?php if ($issue_date) { ?>
                <p>
                    <strong><?php esc_html_e('Issue:', 'contract-pilot'); ?></strong>
                    <?php echo esc_html($issue_date); ?>
                </p>
            <?php } ?>
            <?php if ($due_date) { ?>
                <p>
                    <strong><?php esc_html_e('Due:', 'contract-pilot'); ?></strong>
                    <?php echo esc_html($due_date); ?>
                </p>
            <?php } ?>
        </div>
    </div>
    <div class="contract-pilot-document__divider"></div>
    <div class="contract-pilot-document__billings">
        <div class="contract-pilot-document__billing">
            <h3><?php esc_html_e('From', 'contract-pilot'); ?></h3>
            <p>
                <?php echo wp_kses_post($bill_from_address); ?>
            </p>
        </div>
        <div class="contract-pilot-document__billing">
            <h3><?php esc_html_e('To', 'contract-pilot'); ?></h3>
            <p>
                <?php echo wp_kses_post($bill_to_address); ?>
            </p>
        </div>
    </div>
    <div class="contract-pilot-document__divider"></div>
    <div class="contract-pilot-document__items">
        <table>
            <thead>
            <tr>
                <?php foreach ($columns as $column_key => $column) { ?>
                    <th class="col-<?php echo esc_attr($column_key); ?>">
                        <?php echo esc_html($column); ?>
                    </th>
                <?php } ?>
            </tr>
            </thead>
            <tbody>
            <?php if (!empty($item_rows)) { ?>
                <?php foreach ($item_rows as $row) { ?>
                    <tr>
                        <?php foreach ($columns as $column_key => $column_label) { ?>
                            <?php
                        $cell = isset($row[$column_key]) && is_array($row[$column_key])
                            ? $row[$column_key]
                            : ['value' => '', 'subvalue' => ''];
                            ?>
                            <td class="col-<?php echo esc_attr($column_key); ?>">
                                <?php echo esc_html(isset($cell['value']) ? $cell['value'] : ''); ?>
                                <?php if (!empty($cell['subvalue'])) { ?>
                                    <span class="contract-pilot-item-description"><?php echo nl2br(esc_html($cell['subvalue'])); ?></span>
                                <?php } ?>
                            </td>
                        <?php } ?>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="<?php echo esc_attr(count($columns)); ?>">
                        <?php esc_html_e('No services found.', 'contract-pilot'); ?>
                    </td>
                </tr>
            <?php } ?>
            </tbody>

            <tfoot>
            <?php foreach ($totals as $total) { ?>
                <tr>
                    <td class="col-label" colspan="<?php echo esc_attr(max(1, count($columns) - 1)); ?>">
                        <?php echo esc_html(isset($total['label']) ? $total['label'] : ''); ?>
                    </td>
                    <td class="col-amount<?php echo !empty($total['is_due']) ? ' col-amount--due' : ''; ?>">
                        <?php echo esc_html(isset($total['amount']) ? $total['amount'] : ''); ?>
                    </td>
                </tr>
            <?php } ?>
            </tfoot>
        </table>
    </div>

    <?php if ($note) { ?>
        <div class="contract-pilot-document__note">
            <h3><?php esc_html_e('Notes', 'contract-pilot'); ?></h3>
            <?php echo wp_kses_post(wpautop($note)); ?>
        </div>
    <?php } ?>
    <?php if (!empty($payments)) { ?>
        <div class="contract-pilot-document__divider"></div>
        <div class="contract-pilot-document__payments">
            <h3><?php esc_html_e('Payments', 'contract-pilot'); ?></h3>
            <table>
                <thead>
                <tr>
                    <th><?php esc_html_e('Payment #', 'contract-pilot'); ?></th>
                    <th><?php esc_html_e('Date', 'contract-pilot'); ?></th>
                    <th><?php esc_html_e('Method', 'contract-pilot'); ?></th>
                    <th><?php esc_html_e('Amount', 'contract-pilot'); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($payments as $payment) { ?>
                    <tr>
                        <td>
                            <a href="<?php echo esc_url(isset($payment['url']) ? $payment['url'] : ''); ?>">
                                <?php echo esc_html(isset($payment['number']) ? $payment['number'] : ''); ?>
                            </a>
                        </td>
                        <td><?php echo esc_html(isset($payment['date']) ? $payment['date'] : 'N/A'); ?></td>
                        <td><?php echo esc_html(isset($payment['method']) ? $payment['method'] : 'N/A'); ?></td>
                        <td><?php echo esc_html(isset($payment['amount']) ? $payment['amount'] : ''); ?></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    <?php } ?>
    <?php if (!empty($terms)) { ?>
        <div class="contract-pilot-document__footer">
            <?php echo wp_kses_post(wpautop($terms)); ?>
        </div>
    <?php } ?>
</div>
<?php
})(isset($view) ? $view : null, isset($invoice) ? $invoice : null);
