<?php

namespace Jjpsos\ContractPilot\Admin\ListTables;

use Jjpsos\ContractPilot\Admin\Request;
use Jjpsos\ContractPilot\Models\Customer;

defined("ABSPATH") || exit();


class Customers extends ListTable
{
    public function __construct($args = [])
    {
        parent::__construct(
            wp_parse_args($args, [
                "singular" => "customer",
                "plural" => "customers",
                "screen" => get_current_screen(),
                "args" => [],
            ]),
        );
        $this->base_url = Request::list_table_url(
            admin_url("admin.php?page=contract-pilot-sales&tab=customers"),
        );
    }


    public function prepare_items()
    {
        $this->process_actions();
        $per_page = $this->get_items_per_page("contract_pilot_customers_per_page", 20);
        $paged = $this->get_pagenum();
        $search = $this->get_request_search();
        $order_by = $this->get_request_orderby();
        $order = $this->get_request_order();
        $args = [
            "limit" => $per_page,
            "page" => $paged,
            "search" => $search,
            "orderby" => $order_by,
            "order" => $order,
        ];

        $args = apply_filters("contract_pilot_customers_table_query_args", $args);
        $this->items = contract_pilot()->customers->query($args);
        $total = contract_pilot()->customers->query($args, true);

        $this->set_pagination_args([
            "total_items" => $total,
            "per_page" => $per_page,
        ]);
    }


    protected function bulk_delete($ids)
    {
        if (!current_user_can("contract_pilot_delete_customers")) {
            contract_pilot()->flash->error(
                __(
                    "You do not have permission to delete customers.",
                    "contract-pilot",
                ),
            );
            return;
        }

        $performed = 0;
        foreach ($ids as $id) {
            if (contract_pilot()->customers->delete($id)) {
                ++$performed;
            }
        }
        if (!empty($performed)) {
            contract_pilot()->flash->success(
                sprintf(
                    /* translators: %s: number of customers deleted (formatted). */
                    __(
                        "%s customer(s) deleted successfully.",
                        "contract-pilot",
                    ),
                    number_format_i18n($performed),
                ),
            );
        }
    }


    public function no_items()
    {
        esc_html_e("No customers found.", "contract-pilot");
    }


    protected function get_bulk_actions()
    {
        $actions = [];

        if (current_user_can("contract_pilot_delete_customers")) {
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

        if ("top" === $which) {
            ob_start();
            $this->country_filter("active");
            $output = ob_get_clean();
            if (!empty($output) && $this->has_items()) {
                echo wp_kses_post($output);
                submit_button(
                    __("Filter", "contract-pilot"),
                    "alignleft",
                    "filter_action",
                    false,
                );
            }
        }
    }


    public function get_columns()
    {
        return [
            "cb" => '<input type="checkbox" />',
            "name" => __("Name", "contract-pilot"),
            "email" => __("Email", "contract-pilot"),
            "phone" => __("Phone", "contract-pilot"),
            "country" => __("Country", "contract-pilot"),
            "date" => __("Date", "contract-pilot"),
        ];
    }


    protected function get_sortable_columns()
    {
        return [
            "name" => ["name", false],
            "email" => ["email", false],
            "phone" => ["phone", false],
            "country" => ["country", false],
            "due" => ["due", false],
            "date" => ["date_created", false],
        ];
    }


    public function get_primary_column_name()
    {
        return "name";
    }


    public function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="id[]" value="%d"/>',
            esc_attr($item->id),
        );
    }


    public function column_name($item)
    {
        return sprintf(
            '<a class="row-title" href="%s">%s</a>',
            esc_url($item->get_view_url()),
            wp_kses_post($item->name),
        );
    }


    public function column_email($item)
    {
        return $item->email ? esc_html($item->email) : "&mdash;";
    }


    public function column_phone($item)
    {
        return $item->phone ? esc_html($item->phone) : "&mdash;";
    }


    public function column_country($item)
    {
        return $item->country_name ? esc_html($item->country_name) : "&mdash;";
    }


    public function column_date($item)
    {
        return $item->date_created
            ? contract_pilot_format_datetime($item->date_created, contract_pilot_date_format())
            : "&mdash;";
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
                '<a href="%s" class="del">%s</a>',
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

        if (!current_user_can("contract_pilot_delete_customers")) {
            unset($actions["delete"]);
        }

        if (!current_user_can("contract_pilot_edit_customers")) {
            unset($actions["edit"]);
        }

        return $this->row_actions($actions);
    }
}
