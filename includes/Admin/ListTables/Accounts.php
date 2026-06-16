<?php

namespace Jjpsos\ContractPilot\Admin\ListTables;

use Jjpsos\ContractPilot\Admin\Request;
use Jjpsos\ContractPilot\Models\Account;

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

        $this->base_url = Request::list_table_url(
            admin_url("admin.php?page=contract-pilot-banking&tab=accounts"),
        );
    }


    public function prepare_items()
    {
        $this->process_actions();
        $per_page = $this->get_items_per_page("contract_pilot_accounts_per_page", 20);
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
        ];


        $args = apply_filters("contract_pilot_accounts_table_query_args", $args);

        $this->items = Account::results($args);
        $total = Account::count($args);

        $this->set_pagination_args([
            "total_items" => $total,
            "per_page" => $per_page,
        ]);
    }


    protected function bulk_update_balance($ids)
    {
        if (!current_user_can("contract_pilot_edit_accounts")) {
            contract_pilot()->flash->error(
                __(
                    "You do not have permission to update account balance.",
                    "contract-pilot",
                ),
            );
            return;
        }

        $performed = 0;
        foreach ($ids as $id) {
            $account = contract_pilot()->accounts->get($id);
            if ($account) {
                $account->update_balance();
                ++$performed;
            }
        }
        if (!empty($performed)) {
            contract_pilot()->flash->success(
                sprintf(
                    /* translators: %s: number of accounts updated (formatted). */
                    __(
                        "%s account(s) balance updated successfully.",
                        "contract-pilot",
                    ),
                    number_format_i18n($performed),
                ),
            );
        }
    }


    protected function bulk_delete($ids)
    {
        if (!current_user_can("contract_pilot_delete_accounts")) {
            contract_pilot()->flash->error(
                __(
                    "You do not have permission to delete accounts.",
                    "contract-pilot",
                ),
            );
            return;
        }

        $performed = 0;
        foreach ($ids as $id) {
            if (contract_pilot()->accounts->delete($id)) {
                ++$performed;
            }
        }
        if (!empty($performed)) {
            contract_pilot()->flash->success(
                sprintf(
                    /* translators: %s: number of accounts deleted (formatted). */
                    __(
                        "%s account(s) deleted successfully.",
                        "contract-pilot",
                    ),
                    number_format_i18n($performed),
                ),
            );
        }
    }


    public function no_items()
    {
        esc_html_e("No accounts found.", "contract-pilot");
    }


    protected function get_views()
    {
        $current = $this->get_request_type("all");
        $types_links = [];
        $types = array_merge(
            ["all" => __("All", "contract-pilot")],
            contract_pilot()->accounts->get_types(),
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

        if (current_user_can("contract_pilot_edit_accounts")) {
            $actions["update_balance"] = __(
                "Update Balance",
                "contract-pilot",
            );
        }

        if (current_user_can("contract_pilot_delete_accounts")) {
            $actions["delete"] = __("Delete", "contract-pilot");
        }

        return $actions;
    }


    public function get_columns()
    {
        return [
            "cb" => '<input type="checkbox" />',
            "name" => __("Name", "contract-pilot"),
            "number" => __("Number", "contract-pilot"),
            "date_created" => __("Date", "contract-pilot"),
            "balance" => __("Balance", "contract-pilot"),
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
            ? contract_pilot_format_datetime($item->date_created, contract_pilot_date_format())
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

        if (!current_user_can("contract_pilot_delete_accounts")) {
            unset($actions["delete"]);
        }

        if (!current_user_can("contract_pilot_edit_accounts")) {
            unset($actions["edit"]);
        }

        return $this->row_actions($actions);
    }
}
