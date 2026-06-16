<?php

namespace Jjpsos\ContractPilot\Admin\ListTables;

use Jjpsos\ContractPilot\Admin\Request;
use Jjpsos\ContractPilot\Models\Invoice;
use Jjpsos\ContractPilot\Utilities\DatabaseUtil;

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

        $this->base_url = Request::list_table_url(
            admin_url("admin.php?page=contract-pilot-sales&tab=invoices"),
        );
    }


    public function prepare_items()
    {
        $this->process_actions();
        $per_page = $this->get_items_per_page("contract_pilot_invoices_per_page", 20);
        $paged = $this->get_pagenum();
        $search = $this->get_request_search();
        $order_by = $this->get_request_orderby();
        $order = $this->get_request_order();
        $contact_id = Request::get_int('customer_id');
        $year_month = Request::get_int('m');
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


        $args = apply_filters("contract_pilot_invoices_table_query_args", $args);

        $this->items = Invoice::results($args);
        $total = Invoice::count($args);

        $this->set_pagination_args([
            "total_items" => $total,
            "per_page" => $per_page,
        ]);
    }


    protected function bulk_delete($ids)
    {
        if (!current_user_can("contract_pilot_delete_invoices")) {
            contract_pilot()->flash->error(
                __(
                    "You do not have permission to delete contracts.",
                    "contract-pilot",
                ),
            );
            return;
        }

        $performed = 0;
        foreach ($ids as $id) {
            if (contract_pilot()->invoices->delete($id)) {
                ++$performed;
            }
        }
        if (!empty($performed)) {
            contract_pilot()->flash->success(
                sprintf(
                    /* translators: %s: number of contracts deleted (formatted). */
                    __(
                        "%s contract(s) deleted successfully.",
                        "contract-pilot",
                    ),
                    number_format_i18n($performed),
                ),
            );
        }
    }


    protected function bulk_cancel($ids)
    {
        if (!current_user_can("contract_pilot_edit_invoices")) {
            contract_pilot()->flash->error(
                __(
                    "You do not have permission to edit or cancel contracts.",
                    "contract-pilot",
                ),
            );
            return;
        }

        $performed = 0;
        foreach ($ids as $id) {
            $payments = contract_pilot()->payments->query(["document_id" => $id]);
            foreach ($payments as $payment) {
                $payment->delete();
            }
            $invoice = contract_pilot()->invoices->get($id);
            $invoice->status = "cancelled";
            if ($invoice->save()) {
                ++$performed;
            }
        }
        if (!empty($performed)) {
            contract_pilot()->flash->success(
                sprintf(
                    /* translators: %s: number of contracts canceled (formatted). */
                    __(
                        "%s contract(s) canceled successfully.",
                        "contract-pilot",
                    ),
                    number_format_i18n($performed),
                ),
            );
        }
    }


    public function no_items()
    {
        esc_html_e("No contracts found.", "contract-pilot");
    }


    protected function get_views()
    {
        $current = $this->get_request_status("all");
        $status_links = [];
        $statuses = contract_pilot()->invoices->get_statuses();
        $statuses = array_merge(
            ["all" => __("All", "contract-pilot")],
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

        if (current_user_can("contract_pilot_edit_invoices")) {
            $actions["cancel"] = __("Cancel", "contract-pilot");
        }

        if (current_user_can("contract_pilot_delete_invoices")) {
            $actions["delete"] = __("Delete", "contract-pilot");
        }

        return $actions;
    }


    protected function extra_tablenav($which)
    {
        static $has_items;
        if (!isset($has_items)) {
            $has_items = $this->has_items();
        }

        echo '<div class="alignleft actions">';

        if ("top" === $which) {
            $months = DatabaseUtil::get_results_list_table_year_month_document_filters(
                "invoice",
            );
            $this->date_filter($months);
            $this->contact_filter("customer");
            submit_button(
                __("Filter", "contract-pilot"),
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
            $number_heading = __("Contract/Bill #", "contract-pilot");
        } elseif ("draft" === $status_filter) {
            $number_heading = __("Contract/Draft #", "contract-pilot");
        } elseif ("sent" === $status_filter) {
            $number_heading = __("Contract/Sent #", "contract-pilot");
        } elseif ("accept" === $status_filter) {
            $number_heading = __("Accept/Bill #", "contract-pilot");
        } elseif ("partial" === $status_filter) {
            $number_heading = __("Partial/Bill #", "contract-pilot");
        } elseif ("paid" === $status_filter) {
            $number_heading = __("Paid/Bill #", "contract-pilot");
        } elseif ("overdue" === $status_filter) {
            $number_heading = __("Overdue/Bill #", "contract-pilot");
        } elseif ("otto" === $status_filter) {
            $number_heading = __("Auto/Bill #", "contract-pilot");
        } elseif (
            "cancelled" === $status_filter ||
            "canceled" === $status_filter
        ) {
            $number_heading = __("Cancelled/Bill #", "contract-pilot");
        } else {
            $number_heading = __("Contract #", "contract-pilot");
        }

        return [
            "cb" => '<input type="checkbox" />',
            "number" => $number_heading,
            "issue_date" => __("Issue Date", "contract-pilot"),
            "due_date" => __("Due Date", "contract-pilot"),
            "customer_id" => __("Client", "contract-pilot"),
            "reference" => __("Order #", "contract-pilot"),
            "status" => __("Status", "contract-pilot"),
            "total" => __("Total", "contract-pilot"),
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
            ? contract_pilot_format_datetime($item->issue_date, contract_pilot_date_format())
            : "&mdash;";
    }


    public function column_due_date($item)
    {
        return $item->due_date
            ? contract_pilot_format_datetime($item->due_date, contract_pilot_date_format())
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
        $statuses = contract_pilot()->invoices->get_statuses();
        $status = isset($item->status) ? $item->status : "";
        $label = isset($statuses[$status]) ? $statuses[$status] : "";

        return sprintf(
            '<span class="contract-pilot-status is--%1$s">%2$s</span>',
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
                __("Edit", "contract-pilot"),
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
                __("Delete", "contract-pilot"),
            ),
        ];

        if (!current_user_can("contract_pilot_delete_invoices")) {
            unset($actions["delete"]);
        }

        if (!$item->editable || !current_user_can("contract_pilot_edit_invoices")) {
            unset($actions["edit"]);
        }

        return $this->row_actions($actions);
    }
}
