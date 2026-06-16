<?php

namespace Jjpsos\ContractPilot\Admin\ListTables;

use Jjpsos\ContractPilot\Admin\Request;

defined('ABSPATH') || exit;

abstract class ListTable extends \WP_List_Table
{
    public $base_url;

    public function __construct($args = [])
    {
        parent::__construct($args);
        remove_filter(
            "manage_{$this->screen->id}_columns",
            [$this, 'get_columns'],
            0,
        );
    }

    protected function get_request_orderby()
    {
        return Request::get_string('orderby');
    }

    protected function get_request_order()
    {
        $order = Request::get_string('order');
        if ('desc' === strtolower($order)) {
            return 'DESC';
        }

        return 'ASC';
    }

    protected function get_request_status($fallback = null)
    {
        $status = Request::get_string('status');

        return empty($status) ? $fallback : $status;
    }

    protected function get_request_type($fallback = null)
    {
        $type = Request::get_string('type');

        return empty($type) ? $fallback : $type;
    }

    public function get_request_search()
    {
        return Request::get_string('s');
    }

    protected function process_actions()
    {
        $this->_column_headers = [
            $this->get_columns(),
            get_hidden_columns($this->screen),
            $this->get_sortable_columns(),
        ];

        $action = $this->current_action();
        if (
            !empty($action)
            && array_key_exists($action, $this->get_bulk_actions())
        ) {
            check_admin_referer('bulk-'.$this->_args['plural']);

            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Bulk ids read after check_admin_referer above.
            $ids = isset($_GET['id'])
                ? map_deep(wp_unslash($_GET['id']), 'intval')
                : [];
            $ids = wp_parse_id_list($ids);
            $method = 'bulk_'.$action;
            if (
                array_key_exists($action, $this->get_bulk_actions())
                && method_exists($this, $method)
                && !empty($ids)
            ) {
                $this->$method($ids);
            }
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Post-bulk-action redirect cleanup; nonce already verified above.
        if (isset($_GET['_wpnonce']) && isset($_SERVER['REQUEST_URI'])) {
            wp_safe_redirect(
                remove_query_arg(
                    ['_wp_http_referer', '_wpnonce', 'id', 'action', 'action2'],
                    esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])),
                ),
            );
            exit;
        }
    }

    public function column_metadata($items)
    {
        if (!empty($items)) {
            $items = is_array($items) ? $items : [$items];
            $items = array_filter($items);
            $metadata = sprintf(
                '<div class="column-metadata"><span>%s</span></div>',
                implode('</span><span>', $items),
            );

            return wp_kses_post($metadata);
        }

        return '';
    }

    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'status':
                $statuses = [
                    'active' => __('Active', 'contract-pilot'),
                    'inactive' => __('Inactive', 'contract-pilot'),
                ];
                $status = isset($item->$column_name) ? $item->$column_name : '';
                $label = isset($statuses[$status]) ? $statuses[$status] : '';

                return sprintf(
                    '<span class="contract-pilot-status is--%1$s">%2$s</span>',
                    esc_attr($status),
                    esc_html($label),
                );

            default:
                if (is_object($item) && isset($item->$column_name)) {
                    return empty($item->$column_name)
                        ? '&mdash;'
                        : wp_kses_post($item->$column_name);
                }
        }

        return '&mdash;';
    }

    protected function category_filter($type)
    {
        $category_id = Request::get_int('category_id');
        $category = empty($category_id)
            ? null
            : contract_pilot()->categories->get($category_id);
        ?>
        <select class="contract_pilot_select2" name="category_id" id="filter-by-category" data-action="contract_pilot_json_search" data-type="category" data-subtype="<?php echo esc_attr(
            $type,
        ); ?>" data-placeholder="<?php esc_attr_e('Filter by category', 'contract-pilot'); ?>">
            <?php if (!empty($category)) { ?>
                <option value="<?php echo esc_attr($category->id); ?>" <?php selected(
                    $category_id,
                    $category->id,
                ); ?>>
                    <?php echo esc_html($category->name); ?>
                </option>
            <?php } ?>
        </select>
        <?php
    }

    protected function account_filter()
    {
        $account_id = Request::get_int('account_id');
        $account = empty($account_id)
            ? null
            : contract_pilot()->accounts->get($account_id);
        ?>
        <select class="contract_pilot_select2" name="account_id" id="filter-by-account" data-action="contract_pilot_json_search" data-type="account" data-placeholder="<?php esc_attr_e(
            'Filter by account',
            'contract-pilot',
        ); ?>">
            <?php if (!empty($account)) { ?>
                <option value="<?php echo esc_attr($account->id); ?>" <?php selected(
                    $account_id,
                    $account->id,
                ); ?>>
                    <?php echo esc_html($account->name); ?>
                </option>
            <?php } ?>
        </select>
        <?php
    }

    protected function contact_filter($type)
    {
        if ('customer' !== $type) {
            return;
        }

        $customer_id = Request::get_int('customer_id');
        $customer = empty($customer_id)
            ? null
            : contract_pilot()->customers->get($customer_id);
        ?>
        <select class="contract_pilot_select2" name="customer_id" id="filter-by-customer" data-action="contract_pilot_json_search" data-type="customer" data-placeholder="<?php esc_attr_e(
            'Filter by customer',
            'contract-pilot',
        ); ?>">
            <?php if (!empty($customer)) { ?>
                <option value="<?php echo esc_attr($customer->id); ?>" <?php selected(
                    $customer_id,
                    $customer->id,
                ); ?>>
                    <?php echo esc_html($customer->name); ?>
                </option>
            <?php } ?>
        </select>
        <?php
    }

    protected function date_filter($months)
    {
        $m = Request::get_int('m');
        $month_count = count($months);
        if (
            !$month_count
            || (1 === $month_count && 0 === (int) $months[0]->month)
        ) {
            return;
        }
        ?>
        <select name="m" id="filter-by-date" class="contract_pilot_select2" data-placeholder="<?php esc_attr_e(
            'Filter by date',
            'contract-pilot',
        ); ?>">
            <option<?php selected(
                $m,
                0,
            ); ?> style='display: none'><?php esc_attr_e('Filter by date', 'contract-pilot'); ?></option>
            <?php foreach ($months as $arc_row) {
                if (0 === (int) $arc_row->year || 0 === (int) $arc_row->month) {
                    continue;
                }

                $month = zeroise($arc_row->month, 2);
                $year = $arc_row->year;

                printf(
                    "<option %s value='%s'>%s</option>\n",
                    selected($m, $year.$month, false),
                    esc_attr($arc_row->year.$month),
                    esc_html(
                        \DateTime::createFromFormat('Y-m', $year.'-'.$month)->format(
                            'M Y',
                        ),
                    ),
                );
            } ?>
        </select>
        <?php
    }
}
