<?php


namespace Jjpsos\ContractPilot\Admin;

use Jjpsos\ContractPilot\Admin\Concerns\ContractPilotListTableScreen;
use Jjpsos\ContractPilot\Admin\Concerns\HandlesSaveRequest;
use Jjpsos\ContractPilot\Utilities\Idempotency;

defined('ABSPATH') || exit;

class Invoices
{
    use ContractPilotListTableScreen;
    use HandlesSaveRequest;

    public function __construct()
    {
        add_filter('contract_pilot_sales_page_tabs', [__CLASS__, 'register_tabs']);
        add_filter('contract_pilot_sales_page_tabs', [__CLASS__, 'order_sales_tabs'], 999);
        add_action('admin_post_contract_pilot_edit_invoice', [__CLASS__, 'handle_edit']);
        add_action('admin_post_contract_pilot_invoice_mark_sent', [
            __CLASS__,
            'handle_mark_sent',
        ]);
        add_action('admin_post_contract_pilot_invoice_mark_accept', [
            __CLASS__,
            'handle_mark_accept',
        ]);
        add_action('contract_pilot_sales_page_invoices_loaded', [
            __CLASS__,
            'page_loaded',
        ]);
        add_action('contract_pilot_sales_page_invoices_content', [
            __CLASS__,
            'page_content',
        ]);
        add_action('contract_pilot_invoice_view_sidebar_content', [
            __CLASS__,
            'invoice_notes',
        ]);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_invoice_edit_scripts'], 20);
    }

    public static function register_tabs($tabs)
    {
        if (current_user_can('contract_pilot_read_invoices')) {
            $tabs['invoices'] = __('Contracts/Bills', 'contract-pilot');
        }

        return $tabs;
    }

    /**
     * Force Contracts page tabs order: Clients, Contracts/Bills, Payments.
     *
     * @param array $tabs existing tabs
     *
     * @return array
     */
    public static function order_sales_tabs($tabs)
    {
        if (!is_array($tabs) || empty($tabs)) {
            return $tabs;
        }

        $ordered = [];
        $preferred_order = ['customers', 'invoices', 'payments'];

        foreach ($preferred_order as $tab_id) {
            if (isset($tabs[$tab_id])) {
                $ordered[$tab_id] = $tabs[$tab_id];
                unset($tabs[$tab_id]);
            }
        }

        foreach ($tabs as $tab_id => $label) {
            $ordered[$tab_id] = $label;
        }

        return $ordered;
    }

    /**
     * Sync contract status dropdown with the readonly status label field (inline script on contract-pilot-admin).
     *
     * @param string $hook_suffix current admin page hook suffix
     *
     * @return void
     */
    public static function enqueue_invoice_edit_scripts($hook_suffix)
    {
        if (Menus::PARENT_SLUG.'_page_contract-pilot-sales' !== $hook_suffix) {
            return;
        }

        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Script enqueue routing on capability-gated admin screen.
        $tab = Request::get_key('tab');
        if ('invoices' !== $tab) {
            return;
        }

        $action = Request::get_key('action');
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        if (!in_array($action, ['add', 'edit'], true)) {
            return;
        }

        wp_enqueue_script('contract-pilot-admin');

        $map_json = wp_json_encode(contract_pilot_invoice_status_label_map());
        $inline = sprintf(
            '(function(){var sel=document.getElementById("contract-pilot-invoice-status");var out=document.getElementById("contract-pilot-invoice-status-label-display");if(!sel||!out){return;}var map=%s;function sync(){var v=sel.value||"";var lbl=map[v]||map[v.toLowerCase()]||"";out.textContent=lbl||"\u2014";}sel.addEventListener("change",sync);sync();})();',
            $map_json,
        );

        wp_add_inline_script('contract-pilot-admin', $inline, 'after');
    }

    public static function handle_edit()
    {
        check_admin_referer('contract_pilot_edit_invoice');
        self::contract_pilot_require_capability(
            'contract_pilot_edit_invoices',
            __('You do not have permission to edit contracts.', 'contract-pilot'),
        );

        $referer = wp_get_referer();
        if (!$referer) {
            $referer = admin_url('admin.php?page=contract-pilot-sales&tab=invoices&action=add');
        }
        $id = isset($_POST['id']) ? absint(wp_unslash($_POST['id'])) : 0;
        $is_create = 0 === $id;
        $idempotency_token = '';
        if ($is_create) {
            $idempotency_token = isset($_POST['_cp_idempotency_token'])
                ? sanitize_text_field(wp_unslash($_POST['_cp_idempotency_token']))
                : '';
            $idempotency = Idempotency::acquire_lock(
                'contract_pilot_edit_invoice',
                $idempotency_token,
                'create_invoice',
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
            'issue_date' => isset($_POST['issue_date'])
                ? get_gmt_from_date(
                    sanitize_text_field(wp_unslash($_POST['issue_date'])),
                )
                : '',
            'due_date' => isset($_POST['due_date'])
                ? get_gmt_from_date(
                    sanitize_text_field(wp_unslash($_POST['due_date'])),
                )
                : '',
            'contact_id' => isset($_POST['contact_id'])
                ? absint(wp_unslash($_POST['contact_id']))
                : 0,
            'contact_name' => isset($_POST['contact_name'])
                ? sanitize_text_field(wp_unslash($_POST['contact_name']))
                : '',
            'contact_company' => isset($_POST['contact_company'])
                ? sanitize_text_field(wp_unslash($_POST['contact_company']))
                : '',
            'contact_email' => isset($_POST['contact_email'])
                ? sanitize_text_field(wp_unslash($_POST['contact_email']))
                : '',
            'contact_phone' => isset($_POST['contact_phone'])
                ? sanitize_text_field(wp_unslash($_POST['contact_phone']))
                : '',
            'contact_address' => isset($_POST['contact_address'])
                ? sanitize_text_field(wp_unslash($_POST['contact_address']))
                : '',
            'contact_city' => isset($_POST['contact_city'])
                ? sanitize_text_field(wp_unslash($_POST['contact_city']))
                : '',
            'contact_state' => isset($_POST['contact_state'])
                ? sanitize_text_field(wp_unslash($_POST['contact_state']))
                : '',
            'contact_postcode' => isset($_POST['contact_postcode'])
                ? sanitize_text_field(wp_unslash($_POST['contact_postcode']))
                : '',
            'contact_country' => isset($_POST['contact_country'])
                ? sanitize_text_field(wp_unslash($_POST['contact_country']))
                : '',
            'contact_tax_number' => isset($_POST['contact_tax_number'])
                ? sanitize_text_field(wp_unslash($_POST['contact_tax_number']))
                : '',
            'order_number' => isset($_POST['order_number'])
                ? sanitize_text_field(wp_unslash($_POST['order_number']))
                : '',
            'discount_type' => isset($_POST['discount_type'])
                ? sanitize_text_field(wp_unslash($_POST['discount_type']))
                : 'fixed',
            'discount_value' => isset($_POST['discount_value'])
                ? floatval(wp_unslash($_POST['discount_value']))
                : 0,
            'status' => isset($_POST['status'])
                ? sanitize_text_field(wp_unslash($_POST['status']))
                : 'draft',
            'note' => isset($_POST['note'])
                ? sanitize_textarea_field(wp_unslash($_POST['note']))
                : '',
            'terms' => isset($_POST['terms'])
                ? sanitize_textarea_field(wp_unslash($_POST['terms']))
                : '',
            'items' => isset($_POST['items'])
                ? map_deep(wp_unslash($_POST['items']), 'sanitize_text_field')
                : [],
        ];

        $invoice = contract_pilot()->invoice_service->save($data);
        if (is_wp_error($invoice)) {
            if ($is_create) {
                Idempotency::release_lock($idempotency_token);
            }
            contract_pilot()->flash->error($invoice->get_error_message());
            wp_safe_redirect($referer);
            exit;
        }

        if ($is_create) {
            Idempotency::consume_token($idempotency_token);
        }

        contract_pilot()->flash->success(
            __('Contract saved successfully.', 'contract-pilot'),
        );
        $referer = add_query_arg('id', $invoice->id, $referer);
        $referer = add_query_arg('action', 'view', $referer);
        $referer = remove_query_arg(['add'], $referer);
        wp_safe_redirect($referer);
        exit;
    }

    public static function handle_mark_sent()
    {
        check_admin_referer('contract_pilot_invoice_action');
        self::contract_pilot_require_capability(
            'contract_pilot_edit_invoices',
            __('You do not have permission to perform this action.', 'contract-pilot'),
        );

        $id = isset($_GET['id']) ? absint(wp_unslash($_GET['id'])) : 0;
        if (!$id) {
            wp_die(esc_html__('Invalid request.', 'contract-pilot'));
        }

        $invoice = contract_pilot()->invoices->get($id);
        if (!$invoice) {
            wp_die(
                esc_html__(
                    'You attempted to perform an action on a contract that does not exist.',
                    'contract-pilot',
                ),
            );
        }

        if (contract_pilot()->invoice_service->mark_sent($invoice)) {
            contract_pilot()->flash->success(
                __('Contract marked as sent.', 'contract-pilot'),
            );
        } else {
            contract_pilot()->flash->error(
                __('Failed to mark contract as sent.', 'contract-pilot'),
            );
        }

        $referer = add_query_arg(['action' => 'view'], wp_get_referer());
        wp_safe_redirect($referer);
        exit;
    }

    public static function handle_mark_accept()
    {
        check_admin_referer('contract_pilot_invoice_action');
        self::contract_pilot_require_capability(
            'contract_pilot_edit_invoices',
            __('You do not have permission to perform this action.', 'contract-pilot'),
        );

        $id = isset($_GET['id']) ? absint(wp_unslash($_GET['id'])) : 0;
        if (!$id) {
            wp_die(esc_html__('Invalid request.', 'contract-pilot'));
        }

        $invoice = contract_pilot()->invoices->get($id);
        if (!$invoice) {
            wp_die(
                esc_html__(
                    'You attempted to perform an action on a contract that does not exist.',
                    'contract-pilot',
                ),
            );
        }

        $result = contract_pilot()->invoice_service->mark_accept($invoice);
        if (is_wp_error($result)) {
            contract_pilot()->flash->error($result->get_error_message());
            $referer = add_query_arg(['action' => 'view'], wp_get_referer());
            wp_safe_redirect(
                $referer ? $referer : admin_url('admin.php?page=contract-pilot-sales&tab=invoices'),
            );
            exit;
        }

        if ($result) {
            contract_pilot()->flash->success(
                __('Contract marked as accepted.', 'contract-pilot'),
            );
        } else {
            contract_pilot()->flash->error(
                __('Failed to mark contract as accepted.', 'contract-pilot'),
            );
        }

        $referer = add_query_arg(['action' => 'view'], wp_get_referer());
        wp_safe_redirect($referer);
        exit;
    }

    public static function page_loaded($action)
    {
        self::contract_pilot_reset_list_table();
        switch ($action) {
            case 'add':
                self::contract_pilot_require_capability(
                    'contract_pilot_edit_invoices',
                    __('You do not have permission to add contracts.', 'contract-pilot'),
                );
                break;

            case 'view':
            case 'edit':
                $id = Request::get_int('id');
                if (!contract_pilot()->invoices->get($id)) {
                    wp_die(
                        esc_html__(
                            'You attempted to retrieve a contract that does not exist. Perhaps it was deleted?',
                            'contract-pilot',
                        ),
                    );
                }
                if (
                    'edit' === $action
                    && !contract_pilot()->invoices->get($id)->editable
                ) {
                    wp_die(
                        esc_html__(
                            'You attempted to edit a contract that is not editable.',
                            'contract-pilot',
                        ),
                    );
                }
                if ('view' === $action) {
                    self::contract_pilot_require_capability(
                        'contract_pilot_read_invoices',
                        __('You do not have permission to view contracts.', 'contract-pilot'),
                    );
                }
                if ('edit' === $action) {
                    self::contract_pilot_require_capability(
                        'contract_pilot_edit_invoices',
                        __('You do not have permission to edit contracts.', 'contract-pilot'),
                    );
                }
                break;

            default:
                $screen = get_current_screen();
                $contract_pilot_list_table = new ListTables\Invoices();
                $contract_pilot_list_table->prepare_items();
                self::contract_pilot_store_list_table($contract_pilot_list_table);
                $screen->add_option('per_page', [
                    'label' => __(
                        'Number of contracts per page:',
                        'contract-pilot',
                    ),
                    'default' => 20,
                    'option' => 'contract_pilot_invoices_per_page',
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
                    'screens/invoice-edit',
                    ScreenViewData::invoice_edit(Request::get_int('id')),
                );
                break;
            case 'view':
                contract_pilot_render_admin_view(
                    'screens/invoice-view',
                    ScreenViewData::invoice_view(Request::get_int('id')),
                );
                break;
            default:
                contract_pilot_render_admin_view(
                    'screens/invoice-list',
                    ScreenViewData::invoice_list(self::contract_pilot_fetch_list_table()),
                );
                self::contract_pilot_reset_list_table();
                break;
        }
    }

    public static function invoice_notes($invoice)
    {
        if (! $invoice->exists()) {
            return;
        }

        contract_pilot_render_admin_view(
            'partials/notes-card',
            ScreenViewData::notes_section_for_entity($invoice, 'invoice'),
        );
    }
}
