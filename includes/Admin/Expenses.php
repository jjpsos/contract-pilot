<?php

namespace Jjpsos\ContractPilot\Admin;

use Jjpsos\ContractPilot\Admin\Concerns\ContractPilotListTableScreen;
use Jjpsos\ContractPilot\Admin\Concerns\HandlesSaveRequest;
use Jjpsos\ContractPilot\Models\Expense;
use Jjpsos\ContractPilot\Utilities\Idempotency;

defined('ABSPATH') || exit;


class Expenses
{
    use ContractPilotListTableScreen;
    use HandlesSaveRequest;


    public function __construct()
    {
        add_filter('contract_pilot_purchases_page_tabs', array( __CLASS__, 'register_tabs' ));
        add_action('admin_post_contract_pilot_edit_expense', array( __CLASS__, 'handle_edit' ));
        add_action('admin_post_contract_pilot_update_expense', array( __CLASS__, 'handle_update' ));
        add_action('contract_pilot_purchases_page_expenses_loaded', array( __CLASS__, 'page_loaded' ));
        add_action('contract_pilot_purchases_page_expenses_content', array( __CLASS__, 'page_content' ));
        add_action('contract_pilot_expense_view_sidebar_content', array( __CLASS__, 'expense_attachment' ));
        add_action('contract_pilot_expense_view_sidebar_content', array( __CLASS__, 'expense_notes' ));
    }


    public static function register_tabs($tabs)
    {
        if (current_user_can('contract_pilot_read_expenses')) {
            $tabs['expenses'] = __('Expenses', 'contract-pilot');
        }

        return $tabs;
    }


    public static function handle_edit()
    {
        check_admin_referer('contract_pilot_edit_expense');
        self::contract_pilot_require_capability(
            'contract_pilot_edit_expenses',
            __('You do not have permission to edit expenses.', 'contract-pilot'),
        );

        $referer = wp_get_referer();
        if (! $referer) {
            $referer = admin_url('admin.php?page=contract-pilot-purchases&tab=expenses&action=add');
        }
        $id = isset($_POST['id']) ? absint(wp_unslash($_POST['id'])) : 0;
        $is_create = 0 === $id;
        $idempotency_token = '';
        if ($is_create) {
            $idempotency_token = isset($_POST['_cp_idempotency_token'])
                ? sanitize_text_field(wp_unslash($_POST['_cp_idempotency_token']))
                : '';
            $idempotency = Idempotency::acquire_lock(
                'contract_pilot_edit_expense',
                $idempotency_token,
                'create_expense',
            );
            if (empty($idempotency['ok'])) {
                contract_pilot()->flash->error(
                    Idempotency::get_error_message((string) $idempotency['status'])
                );
                wp_safe_redirect($referer);
                exit;
            }
        }
        $data    = array(
            'id'             => $id,
            'payment_date'   => isset($_POST['payment_date']) ? get_gmt_from_date(sanitize_text_field(wp_unslash($_POST['payment_date']))) : '',
            'account_id'     => isset($_POST['account_id']) ? absint(wp_unslash($_POST['account_id'])) : 0,
            'amount'         => isset($_POST['amount']) ? floatval(wp_unslash($_POST['amount'])) : 0,
            // Exchange rate is not exposed in admin; fixed to 1 (base currency).
            'exchange_rate'  => 1,
            'category_id'    => isset($_POST['category_id']) ? absint(wp_unslash($_POST['category_id'])) : 0,
            'contact_id'     => isset($_POST['contact_id']) ? absint(wp_unslash($_POST['contact_id'])) : 0,
            'attachment_id'  => isset($_POST['attachment_id']) ? absint(wp_unslash($_POST['attachment_id'])) : 0,
            'payment_method' => isset($_POST['payment_method']) ? sanitize_text_field(wp_unslash($_POST['payment_method'])) : '',
            'reference'      => isset($_POST['reference']) ? sanitize_text_field(wp_unslash($_POST['reference'])) : '',
            'note'           => isset($_POST['note']) ? sanitize_textarea_field(wp_unslash($_POST['note'])) : '',
            'status'         => isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : 'active',
        );
        $expense = contract_pilot()->expense_service->save($data);
        if (is_wp_error($expense)) {
            if ($is_create) {
                Idempotency::release_lock($idempotency_token);
            }
            contract_pilot()->flash->error($expense->get_error_message());
        } else {
            if ($is_create) {
                Idempotency::consume_token($idempotency_token);
            }
            contract_pilot()->flash->success(__('Expense saved successfully.', 'contract-pilot'));
            $referer = add_query_arg('id', $expense->id, $referer);
            $referer = add_query_arg('action', 'edit', $referer);
            $referer = remove_query_arg(array( 'add' ), $referer);
        }

        wp_safe_redirect($referer);
        exit;
    }


    public static function handle_update()
    {
        check_admin_referer('contract_pilot_update_expense');
        self::contract_pilot_require_capability(
            'contract_pilot_edit_expenses',
            __('You do not have permission to update expense.', 'contract-pilot'),
        );

        $referer        = wp_get_referer();
        $id             = isset($_POST['id']) ? absint(wp_unslash($_POST['id'])) : 0;
        $status         = isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : '';
        $attachment_id  = isset($_POST['attachment_id']) ? absint(wp_unslash($_POST['attachment_id'])) : 0;
        $expense_action = isset($_POST['payment_action']) ? sanitize_text_field(wp_unslash($_POST['payment_action'])) : '';
        $expense        = contract_pilot()->expenses->get($id);


        if (! $expense) {
            contract_pilot()->flash->error(__('Expense not found.', 'contract-pilot'));

            return;
        }


        $has_changes = contract_pilot()->expense_service->apply_updates(
            $expense,
            $status,
            $attachment_id,
        );

        if ($has_changes) {
            $ret = $expense->save();
            if (is_wp_error($ret)) {
                contract_pilot()->flash->error($ret->get_error_message());
            } else {
                contract_pilot()->flash->success(__('Expense updated successfully.', 'contract-pilot'));
            }
        }


        if (! empty($expense_action)) {
            switch ($expense_action) {
                case 'send_receipt':
                    break;
                default:
                    do_action('contract_pilot_expense_action_' . $expense_action, $expense);
                    break;
            }
        }

        wp_safe_redirect($referer);
        exit;
    }


    public static function page_loaded($action)
    {
        self::contract_pilot_reset_list_table();
        switch ($action) {
            case 'add':
                self::contract_pilot_require_capability(
                    'contract_pilot_edit_expenses',
                    __('You do not have permission to add expenses.', 'contract-pilot'),
                );
                break;

            case 'view':
            case 'edit':
                $id = Request::get_int('id');
                if (! contract_pilot()->expenses->get($id)) {
                    wp_die(esc_html__('You attempted to retrieve a expense that does not exist. Perhaps it was deleted?', 'contract-pilot'));
                }
                if ('view' === $action) {
                    self::contract_pilot_require_capability(
                        'contract_pilot_read_expenses',
                        __('You do not have permission to view expenses.', 'contract-pilot'),
                    );
                }
                if ('edit' === $action) {
                    self::contract_pilot_require_capability(
                        'contract_pilot_edit_expenses',
                        __('You do not have permission to edit expenses.', 'contract-pilot'),
                    );
                }
                break;

            default:
                $screen         = get_current_screen();
                $contract_pilot_list_table = new ListTables\Expenses();
                $contract_pilot_list_table->prepare_items();
                self::contract_pilot_store_list_table($contract_pilot_list_table);
                $screen->add_option(
                    'per_page',
                    array(
                        'label'   => __('Number of items per page:', 'contract-pilot'),
                        'default' => 20,
                        'option'  => 'contract_pilot_expenses_per_page',
                    )
                );
                break;
        }
    }


    public static function page_content($action)
    {
        switch ($action) {
            case 'add':
            case 'edit':
                contract_pilot_render_admin_view(
                    'screens/expense-edit',
                    ScreenViewData::expense_edit(Request::get_int('id')),
                );
                break;
            case 'view':
                contract_pilot_render_admin_view(
                    'screens/expense-view',
                    ScreenViewData::expense_view(Request::get_int('id')),
                );
                break;
            default:
                contract_pilot_render_admin_view(
                    'screens/expense-list',
                    ScreenViewData::expense_list(self::contract_pilot_fetch_list_table()),
                );
                self::contract_pilot_reset_list_table();
                break;
        }
    }


    public static function expense_attachment($expense)
    {
        contract_pilot_render_admin_view(
            'partials/attachment-card',
            ScreenViewData::attachment_card((int) $expense->attachment_id),
        );
    }


    public static function expense_notes($expense)
    {
        if (! $expense) {
            return;
        }

        contract_pilot_render_admin_view(
            'partials/notes-card',
            ScreenViewData::notes_section_for_entity($expense, 'expense'),
        );
    }
}
