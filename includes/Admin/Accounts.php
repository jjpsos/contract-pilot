<?php


namespace Jjpsos\ContractPilot\Admin;

use Jjpsos\ContractPilot\Admin\Concerns\ContractPilotListTableScreen;
use Jjpsos\ContractPilot\Admin\Concerns\HandlesSaveRequest;
use Jjpsos\ContractPilot\Models\Account;

defined('ABSPATH') || exit;

class Accounts
{
    use ContractPilotListTableScreen;
    use HandlesSaveRequest;

    public function __construct()
    {
        add_filter('contract_pilot_banking_page_tabs', [__CLASS__, 'register_tabs']);
        add_action('admin_post_contract_pilot_edit_account', [__CLASS__, 'handle_edit']);
        add_action('contract_pilot_banking_page_accounts_loaded', [
            __CLASS__,
            'page_loaded',
        ]);
        add_action('contract_pilot_banking_page_accounts_content', [
            __CLASS__,
            'page_content',
        ]);
        add_action('contract_pilot_account_profile_section_overview', [
            __CLASS__,
            'overview_section',
        ]);
        add_action('contract_pilot_account_profile_section_payments', [
            __CLASS__,
            'payments_section',
        ]);
        add_action('contract_pilot_account_profile_section_expenses', [
            __CLASS__,
            'expenses_section',
        ]);
        add_action('contract_pilot_account_profile_section_notes', [
            __CLASS__,
            'account_notes',
        ]);
    }

    public static function register_tabs($tabs)
    {
        if (
            current_user_can('contract_pilot_read_accounts')
            || current_user_can('contract_pilot_banking_tools_access')
            || current_user_can('contract_pilot_manage_options')
        ) {
            $tabs['accounts'] = __('Account', 'contract-pilot');
        }

        return $tabs;
    }

    public static function handle_edit()
    {
        check_admin_referer('contract_pilot_edit_account');
        self::contract_pilot_require_capability(
            'contract_pilot_edit_accounts',
            __('You do not have permission to edit accounts.', 'contract-pilot'),
        );

        $referer = wp_get_referer();
        $data = [
            'id' => isset($_POST['id']) ? absint(wp_unslash($_POST['id'])) : 0,
            'type' => isset($_POST['type'])
                ? sanitize_text_field(wp_unslash($_POST['type']))
                : '',
            'name' => isset($_POST['name'])
                ? sanitize_text_field(wp_unslash($_POST['name']))
                : '',
            'number' => isset($_POST['number'])
                ? sanitize_text_field(wp_unslash($_POST['number']))
                : '',
            // Account currency is locked to global base currency.
            'currency' => contract_pilot_base_currency(),
        ];

        $account = contract_pilot()->account_service->save($data);

        self::contract_pilot_complete_save(
            $account,
            $referer,
            __('Account saved successfully.', 'contract-pilot'),
            static function ($referer, $account) {
                $referer = remove_query_arg(['action'], $referer);

                return add_query_arg(
                    [
                        'action' => 'view',
                        'id' => $account->id,
                    ],
                    $referer,
                );
            },
        );
    }

    public static function page_loaded($action)
    {
        self::contract_pilot_reset_list_table();
        switch ($action) {
            case 'add':
                self::contract_pilot_require_capability(
                    'contract_pilot_edit_accounts',
                    __('You do not have permission to add accounts.', 'contract-pilot'),
                );
                break;
            case 'edit':
                $id = Request::get_int('id');
                if (!contract_pilot()->accounts->get($id)) {
                    wp_die(
                        esc_html__(
                            'You attempted to retrieve an account that does not exist. Perhaps it was deleted?',
                            'contract-pilot',
                        ),
                    );
                }
                if ('edit' === $action) {
                    self::contract_pilot_require_capability(
                        'contract_pilot_edit_accounts',
                        __('You do not have permission to edit accounts.', 'contract-pilot'),
                    );
                }
                break;

            default:
                $screen = get_current_screen();
                $contract_pilot_list_table = new ListTables\Accounts();
                $contract_pilot_list_table->prepare_items();
                self::contract_pilot_store_list_table($contract_pilot_list_table);
                $screen->add_option('per_page', [
                    'label' => __(
                        'Number of accounts per page:',
                        'contract-pilot',
                    ),
                    'default' => 20,
                    'option' => 'contract_pilot_accounts_per_page',
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
                    'screens/account-edit',
                    ScreenViewData::account_edit(Request::get_int('id')),
                );
                break;
            case 'view':
                contract_pilot_render_admin_view(
                    'screens/account-view',
                    ScreenViewData::account_view(Request::get_int('id'), Request::get_key('section')),
                );
                break;
            default:
                contract_pilot_render_admin_view(
                    'screens/account-list',
                    ScreenViewData::account_list(self::contract_pilot_fetch_list_table()),
                );
                self::contract_pilot_reset_list_table();
                break;
        }
    }

    public static function overview_section($account)
    {
        contract_pilot_render_admin_view(
            'partials/sections/account-overview',
            ScreenViewData::account_overview($account),
        );
    }

    public static function payments_section($account)
    {
        contract_pilot_render_admin_view(
            'partials/payments-table',
            ScreenViewData::account_payments_table($account),
        );
    }

    public static function expenses_section($account)
    {
        contract_pilot_render_admin_view(
            'partials/expenses-table',
            ScreenViewData::account_expenses_table($account),
        );
    }

    public static function account_notes($account)
    {
        if (! $account->exists()) {
            return;
        }

        contract_pilot_render_admin_view(
            'partials/notes-section',
            ScreenViewData::notes_section_for_entity($account, 'account'),
        );
    }
}
