<?php

namespace Jjpsos\ContractPilot\Admin;

use Jjpsos\ContractPilot\Admin\Concerns\ContractPilotListTableScreen;
use Jjpsos\ContractPilot\Admin\Concerns\HandlesSaveRequest;
use Jjpsos\ContractPilot\Models\Item;

defined('ABSPATH') || exit;

class Items
{
    use ContractPilotListTableScreen;
    use HandlesSaveRequest;

    public function __construct()
    {
        add_filter('contract_pilot_items_page_tabs', array(__CLASS__, 'register_tabs'));
        add_action('admin_post_contract_pilot_edit_item', array(__CLASS__, 'handle_edit'));
        add_action('contract_pilot_items_page_items_loaded', array(__CLASS__, 'page_loaded'));
        add_action('contract_pilot_items_page_items_content', array(__CLASS__, 'page_content'));
        add_action('contract_pilot_item_edit_sidebar_content', array(__CLASS__, 'item_notes'));
    }

    public static function register_tabs($tabs)
    {
        if (current_user_can('contract_pilot_read_items')) {
            $tabs['items'] = __('Services', 'contract-pilot');
        }

        return $tabs;
    }

    public static function handle_edit()
    {
        check_admin_referer('contract_pilot_edit_item');
        self::contract_pilot_require_capability(
            'contract_pilot_edit_items',
            __('You do not have permission to edit services.', 'contract-pilot'),
        );

        $referer = wp_get_referer();
        $price = isset($_POST['price'])
            ? floatval(wp_unslash($_POST['price']))
            : 0;
        // Cost is not shown in the admin UI; keep DB/API in sync with price.
        $cost = isset($_POST['cost'])
            ? floatval(wp_unslash($_POST['cost']))
            : $price;

        $data = [
            'id' => isset($_POST['id']) ? absint(wp_unslash($_POST['id'])) : 0,
            'type' => isset($_POST['type'])
                ? sanitize_text_field(wp_unslash($_POST['type']))
                : '',
            'name' => isset($_POST['name'])
                ? sanitize_text_field(wp_unslash($_POST['name']))
                : '',
            'description' => isset($_POST['description'])
                ? sanitize_text_field(wp_unslash($_POST['description']))
                : '',
            'unit' => isset($_POST['unit'])
                ? sanitize_text_field(wp_unslash($_POST['unit']))
                : '',
            'price' => $price,
            'cost' => $cost,
            'tax_ids' => isset($_POST['tax_ids'])
                ? array_map('absint', wp_unslash($_POST['tax_ids']))
                : [],
            'category_id' => isset($_POST['category_id'])
                ? absint(wp_unslash($_POST['category_id']))
                : 0,
        ];

        $item = contract_pilot()->item_service->save($data);

        self::contract_pilot_complete_save(
            $item,
            $referer,
            __('Service saved successfully.', 'contract-pilot'),
            static function ($referer, $item) {
                $referer = add_query_arg('id', $item->id, $referer);

                return remove_query_arg(array('add'), $referer);
            },
        );
    }

    public static function page_loaded($action)
    {
        self::contract_pilot_reset_list_table();

        switch ($action) {
            case 'add':
                self::contract_pilot_require_capability(
                    'contract_pilot_edit_items',
                    __('You do not have permission to add services.', 'contract-pilot'),
                );
                break;

            case 'edit':
                $id = Request::get_int('id');
                if (!contract_pilot()->items->get($id)) {
                    wp_die(esc_html__('You attempted to retrieve a service that does not exist. Perhaps it was deleted?', 'contract-pilot'));
                }
                self::contract_pilot_require_capability(
                    'contract_pilot_edit_items',
                    __('You do not have permission to edit services.', 'contract-pilot'),
                );
                break;

            default:
                $screen = get_current_screen();
                $contract_pilot_list_table = new ListTables\Items();
                $contract_pilot_list_table->prepare_items();
                self::contract_pilot_store_list_table($contract_pilot_list_table);
                $screen->add_option('per_page', array(
                    'label' => __('Number of services per page:', 'contract-pilot'),
                    'default' => 20,
                    'option' => 'contract_pilot_items_per_page',
                ));
                break;
        }
    }

    public static function page_content($action)
    {
        switch ($action) {
            case 'add':
            case 'edit':
                contract_pilot_render_admin_view(
                    'screens/item-edit',
                    ScreenViewData::item_edit(Request::get_int('id')),
                );
                break;
            default:
                contract_pilot_render_admin_view(
                    'screens/item-list',
                    ScreenViewData::item_list(self::contract_pilot_fetch_list_table()),
                );
                self::contract_pilot_reset_list_table();
                break;
        }
    }

    public static function item_notes($item)
    {
        if (! $item->exists()) {
            return;
        }

        contract_pilot_render_admin_view(
            'partials/notes-card',
            ScreenViewData::notes_section_for_entity($item, 'item', 'contract_pilot_edit_items'),
        );
    }
}