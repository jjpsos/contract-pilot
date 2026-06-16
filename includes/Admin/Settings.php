<?php


namespace Jjpsos\ContractPilot\Admin;

use Jjpsos\ContractPilot\Admin\Settings\Extensions;
use Jjpsos\ContractPilot\Admin\Settings\General;
use Jjpsos\ContractPilot\Admin\Settings\Page;
use Jjpsos\ContractPilot\Admin\Settings\Purchases;
use Jjpsos\ContractPilot\Admin\Settings\Sales;
use Jjpsos\ContractPilot\Admin\Settings\Taxes;

defined('ABSPATH') || exit;

class Settings
{
    public function __construct()
    {
        add_filter('contract_pilot_settings_page_tabs', [__CLASS__, 'register_tabs'], -1);
        add_action('contract_pilot_settings_page_loaded', [__CLASS__, 'save_settings']);
        add_action('contract_pilot_settings_page_banking_accounts_content', [
            __CLASS__,
            'banking_accounts_tab',
        ]);
        add_action('contract_pilot_settings_page_feature_access_content', [
            __CLASS__,
            'feature_access_tab',
        ]);
        add_action('contract_pilot_settings_save_general', [
            __CLASS__,
            'sync_banking_tools_role_access',
        ], 20);
        add_action('contract_pilot_settings_save_feature_access', [
            __CLASS__,
            'save_feature_access_settings',
        ]);
        add_action('contract_pilot_settings_save_banking_accounts', [
            __CLASS__,
            'save_banking_account_settings',
        ]);
        add_action('init', [__CLASS__, 'sync_banking_tools_role_access'], 20);
        add_action('init', [__CLASS__, 'cleanup_legacy_feature_access_options'], 21);
        add_action('admin_init', [__CLASS__, 'guard_banking_tools_pages'], 5);
        add_filter('user_has_cap', [__CLASS__, 'grant_dynamic_banking_tools_access'], 10, 4);
    }

    public static function register_tabs($tabs)
    {
        $existing_tabs = is_array($tabs) ? $tabs : [];
        $pages = self::get_pages();
        $ordered_tabs = [];

        foreach ($pages as $page) {
            if (
                is_subclass_of($page, Page::class)
                && $page->id
                && $page->label
            ) {
                $ordered_tabs[$page->id] = $page->label;
            }
        }

        if (current_user_can('contract_pilot_manage_options')) {
            $ordered_tabs['banking_accounts'] = __(
                'Banking Account',
                'contract-pilot',
            );
        }
        if (current_user_can('contract_pilot_manage_options')) {
            $ordered_tabs['feature_access'] = __('Feature Access', 'contract-pilot');
        }

        $preferred_order = [
            'general',
            'sales',
            'taxes',
            'purchases',
            'categories',
            'banking_accounts',
            'feature_access',
        ];

        $sorted_tabs = [];

        foreach ($preferred_order as $tab_id) {
            if (isset($ordered_tabs[$tab_id])) {
                $sorted_tabs[$tab_id] = $ordered_tabs[$tab_id];
                unset($ordered_tabs[$tab_id]);
                unset($existing_tabs[$tab_id]);
                continue;
            }

            if (isset($existing_tabs[$tab_id])) {
                $sorted_tabs[$tab_id] = $existing_tabs[$tab_id];
                unset($existing_tabs[$tab_id]);
            }
        }

        foreach ($ordered_tabs as $tab_id => $tab_label) {
            $sorted_tabs[$tab_id] = $tab_label;
            unset($existing_tabs[$tab_id]);
        }

        foreach ($existing_tabs as $tab_id => $tab_label) {
            $sorted_tabs[$tab_id] = $tab_label;
        }

        return $sorted_tabs;
    }

    /**
     * Settings tab content: shortcut to banking accounts page.
     *
     * @return void
     */
    public static function banking_accounts_tab()
    {
        $selected_currency = contract_pilot_base_currency();
        $currency_options = contract_pilot_allowed_base_currencies();
        ?>
        <div class="contract-pilot-card">
            <div class="contract-pilot-card__header">
                <h3 class="contract-pilot-card__title"><?php esc_html_e('Banking Account', 'contract-pilot'); ?></h3>
            </div>
            <div class="contract-pilot-card__body">
                <p><?php esc_html_e('Manage your banking accounts from the Banking section.', 'contract-pilot'); ?></p>
                <form method="post" action="">
                    <?php wp_nonce_field('contract_pilot_save_settings'); ?>
                    <input type="hidden" name="save_settings" value="1"/>
                    <p>
                        <label for="contract-pilot-base-currency-banking"><strong>
                            <?php esc_html_e('Global Base Currency', 'contract-pilot'); ?></strong></label><br/>
                        <select id="contract-pilot-base-currency-banking" name="contract_pilot_base_currency">
                            <?php foreach ($currency_options as $currency_code => $currency_label) { ?>
                                <option value="<?php echo esc_attr($currency_code); ?>"
                                <?php selected($selected_currency, $currency_code); ?>>
                                <?php echo esc_html($currency_label); ?></option>
                            <?php } ?>
                        </select>
                    </p>

                    <p class="description">
                        <?php esc_html_e(
                            'This selection is used as the default currency across the plugin.',
                            'contract-pilot',
                        ); ?>
                    </p>

                    <p>
                        <button type="submit" class="button"><?php esc_html_e('Save Currency', 'contract-pilot'); ?> 
                        </button>
                    </p>
                </form>

                <p>
                    <a class="button button-primary" 
                        href="<?php echo esc_url(admin_url('admin.php?page=contract-pilot-banking&tab=accounts')); ?>">
                        <?php esc_html_e('Open Banking Accounts', 'contract-pilot'); ?>
                    </a>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Save banking account settings.
     *
     * @return void
     */
    public static function save_banking_account_settings()
    {
        if (!current_user_can('contract_pilot_manage_options')) {
            return;
        }

        if (!isset($_POST['save_settings'])) {
            return;
        }

        check_admin_referer('contract_pilot_save_settings');

        $currency = strtoupper(
            Request::get_post_string('contract_pilot_base_currency'),
        );
        $allowed_currencies = array_keys(contract_pilot_allowed_base_currencies());

        if (!in_array($currency, $allowed_currencies, true)) {
            contract_pilot()->flash->error(__('Invalid base currency selected.', 'contract-pilot'));

            return;
        }

        update_option('contract_pilot_base_currency', $currency);
        contract_pilot()->flash->success(__('Base currency updated.', 'contract-pilot'));
    }

    /**
     * Settings tab content: feature access controls.
     *
     * @return void
     */
    public static function feature_access_tab()
    {
        $is_unlocked = 'yes' === get_user_meta(
            get_current_user_id(),
            'contract_pilot_bt_secret_unlocked',
            true,
        );
        $show_dashboard_intro = 'no' !== get_option('contract_pilot_dashboard_message_enabled', 'yes');
        ?>
        <div class="contract-pilot-card">
            <?php if ($show_dashboard_intro) { ?>
                <div class="contract-pilot-card__header">
                    <h3 class="contract-pilot-card__title"><?php esc_html_e('About Contract Pilot', 'contract-pilot'); ?></h3>
                </div>
            <?php } ?>
            <div class="contract-pilot-card__body">
                <?php if ($show_dashboard_intro) { ?>
                    <p><?php esc_html_e(
                        'Contract Pilot helps you manage contracts and related business records in WordPress.',
                        'contract-pilot'
                    ); ?></p>
                    <p><?php esc_html_e(
                        'It builds on a structured admin UI and REST-oriented workflows suitable for small businesses.',
                        'contract-pilot'
                    ); ?></p>
                    <p>
                        <?php esc_html_e('Go to', 'contract-pilot'); ?>
                        <a href="https://www.softestate.net/contract-pilot/" target="_blank" rel="noopener noreferrer">https://www.softestate.net/contract-pilot/</a>
                        <?php esc_html_e('for a showcase of advanced features.', 'contract-pilot'); ?>
                    </p>
                <?php } ?>
                <form method="post" action="">
                    <?php wp_nonce_field('contract_pilot_save_settings'); ?>
                    <input type="hidden" name="save_settings" value="1"/>
                    <input type="hidden" name="contract_pilot_feature_access_action" value="toggle_dashboard_intro_message"/>
                    <p>
                        <label for="contract-pilot-dashboard-message-enabled-feature-access">
                            <input type="checkbox" id="contract-pilot-dashboard-message-enabled-feature-access" name="contract_pilot_dashboard_message_enabled" value="yes" <?php checked(
                                $show_dashboard_intro,
                                true
                            ); ?> />
                            <?php esc_html_e('Show dashboard intro message', 'contract-pilot'); ?>
                        </label>
                    </p>
                    <p>
                        <button type="submit" class="button"><?php esc_html_e('Save', 'contract-pilot'); ?></button>
                    </p>
                </form>
            </div>
        </div>
        <div class="contract-pilot-card">
            <div class="contract-pilot-card__body">
                <p>
                    <strong><?php esc_html_e('Safety locks for Banking', 'contract-pilot'); ?></strong>
                </p>
                <p>
                    <strong><?php esc_html_e('Current user unlocked:', 'contract-pilot'); ?></strong>
                    <?php echo esc_html($is_unlocked ? 'yes' : 'no'); ?>
                </p>
                <form method="post" action="">
                    <?php wp_nonce_field('contract_pilot_save_settings'); ?>
                    <input type="hidden" name="save_settings" value="1"/>
                    <input type="hidden" name="contract_pilot_feature_access_action" value="unlock_user"/>
                    <p>
                        <button type="submit" class="button"><?php esc_html_e(
                            'Enable for Current User',
                            'contract-pilot',
                        ); ?></button>
                    </p>
                </form>
                <form method="post" action="">
                    <?php wp_nonce_field('contract_pilot_save_settings'); ?>
                    <input type="hidden" name="save_settings" value="1"/>
                    <input type="hidden" name="contract_pilot_feature_access_action" value="lock_user"/>
                    <p>
                        <button type="submit" class="button"><?php esc_html_e(
                            'Lock Current User',
                            'contract-pilot',
                        ); ?>
                        </button>
                    </p>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Save feature access and user unlock status.
     *
     * @return void
     */
    public static function save_feature_access_settings()
    {
        if (!current_user_can('contract_pilot_manage_options')) {
            return;
        }

        if (!isset($_POST['save_settings'])) {
            return;
        }

        check_admin_referer('contract_pilot_save_settings');

        $action = Request::get_post_string('contract_pilot_feature_access_action');
        if ('' === $action) {
            return;
        }

        $action = sanitize_key($action);

        if ('unlock_user' === $action) {
            update_user_meta(get_current_user_id(), 'contract_pilot_bt_secret_unlocked', 'yes');
            contract_pilot()->flash->success(__('Current user enabled for Banking.', 'contract-pilot'));

            return;
        }

        if ('lock_user' === $action) {
            delete_user_meta(get_current_user_id(), 'contract_pilot_bt_secret_unlocked');
            contract_pilot()->flash->success(__('Current user has been locked.', 'contract-pilot'));

            return;
        }

        if ('toggle_dashboard_intro_message' === $action) {
            $is_enabled = isset($_POST['contract_pilot_dashboard_message_enabled'])
                ? 'yes'
                : 'no';
            update_option('contract_pilot_dashboard_message_enabled', $is_enabled);
            contract_pilot()->flash->success(__('Dashboard intro message setting saved.', 'contract-pilot'));
        }
    }

    /**
     * Show a custom lock message for Banking pages.
     *
     * @return void
     */
    public static function guard_banking_tools_pages()
    {
        if (!is_admin()) {
            return;
        }

        $page = Request::get_key('page');
        if ('contract-pilot-banking' !== $page) {
            return;
        }

        if (current_user_can('contract_pilot_banking_tools_access')) {
            return;
        }

        wp_die(
            esc_html__(
                'Safety locks. Enable access in Settings > Feature Access.',
                'contract-pilot'
            )
        );
    }

    /**
     * Remove deprecated feature access options.
     *
     * @return void
     */
    public static function cleanup_legacy_feature_access_options()
    {
        delete_option('contract_pilot_bt_secret_enabled');
        delete_option('contract_pilot_bt_secret_hash');
    }

    public static function save_settings($tab)
    {
        if (!isset($_POST['save_settings'])) {
            return;
        }

        if (
            !check_admin_referer('contract_pilot_save_settings')
            || !current_user_can('contract_pilot_manage_options')
        ) {
            return;
        }

        do_action('contract_pilot_settings_save_'.$tab);
    }

    public static function get_pages()
    {
        $pages = apply_filters('contract_pilot_settings_pages', [
            new General(),
            new Sales(),
            new Purchases(),
            new Taxes(),
        ]);

        $extensions = new Extensions();
        if ($extensions->get_sections()) {
            $pages[] = $extensions;
        }

        return $pages;
    }

    /**
     * Return role options for settings checkboxes.
     *
     * @return array<string, string>
     */
    public static function get_role_options()
    {
        $roles = function_exists('get_editable_roles')
            ? get_editable_roles()
            : [];
        $options = [];

        foreach ($roles as $role_key => $role_data) {
            if (!empty($role_data['name'])) {
                $options[$role_key] = translate_user_role($role_data['name']);
            }
        }

        return $options;
    }

    /**
     * Grant Banking capabilities to selected roles.
     *
     * @return void
     */
    public static function sync_banking_tools_role_access()
    {
        $allowed_roles = get_option('contract_pilot_banking_tools_access_roles', []);
        $allowed_roles = is_array($allowed_roles) ? $allowed_roles : [];
        $role_options = self::get_role_options();
        $allowed_roles = array_values(array_intersect($allowed_roles, array_keys($role_options)));

        if (empty($allowed_roles)) {
            return;
        }

        $caps_to_grant = [
            'contract_pilot_access',
            'contract_pilot_manage_notes',
            'contract_pilot_read_accounts',
            'contract_pilot_read_transfers',
        ];

        foreach ($allowed_roles as $role_key) {
            $role = get_role($role_key);
            if (!$role) {
                continue;
            }

            foreach ($caps_to_grant as $cap) {
                $role->add_cap($cap);
            }
        }
    }

    /**
     * Dynamically grant Banking access cap to selected roles.
     *
     * @param array    $allcaps existing user caps
     * @param array    $caps    required primitive caps
     * @param array    $args    original capability check args
     * @param \WP_User $user    current user object
     *
     * @return array
     */
    public static function grant_dynamic_banking_tools_access(
        $allcaps,
        $caps,
        $args,
        $user
    ) {
        if (
            empty($args[0])
            || 'contract_pilot_banking_tools_access' !== $args[0]
            || !($user instanceof \WP_User)
        ) {
            return $allcaps;
        }

        $is_unlocked = 'yes' === get_user_meta($user->ID, 'contract_pilot_bt_secret_unlocked', true);
        $allowed_roles = get_option('contract_pilot_banking_tools_access_roles', []);
        $allowed_roles = is_array($allowed_roles) ? $allowed_roles : [];
        $user_roles = is_array($user->roles) ? $user->roles : [];
        $is_role_allowed = !empty(array_intersect($allowed_roles, $user_roles));
        $is_settings_admin = !empty($allcaps['contract_pilot_manage_options']);

        if (($is_role_allowed || $is_settings_admin) && $is_unlocked) {
            $allcaps['contract_pilot_banking_tools_access'] = true;
        } else {
            $allcaps['contract_pilot_banking_tools_access'] = false;
        }

        return $allcaps;
    }

    /**
     * Show runtime diagnostics for Banking access checks.
     *
     * @return void
     */
    public static function render_banking_tools_diagnostics()
    {
        if (!current_user_can('contract_pilot_manage_options')) {
            return;
        }

        $user = wp_get_current_user();
        $user_roles = isset($user->roles) && is_array($user->roles) ? $user->roles : [];
        $allowed_roles = get_option('contract_pilot_banking_tools_access_roles', []);
        $allowed_roles = is_array($allowed_roles) ? $allowed_roles : [];
        $option_contains_role = !empty(array_intersect($allowed_roles, $user_roles));
        ?>
        <div class="contract-pilot-card" style="margin-top: 20px;">
            <div class="contract-pilot-card__header">
                <h3 class="contract-pilot-card__title"><?php esc_html_e(
                    'Banking Access Diagnostics',
                    'contract-pilot',
                ); ?></h3>
            </div>
            <div class="contract-pilot-card__body">
                <p>
                    <strong><?php esc_html_e('Current user roles:', 'contract-pilot'); ?></strong>
                    <?php echo esc_html(!empty($user_roles) ? implode(', ', $user_roles) : '(none)'); ?>
                </p>
                <p>
                    <strong><?php esc_html_e(
                        "current_user_can('contract_pilot_banking_tools_access'):",
                        'contract-pilot',
                    ); ?></strong>
                    <?php echo esc_html(
                        current_user_can('contract_pilot_banking_tools_access') ? 'true' : 'false',
                    ); ?>
                </p>
                <p>
                    <strong><?php esc_html_e(
                        'Role option contains current user role:',
                        'contract-pilot',
                    ); ?></strong>
                    <?php echo esc_html($option_contains_role ? 'true' : 'false'); ?>
                </p>
            </div>
        </div>
        <?php
    }

    public static function output_fields($options)
    {
        foreach ($options as $value) {
            $defaults = [
                'type' => 'text',
                'title' => '',
                'id' => '',
                'class' => '',
                'desc' => '',
                'default' => '',
                'desc_tip' => false,
                'css' => '',
                'placeholder' => '',
                'maxlength' => false,
                'required' => false,
                'autocomplete' => false,
                'options' => [],
                'attrs' => [],
                'autoload' => false,
                'suffix' => false,
            ];

            $value = wp_parse_args($value, $defaults);

            if (empty($value['type'])) {
                continue;
            }

            if (empty($value['name'])) {
                $value['name'] = $value['id'];
            }

            if (!isset($value['value'])) {
                $value['value'] = self::get_option(
                    $value['name'],
                    $value['default'],
                );
            }

            $value = apply_filters('contract_pilot_setting_field', $value);

            $value = apply_filters(
                "contract_pilot_setting_field_{$value['type']}",
                $value,
            );

            $attrs = [];

            foreach ($value as $attr_key => $attr_value) {
                if (empty($attr_key) || empty($attr_value)) {
                    continue;
                }
                if (str_starts_with($attr_key, 'attr-')) {
                    $attrs[] =
                    esc_attr(substr($attr_key, 5)).
                    '="'.
                    esc_attr($attr_value).
                        '"';
                } elseif (str_starts_with($attr_key, 'data-')) {
                    $attrs[] =
                    esc_attr($attr_key).
                    '="'.
                    esc_attr($attr_value).
                        '"';
                } elseif (
                    in_array(
                        $attr_key,
                        ['readonly', 'disabled', 'required', 'autofocus'],
                        true,
                    )
                ) {
                    $attrs[] =
                    esc_attr($attr_key).'="'.esc_attr($attr_key).'"';
                } elseif (
                    in_array(
                        $attr_key,
                        ['maxlength', 'placeholder', 'autocomplete', 'css'],
                        true,
                    )
                ) {
                    $attrs[] =
                    esc_attr($attr_key).
                    '="'.
                    esc_attr($attr_value).
                        '"';
                }
            }

            if (!empty($value['conditional'])) {
                $conditional = wp_parse_args($value['conditional'], [
                    'field' => '',
                    'compare' => '==',
                    'value' => '',
                ]);

                $value['attrs']['data-conditional'] = wp_json_encode(
                    $conditional,
                );
            }

            foreach ($value['attrs'] as $attr => $attr_value) {
                $attrs[] = esc_attr($attr).'="'.esc_attr($attr_value).'"';
            }

            $field_description = self::get_field_description($value);
            $description = $field_description['description'];
            $tooltip = $field_description['tooltip'];

            $suffix = is_callable($value['suffix'])
                ? call_user_func($value['suffix'], $value)
                : $value['suffix'];

            switch ($value['type']) {
                case 'title':
                    if (!empty($value['title'])) {
                        echo '<h2>'.esc_html($value['title']).'</h2>';
                    }
                    if (!empty($value['desc'])) {
                        echo wp_kses_post(wpautop(wptexturize($value['desc'])));
                    }
                    echo '<table class="form-table contract-pilot-settings-table">';
                    if (!empty($value['id'])) {
                        do_action(
                            'contract_pilot_settings_'.sanitize_title($value['id']),
                        );
                    }
                    break;

                case 'sectionend':
                    if (!empty($value['id'])) {
                        do_action(
                            'contract_pilot_settings_'.
                            sanitize_title($value['id']).
                            '_end',
                        );
                    }
                    echo '</table>';
                    if (!empty($value['id'])) {
                        do_action(
                            'contract_pilot_settings_'.
                            sanitize_title($value['id']).
                            '_after',
                        );
                    }

                    break;
                case 'text':
                case 'password':
                case 'datetime':
                case 'date':
                case 'month':
                case 'time':
                case 'week':
                case 'number':
                case 'email':
                case 'url':
                case 'tel':
                    ?>
                    <tr valign="top">
                        <th scope="row" class="titledesc">
                            <label
                                for="<?php echo esc_attr($value['name']); ?>"><?php
                                echo esc_html($value['title']);
                    echo wp_kses_post($tooltip);
                    ?></label>
                        </th>
                        <td class="forminp forminp-<?php echo esc_attr($value['type']); ?>">
                            <input
                                name="<?php echo esc_attr($value['name']); ?>"
                                id="<?php echo esc_attr($value['name']); ?>"
                                type="<?php echo esc_attr($value['type']); ?>"
                                style="<?php echo esc_attr($value['css']); ?>"
                                value="<?php echo esc_attr($value['value']); ?>"
                                class="<?php echo esc_attr($value['class']); ?>"
                                placeholder="<?php echo esc_attr($value['placeholder']); ?>"
                                <?php echo wp_kses_post(implode(' ', $attrs)); ?>
                            />
                            <?php echo wp_kses_post($suffix); ?>
                            <?php echo wp_kses_post($description); ?>
                        </td>
                    </tr>
                    <?php
                    break;
                case 'textarea':
                    ?> // Textarea.
                    <tr valign="top">
                        <th scope="row" class="titledesc">
                            <label
                                for="<?php echo esc_attr($value['name']); ?>"><?php
                                echo esc_html($value['title']);
                    echo wp_kses_post($tooltip);
                    ?></label>
                        </th>
                        <td class="forminp forminp-<?php echo esc_attr($value['type']); ?>">
                        <textarea
                            name="<?php echo esc_attr($value['name']); ?>"
                            id="<?php echo esc_attr($value['name']); ?>"
                            style="<?php echo esc_attr($value['css']); ?>"
                            class="<?php echo esc_attr($value['class']); ?>"
                            placeholder="<?php echo esc_attr($value['placeholder']); ?>"
                            <?php echo wp_kses_post(implode(' ', $attrs)); ?>
                        ><?php echo esc_textarea($value['value']); ?></textarea>
                            <?php echo wp_kses_post($suffix); ?>
                            <?php echo wp_kses_post($description); ?>
                        </td>
                    </tr>
                    <?php
                    break;
                case 'select':
                    $value['value'] = wp_parse_list($value['value']);
                    $value['value'] = array_map('strval', $value['value']);
                    $value['placeholder'] = !empty($value['placeholder'])
                        ? $value['placeholder']
                        : __('Select an option&hellip;', 'contract-pilot');
                    if (!empty($value['multiple'])) {
                        $value['name'] .= '[]';
                        $attrs[] = 'multiple="multiple"';
                    }
                    if (
                        !empty($value['option_label'])
                        && !empty($value['option_value'])
                    ) {
                        if (!is_array($value['options'])) {
                            $value['options'] = [];
                        }
                        $value['options'] = array_filter($value['options']);
                        $value['options'] = wp_list_pluck(
                            $value['options'],
                            $value['option_label'],
                            $value['option_value'],
                        );
                    }
                    ?>
                    <tr valign="top">
                        <th scope="row" class="titledesc">
                            <label
                                for="<?php echo esc_attr($value['name']); ?>"><?php
                                echo esc_html($value['title']);
                    echo wp_kses_post($tooltip);
                    ?></label>
                        </th>
                        <td class="forminp forminp-<?php echo esc_attr($value['type']); ?>">
                            <select
                                name="<?php echo esc_attr($value['name']); ?>"
                                id="<?php echo esc_attr($value['name']); ?>"
                                type="<?php echo esc_attr($value['type']); ?>"
                                style="<?php echo esc_attr($value['css']); ?>"
                                class="<?php echo esc_attr($value['class']); ?>"
                                <?php echo wp_kses_post(implode(' ', $attrs)); ?>
                            >
                                <option value=""><?php echo esc_html($value['placeholder']); ?></option>
                                <?php foreach ($value['options'] as $key => $val) { ?>
                                    <option
                                        value="<?php echo esc_attr($key); ?>" <?php selected(
                                            in_array((string) $key, $value['value'], true),
                                            true,
                                        ); ?>><?php echo esc_html($val); ?></option>
                                <?php } ?>
                            </select>
                            <?php echo wp_kses_post($suffix); ?>
                            <?php echo wp_kses_post($description); ?>
                        </td>

                    </tr>
                    <?php
                    break;
                case 'radio':
                    ?>
                    <tr valign="top">
                        <th scope="row" class="titledesc">
                            <label
                                for="<?php echo esc_attr($value['name']); ?>"><?php
                                echo esc_html($value['title']);
                    echo wp_kses_post($tooltip);
                    ?>
                            </label>
                        </th>
                        <td class="forminp forminp-<?php echo esc_attr($value['type']); ?>">
                            <fieldset>
                                <legend class="screen-reader-text">
                                    <span><?php echo esc_html($value['title']); ?></span></legend>
                                <?php foreach ($value['options'] as $key => $val) { ?>
                                    <label
                                        for="<?php echo esc_attr($value['name']); ?>_<?php echo esc_attr($key); ?>">
                                        <input
                                            name="<?php echo esc_attr($value['name']); ?>"
                                            id="<?php echo esc_attr($value['name']); ?>_<?php echo esc_attr($key); ?>"
                                            type="radio"
                                            value="<?php echo esc_attr($key); ?>"
                                            style="<?php echo esc_attr($value['css']); ?>"
                                            class="<?php echo esc_attr($value['class']); ?>"
                                            <?php echo wp_kses_post(implode(' ', $attrs)); ?>
                                            <?php checked($value['value'], $key); ?>
                                        />
                                        <?php echo esc_html($val); ?>
                                    </label>
                                    <br/>
                                <?php } ?>
                            </fieldset>
                            <?php echo wp_kses_post($description); ?>
                        </td>
                    </tr>
                    <?php
                    break;
                case 'checkbox':
                    ?>
                    <tr valign="top">
                        <th scope="row" class="titledesc">
                            <label
                                for="<?php echo esc_attr($value['name']); ?>"><?php
                                echo esc_html($value['title']);
                    echo wp_kses_post($tooltip);
                    ?></label>
                        </th>
                        <td class="forminp forminp-<?php echo esc_attr($value['type']); ?>">
                            <fieldset>
                                <legend class="screen-reader-text">
                                    <span><?php echo esc_html($value['title']); ?></span></legend>
                                <label for="<?php echo esc_attr($value['name']); ?>">
                                    <input
                                        name="<?php echo esc_attr($value['name']); ?>"
                                        id="<?php echo esc_attr($value['name']); ?>"
                                        type="checkbox"
                                        value="yes"
                                        style="<?php echo esc_attr($value['css']); ?>"
                                        class="<?php echo esc_attr($value['class']); ?>"
                                        <?php echo wp_kses_post(implode(' ', $attrs)); ?>
                                        <?php checked($value['value'], 'yes'); ?>
                                    />
                                    <?php echo wp_kses_post($description); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    <?php
                    break;
                case 'checkboxes':
                    ?>
                    <tr valign="top">
                        <th scope="row" class="titledesc">
                            <label
                                for="<?php echo esc_attr($value['name']); ?>"><?php
                                echo esc_html($value['title']);
                    echo wp_kses_post($tooltip);
                    ?>
                            </label>
                        </th>
                        <td class="forminp forminp-<?php echo esc_attr($value['type']); ?>">
                            <fieldset>
                                <legend class="screen-reader-text">
                                    <span><?php echo esc_html($value['title']); ?></span></legend>
                                <?php foreach ($value['options'] as $key => $val) { ?>
                                    <label
                                        for="<?php echo esc_attr($value['name']); ?>_<?php echo esc_attr($key); ?>">
                                        <input
                                            name="<?php echo esc_attr($value['name']); ?>[]"
                                            id="<?php echo esc_attr($value['name']); ?>_<?php echo esc_attr($key); ?>"
                                            type="checkbox"
                                            value="<?php echo esc_attr($key); ?>"
                                            style="<?php echo esc_attr($value['css']); ?>"
                                            class="<?php echo esc_attr($value['class']); ?>"
                                            <?php echo wp_kses_post(implode(' ', $attrs)); ?>
                                            <?php checked(in_array((string) $key, $value['value'], true), true); ?>
                                        />
                                        <?php echo esc_html($val); ?>
                                    </label>
                                    <br/>
                                <?php } ?>
                                <?php echo wp_kses_post($description); ?>
                            </fieldset>
                        </td>
                    </tr>
                    <?php
                    break;
                case 'color':
                    ?>
                    <tr valign="top">
                        <th scope="row" class="titledesc">
                            <label
                                for="<?php echo esc_attr($value['name']); ?>"><?php
                                echo esc_html($value['title']);
                    echo wp_kses_post($tooltip);
                    ?></label>
                        </th>
                        <td class="forminp forminp-<?php echo esc_attr($value['type']); ?>">
                            <fieldset>
                                <legend class="screen-reader-text">
                                    <span><?php echo esc_html($value['title']); ?></span></legend>
                                <input
                                    name="<?php echo esc_attr($value['name']); ?>"
                                    id="<?php echo esc_attr($value['name']); ?>"
                                    type="text"
                                    value="<?php echo esc_attr($value['value']); ?>"
                                    style="<?php echo esc_attr($value['css']); ?>"
                                    class="colorpick <?php echo esc_attr($value['class']); ?>"
                                    <?php echo wp_kses_post(implode(' ', $attrs)); ?>
                                />
                                <?php echo wp_kses_post($suffix); ?>
                                <?php echo wp_kses_post($description); ?>
                            </fieldset>
                        </td>
                    </tr>
                    <?php
                    break;
                case 'relative_date_selector':
                    $periods = [
                        'days' => __('Day(s)', 'contract-pilot'),
                        'weeks' => __('Week(s)', 'contract-pilot'),
                        'months' => __('Month(s)', 'contract-pilot'),
                        'years' => __('Year(s)', 'contract-pilot'),
                    ];
                    $value['number'] = !empty($value['number'])
                        ? absint($value['number'])
                        : '';
                    $value['period'] = !empty($value['period'])
                        ? $value['period']
                        : 'days';
                    ?>
                    <tr valign="top">
                        <th scope="row" class="titledesc">
                            <label
                                for="<?php echo esc_attr($value['name']); ?>"><?php echo esc_html(
                                    $value['title'],
                                ); ?></label>
                            <?php echo wp_kses_post($tooltip); ?>
                        </th>
                        <td class="forminp forminp-<?php echo esc_attr($value['type']); ?>">
                            <fieldset>
                                <legend class="screen-reader-text">
                                    <span><?php echo esc_html($value['title']); ?></span></legend>
                                <input
                                    name="<?php echo esc_attr($value['name']); ?>"
                                    id="<?php echo esc_attr($value['name']); ?>"
                                    type="number"
                                    value="<?php echo esc_attr($value['number']); ?>"
                                    style="width: 80px;<?php echo esc_attr($value['css']); ?>"
                                    class="<?php echo esc_attr($value['class']); ?>"
                                    step="1"
                                    min="1"
                                    <?php echo wp_kses_post(implode(' ', $attrs)); ?>
                                />&nbsp;
                                <select name="<?php echo esc_attr($value['name']); ?>[period]" style="width: auto;">
                                    <?php foreach ($periods as $period => $label) { ?>
                                        <option
                                            value="<?php echo esc_attr($period); ?>" <?php selected(
                                                $period,
                                                $value['period'],
                                            ); ?>><?php echo esc_html($label); ?></option>
                                    <?php } ?>
                                </select>
                                <?php echo wp_kses_post($description); ?>
                            </fieldset>
                        </td>
                    </tr>
                    <?php
                    break;

                case 'wp_editor':
                    $settings = [
                        'textarea_name' => $value['name'],
                        'textarea_rows' => 10,
                        'teeny' => true,
                    ]; ?>
                    <tr valign="top">
                        <th scope="row" class="titledesc">
                            <label for="<?php echo esc_attr($value['name']); ?>"><?php
                            echo esc_html($value['title']);
                    echo wp_kses_post($tooltip);
                    ?>
                            </label>
                        </th>
                        <td class="forminp forminp-<?php echo esc_attr($value['type']); ?>">
                            <?php wp_editor($value['value'], $value['name'], $settings); ?>
                            <?php echo wp_kses_post($description); ?>
                        </td>
                    </tr>
                    <?php
                    break;

                case 'page':
                    $option_value = $value['value'];
                    $page = get_post($option_value);

                    if (!is_null($page)) {
                        $page = get_post($option_value);
                        $option_display_name = sprintf(
                            /* translators: 1: page title 2: numeric page ID */
                            __('%1$s (ID: %2$s)', 'contract-pilot'),
                            $page->post_title,
                            $option_value,
                        );
                    }
                    ?>
                    <tr valign="top">
                        <th scope="row" class="titledesc">
                            <label for="<?php echo esc_attr($value['name']); ?>"><?php
                            echo esc_html($value['title']);
                    echo wp_kses_post($tooltip);
                    ?></label>
                        </th>
                        <td class="forminp forminp-<?php echo esc_attr($value['type']); ?>">
                            <select
                                name="<?php echo esc_attr($value['name']); ?>"
                                id="<?php echo esc_attr($value['name']); ?>"
                                style="<?php echo esc_attr($value['css']); ?>"
                                class="contract_pilot_select2 <?php echo esc_attr($value['class']); ?>"
                                <?php echo wp_kses_post(implode(' ', $attrs)); ?>
                                data-placeholder="<?php esc_attr_e(
                                    'Search for a page&hellip;',
                                    'contract-pilot',
                                ); ?>"
                                data-allow_clear="true"
                                data-action="contract_pilot_json_search"
                                data-type="page"
                            >
                                <?php if (!is_null($page)) {?>
                                    <option value="<?php echo esc_attr(
                                        $option_value,
                                    ); ?>" selected="selected"><?php echo esc_html(
                                        wp_strip_all_tags($option_display_name),
                                    ); ?></option>
                                <?php }?>
                            </select>
                            <?php echo wp_kses_post($description); ?>
                        </td>
                    </tr>
                    <?php
                    break;

                case 'callback':
                    ?>
                    <tr valign="top">
                        <th scope="row" class="titledesc">
                            <label
                                for="<?php echo esc_attr($value['name']); ?>"><?php
                                echo esc_html($value['title']);
                    echo wp_kses_post($tooltip);
                    ?></label>
                        </th>
                        <td class="forminp forminp-<?php echo esc_attr($value['type']); ?>">
                            <?php call_user_func($value['callback'], $value); ?>
                        </td>
                    </tr>
                    <?php
                    break;
                case 'html':
                    ?>
                    <tr valign="top">
                        <th scope="row" class="titledesc">
                            <label
                                for="<?php echo esc_attr($value['name']); ?>"><?php
                                echo esc_html($value['title']);
                    echo wp_kses_post($tooltip);
                    ?></label>
                        </th>
                        <td class="forminp forminp-<?php echo esc_attr($value['type']); ?>">
                            <?php echo wp_kses_post($value['content']); ?>
                        </td>
                    </tr>
                    <?php
                    break;
                default:
                    do_action('contract_pilot_settings_field_'.$value['type'], $value);
                    break;
            }
        }
    }

    public static function get_option($option_name, $fallback = '')
    {
        if (strstr($option_name, '[')) {
            parse_str($option_name, $option_array);

            $option_name = current(array_keys($option_array));

            $option_values = get_option($option_name, '');
            $key = key($option_array[$option_name]);

            $option_value = isset($option_values[$key])
                ? $option_values[$key]
                : $fallback;
        } else {
            $option_value = get_option($option_name, $fallback);
        }

        if (is_array($option_value)) {
            $option_value = wp_unslash($option_value);
        } elseif (!is_null($option_value)) {
            $option_value = stripslashes($option_value);
        }

        return null === $option_value ? $fallback : $option_value;
    }

    public static function save_fields($options, $data = null)
    {
        if (is_null($data)) {
            $data = Request::sanitize_post_data();
        }

        if (empty($data)) {
            return false;
        }

        $update_options = [];
        $autoload_options = [];

        foreach ($options as $option) {
            if (!isset($option['id'], $option['type'])) {
                continue;
            }
            $option_name = isset($option['name'])
                ? $option['name']
                : $option['id'];

            if (strstr($option_name, '[')) {
                parse_str($option_name, $option_name_array);
                $option_name = current(array_keys($option_name_array));
                $setting_name = key($option_name_array[$option_name]);
                $raw_value = isset($data[$option_name][$setting_name])
                ? contract_pilot_clean($data[$option_name][$setting_name])
                : null;
            } else {
                $setting_name = '';
                $raw_value = isset($data[$option_name])
                    ? contract_pilot_clean($data[$option_name])
                    : null;
            }

            switch ($option['type']) {
                case 'checkbox':
                    $value =
                    '1' === $raw_value || 'yes' === $raw_value
                        ? 'yes'
                        : 'no';
                    break;
                case 'textarea':
                    $value = wp_kses_post(trim($raw_value));
                    break;
                default:
                    $value = contract_pilot_clean($raw_value);
                    break;
            }

            $sanitize_cb = isset($option['sanitize_cb'])
                ? $option['sanitize_cb']
                : false;
            if ($sanitize_cb && is_callable($sanitize_cb)) {
                $value = call_user_func($sanitize_cb, $value);
            }

            $value = apply_filters(
                'contract_pilot_settings_sanitize_option',
                $value,
                $option,
                $raw_value,
            );

            $value = apply_filters(
                "contract_pilot_settings_sanitize_option_$option_name",
                $value,
                $option,
                $raw_value,
            );

            if (is_null($value)) {
                continue;
            }

            if ($option_name && $setting_name) {
                if (!isset($update_options[$option_name])) {
                    $update_options[$option_name] = get_option(
                        $option_name,
                        [],
                    );
                }
                if (!is_array($update_options[$option_name])) {
                    $update_options[$option_name] = [];
                }
                $update_options[$option_name][$setting_name] = $value;
            } else {
                $update_options[$option_name] = $value;
            }

            $autoload_options[$option_name] = isset($option['autoload'])
                ? (bool) $option['autoload']
                : true;
        }

        foreach ($update_options as $name => $value) {
            update_option(
                $name,
                $value,
                $autoload_options[$name] ? 'yes' : 'no',
            );
        }

        return true;
    }

    public static function get_field_description($value)
    {
        $description = '';
        $tooltip = '';

        if (true === $value['desc_tip']) {
            $tooltip = $value['desc'];
        } elseif (!empty($value['desc_tip'])) {
            $description = $value['desc'];
            $tooltip = $value['desc_tip'];
        } elseif (!empty($value['desc'])) {
            $description = $value['desc'];
        }

        if ($description && in_array($value['type'], ['radio'], true)) {
            $description =
            '<p style="margin-top:0">'.
            wp_kses_post($description).
                '</p>';
        } elseif (
            $description
            && in_array($value['type'], ['checkbox'], true)
        ) {
            $description = wp_kses_post($description);
        } elseif ($description) {
            $description =
            '<p class="description">'.wp_kses_post($description).'</p>';
        }

        if ($tooltip && in_array($value['type'], ['checkbox'], true)) {
            $tooltip = '<p class="description">'.$tooltip.'</p>';
        } elseif ($tooltip) {
            $tooltip = contract_pilot_tooltip($tooltip);
        }

        return [
            'description' => $description,
            'tooltip' => $tooltip,
        ];
    }
}
