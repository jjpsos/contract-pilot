<?php

namespace Jjpsos\ContractPilot\Admin;

use Jjpsos\ContractPilot\Models\Account;
use Jjpsos\ContractPilot\Models\Category;
use Jjpsos\ContractPilot\Models\Customer;
use Jjpsos\ContractPilot\Models\Expense;
use Jjpsos\ContractPilot\Models\Invoice;
use Jjpsos\ContractPilot\Models\Item;
use Jjpsos\ContractPilot\Models\Payment;
use Jjpsos\ContractPilot\Models\Tax;
use Jjpsos\ContractPilot\Models\Transfer;
use Jjpsos\ContractPilot\Utilities\DatabaseUtil;
use Jjpsos\ContractPilot\Utilities\I18nUtil;
use Jjpsos\ContractPilot\Utilities\ReportsUtil;

defined('ABSPATH') || exit;

/**
 * Prepares variables for passive admin edit/view templates.
 *
 * Handlers load data here (or inline), assign to locals, then include the view.
 */
class ScreenViewData
{
    /**
     * @param array<string, array{label: string, icon: string}> $defaults
     *
     * @return array{sections: array<string, array{label: string, icon: string}>, current_section: string}
     */
    public static function customer_profile_sections(array $defaults, string $section_slug): array
    {
        $sections = apply_filters('contract_pilot_customer_view_sections', $defaults);

        return self::resolve_profile_section($sections, $section_slug);
    }

    /**
     * @param array<string, array{label: string, icon: string}> $defaults
     *
     * @return array{sections: array<string, array{label: string, icon: string}>, current_section: string}
     */
    public static function account_profile_sections(array $defaults, string $section_slug): array
    {
        $sections = apply_filters('contract_pilot_account_view_sections', $defaults);

        return self::resolve_profile_section($sections, $section_slug);
    }

    /**
     * @param array<string, array{label: string, icon: string}> $sections
     *
     * @return array{sections: array<string, array{label: string, icon: string}>, current_section: string}
     */
    private static function resolve_profile_section(array $sections, string $section_slug): array
    {
        $current_section = ! array_key_exists($section_slug, $sections)
            ? (string) current(array_keys($sections))
            : $section_slug;

        return compact('sections', 'current_section');
    }

    /**
     * @return array<string, mixed>
     */
    public static function invoice_edit(int $id): array
    {
        $invoice = Invoice::make($id);
        $columns = contract_pilot()->invoices->get_columns();

        if (! $invoice->is_taxed()) {
            unset($columns['tax']);
        }

        $contract_pilot_invoice_heading_sl = $invoice->exists()
            ? contract_pilot_invoice_heading_status_label($invoice)
            : '';
        $contract_pilot_edit_title = '';

        if ($invoice->exists()) {
            $contract_pilot_edit_title = '' !== $contract_pilot_invoice_heading_sl
                ? sprintf(
                    /* translators: %s: status label, e.g. Contract/Draft */
                    __('Edit %s', 'contract-pilot'),
                    $contract_pilot_invoice_heading_sl
                )
                : __('Edit Contract', 'contract-pilot');
        }

        $status_options = contract_pilot()->invoices->get_statuses();
        if ($invoice->status && ! isset($status_options[ $invoice->status ])) {
            $status_options[ $invoice->status ] = $invoice->status;
        }

        $contract_pilot_status_label_display = isset($invoice->status_label)
            ? trim((string) $invoice->status_label)
            : '';
        if ('' === $contract_pilot_status_label_display && $invoice->status) {
            $contract_pilot_status_label_display = contract_pilot_invoice_status_label_for_status($invoice->status);
        }

        return compact(
            'invoice',
            'columns',
            'contract_pilot_invoice_heading_sl',
            'contract_pilot_edit_title',
            'status_options',
            'contract_pilot_status_label_display',
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function invoice_view(int $id): array
    {
        $invoice = contract_pilot()->invoices->get($id);
        $mark_sent_url = wp_nonce_url(
            add_query_arg(
                array(
                    'action' => 'contract_pilot_invoice_mark_sent',
                    'id'     => $invoice->id,
                ),
                admin_url('admin-post.php')
            ),
            'contract_pilot_invoice_action'
        );
        $mark_accept_url = wp_nonce_url(
            add_query_arg(
                array(
                    'action' => 'contract_pilot_invoice_mark_accept',
                    'id'     => $invoice->id,
                ),
                admin_url('admin-post.php')
            ),
            'contract_pilot_invoice_action'
        );

        $contract_pilot_invoice_heading_sl = contract_pilot_invoice_heading_status_label($invoice);
        $contract_pilot_view_title = '' !== $contract_pilot_invoice_heading_sl
            ? sprintf(
                /* translators: %s: status label, e.g. Contract/Draft */
                __('View %s', 'contract-pilot'),
                $contract_pilot_invoice_heading_sl
            )
            : __('View Contract', 'contract-pilot');
        $payment_methods = contract_pilot_get_payment_methods();

        return compact(
            'invoice',
            'mark_sent_url',
            'mark_accept_url',
            'contract_pilot_invoice_heading_sl',
            'contract_pilot_view_title',
            'payment_methods',
        );
    }

    /**
     * Dashboard main screen (reports tabs + intro message card).
     *
     * @return array<string, mixed>
     */
    public static function dashboard(): array
    {
        $contract_pilot_show_reports = current_user_can('contract_pilot_read_reports');
        $contract_pilot_report_tabs = array();
        $contract_pilot_current_report_tab = 'sales';
        $contract_pilot_reports_base_url = Request::admin_url(
            admin_url('admin.php?page=contract-pilot'),
        );

        if ($contract_pilot_show_reports) {
            $contract_pilot_report_tabs = array(
                'sales'    => __('Sales Report', 'contract-pilot'),
                'expenses' => __('Expenses Report', 'contract-pilot'),
                'profits'  => __('Profits Report', 'contract-pilot'),
            );
            $contract_pilot_current_report_tab = Request::get_key(
                'dashboard_report_tab',
                'sales',
                'contract_pilot_read_reports',
            );

            if (! isset($contract_pilot_report_tabs[ $contract_pilot_current_report_tab ])) {
                $contract_pilot_current_report_tab = 'sales';
            }
        }

        $contract_pilot_message_enabled = 'no' !== get_option(
            'contract_pilot_dashboard_message_enabled',
            'yes',
        );
        $contract_pilot_contribution_url = 'https://www.softestate.net/contribution/';

        return compact(
            'contract_pilot_show_reports',
            'contract_pilot_report_tabs',
            'contract_pilot_current_report_tab',
            'contract_pilot_reports_base_url',
            'contract_pilot_message_enabled',
            'contract_pilot_contribution_url',
        );
    }

    /**
     * @param \WP_List_Table|null $contract_pilot_list_table
     * @param array<string, mixed> $contract_pilot_extra
     *
     * @return array<string, mixed>
     */
    private static function list_screen_data($contract_pilot_list_table, array $contract_pilot_extra = array()): array
    {
        return array_merge(
            array('contract_pilot_list_table' => $contract_pilot_list_table),
            $contract_pilot_extra,
        );
    }

    /**
     * @param \WP_List_Table|null $contract_pilot_list_table
     *
     * @return array<string, mixed>
     */
    public static function account_list($contract_pilot_list_table): array
    {
        return self::list_screen_data(
            $contract_pilot_list_table,
            array('contract_pilot_filter_type' => Request::get_string('type')),
        );
    }

    /**
     * @param \WP_List_Table|null $contract_pilot_list_table
     *
     * @return array<string, mixed>
     */
    public static function customer_list($contract_pilot_list_table): array
    {
        return self::list_screen_data($contract_pilot_list_table);
    }

    /**
     * @param \WP_List_Table|null $contract_pilot_list_table
     *
     * @return array<string, mixed>
     */
    public static function category_list($contract_pilot_list_table): array
    {
        return self::list_screen_data(
            $contract_pilot_list_table,
            array('contract_pilot_filter_type' => Request::get_string('type')),
        );
    }

    /**
     * @param \WP_List_Table|null $contract_pilot_list_table
     *
     * @return array<string, mixed>
     */
    public static function item_list($contract_pilot_list_table): array
    {
        return self::list_screen_data(
            $contract_pilot_list_table,
            array('contract_pilot_filter_type' => Request::get_string('type')),
        );
    }

    /**
     * @param \WP_List_Table|null $contract_pilot_list_table
     *
     * @return array<string, mixed>
     */
    public static function invoice_list($contract_pilot_list_table): array
    {
        return self::list_screen_data(
            $contract_pilot_list_table,
            array('contract_pilot_filter_status' => Request::get_string('status')),
        );
    }

    /**
     * @param \WP_List_Table|null $contract_pilot_list_table
     *
     * @return array<string, mixed>
     */
    public static function payment_list($contract_pilot_list_table): array
    {
        return self::list_screen_data($contract_pilot_list_table);
    }

    /**
     * @param \WP_List_Table|null $contract_pilot_list_table
     *
     * @return array<string, mixed>
     */
    public static function expense_list($contract_pilot_list_table): array
    {
        return self::list_screen_data($contract_pilot_list_table);
    }

    /**
     * @param \WP_List_Table|null $contract_pilot_list_table
     *
     * @return array<string, mixed>
     */
    public static function transfer_list($contract_pilot_list_table): array
    {
        return self::list_screen_data($contract_pilot_list_table);
    }

    /**
     * @param \WP_List_Table|null $contract_pilot_list_table
     *
     * @return array<string, mixed>
     */
    public static function tax_list($contract_pilot_list_table): array
    {
        return self::list_screen_data($contract_pilot_list_table);
    }

    /**
     * @return array<string, mixed>
     */
    public static function account_edit(int $id): array
    {
        return array(
            'account'        => Account::make($id),
            'account_types'  => contract_pilot()->accounts->get_types(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function customer_edit(int $id): array
    {
        return array(
            'customer'  => Customer::make($id),
            'countries' => I18nUtil::get_countries(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function category_edit(int $id): array
    {
        return array(
            'category'       => Category::make($id),
            'type_default'   => Request::get_string('type'),
            'category_types' => contract_pilot()->categories->get_types(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function item_edit(int $id): array
    {
        return array(
            'item'       => Item::make($id),
            'item_types' => contract_pilot()->items->get_types(),
            'item_units' => contract_pilot()->items->get_units(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function tax_edit(int $id): array
    {
        return array(
            'tax' => Tax::make($id),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function transfer_edit(int $id): array
    {
        return array(
            'transfer' => Transfer::make($id),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function expense_edit(int $id): array
    {
        return array(
            'expense' => Expense::make($id),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function payment_edit(int $id): array
    {
        return array(
            'payment' => Payment::make($id),
        );
    }

    /**
     * @return array<string, array{label: string, icon: string}>
     */
    private static function account_view_section_defaults(): array
    {
        return array(
            'overview' => array(
                'label' => __('Overview', 'contract-pilot'),
                'icon'  => 'admin-settings',
            ),
            'payments' => array(
                'label' => __('Payments', 'contract-pilot'),
                'icon'  => 'money',
            ),
            'expenses' => array(
                'label' => __('Expenses', 'contract-pilot'),
                'icon'  => 'money-alt',
            ),
            'notes'    => array(
                'label' => __('Notes', 'contract-pilot'),
                'icon'  => 'admin-comments',
            ),
        );
    }

    /**
     * @return array<string, array{label: string, icon: string}>
     */
    private static function customer_view_section_defaults(): array
    {
        return array(
            'overview' => array(
                'label' => __('Overview', 'contract-pilot'),
                'icon'  => 'admin-settings',
            ),
            'payments' => array(
                'label' => __('Payments', 'contract-pilot'),
                'icon'  => 'money',
            ),
            'invoices' => array(
                'label' => __('Contracts/Bills', 'contract-pilot'),
                'icon'  => 'text-page',
            ),
            'notes'    => array(
                'label' => __('Notes', 'contract-pilot'),
                'icon'  => 'admin-comments',
            ),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function account_view(int $id, string $section_slug): array
    {
        $account = contract_pilot()->accounts->get($id);

        return array_merge(
            self::account_profile_sections(self::account_view_section_defaults(), $section_slug),
            array(
                'account'           => $account,
                'currency_symbol'   => contract_pilot()->currencies->get_symbol($account->currency),
            ),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function customer_view(int $id, string $section_slug): array
    {
        $customer = contract_pilot()->customers->get($id);

        return array_merge(
            self::customer_profile_sections(self::customer_view_section_defaults(), $section_slug),
            array(
                'customer' => $customer,
            ),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function payment_view(int $id): array
    {
        return array(
            'payment' => contract_pilot()->payments->get($id),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function expense_view(int $id): array
    {
        return array(
            'expense' => contract_pilot()->expenses->get($id),
        );
    }

    /**
     * @param object $account Account model.
     *
     * @return array<string, mixed>
     */
    public static function account_overview($account): array
    {
        $contract_pilot_start_date = ReportsUtil::get_year_start_date();
        $contract_pilot_end_date   = ReportsUtil::get_year_end_date();
        $contract_pilot_transactions = DatabaseUtil::get_results_account_overview_transactions(
            $account->id,
            get_gmt_from_date($contract_pilot_start_date),
            get_gmt_from_date($contract_pilot_end_date),
        );

        $contract_pilot_stats = array(
            array(
                'label' => __('Incoming', 'contract-pilot'),
                'value' => contract_pilot_format_amount(
                    array_sum(
                        wp_list_pluck(
                            wp_list_filter($contract_pilot_transactions, array( 'type' => 'payment' )),
                            'amount',
                        ),
                    ),
                    $account->currency,
                ),
                'meta'  => array(
                    contract_pilot_format_datetime(get_gmt_from_date($contract_pilot_start_date), 'Y'),
                ),
            ),
            array(
                'label' => __('Outgoing', 'contract-pilot'),
                'value' => contract_pilot_format_amount(
                    array_sum(
                        wp_list_pluck(
                            wp_list_filter($contract_pilot_transactions, array( 'type' => 'expense' )),
                            'amount',
                        ),
                    ),
                    $account->currency,
                ),
                'meta'  => array(
                    contract_pilot_format_datetime(get_gmt_from_date($contract_pilot_start_date), 'Y'),
                ),
            ),
            array(
                'label' => __('Balance', 'contract-pilot'),
                'value' => $account->formatted_balance,
            ),
        );
        $contract_pilot_stats = apply_filters('contract_pilot_account_overview_stats', $contract_pilot_stats);

        $contract_pilot_payments_chart = ReportsUtil::annualize_data(
            wp_list_filter($contract_pilot_transactions, array( 'type' => 'payment' )),
        );
        $contract_pilot_expenses_chart = ReportsUtil::annualize_data(
            wp_list_filter($contract_pilot_transactions, array( 'type' => 'expense' )),
        );

        $contract_pilot_attributes = array(
            array(
                'label' => __('Name', 'contract-pilot'),
                'value' => $account->name,
            ),
            array(
                'label' => __('Number', 'contract-pilot'),
                'value' => $account->number,
            ),
            array(
                'label' => __('Currency', 'contract-pilot'),
                'value' => $account->currency,
            ),
            array(
                'label' => __('Created', 'contract-pilot'),
                'value' => $account->date_created
                    ? contract_pilot_format_datetime($account->date_created, contract_pilot_date_format())
                    : '&mdash;',
            ),
            array(
                'label' => __('Updated', 'contract-pilot'),
                'value' => $account->date_updated
                    ? contract_pilot_format_datetime($account->date_updated, contract_pilot_date_format())
                    : '&mdash;',
            ),
        );

        return array(
            'contract_pilot_chart_title'     => __('Overview', 'contract-pilot'),
            'contract_pilot_chart_canvas_id' => 'contract-pilot-account-chart',
            'contract_pilot_chart_currency'  => contract_pilot()->currencies->get_symbol($account->currency),
            'contract_pilot_chart'           => array(
                'type'     => 'line',
                'labels'   => array_keys($contract_pilot_payments_chart),
                'datasets' => array(
                    array(
                        'label'           => __('Incoming', 'contract-pilot'),
                        'backgroundColor' => '#4CAF50',
                        'data'            => array_values($contract_pilot_payments_chart),
                    ),
                    array(
                        'label'           => __('Outgoing', 'contract-pilot'),
                        'backgroundColor' => '#F44336',
                        'data'            => array_values($contract_pilot_expenses_chart),
                    ),
                ),
            ),
            'contract_pilot_stats'           => $contract_pilot_stats,
            'contract_pilot_stats_columns'   => 3,
            'contract_pilot_details_heading' => __('Account Details', 'contract-pilot'),
            'contract_pilot_attributes'      => $contract_pilot_attributes,
        );
    }

    /**
     * @param object $customer Customer model.
     *
     * @return array<string, mixed>
     */
    public static function customer_overview($customer): array
    {
        $contract_pilot_year_start = ReportsUtil::get_year_start_date();
        $contract_pilot_year_end   = ReportsUtil::get_year_end_date();
        $contract_pilot_results    = DatabaseUtil::get_results_customer_payment_chart_rows(
            $customer->id,
            get_gmt_from_date($contract_pilot_year_start),
            get_gmt_from_date($contract_pilot_year_end),
        );

        $contract_pilot_invoices_total = DatabaseUtil::get_var_customer_invoices_total($customer->id);
        $contract_pilot_paid_total     = DatabaseUtil::get_var_customer_payments_total($customer->id);
        $contract_pilot_due_total      = empty($contract_pilot_invoices_total)
            ? 0
            : max($contract_pilot_invoices_total - $contract_pilot_paid_total, 0);
        $contract_pilot_chart_data     = ReportsUtil::annualize_data($contract_pilot_results);

        $contract_pilot_attributes = array(
            array( 'label' => __('Name', 'contract-pilot'), 'value' => $customer->name ),
            array( 'label' => __('Company', 'contract-pilot'), 'value' => $customer->company ),
            array( 'label' => __('Email', 'contract-pilot'), 'value' => $customer->email ),
            array( 'label' => __('Phone', 'contract-pilot'), 'value' => $customer->phone ),
            array( 'label' => __('Website', 'contract-pilot'), 'value' => $customer->website ),
            array( 'label' => __('Address', 'contract-pilot'), 'value' => $customer->address ),
            array( 'label' => __('City', 'contract-pilot'), 'value' => $customer->city ),
            array( 'label' => __('State', 'contract-pilot'), 'value' => $customer->state ),
            array( 'label' => __('Postcode', 'contract-pilot'), 'value' => $customer->postcode ),
            array( 'label' => __('Country', 'contract-pilot'), 'value' => $customer->country_name ),
            array( 'label' => __('Tax Number', 'contract-pilot'), 'value' => $customer->tax_number ),
            array( 'label' => __('Currency', 'contract-pilot'), 'value' => $customer->currency ),
            array(
                'label' => __('Created', 'contract-pilot'),
                'value' => $customer->date_created
                    ? contract_pilot_format_datetime($customer->date_created, contract_pilot_date_format())
                    : '&mdash;',
            ),
            array(
                'label' => __('Updated', 'contract-pilot'),
                'value' => $customer->date_updated
                    ? contract_pilot_format_datetime($customer->date_updated, contract_pilot_date_format())
                    : '&mdash;',
            ),
        );

        return array(
            'contract_pilot_chart_title'     => __('Overview', 'contract-pilot'),
            'contract_pilot_chart_canvas_id' => 'contract-pilot-customer-chart',
            'contract_pilot_chart_currency'  => contract_pilot()->currencies->get_symbol(contract_pilot_base_currency()),
            'contract_pilot_chart'           => array(
                'type'     => 'line',
                'labels'   => array_keys($contract_pilot_chart_data),
                'datasets' => array(
                    array(
                        'label'           => __('Payments', 'contract-pilot'),
                        'backgroundColor' => '#3644ff',
                        'borderColor'     => '#3644ff',
                        'fill'            => false,
                        'data'            => array_values($contract_pilot_chart_data),
                    ),
                ),
            ),
            'contract_pilot_stats'           => array(
                array(
                    'label' => __('Due', 'contract-pilot'),
                    'value' => contract_pilot_format_amount($contract_pilot_due_total),
                ),
                array(
                    'label' => __('Paid', 'contract-pilot'),
                    'value' => contract_pilot_format_amount($contract_pilot_paid_total),
                ),
            ),
            'contract_pilot_stats_columns'   => 2,
            'contract_pilot_details_heading' => __('Details', 'contract-pilot'),
            'contract_pilot_attributes'      => $contract_pilot_attributes,
        );
    }

    /**
     * @param object $account Account model.
     *
     * @return array<string, mixed>
     */
    public static function account_payments_table($account): array
    {
        return array(
            'contract_pilot_payments' => contract_pilot()->payments->query(
                array(
                    'account_id' => $account->id,
                    'orderby'    => 'date_created',
                    'order'      => 'DESC',
                    'limit'      => 20,
                ),
            ),
        );
    }

    /**
     * @param object $account Account model.
     *
     * @return array<string, mixed>
     */
    public static function account_expenses_table($account): array
    {
        return array(
            'contract_pilot_expenses' => contract_pilot()->expenses->query(
                array(
                    'account_id' => $account->id,
                    'orderby'    => 'date_created',
                    'order'      => 'DESC',
                    'limit'      => 20,
                ),
            ),
        );
    }

    /**
     * @param object $customer Customer model.
     *
     * @return array<string, mixed>
     */
    public static function customer_payments_table($customer): array
    {
        return array(
            'contract_pilot_payments' => contract_pilot()->payments->query(
                array(
                    'contact_id'        => $customer->id,
                    'contact_id__not'   => '',
                    'limit'             => 20,
                    'orderby'           => 'payment_date',
                    'order'             => 'DESC',
                ),
            ),
        );
    }

    /**
     * @param object $customer Customer model.
     *
     * @return array<string, mixed>
     */
    public static function customer_invoices_table($customer): array
    {
        return array(
            'contract_pilot_invoices' => contract_pilot()->invoices->query(
                array(
                    'contact_id'        => $customer->id,
                    'contact_id__neq'   => '',
                    'limit'             => 20,
                    'orderby'           => 'date',
                    'order'             => 'DESC',
                ),
            ),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function notes_section(int $parent_id, string $parent_type, ?string $note_capability = null): array
    {
        $data = array(
            'contract_pilot_notes'            => contract_pilot()->notes->query(
                array(
                    'parent_id'   => $parent_id,
                    'parent_type' => $parent_type,
                    'orderby'     => 'date_created',
                    'order'       => 'DESC',
                    'limit'       => 20,
                ),
            ),
            'contract_pilot_note_parent_id'   => $parent_id,
            'contract_pilot_note_parent_type' => $parent_type,
        );

        if (null !== $note_capability) {
            $data['contract_pilot_note_capability'] = $note_capability;
        }

        return $data;
    }

    /**
     * @param object $entity Entity with id property.
     *
     * @return array<string, mixed>
     */
    public static function notes_section_for_entity($entity, string $parent_type, ?string $note_capability = null): array
    {
        if (! is_object($entity) || empty($entity->id)) {
            return array();
        }

        return self::notes_section((int) $entity->id, $parent_type, $note_capability);
    }

    /**
     * @return array<string, mixed>
     */
    public static function attachment_card(int $attachment_id): array
    {
        return array(
            'contract_pilot_attachment_id' => $attachment_id,
        );
    }
}
