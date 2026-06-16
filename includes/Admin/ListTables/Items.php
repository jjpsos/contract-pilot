<?php

namespace Jjpsos\ContractPilot\Admin\ListTables;

use Jjpsos\ContractPilot\Admin\Request;
use Jjpsos\ContractPilot\Models\Item;

defined("ABSPATH") || exit();


class Items extends ListTable
{
    public function __construct($args = [])
    {
        parent::__construct(
            wp_parse_args($args, [
                "singular" => "service",
                "plural" => "services",
                "screen" => get_current_screen(),
                "args" => [],
            ]),
        );

        $this->base_url = Request::list_table_url(
            admin_url("admin.php?page=contract-pilot-items&tab=items"),
        );
    }


    public function prepare_items()
    {
        $this->process_actions();
        $this->_column_headers = [
            $this->get_columns(),
            get_hidden_columns($this->screen),
            $this->get_sortable_columns(),
        ];
        $per_page = $this->get_items_per_page("contract_pilot_items_per_page", 20);
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
            "type" => Request::get_string("type"),
            "category_id" => Request::get_int("category_id"),
        ];


        $args = apply_filters("contract_pilot_items_table_query_args", $args);
        $this->items = Item::results($args);
        $total = Item::count($args);
        $this->set_pagination_args([
            "total_items" => $total,
            "per_page" => $per_page,
        ]);
    }


    protected function bulk_delete($ids)
    {
        if (!current_user_can("contract_pilot_delete_items")) {
            contract_pilot()->flash->error(
                __(
                    "You do not have permission to delete services.",
                    "contract-pilot",
                ),
            );
            return;
        }

        $performed = 0;
        foreach ($ids as $id) {
            if (contract_pilot()->items->delete($id)) {
                ++$performed;
            }
        }
        if (!empty($performed)) {
            contract_pilot()->flash->success(
                sprintf(
                    /* translators: %s: number of services deleted (formatted). */
                    __(
                        "%s service(s) deleted successfully.",
                        "contract-pilot",
                    ),
                    number_format_i18n($performed),
                ),
            );
        }
    }


    public function no_items()
    {
        esc_html_e("No services found.", "contract-pilot");
    }


    protected function get_views()
    {
        $current = $this->get_request_type("all");
        $types_links = [];
        $types = array_merge(
            ["all" => __("All", "contract-pilot")],
            contract_pilot()->items->get_types(),
        );

        foreach ($types as $type => $label) {
            $link =
                "all" === $type
                    ? $this->base_url
                    : add_query_arg("type", $type, $this->base_url);
            $args = "all" === $type ? [] : ["type" => $type];
            $count = Item::count($args);
            $label = sprintf(
                '%s <span class="count">(%s)</span>',
                esc_html($label),
                number_format_i18n($count),
            );

            $types_links[$type] = [
                "url" => $link,
                "label" => $label,
                "current" => $current === $type,
            ];
        }

        return $this->get_views_links($types_links);
    }


    protected function get_bulk_actions()
    {
        $actions = [];

        if (current_user_can("contract_pilot_delete_items")) {
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
            $this->category_filter("item");
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
            "name" => __("Name", "contract-pilot"),
            "type" => __("Type", "contract-pilot"),
            "category" => __("Category", "contract-pilot"),
            "price" => __("Price", "contract-pilot"),
            "date_created" => __("Date", "contract-pilot"),
        ];
    }


    protected function get_sortable_columns()
    {
        return [
            "name" => ["name", false],
            "type" => ["type", false],
            "category" => ["category_id", false],
            "price" => ["price", false],
            "date_created" => ["date_created", false],
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
            esc_url($item->get_edit_url()),
            wp_kses_post($item->name),
        );
    }


    public function column_type($item)
    {
        $types = contract_pilot()->items->get_types();

        return isset($types[$item->type])
            ? esc_html($types[$item->type])
            : $item->type;
    }


    public function column_category($item)
    {
        if ($item->category) {
            return sprintf(
                '<a href="%s">%s</a>',
                esc_url(
                    add_query_arg(
                        "category_id",
                        $item->category->id,
                        $this->base_url,
                    ),
                ),
                wp_kses_post($item->category->name),
            );
        }

        return "&mdash;";
    }


    public function column_price($item)
    {
        return esc_html($item->formatted_price);
    }


    public function column_date_created($item)
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
            "id" => sprintf("#%d", esc_attr($item->id)),
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

        if (!current_user_can("contract_pilot_delete_items")) {
            unset($actions["delete"]);
        }

        if (!current_user_can("contract_pilot_edit_items")) {
            unset($actions["edit"]);
        }

        return $this->row_actions($actions);
    }
}
