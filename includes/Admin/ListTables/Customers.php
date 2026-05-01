<?php

namespace Otto\Admin\ListTables;

use Otto\Models\Customer;

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
        $this->base_url = admin_url("admin.php?page=eac-sales&tab=customers");
    }

    
    public function prepare_items()
    {
        $this->process_actions();
        $per_page = $this->get_items_per_page("eac_customers_per_page", 20);
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
        
        $args = apply_filters("eac_customers_table_query_args", $args);
        $this->items = EAC()->customers->query($args);
        $total = EAC()->customers->query($args, true);

        $this->set_pagination_args([
            "total_items" => $total,
            "per_page" => $per_page,
        ]);
    }

    
    protected function bulk_delete($ids)
    {
        if (!current_user_can("eac_delete_customers")) {
            
            EAC()->flash->error(
                __(
                    "You do not have permission to delete customers.",
                    "otto-contracts",
                ),
            );
            return;
        }

        $performed = 0;
        foreach ($ids as $id) {
            if (EAC()->customers->delete($id)) {
                ++$performed;
            }
        }
        if (!empty($performed)) {
            
            EAC()->flash->success(
                sprintf(
                    __(
                        "%s customer(s) deleted successfully.",
                        "otto-contracts",
                    ),
                    number_format_i18n($performed),
                ),
            );
        }
    }

    
    public function no_items()
    {
        esc_html_e("No customers found.", "otto-contracts");
    }

    
    protected function get_bulk_actions()
    {
        $actions = [];

        if (current_user_can("eac_delete_customers")) {
            
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

        if ("top" === $which) {
            ob_start();
            $this->country_filter("active");
            $output = ob_get_clean();
            if (!empty($output) && $this->has_items()) {
                echo $output; 
                submit_button(
                    __("Filter", "otto-contracts"),
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
            "name" => __("Name", "otto-contracts"),
            "email" => __("Email", "otto-contracts"),
            "phone" => __("Phone", "otto-contracts"),
            "country" => __("Country", "otto-contracts"),
            "date" => __("Date", "otto-contracts"),
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
            ? eac_format_datetime($item->date_created, eac_date_format())
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

        if (!current_user_can("eac_delete_customers")) {
            
            unset($actions["delete"]);
        }

        if (!current_user_can("eac_edit_customers")) {
            
            unset($actions["edit"]);
        }

        return $this->row_actions($actions);
    }
}
