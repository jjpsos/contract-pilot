<?php

namespace Jjpsos\ContractPilot\Admin\ListTables;

use Jjpsos\ContractPilot\Admin\Request;
use Jjpsos\ContractPilot\Models\Category;

defined("ABSPATH") || exit();


class Categories extends ListTable
{
    public function __construct($args = [])
    {
        parent::__construct(
            wp_parse_args($args, [
                "singular" => "category",
                "plural" => "categories",
                "screen" => get_current_screen(),
                "args" => [],
            ]),
        );

        $this->base_url = Request::list_table_url(
            admin_url("admin.php?page=contract-pilot-settings&tab=categories"),
        );
    }


    public function prepare_items()
    {
        $this->process_actions();
        $per_page = $this->get_items_per_page("contract_pilot_categories_per_page");
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
            "type" => $this->get_request_type(),
        ];


        $args = apply_filters("contract_pilot_categories_table_query_args", $args);

        $this->items = Category::results($args);
        $total = Category::count($args);
        $this->set_pagination_args([
            "total_items" => $total,
            "per_page" => $per_page,
        ]);
    }


    protected function bulk_delete($ids)
    {
        $performed = 0;
        foreach ($ids as $id) {
            if (contract_pilot()->categories->delete($id)) {
                ++$performed;
            }
        }
        if (!empty($performed)) {
            contract_pilot()->flash->success(
                sprintf(
                    /* translators: %s: number of categories deleted (formatted). */
                    __(
                        "%s category(s) deleted successfully.",
                        "contract-pilot",
                    ),
                    number_format_i18n($performed),
                ),
            );
        }
    }


    public function no_items()
    {
        esc_html_e("No categories found.", "contract-pilot");
    }


    protected function get_views()
    {
        $current = $this->get_request_type("all");
        $types_links = [];
        $types = array_merge(
            ["all" => __("All", "contract-pilot")],
            contract_pilot()->categories->get_types(),
        );

        foreach ($types as $type => $label) {
            $link =
                "all" === $type
                    ? $this->base_url
                    : add_query_arg("type", $type, $this->base_url);
            $args = "all" === $type ? [] : ["type" => $type];
            $count = Category::count($args);
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
        $actions = [
            "delete" => __("Delete", "contract-pilot"),
        ];

        return $actions;
    }


    protected function extra_tablenav($which)
    {
    }


    public function get_columns()
    {
        return [
            "cb" => '<input type="checkbox" />',
            "name" => __("Name", "contract-pilot"),
            "description" => __("Description", "contract-pilot"),
            "type" => __("Type", "contract-pilot"),
        ];
    }


    protected function get_sortable_columns()
    {
        return [
            "name" => ["name", false],
            "description" => ["description", false],
            "type" => ["type", false],
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
            esc_url(
                add_query_arg(
                    [
                        "action" => "edit",
                        "id" => $item->id,
                    ],
                    $this->base_url,
                ),
            ),
            wp_kses_post($item->name),
        );
    }


    public function column_type($item)
    {
        $types = contract_pilot()->categories->get_types();

        return isset($types[$item->type]) ? $types[$item->type] : "";
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
                esc_url(
                    add_query_arg(
                        [
                            "action" => "edit",
                            "id" => $item->id,
                        ],
                        $this->base_url,
                    ),
                ),
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

        if (!current_user_can("contract_pilot_delete_categories")) {
            unset($actions["delete"]);
        }

        if (!current_user_can("contract_pilot_edit_categories")) {
            unset($actions["edit"]);
        }

        return $this->row_actions($actions);
    }
}
