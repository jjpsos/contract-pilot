<?php

namespace Otto\Admin\ListTables;

use Otto\Models\Transfer;
use Otto\Utilities\ReportsUtil;

defined("ABSPATH") || exit();


class Transfers extends ListTable
{
    
    public function __construct($args = [])
    {
        parent::__construct(
            wp_parse_args($args, [
                "singular" => "transfer",
                "plural" => "transfers",
                "screen" => get_current_screen(),
                "args" => [],
            ]),
        );

        $this->base_url = admin_url("admin.php?page=eac-banking&tab=transfers");
    }

    
    public function prepare_items()
    {
        $this->process_actions();
        $this->_column_headers = [
            $this->get_columns(),
            get_hidden_columns($this->screen),
            $this->get_sortable_columns(),
        ];
        $per_page = $this->get_items_per_page("eac_transfers_per_page", 20);
        $paged = $this->get_pagenum();
        $search = $this->get_request_search();
        $order_by = $this->get_request_orderby();
        $order = $this->get_request_order();
        $year_month = filter_input(INPUT_GET, "m", FILTER_VALIDATE_INT);
        $args = [
            "limit" => $per_page,
            "page" => $paged,
            "search" => $search,
            "orderby" => $order_by,
            "order" => $order,
        ];

        if (!empty($year_month) && preg_match('/^[0-9]{6}$/', $year_month)) {
            $year = (int) substr($year_month, 0, 4);
            $month = (int) substr($year_month, 4, 2);
            $start = get_gmt_from_date("$year-$month-01 00:00:00");
            $end = get_gmt_from_date(
                date_create("$year-$month")->format("Y-m-t 23:59:59"),
            );
            $args["transfer_date__between"] = [$start, $end];
        }

        
        $args = apply_filters("eac_transfers_table_query_args", $args);

        $this->items = Transfer::results($args);
        $total = Transfer::count($args);
        $this->set_pagination_args([
            "total_items" => $total,
            "per_page" => $per_page,
        ]);
    }

    
    protected function bulk_delete($ids)
    {
        if (!current_user_can("eac_delete_transfers")) {
            
            EAC()->flash->error(
                __(
                    "You do not have permission to delete transfers.",
                    "otto-contracts",
                ),
            );
            return;
        }

        $performed = 0;
        foreach ($ids as $id) {
            if (EAC()->transfers->delete($id)) {
                ++$performed;
            }
        }
        if (!empty($performed)) {
            
            EAC()->flash->success(
                sprintf(
                    __(
                        "%s transfers(s) deleted successfully.",
                        "otto-contracts",
                    ),
                    number_format_i18n($performed),
                ),
            );
        }
    }

    
    public function no_items()
    {
        esc_html_e("No transfers found.", "otto-contracts");
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
            $date_column = ReportsUtil::get_localized_time_sql("transfer_date");
            $months = $wpdb->get_results(
                
                "SELECT DISTINCT YEAR( $date_column ) AS year, MONTH( $date_column ) AS month
					FROM {$wpdb->prefix}otto_transfers
					WHERE transfer_date IS NOT NULL
					ORDER BY transfer_date DESC",
                
            );

            $this->date_filter($months);
            submit_button(
                __("Filter", "otto-contracts"),
                "",
                "filter_action",
                false,
            );
        }
        echo "</div>";
    }

    
    protected function get_bulk_actions()
    {
        $actions = [];

        if (current_user_can("eac_delete_transfers")) {
            
            $actions["delete"] = __("Delete", "otto-contracts");
        }

        return $actions;
    }

    
    public function get_columns()
    {
        return [
            "cb" => '<input type="checkbox" />',
            "transfer_date" => __("Date", "otto-contracts"),
            "from_account_id" => __("From Account", "otto-contracts"),
            "to_account_id" => __("To Account", "otto-contracts"),
            "reference" => __("Reference", "otto-contracts"),
            "amount" => __("Amount", "otto-contracts"),
        ];
    }

    
    protected function get_sortable_columns()
    {
        return [
            "transfer_date" => ["transfer_date", false],
            "amount" => ["amount", false],
            "from_account_id" => ["from_account_id", false],
            "to_account_id" => ["to_account_id", false],
            "reference" => ["reference", false],
        ];
    }

    
    public function get_primary_column_name()
    {
        return "transfer_date";
    }

    
    public function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="id[]" value="%d"/>',
            esc_attr($item->id),
        );
    }

    
    public function column_transfer_date($item)
    {
        return sprintf(
            '<a class="row-title" href="%s">%s</a>',
            esc_url($item->get_edit_url()),
            esc_html(
                $item->transfer_date
                    ? eac_format_datetime(
                        $item->transfer_date,
                        eac_date_format(),
                    )
                    : "&mdash;",
            ),
        );
    }

    
    public function column_from_account_id($item)
    {
        if ($item->expense && $item->expense->account) {
            return sprintf(
                '<a href="%s">%s</a>',
                esc_url($item->expense->account->get_view_url()),
                esc_html($item->expense->account->name),
            );
        }

        return "&mdash;";
    }

    
    public function column_to_account_id($item)
    {
        if ($item->payment && $item->payment->account) {
            return sprintf(
                '<a href="%s">%s</a>',
                esc_url($item->payment->account->get_view_url()),
                esc_html($item->payment->account->name),
            );
        }

        return "&mdash;";
    }

    
    public function column_reference($item)
    {
        return $item->reference ? esc_html($item->reference) : "&mdash;";
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
            "id" => sprintf("#%d", esc_attr($item->id)),
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

        if (!current_user_can("eac_delete_transfers")) {
            
            unset($actions["delete"]);
        }

        if (!current_user_can("eac_edit_transfers")) {
            
            unset($actions["edit"]);
        }

        return $this->row_actions($actions);
    }
}
