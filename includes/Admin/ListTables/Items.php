<?php

namespace Otto\Admin\ListTables;

use Otto\Models\Item;

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

        $this->base_url = admin_url("admin.php?page=eac-items&tab=items");
    }

    
    public function prepare_items()
    {
        $this->process_actions();
        $this->_column_headers = [
            $this->get_columns(),
            get_hidden_columns($this->screen),
            $this->get_sortable_columns(),
        ];
        $per_page = $this->get_items_per_page("eac_items_per_page", 20);
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
            "type" => filter_input(
                INPUT_GET,
                "type",
                FILTER_SANITIZE_FULL_SPECIAL_CHARS,
            ),
            "category_id" => filter_input(
                INPUT_GET,
                "category_id",
                FILTER_VALIDATE_INT,
            ),
        ];

        
        $args = apply_filters("eac_items_table_query_args", $args);
        $this->items = Item::results($args);
        $total = Item::count($args);
        $this->set_pagination_args([
            "total_items" => $total,
            "per_page" => $per_page,
        ]);
    }

    
    protected function bulk_delete($ids)
    {
        if (!current_user_can("eac_delete_items")) {
            
            EAC()->flash->error(
                __(
                    "You do not have permission to delete services.",
                    "otto-contracts",
                ),
            );
            return;
        }

        $performed = 0;
        foreach ($ids as $id) {
            if (EAC()->items->delete($id)) {
                ++$performed;
            }
        }
        if (!empty($performed)) {
            
            EAC()->flash->success(
                sprintf(
                    __(
                        "%s service(s) deleted successfully.",
                        "otto-contracts",
                    ),
                    number_format_i18n($performed),
                ),
            );
        }
    }

    
    public function no_items()
    {
        esc_html_e("No services found.", "otto-contracts");
    }

    
    protected function get_views()
    {
        $current = $this->get_request_type("all");
        $types_links = [];
        $types = array_merge(
            ["all" => __("All", "otto-contracts")],
            EAC()->items->get_types(),
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

        if (current_user_can("eac_delete_items")) {
            
            $actions["delete"] = __("Delete", "otto-contracts");
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
            "name" => __("Name", "otto-contracts"),
            "type" => __("Type", "otto-contracts"),
            "category" => __("Category", "otto-contracts"),
            "cost" => __("Cost", "otto-contracts"),
            "price" => __("Price", "otto-contracts"),
            "date_created" => __("Date", "otto-contracts"),
        ];
    }

    
    protected function get_sortable_columns()
    {
        return [
            "name" => ["name", false],
            "type" => ["type", false],
            "category" => ["category_id", false],
            "cost" => ["cost", false],
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
        $types = EAC()->items->get_types();

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

    
    public function column_cost($item)
    {
        return esc_html($item->formatted_cost);
    }

    
    public function column_date_created($item)
    {
        return $item->date_created
            ? eac_format_datetime($item->date_created, eac_date_format())
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

        if (!current_user_can("eac_delete_items")) {
            
            unset($actions["delete"]);
        }

        if (!current_user_can("eac_edit_items")) {
            
            unset($actions["edit"]);
        }

        return $this->row_actions($actions);
    }
}
