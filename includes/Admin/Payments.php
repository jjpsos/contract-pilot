<?php


namespace Jjpsos\ContractPilot\Admin;

use Jjpsos\ContractPilot\Admin\Concerns\ContractPilotListTableScreen;
use Jjpsos\ContractPilot\Admin\Concerns\HandlesSaveRequest;
use Jjpsos\ContractPilot\Models\Payment;
use Jjpsos\ContractPilot\Utilities\Idempotency;

defined('ABSPATH') || exit;

class Payments
{
    use ContractPilotListTableScreen;
    use HandlesSaveRequest;

    public function __construct()
    {
        add_filter('contract_pilot_sales_page_tabs', [__CLASS__, 'register_tabs']);
        add_action('admin_post_contract_pilot_edit_payment', [__CLASS__, 'handle_edit']);
        add_action('admin_post_contract_pilot_update_payment', [
            __CLASS__,
            'handle_update',
        ]);
        add_action('contract_pilot_sales_page_payments_loaded', [
            __CLASS__,
            'page_loaded',
        ]);
        add_action('contract_pilot_sales_page_payments_content', [
            __CLASS__,
            'page_content',
        ]);
        add_action('contract_pilot_payment_view_sidebar_content', [
            __CLASS__,
            'payment_attachment',
        ]);
        add_action('contract_pilot_payment_view_sidebar_content', [
            __CLASS__,
            'payment_notes',
        ]);
    }

    public static function register_tabs($tabs)
    {
        if (current_user_can('contract_pilot_read_payments')) {
            $tabs['payments'] = __('Payments', 'contract-pilot');
        }

        return $tabs;
    }

    public static function handle_edit()
    {
        check_admin_referer('contract_pilot_edit_payment');
        self::contract_pilot_require_capability(
            'contract_pilot_edit_payments',
            __('You do not have permission to edit payments.', 'contract-pilot'),
        );

        $referer = wp_get_referer();
        if (!$referer) {
            $referer = admin_url('admin.php?page=contract-pilot-sales&tab=payments&action=add');
        }
        $id = isset($_POST['id']) ? absint(wp_unslash($_POST['id'])) : 0;
        $is_create = 0 === $id;
        $idempotency_token = '';
        if ($is_create) {
            $idempotency_token = isset($_POST['_cp_idempotency_token'])
                ? sanitize_text_field(wp_unslash($_POST['_cp_idempotency_token']))
                : '';
            $idempotency = Idempotency::acquire_lock(
                'contract_pilot_edit_payment',
                $idempotency_token,
                'create_payment',
            );
            if (empty($idempotency['ok'])) {
                contract_pilot()->flash->error(
                    Idempotency::get_error_message((string) $idempotency['status']),
                );
                wp_safe_redirect($referer);
                exit;
            }
        }
        $data = [
            'id' => $id,
            'payment_date' => isset($_POST['payment_date'])
                ? get_gmt_from_date(
                    sanitize_text_field(wp_unslash($_POST['payment_date'])),
                )
                : '',
            'account_id' => isset($_POST['account_id'])
                ? absint(wp_unslash($_POST['account_id']))
                : 0,
            'amount' => isset($_POST['amount'])
                ? floatval(wp_unslash($_POST['amount']))
                : 0,
            // Exchange rate is not exposed in admin; fixed to 1 (base currency).
            'exchange_rate' => 1,
            'category_id' => isset($_POST['category_id'])
                ? absint(wp_unslash($_POST['category_id']))
                : 0,
            'contact_id' => isset($_POST['contact_id'])
                ? absint(wp_unslash($_POST['contact_id']))
                : 0,
            'attachment_id' => isset($_POST['attachment_id'])
                ? absint(wp_unslash($_POST['attachment_id']))
                : 0,
            'payment_method' => isset($_POST['payment_method'])
                ? sanitize_text_field(wp_unslash($_POST['payment_method']))
                : '',
            'reference' => isset($_POST['reference'])
                ? sanitize_text_field(wp_unslash($_POST['reference']))
                : '',
            'note' => isset($_POST['note'])
                ? sanitize_textarea_field(wp_unslash($_POST['note']))
                : '',
            'status' => isset($_POST['status'])
                ? sanitize_text_field(wp_unslash($_POST['status']))
                : 'active',
        ];
        $payment = contract_pilot()->payment_service->save($data);
        if (is_wp_error($payment)) {
            if ($is_create) {
                Idempotency::release_lock($idempotency_token);
            }
            contract_pilot()->flash->error($payment->get_error_message());
        } else {
            if ($is_create) {
                Idempotency::consume_token($idempotency_token);
            }
            contract_pilot()->flash->success(
                __('Payment saved successfully.', 'contract-pilot'),
            );
            $referer = add_query_arg('id', $payment->id, $referer);
            $referer = add_query_arg('action', 'view', $referer);
            $referer = remove_query_arg(['add'], $referer);
        }

        wp_safe_redirect($referer);
        exit;
    }

    public static function handle_update()
    {
        check_admin_referer('contract_pilot_update_payment');
        self::contract_pilot_require_capability(
            'contract_pilot_edit_payments',
            __('You do not have permission to update payments.', 'contract-pilot'),
        );

        $referer = wp_get_referer();
        $id = isset($_POST['id']) ? absint(wp_unslash($_POST['id'])) : 0;
        $status = isset($_POST['status'])
            ? sanitize_text_field(wp_unslash($_POST['status']))
            : '';
        $attachment_id = isset($_POST['attachment_id'])
            ? absint(wp_unslash($_POST['attachment_id']))
            : 0;
        $payment_action = isset($_POST['payment_action'])
            ? sanitize_text_field(wp_unslash($_POST['payment_action']))
            : '';
        $payment = contract_pilot()->payments->get($id);

        if (!$payment) {
            contract_pilot()->flash->error(__('Payment not found.', 'contract-pilot'));

            return;
        }

        $has_changes = contract_pilot()->payment_service->apply_updates(
            $payment,
            $status,
            $attachment_id,
        );

        if ($has_changes) {
            $ret = $payment->save();
            if (is_wp_error($ret)) {
                contract_pilot()->flash->error($ret->get_error_message());
            } else {
                contract_pilot()->flash->success(
                    __('Payment updated successfully.', 'contract-pilot'),
                );
            }
        }

        if (!empty($payment_action)) {
            switch ($payment_action) {
                case 'send_receipt':
                    break;
                default:
                    do_action(
                        'contract_pilot_payment_action_'.$payment_action,
                        $payment,
                    );
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
                    'contract_pilot_edit_payments',
                    __('You do not have permission to add payments.', 'contract-pilot'),
                );
                break;

            case 'view':
            case 'edit':
                $id = Request::get_int('id');
                if (!contract_pilot()->payments->get($id)) {
                    wp_die(
                        esc_html__(
                            'You attempted to retrieve a payment that does not exist. Perhaps it was deleted?',
                            'contract-pilot',
                        ),
                    );
                }
                if (
                    'edit' === $action
                    && !contract_pilot()->payments->get($id)->editable
                ) {
                    wp_die(
                        esc_html__(
                            'You attempted to edit a payment that is not editable.',
                            'contract-pilot',
                        ),
                    );
                }
                if ('view' === $action) {
                    self::contract_pilot_require_capability(
                        'contract_pilot_read_payments',
                        __('You do not have permission to view payments.', 'contract-pilot'),
                    );
                }
                if ('edit' === $action) {
                    self::contract_pilot_require_capability(
                        'contract_pilot_edit_payments',
                        __('You do not have permission to edit payments.', 'contract-pilot'),
                    );
                }
                break;

            default:
                $screen = get_current_screen();
                $contract_pilot_list_table = new ListTables\Payments();
                $contract_pilot_list_table->prepare_items();
                self::contract_pilot_store_list_table($contract_pilot_list_table);
                $screen->add_option('per_page', [
                    'label' => __(
                        'Number of items per page:',
                        'contract-pilot',
                    ),
                    'default' => 20,
                    'option' => 'contract_pilot_payments_per_page',
                ]);
                break;
        }
    }

    public static function page_content($action)
    {
        switch ($action) {
            case 'add':
            case 'edit':
                contract_pilot_render_admin_view(
                    'screens/payment-edit',
                    ScreenViewData::payment_edit(Request::get_int('id')),
                );
                break;
            case 'view':
                contract_pilot_render_admin_view(
                    'screens/payment-view',
                    ScreenViewData::payment_view(Request::get_int('id')),
                );
                break;
            default:
                contract_pilot_render_admin_view(
                    'screens/payment-list',
                    ScreenViewData::payment_list(self::contract_pilot_fetch_list_table()),
                );
                self::contract_pilot_reset_list_table();
                break;
        }
    }

    public static function payment_attachment($payment)
    {
        contract_pilot_render_admin_view(
            'partials/attachment-card',
            ScreenViewData::attachment_card((int) $payment->attachment_id),
        );
    }

    public static function payment_notes($payment)
    {
        if (! $payment->exists()) {
            return;
        }

        contract_pilot_render_admin_view(
            'partials/notes-card',
            ScreenViewData::notes_section_for_entity($payment, 'payment'),
        );
    }
}
