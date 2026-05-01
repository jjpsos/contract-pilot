<?php

namespace Otto\Admin\ListTables;

use Otto\Models\Invoice;
use Otto\Utilities\ReportsUtil;

defined("ABSPATH") || exit();


class Invoices extends ListTable
{
    
    public function __construct($args = [])
    {
        parent::__construct(
            wp_parse_args($args, [
                "singular" => "contract",
                "plural" => "contracts",
                "screen" => get_current_screen(),
                "args" => [],
            ]),
        );

        $this->base_url = admin_url("admin.php?page=eac-sales&tab=invoices");
    }

    
    public function prepare_items()
    {
        $this->process_actions();
        $per_page = $this->get_items_per_page("eac_invoices_per_page", 20);
        $paged = $this->get_pagenum();
        $search = $this->get_request_search();
        $order_by = $this->get_request_orderby();
        $order = $this->get_request_order();
        $contact_id = filter_input(
            INPUT_GET,
            "customer_id",
            FILTER_VALIDATE_INT,
        );
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

        $req_status = isset($args["status"]) ? (string) $args["status"] : "";
        if ("cancelled" === $req_status || "canceled" === $req_status) {
            unset($args["status"]);
            $args["status__in"] = ["cancelled", "canceled"];
        }

        if (!empty($year_month) && preg_match('/^[0-9]{6}$/', $year_month)) {
            $year = (int) substr($year_month, 0, 4);
            $month = (int) substr($year_month, 4, 2);
            $start = get_gmt_from_date("$year-$month-01 00:00:00");
            $end = get_gmt_from_date(
                date_create("$year-$month")->format("Y-m-t 23:59:59"),
            );
            $args["issue_date__between"] = [$start, $end];
        }

        
        $args = apply_filters("eac_invoices_table_query_args", $args);

        $this->items = Invoice::results($args);
        $total = Invoice::count($args);

        $this->set_pagination_args([
            "total_items" => $total,
            "per_page" => $per_page,
        ]);
    }

    
    protected function bulk_delete($ids)
    {
        if (!current_user_can("eac_delete_invoices")) {
            
            EAC()->flash->error(
                __(
                    "You do not have permission to delete contracts.",
                    "otto-contracts",
                ),
            );
            return;
        }

        $performed = 0;
        foreach ($ids as $id) {
            if (EAC()->invoices->delete($id)) {
                ++$performed;
            }
        }
        if (!empty($performed)) {
            
            EAC()->flash->success(
                sprintf(
                    __(
                        "%s contract(s) deleted successfully.",
                        "otto-contracts",
                    ),
                    number_format_i18n($performed),
                ),
            );
        }
    }

    
    protected function bulk_cancel($ids)
    {
        if (!current_user_can("eac_edit_invoices")) {
            
            EAC()->flash->error(
                __(
                    "You do not have permission to edit or cancel contracts.",
                    "otto-contracts",
                ),
            );
            return;
        }

        $performed = 0;
        foreach ($ids as $id) {
            $payments = EAC()->payments->query(["document_id" => $id]);
            foreach ($payments as $payment) {
                $payment->delete();
            }
            $invoice = EAC()->invoices->get($id);
            $invoice->status = "cancelled";
            if ($invoice->save()) {
                ++$performed;
            }
        }
        if (!empty($performed)) {
            
            EAC()->flash->success(
                sprintf(
                    __(
                        "%s contract(s) canceled successfully.",
                        "otto-contracts",
                    ),
                    number_format_i18n($performed),
                ),
            );
        }
    }

    
    public function no_items()
    {
        esc_html_e("No contracts found.", "otto-contracts");
    }

    
    protected function get_views()
    {
        $current = $this->get_request_status("all");
        $status_links = [];
        $statuses = EAC()->invoices->get_statuses();
        $statuses = array_merge(
            ["all" => __("All", "otto-contracts")],
            $statuses,
        );

        foreach ($statuses as $status => $label) {
            $link =
                "all" === $status
                    ? $this->base_url
                    : add_query_arg("status", $status, $this->base_url);
            $args = "all" === $status ? [] : ["status" => $status];
            if ("cancelled" === $status) {
                unset($args["status"]);
                $args["status__in"] = ["cancelled", "canceled"];
            }
            $count = Invoice::count($args);
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

        if (current_user_can("eac_edit_invoices")) {
            
            $actions["cancel"] = __("Cancel", "otto-contracts");
        }

        if (current_user_can("eac_delete_invoices")) {
            
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
                    "invoice",
                ),
                
            );
            $this->date_filter($months);
            $this->contact_filter("customer");
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
        $status_filter = $this->get_request_status("all");
        if ("all" === $status_filter) {
            $number_heading = __("Contract/Bill #", "otto-contracts");
        } elseif ("draft" === $status_filter) {
            $number_heading = __("Contract/Draft #", "otto-contracts");
        } elseif ("sent" === $status_filter) {
            $number_heading = __("Contract/Sent #", "otto-contracts");
        } elseif ("accept" === $status_filter) {
            $number_heading = __("Accept/Bill #", "otto-contracts");
        } elseif ("partial" === $status_filter) {
            $number_heading = __("Partial/Bill #", "otto-contracts");
        } elseif ("paid" === $status_filter) {
            $number_heading = __("Paid/Bill #", "otto-contracts");
        } elseif ("overdue" === $status_filter) {
            $number_heading = __("Overdue/Bill #", "otto-contracts");
        } elseif ("otto" === $status_filter) {
            $number_heading = __("Otto/Bill #", "otto-contracts");
        } elseif (
            "cancelled" === $status_filter ||
            "canceled" === $status_filter
        ) {
            $number_heading = __("Cancelled/Bill #", "otto-contracts");
        } else {
            $number_heading = __("Contract #", "otto-contracts");
        }

        return [
            "cb" => '<input type="checkbox" />',
            "number" => $number_heading,
            "issue_date" => __("Issue Date", "otto-contracts"),
            "due_date" => __("Due Date", "otto-contracts"),
            "customer_id" => __("Client", "otto-contracts"),
            "reference" => __("Order #", "otto-contracts"),
            "status" => __("Status", "otto-contracts"),
            "total" => __("Total", "otto-contracts"),
        ];
    }

    
    protected function get_sortable_columns()
    {
        return [
            "number" => ["number", false],
            "reference" => ["reference", false],
            "issue_date" => ["issue_date", false],
            "due_date" => ["due_date", false],
            "customer_id" => ["customer_id", false],
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

    
    public function column_reference($item)
    {
        return $item->reference ? esc_html($item->reference) : "&mdash;";
    }

    
    public function column_issue_date($item)
    {
        return $item->issue_date
            ? eac_format_datetime($item->issue_date, eac_date_format())
            : "&mdash;";
    }

    
    public function column_due_date($item)
    {
        return $item->due_date
            ? eac_format_datetime($item->due_date, eac_date_format())
            : "&mdash;";
    }

    
    public function column_total($item)
    {
        return esc_html($item->formatted_total);
    }

    
    public function column_customer_id($item)
    {
        if ($item->customer) {
            return sprintf(
                '<a href="%s">%s</a>',
                esc_url($item->customer->get_view_url()),
                wp_kses_post($item->customer->name),
            );
        }

        return "&mdash;";
    }

    
    public function column_status($item)
    {
        $statuses = EAC()->invoices->get_statuses();
        $status = isset($item->status) ? $item->status : "";
        $label = isset($statuses[$status]) ? $statuses[$status] : "";

        return sprintf(
            '<span class="eac-status is--%1$s">%2$s</span>',
            esc_attr($status),
            esc_html($label),
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

        if (!current_user_can("eac_delete_invoices")) {
            
            unset($actions["delete"]);
        }

        if (!$item->editable || !current_user_can("eac_edit_invoices")) {
            
            unset($actions["edit"]);
        }

        return $this->row_actions($actions);
    }
}
