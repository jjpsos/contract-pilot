<?php

namespace Jjpsos\ContractPilot\Admin\ListTables;

use Jjpsos\ContractPilot\Admin\Request;
use Jjpsos\ContractPilot\Models\Payment;
use Jjpsos\ContractPilot\Utilities\DatabaseUtil;

defined("ABSPATH") || exit();


class Payments extends ListTable
{
    public function __construct($args = [])
    {
        parent::__construct(
            wp_parse_args($args, [
                "singular" => "payment",
                "plural" => "payments",
                "screen" => get_current_screen(),
                "args" => [],
            ]),
        );
        $this->base_url = Request::list_table_url(
            admin_url("admin.php?page=contract-pilot-sales&tab=payments"),
        );
    }


    public function prepare_items()
    {
        $this->process_actions();
        $per_page = $this->get_items_per_page("contract_pilot_payments_per_page", 20);
        $paged = $this->get_pagenum();
        $search = $this->get_request_search();
        $order_by = $this->get_request_orderby();
        $order = $this->get_request_order();
        $account_id = Request::get_int('account_id');
        $category_id = Request::get_int('category_id');
        $contact_id = Request::get_int('customer_id');
        $year_month = Request::get_int('m');
        $args = [
            "limit" => $per_page,
            "page" => $paged,
            "search" => $search,
            "orderby" => $order_by,
            "order" => $order,
            "status" => $this->get_request_status(),
            "account_id" => $account_id,
            "category_id" => $category_id,
            "contact_id" => $contact_id,
        ];

        if (!empty($year_month) && preg_match('/^[0-9]{6}$/', $year_month)) {
            $year = (int) substr($year_month, 0, 4);
            $month = (int) substr($year_month, 4, 2);
            $start = get_gmt_from_date("$year-$month-01 00:00:00");
            $end = get_gmt_from_date(
                date_create("$year-$month")->format("Y-m-t 23:59:59"),
            );
            $args["payment_date__between"] = [$start, $end];
        }


        $args = apply_filters("contract_pilot_payments_table_query_args", $args);

        $this->items = Payment::results($args);
        $total = Payment::count($args);

        $this->set_pagination_args([
            "total_items" => $total,
            "per_page" => $per_page,
        ]);
    }


    protected function bulk_delete($ids)
    {
        if (!current_user_can("contract_pilot_delete_payments")) {
            contract_pilot()->flash->error(
                __(
                    "You do not have permission to delete payments.",
                    "contract-pilot",
                ),
            );

            return;
        }

        $performed = 0;
        foreach ($ids as $id) {
            if (contract_pilot()->payments->delete($id)) {
                ++$performed;
            }
        }
        if (!empty($performed)) {
            contract_pilot()->flash->success(
                sprintf(
                    /* translators: %s: number of payments deleted (formatted). */
                    __(
                        "%s payment(s) deleted successfully.",
                        "contract-pilot",
                    ),
                    number_format_i18n($performed),
                ),
            );
        }
    }


    public function no_items()
    {
        esc_html_e("No payments found.", "contract-pilot");
    }


    protected function get_bulk_actions()
    {
        $actions = [];

        if (current_user_can("contract_pilot_delete_payments")) {
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
            $months = DatabaseUtil::get_results_list_table_year_month_transaction_filters(
                "payment",
            );

            $this->date_filter($months);
            $this->contact_filter("customer");
            $this->account_filter();
            $this->category_filter("payment");
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
        return [
            "cb" => '<input type="checkbox" />',
            "number" => __("Payment #", "contract-pilot"),
            "date" => __("Date", "contract-pilot"),
            "account_id" => __("Account", "contract-pilot"),
            "customer_id" => __("Customer", "contract-pilot"),
            "invoice_id" => __("Contract", "contract-pilot"),
            "reference" => __("Reference", "contract-pilot"),
            "amount" => __("Amount", "contract-pilot"),
        ];
    }


    protected function get_sortable_columns()
    {
        return [
            "date" => ["payment_date", true],
            "number" => ["number", false],
            "account_id" => ["account_id", false],
            "invoice_id" => ["invoice_id", false],
            "customer_id" => ["customer_id", false],
            "reference" => ["reference", false],
            "amount" => ["amount", false],
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


    public function column_date($item)
    {
        return $item->payment_date
            ? contract_pilot_format_datetime($item->payment_date, contract_pilot_date_format())
            : "&mdash;";
    }


    public function column_account_id($item)
    {
        $account = $item->account
            ? sprintf(
                '<a href="%s">%s</a>',
                esc_url($item->account->get_view_url()),
                wp_kses_post($item->account->name),
            )
            : "&mdash;";
        $metadata =
            $item->account && $item->account->number
                ? ucfirst($item->account->number)
                : "";

        return sprintf("%s%s", $account, $this->column_metadata($metadata));
    }


    public function column_invoice_id($item)
    {
        $invoice = "&mdash;";
        $metadata = "";
        if ($item->invoice) {
            $invoice = sprintf(
                '<a href="%s">%s</a>',
                esc_url($item->invoice->get_view_url()),
                wp_kses_post($item->invoice->number),
            );
        }

        return sprintf(
            "%s",
            empty($this->column_metadata($metadata))
                ? $invoice
                : $this->column_metadata($metadata),
        );
    }


    public function column_customer_id($item)
    {
        $customer = $item->customer
            ? sprintf(
                '<a href="%s">%s</a>',
                esc_url($item->customer->get_view_url()),
                wp_kses_post($item->customer->name),
            )
            : "&mdash;";
        $metadata =
            $item->customer && $item->customer->company
                ? $item->customer->company
                : "";

        return sprintf("%s%s", $customer, $this->column_metadata($metadata));
    }


    public function column_amount($item)
    {
        return esc_html($item->formatted_amount);
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

        if (!current_user_can("contract_pilot_delete_payments")) {
            unset($actions["delete"]);
        }

        if (!$item->editable || !current_user_can("contract_pilot_edit_payments")) {
            unset($actions["edit"]);
        }

        return $this->row_actions($actions);
    }
}
