<?php

namespace Otto\Admin\ListTables;

use Otto\Models\Account;

defined("ABSPATH") || exit();


class Accounts extends ListTable
{
    
    public function __construct($args = [])
    {
        parent::__construct(
            wp_parse_args($args, [
                "singular" => "account",
                "plural" => "accounts",
                "screen" => get_current_screen(),
                "args" => [],
            ]),
        );

        $this->base_url = admin_url("admin.php?page=eac-banking&tab=accounts");
    }

    
    public function prepare_items()
    {
        $this->process_actions();
        $per_page = $this->get_items_per_page("eac_accounts_per_page", 20);
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
        ];

        
        $args = apply_filters("eac_accounts_table_query_args", $args);

        $this->items = Account::results($args);
        $total = Account::count($args);

        $this->set_pagination_args([
            "total_items" => $total,
            "per_page" => $per_page,
        ]);
    }

    
    protected function bulk_update_balance($ids)
    {
        if (!current_user_can("eac_edit_accounts")) {
            
            EAC()->flash->error(
                __(
                    "You do not have permission to update account balance.",
                    "otto-contracts",
                ),
            );
            return;
        }

        $performed = 0;
        foreach ($ids as $id) {
            $account = EAC()->accounts->get($id);
            if ($account) {
                $account->update_balance();
                ++$performed;
            }
        }
        if (!empty($performed)) {
            
            EAC()->flash->success(
                sprintf(
                    __(
                        "%s account(s) balance updated successfully.",
                        "otto-contracts",
                    ),
                    number_format_i18n($performed),
                ),
            );
        }
    }

    
    protected function bulk_delete($ids)
    {
        if (!current_user_can("eac_delete_accounts")) {
            
            EAC()->flash->error(
                __(
                    "You do not have permission to delete accounts.",
                    "otto-contracts",
                ),
            );
            return;
        }

        $performed = 0;
        foreach ($ids as $id) {
            if (EAC()->accounts->delete($id)) {
                ++$performed;
            }
        }
        if (!empty($performed)) {
            
            EAC()->flash->success(
                sprintf(
                    __(
                        "%s account(s) deleted successfully.",
                        "otto-contracts",
                    ),
                    number_format_i18n($performed),
                ),
            );
        }
    }

    
    public function no_items()
    {
        esc_html_e("No accounts found.", "otto-contracts");
    }

    
    protected function get_views()
    {
        $current = $this->get_request_type("all");
        $types_links = [];
        $types = array_merge(
            ["all" => __("All", "otto-contracts")],
            EAC()->accounts->get_types(),
        );

        foreach ($types as $type => $label) {
            $link =
                "all" === $type
                    ? $this->base_url
                    : add_query_arg("type", $type, $this->base_url);
            $args = "all" === $type ? [] : ["type" => $type];
            $count = Account::count($args);
            $label = sprintf(
                '%s <span class="count">(%s)</span>',
                esc_html($label),
                number_format_i18n($count),
            );

            $types_links["bank-" . $type] = [
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

        if (current_user_can("eac_edit_accounts")) {
            
            $actions["update_balance"] = __(
                "Update Balance",
                "otto-contracts",
            );
        }

        if (current_user_can("eac_delete_accounts")) {
            
            $actions["delete"] = __("Delete", "otto-contracts");
        }

        return $actions;
    }

    
    public function get_columns()
    {
        return [
            "cb" => '<input type="checkbox" />',
            "name" => __("Name", "otto-contracts"),
            "number" => __("Number", "otto-contracts"),
            "date_created" => __("Date", "otto-contracts"),
            "balance" => __("Balance", "otto-contracts"),
        ];
    }

    
    protected function get_sortable_columns()
    {
        return [
            "name" => ["name", false],
            "number" => ["number", false],
            "balance" => ["balance", false],
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
            esc_url($item->get_view_url()),
            wp_kses_post($item->name),
        );
    }

    
    public function column_date_created($item)
    {
        return $item->date_created
            ? eac_format_datetime($item->date_created, eac_date_format())
            : "&mdash;";
    }

    
    public function column_balance($item)
    {
        return esc_html($item->formatted_balance);
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

        if (!current_user_can("eac_delete_accounts")) {
            
            unset($actions["delete"]);
        }

        if (!current_user_can("eac_edit_accounts")) {
            
            unset($actions["edit"]);
        }

        return $this->row_actions($actions);
    }
}
