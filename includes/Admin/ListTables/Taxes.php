<?php

namespace Jjpsos\ContractPilot\Admin\ListTables;

use Jjpsos\ContractPilot\Admin\Request;
use Jjpsos\ContractPilot\Models\Tax;

defined("ABSPATH") || exit();


class Taxes extends ListTable
{
    public function __construct($args = [])
    {
        parent::__construct(
            wp_parse_args($args, [
                "singular" => "tax",
                "plural" => "taxes",
                "screen" => get_current_screen(),
                "args" => [],
            ]),
        );

        $this->base_url = Request::list_table_url(
            admin_url("admin.php?page=contract-pilot-settings&tab=taxes&section=rates"),
        );
    }


    public function prepare_items()
    {
        $this->process_actions();
        $per_page = $this->get_items_per_page("contract_pilot_taxes_per_page", 20);
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
            "status" => $this->get_request_status(),
        ];


        $args = apply_filters("contract_pilot_taxes_table_query_args", $args);

        $this->items = contract_pilot()->taxes->query($args);
        $total = contract_pilot()->taxes->query($args, true);
        $this->set_pagination_args([
            "total_items" => $total,
            "per_page" => $per_page,
        ]);
    }


    protected function bulk_delete($ids)
    {
        $performed = 0;
        foreach ($ids as $id) {
            if (contract_pilot()->taxes->delete($id)) {
                ++$performed;
            }
        }
        if (!empty($performed)) {
            contract_pilot()->flash->success(
                sprintf(
                    /* translators: %s: number of taxes deleted (formatted). */
                    __(
                        "%s tax(es) deleted successfully.",
                        "contract-pilot",
                    ),
                    number_format_i18n($performed),
                ),
            );
        }
    }


    public function no_items()
    {
        esc_html_e("No taxes found.", "contract-pilot");
    }


    protected function get_bulk_actions()
    {
        $actions = [
            "delete" => __("Delete", "contract-pilot"),
        ];

        return $actions;
    }


    public function get_columns()
    {
        return [
            "cb" => '<input type="checkbox" />',
            "name" => __("Name", "contract-pilot"),
            "rate" => __("Rate", "contract-pilot"),
            "compound" => __("Compound", "contract-pilot"),
        ];
    }


    protected function get_sortable_columns()
    {
        return [
            "name" => ["name", false],
            "rate" => ["rate", false],
            "compound" => ["compound", false],
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


    public function column_rate($item)
    {
        return sprintf("%s%%", esc_attr($item->rate));
    }


    public function column_compound($item)
    {
        return $item->compound
            ? __("Yes", "contract-pilot")
            : __("No", "contract-pilot");
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

        if (!current_user_can("contract_pilot_delete_taxes")) {
            unset($actions["delete"]);
        }

        if (!current_user_can("contract_pilot_edit_taxes")) {
            unset($actions["edit"]);
        }

        return $this->row_actions($actions);
    }
}
