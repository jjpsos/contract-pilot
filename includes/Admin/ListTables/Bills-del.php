<?php

namespace Otto\Admin\ListTables;

use Otto\Models\Bill;
use Otto\Utilities\ReportsUtil;

defined("ABSPATH") || exit();


class Bills extends ListTable
{
    
    public function __construct($args = [])
    {
        parent::__construct(
            wp_parse_args($args, [
                "singular" => "bill",
                "plural" => "bills",
                "screen" => get_current_screen(),
                "args" => [],
            ]),
        );

        $this->base_url = admin_url("admin.php?page=eac-purchases&tab=bills");
    }

    
    public function prepare_items()
    {
        $this->process_actions();
        $per_page = $this->get_items_per_page("eac_bills_per_page", 20);
        $paged = $this->get_pagenum();
        $search = $this->get_request_search();
        $order_by = $this->get_request_orderby();
        $order = $this->get_request_order();
        $contact_id = filter_input(INPUT_GET, "vendor_id", FILTER_VALIDATE_INT);
        $year_month = filter_input(INPUT_GET, "m", FILTER_VALIDATE_INT);
        $args = [
            "limit" => $per_page,
            "page" => $paged,
            "search" => $search,
            "orderby" => $order_by,
            "order" => $order,
            "status" => $this->get_request_status(),
            "contact_id" => $contact_id,
        ];

        if (!empty($year_month) && preg_match('/^[0-9]{6}$/', $year_month)) {
            $year = (int) substr($year_month, 0, 4);
            $month = (int) substr($year_month, 4, 2);
            $start = get_gmt_from_date("$year-$month-01 00:00:00");
            $end = get_gmt_from_date(
                date_create("$year-$month")->format("Y-m-t 23:59:59"),
            );
            $args["issue_date__between"] = [$start, $end];
        }

        
        $args = apply_filters("eac_bills_table_query_args", $args);

        $this->items = Bill::results($args);
        $total = Bill::count($args);

        $this->set_pagination_args([
            "total_items" => $total,
            "per_page" => $per_page,
        ]);
    }

    
    protected function bulk_set_draft($ids)
    {
        if (!current_user_can("eac_edit_bills")) {
            
            EAC()->flash->error(
                __(
                    "You do not have permission to perform this action.",
                    "otto-contracts",
                ),
            );
            return;
        }

        $performed = 0;
        foreach ($ids as $id) {
            $bill = EAC()->bills->get($id);
            if ($bill && $bill->fill(["status" => "draft"])->save()) {
                ++$performed;
            }
        }
        if (!empty($performed)) {
            
            EAC()->flash->success(
                sprintf(
                    __(
                        "%s bill(s) marked as draft successfully.",
                        "otto-contracts",
                    ),
                    number_format_i18n($performed),
                ),
            );
        }
    }

    
    protected function bulk_set_received($ids)
    {
        if (!current_user_can("eac_edit_bills")) {
            
            EAC()->flash->error(
                __(
                    "You do not have permission to perform this action.",
                    "otto-contracts",
                ),
            );
            return;
        }

        $performed = 0;
        foreach ($ids as $id) {
            $bill = EAC()->bills->get($id);
            if ($bill && $bill->fill(["status" => "received"])->save()) {
                ++$performed;
            }
        }
        if (!empty($performed)) {
            
            EAC()->flash->success(
                sprintf(
                    __(
                        "%s bill(s) marked as received successfully.",
                        "otto-contracts",
                    ),
                    number_format_i18n($performed),
                ),
            );
        }
    }

    
    protected function bulk_set_overdue($ids)
    {
        if (!current_user_can("eac_edit_bills")) {
            
            EAC()->flash->error(
                __(
                    "You do not have permission to perform this action.",
                    "otto-contracts",
                ),
            );
            return;
        }

        $performed = 0;
        foreach ($ids as $id) {
            $bill = EAC()->bills->get($id);
            if ($bill && $bill->fill(["status" => "overdue"])->save()) {
                ++$performed;
            }
        }
        if (!empty($performed)) {
            
            EAC()->flash->success(
                sprintf(
                    __(
                        "%s bill(s) marked as overdue successfully.",
                        "otto-contracts",
                    ),
                    number_format_i18n($performed),
                ),
            );
        }
    }

    
    protected function bulk_set_cancelled($ids)
    {
        if (!current_user_can("eac_edit_bills")) {
            
            EAC()->flash->error(
                __(
                    "You do not have permission to perform this action.",
                    "otto-contracts",
                ),
            );
            return;
        }

        $performed = 0;
        foreach ($ids as $id) {
            $bill = EAC()->bills->get($id);
            if ($bill && $bill->fill(["status" => "cancelled"])->save()) {
                ++$performed;
            }
        }
        if (!empty($performed)) {
            
            EAC()->flash->success(
                sprintf(
                    __(
                        "%s bill(s) marked as cancelled successfully.",
                        "otto-contracts",
                    ),
                    number_format_i18n($performed),
                ),
            );
        }
    }

    
    protected function bulk_delete($ids)
    {
        if (!current_user_can("eac_delete_bills")) {
            
            EAC()->flash->error(
                __(
                    "You do not have permission to delete bills.",
                    "otto-contracts",
                ),
            );
            return;
        }

        $performed = 0;
        foreach ($ids as $id) {
            if (EAC()->bills->delete($id)) {
                ++$performed;
            }
        }
        if (!empty($performed)) {
            
            EAC()->flash->success(
                sprintf(
                    __(
                        "%s bill(s) deleted successfully.",
                        "otto-contracts",
                    ),
                    number_format_i18n($performed),
                ),
            );
        }
    }

    
    public function no_items()
    {
        esc_html_e("No bills found.", "otto-contracts");
    }

    
    protected function get_views()
    {
        $current = $this->get_request_status("all");
        $status_links = [];
        $statuses = EAC()->bills->get_statuses();

        foreach ($statuses as $status => $label) {
            $link =
                "all" === $status
                    ? $this->base_url
                    : add_query_arg("status", $status, $this->base_url);
            $args = "all" === $status ? [] : ["status" => $status];
            $count = Bill::count($args);
            $label = sprintf(
                '%s <span class="count">(%s)</span>',
                esc_html($label),
                number_format_i18n($count),
            );

            $status_links[$status] = [
                "url" => $link,
                "label" => $label,
                "current" => $current === $status,
            ];
        }

        return $this->get_views_links($status_links);
    }

    
    protected function get_bulk_actions()
    {
        $actions = [];

        if (current_user_can("eac_edit_bills")) {
            
            $actions["set_draft"] = __("Set Draft", "otto-contracts");
            $actions["set_received"] = __("Set Received", "otto-contracts");
            $actions["set_overdue"] = __("Set Overdue", "otto-contracts");
            $actions["set_cancelled"] = __(
                "Set Cancelled",
                "otto-contracts",
            );
        }

        if (current_user_can("eac_delete_bills")) {
            
            $actions["delete"] = __("Delete", "otto-contracts");
        }

        return $actions;
    }

    
    protected function extra_tablenav($which)
    {
        global $wpdb;
        static $has_items;
        if (!isset($has_items)) {
            $has_items = $this->has_items();
        }

        echo '<div class="alignleft actions">';

        if ("top" === $which) {
            $date_column = ReportsUtil::get_localized_time_sql("issue_date");
            $months = $wpdb->get_results(
                
                $wpdb->prepare(
                    "SELECT DISTINCT YEAR( {$date_column} ) AS year, MONTH( {$date_column} ) AS month
					FROM {$wpdb->prefix}otto_documents
					WHERE type = %s AND issue_date IS NOT NULL
					ORDER BY issue_date DESC",
                    "bill",
                ),
                
            );
            $this->date_filter($months);
            $this->contact_filter("vendor");
            submit_button(
                __("Filter", "otto-contracts"),
                "",
                "filter_action",
                false,
            );
        }

        echo "</div>";
    }

    
    public function get_columns()
    {
        return [
            "cb" => '<input type="checkbox" />',
            "number" => __("Bill #", "otto-contracts"),
            "issue_date" => __("Issue Date", "otto-contracts"),
            "payment_date" => __("Payment Date", "otto-contracts"),
            "vendor_id" => __("Vendor", "otto-contracts"),
            "reference" => __("Order #", "otto-contracts"),
            "status" => __("Status", "otto-contracts"),
            "total" => __("Total", "otto-contracts"),
        ];
    }

    
    protected function get_sortable_columns()
    {
        return [
            "number" => ["number", false],
            "issue_date" => ["issue_date", false],
            "payment_date" => ["payment_date", false],
            "vendor_id" => ["vendor_id", false],
            "reference" => ["reference", false],
            "status" => ["status", false],
            "total" => ["total", false],
        ];
    }

    
    public function get_primary_column_name()
    {
        return "number";
    }

    
    public function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="id[]" value="%d"/>',
            esc_attr($item->id),
        );
    }

    
    public function column_number($item)
    {
        return sprintf(
            '<a class="row-title" href="%s">%s</a>',
            esc_url($item->get_view_url()),
            wp_kses_post($item->number),
        );
    }

    
    public function column_issue_date($item)
    {
        $date = $item->issue_date
            ? eac_format_datetime($item->issue_date, eac_date_format())
            : "&mdash;";
        $metadata = $item->due_date
            ? sprintf(
                 __(
                    "Due: %s",
                    "otto-contracts",
                ),
                eac_format_datetime($item->due_date, eac_date_format()),
            )
            : "";

        return sprintf("%s%s", $date, $this->column_metadata($metadata));
    }

    
    public function column_vendor_id($item)
    {
        if ($item->vendor) {
            return sprintf(
                '<a href="%s">%s</a>',
                esc_url($item->vendor->get_view_url()),
                wp_kses_post($item->vendor->name),
            );
        }

        return "&mdash;";
    }

    
    public function column_reference($item)
    {
        return $item->reference ? esc_html($item->reference) : "&mdash;";
    }

    
    public function column_total($item)
    {
        return esc_html($item->formatted_total);
    }

    
    public function column_status($item)
    {
        return sprintf(
            '<span class="eac-status is--%1$s">%2$s</span>',
            esc_attr($item->status),
            esc_html($item->status_label),
        );
    }

    
    protected function handle_row_actions($item, $column_name, $primary)
    {
        if ($primary !== $column_name) {
            return null;
        }
        $actions = [
            "edit" => sprintf(
                '<a href="%s">%s</a>',
                esc_url($item->get_edit_url()),
                __("Edit", "otto-contracts"),
            ),
            "delete" => sprintf(
                '<a href="%s" class="del del_confirm">%s</a>',
                esc_url(
                    wp_nonce_url(
                        add_query_arg(
                            [
                                "action" => "delete",
                                "id" => $item->id,
                            ],
                            $this->base_url,
                        ),
                        "bulk-" . $this->_args["plural"],
                    ),
                ),
                __("Delete", "otto-contracts"),
            ),
        ];

        if (!current_user_can("eac_delete_bills")) {
            
            unset($actions["delete"]);
        }

        if (!current_user_can("eac_edit_bills")) {
            
            unset($actions["edit"]);
        }

        return $this->row_actions($actions);
    }
}
