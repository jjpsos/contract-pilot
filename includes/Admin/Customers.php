<?php


namespace Jjpsos\ContractPilot\Admin;

use Jjpsos\ContractPilot\Admin\Concerns\ContractPilotListTableScreen;
use Jjpsos\ContractPilot\Admin\Concerns\HandlesSaveRequest;
use Jjpsos\ContractPilot\Models\Customer;
use Jjpsos\ContractPilot\Utilities\I18nUtil;

defined('ABSPATH') || exit;

class Customers
{
    use ContractPilotListTableScreen;
    use HandlesSaveRequest;

    public function __construct()
    {
        add_filter('contract_pilot_sales_page_tabs', [__CLASS__, 'register_tabs']);
        add_action('admin_post_contract_pilot_edit_customer', [__CLASS__, 'handle_edit']);
        add_action('contract_pilot_sales_page_customers_loaded', [
            __CLASS__,
            'page_loaded',
        ]);
        add_action('contract_pilot_sales_page_customers_content', [
            __CLASS__,
            'page_content',
        ]);
        add_action('contract_pilot_customer_profile_section_overview', [
            __CLASS__,
            'overview_section',
        ]);
        add_action('contract_pilot_customer_profile_section_payments', [
            __CLASS__,
            'payments_section',
        ]);
        add_action('contract_pilot_customer_profile_section_invoices', [
            __CLASS__,
            'invoices_section',
        ]);
        add_action('contract_pilot_customer_profile_section_notes', [
            __CLASS__,
            'notes_section',
        ]);
    }

    public static function register_tabs($tabs)
    {
        if (current_user_can('contract_pilot_read_customers')) {
            $tabs['customers'] = __('Clients', 'contract-pilot');
        }

        return $tabs;
    }

    public static function handle_edit()
    {
        check_admin_referer('contract_pilot_edit_customer');
        self::contract_pilot_require_capability(
            'contract_pilot_edit_customers',
            __('You do not have permission to edit customers.', 'contract-pilot'),
        );

        $referer = wp_get_referer();
        $data = [
            'id' => isset($_POST['id'])
                ? sanitize_text_field(wp_unslash($_POST['id']))
                : '',
            'name' => isset($_POST['name'])
                ? sanitize_text_field(wp_unslash($_POST['name']))
                : '',
            'company' => isset($_POST['company'])
                ? sanitize_text_field(wp_unslash($_POST['company']))
                : '',
            'email' => isset($_POST['email'])
                ? sanitize_email(wp_unslash($_POST['email']))
                : '',
            'phone' => isset($_POST['phone'])
                ? sanitize_text_field(wp_unslash($_POST['phone']))
                : '',
            'website' => isset($_POST['website'])
                ? esc_url_raw(wp_unslash($_POST['website']))
                : '',
            'address' => isset($_POST['address'])
                ? sanitize_text_field(wp_unslash($_POST['address']))
                : '',
            'city' => isset($_POST['city'])
                ? sanitize_text_field(wp_unslash($_POST['city']))
                : '',
            'state' => isset($_POST['state'])
                ? sanitize_text_field(wp_unslash($_POST['state']))
                : '',
            'postcode' => isset($_POST['postcode'])
                ? sanitize_text_field(wp_unslash($_POST['postcode']))
                : '',
            'country' => isset($_POST['country'])
                ? sanitize_text_field(wp_unslash($_POST['country']))
                : '',
            'tax_number' => isset($_POST['tax_number'])
                ? sanitize_text_field(wp_unslash($_POST['tax_number']))
                : '',
            // Customer currency is locked to global base currency.
            'currency' => contract_pilot_base_currency(),
        ];

        $customer = contract_pilot()->customer_service->save($data);

        self::contract_pilot_complete_save(
            $customer,
            $referer,
            __('Customer saved successfully.', 'contract-pilot'),
            static function ($referer, $customer) {
                $referer = add_query_arg('id', $customer->id, $referer);
                $referer = add_query_arg('action', 'view', $referer);

                return remove_query_arg(['add'], $referer);
            },
        );
    }

    public static function page_loaded($action)
    {
        self::contract_pilot_reset_list_table();
        switch ($action) {
            case 'add':
                self::contract_pilot_require_capability(
                    'contract_pilot_edit_customers',
                    __('You do not have permission to add customers.', 'contract-pilot'),
                );
                break;

            case 'view':
            case 'edit':
                $id = Request::get_int('id');
                if (!contract_pilot()->customers->get($id)) {
                    wp_die(
                        esc_html__(
                            'You attempted to retrieve a customer that does not exist. Perhaps it was deleted?',
                            'contract-pilot',
                        ),
                    );
                }
                if ('view' === $action) {
                    self::contract_pilot_require_capability(
                        'contract_pilot_read_customers',
                        __('You do not have permission to view customers.', 'contract-pilot'),
                    );
                }
                if ('edit' === $action) {
                    self::contract_pilot_require_capability(
                        'contract_pilot_edit_customers',
                        __('You do not have permission to edit customers.', 'contract-pilot'),
                    );
                }
                break;

            default:
                $screen = get_current_screen();
                $contract_pilot_list_table = new ListTables\Customers();
                $contract_pilot_list_table->prepare_items();
                self::contract_pilot_store_list_table($contract_pilot_list_table);
                $screen->add_option('per_page', [
                    'label' => __(
                        'Number of items per page:',
                        'contract-pilot',
                    ),
                    'default' => 20,
                    'option' => 'contract_pilot_customers_per_page',
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
                    'screens/customer-edit',
                    ScreenViewData::customer_edit(Request::get_int('id')),
                );
                break;

            case 'view':
                contract_pilot_render_admin_view(
                    'screens/customer-view',
                    ScreenViewData::customer_view(Request::get_int('id'), Request::get_key('section')),
                );
                break;

            default:
                contract_pilot_render_admin_view(
                    'screens/customer-list',
                    ScreenViewData::customer_list(self::contract_pilot_fetch_list_table()),
                );
                self::contract_pilot_reset_list_table();
                break;
        }
    }

    public static function overview_section($customer)
    {
        contract_pilot_render_admin_view(
            'partials/sections/customer-overview',
            ScreenViewData::customer_overview($customer),
        );
    }

    public static function payments_section($customer)
    {
        contract_pilot_render_admin_view(
            'partials/payments-table',
            ScreenViewData::customer_payments_table($customer),
        );
    }

    public static function invoices_section($customer)
    {
        contract_pilot_render_admin_view(
            'partials/invoices-table',
            ScreenViewData::customer_invoices_table($customer),
        );
    }

    public static function notes_section($customer)
    {
        contract_pilot_render_admin_view(
            'partials/notes-section',
            ScreenViewData::notes_section_for_entity($customer, 'customer'),
        );
    }
}
