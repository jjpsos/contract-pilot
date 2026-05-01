<?php

namespace Otto\Admin\ListTables;

use Otto\Models\Category;

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

        $this->base_url = admin_url(
            "admin.php?page=eac-settings&tab=categories",
        );
    }

    
    public function prepare_items()
    {
        $this->process_actions();
        $per_page = $this->get_items_per_page("eac_categories_per_page");
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

        
        $args = apply_filters("eac_categories_table_query_args", $args);

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
            if (EAC()->categories->delete($id)) {
                ++$performed;
            }
        }
        if (!empty($performed)) {
            
            EAC()->flash->success(
                sprintf(
                    __(
                        "%s category(s) deleted successfully.",
                        "otto-contracts",
                    ),
                    number_format_i18n($performed),
                ),
            );
        }
    }

    
    public function no_items()
    {
        esc_html_e("No categories found.", "otto-contracts");
    }

    
    protected function get_views()
    {
        $current = $this->get_request_type("all");
        $types_links = [];
        $types = array_merge(
            ["all" => __("All", "otto-contracts")],
            EAC()->categories->get_types(),
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
            "delete" => __("Delete", "otto-contracts"),
        ];

        return $actions;
    }

    
    protected function extra_tablenav($which) {}

    
    public function get_columns()
    {
        return [
            "cb" => '<input type="checkbox" />',
            "name" => __("Name", "otto-contracts"),
            "description" => __("Description", "otto-contracts"),
            "type" => __("Type", "otto-contracts"),
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
        $types = EAC()->categories->get_types();

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
                __("Edit", "otto-contracts"),
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
                __("Delete", "otto-contracts"),
            ),
        ];

        if (!current_user_can("eac_delete_categories")) {
            
            unset($actions["delete"]);
        }

        if (!current_user_can("eac_edit_categories")) {
            
            unset($actions["edit"]);
        }

        return $this->row_actions($actions);
    }
}
