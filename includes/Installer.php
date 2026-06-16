<?php

namespace Jjpsos\ContractPilot;

use Jjpsos\ContractPilot\Admin\Settings;
use Jjpsos\ContractPilot\Utilities\DatabaseUtil;

defined("ABSPATH") || exit();


class Installer
{
    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Custom `pilot_*` install/rename/uninstall/seed via $wpdb; includes intentional DDL; `$accounts_table_sql` is esc_sql-wrapped literals for static analysis limits.
    private const DB_CACHE_GROUP = "contract_pilot_installer";
    private const DB_CACHE_TTL = 300;

    protected $updates = [
        "2.0.0" => [
            "contract_pilot_update_120_transactions",
            "contract_pilot_update_120_documents",
            "contract_pilot_update_120_accounts",
            "contract_pilot_update_120_categories",
            "contract_pilot_update_120_contacts",
            "contract_pilot_update_120_items",
            "contract_pilot_update_120_notes",
            "contract_pilot_update_120_misc",
        ],
        "2.0.9" => ["contract_pilot_update_209_roles"],
        "2.1.3" => ["contract_pilot_update_213_roles"],
        "2.2.2" => ["contract_pilot_update_222_timezone"],
        "9.40.0" => ["contract_pilot_update_940_documents_type_number_unique"],
    ];

    /**
     * @param string $scope
     * @param array<string, mixed> $parts
     * @return string
     */
    private static function build_db_cache_key($scope, $parts = [])
    {
        return $scope . ":" . md5((string) wp_json_encode($parts));
    }

    /**
     * @param string $key
     * @param bool &$found
     * @return mixed
     */
    private static function db_cache_get($key, &$found)
    {
        $payload = wp_cache_get($key, self::DB_CACHE_GROUP);
        if (is_array($payload) && array_key_exists("value", $payload)) {
            $found = true;
            return $payload["value"];
        }

        $found = false;
        return null;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return void
     */
    private static function db_cache_set($key, $value)
    {
        wp_cache_set(
            $key,
            ["value" => $value],
            self::DB_CACHE_GROUP,
            self::DB_CACHE_TTL,
        );
    }

    /**
     * @return void
     */
    private static function invalidate_db_cache()
    {
        wp_cache_delete("schema_checks", self::DB_CACHE_GROUP);
        \Jjpsos\ContractPilot\Utilities\DatabaseUtil::invalidate_query_cache();
    }


    public function __construct()
    {
        add_action("init", [$this, "dispatch_queue"], 5);
        add_action("admin_init", [$this, "check_update"], 5);
        add_action("admin_notices", [$this, "update_notice"]);
        add_action("admin_init", [$this, "activation_redirect"]);
        add_action(
            "contract_pilot_run_update_callback",
            [$this, "run_update_callback"],
            10,
            2,
        );
        add_action("contract_pilot_update_db_version", [$this, "update_db_version"]);
    }


    public function dispatch_queue()
    {
        contract_pilot()->queue()->dispatch_due_events();
    }


    public function check_update()
    {
        $db_version = contract_pilot()->get_db_version();
        $current_version = contract_pilot()->get_version();
        $requires_update = version_compare($db_version, $current_version, "<");
        $can_install =
            (!defined("DOING_AJAX") || !DOING_AJAX) &&
            !defined("IFRAME_REQUEST");

        if (
            $can_install &&
            $requires_update &&
            !contract_pilot()->queue()->get_next("contract_pilot_run_update_callback")
        ) {
            static::install();
            $update_versions = array_keys($this->updates);
            usort($update_versions, "version_compare");
            if (
                !is_null($db_version) &&
                version_compare($db_version, end($update_versions), "<")
            ) {
                $this->update();
            } else {
                contract_pilot()->update_db_version($current_version);
            }
        }
    }


    public function update()
    {
        $db_version = contract_pilot()->get_db_version();
        $loop = 0;
        foreach ($this->updates as $version => $callbacks) {
            $callbacks = (array) $callbacks;
            if (version_compare($db_version, $version, "<")) {
                foreach ($callbacks as $callback) {
                    contract_pilot()
                        ->queue()
                        ->schedule_single(
                            time() + $loop,
                            "contract_pilot_run_update_callback",
                            [
                                "callback" => $callback,
                                "version" => $version,
                            ],
                        );
                    ++$loop;
                }
            }
            ++$loop;
        }

        if (
            version_compare(
                contract_pilot()->get_db_version(),
                contract_pilot()->get_version(),
                "<",
            ) &&
            !contract_pilot()->queue()->get_next("contract_pilot_update_db_version")
        ) {
            contract_pilot()
                ->queue()
                ->schedule_single(time() + $loop, "contract_pilot_update_db_version", [
                    "version" => contract_pilot()->get_version(),
                ]);
        }
    }


    public function update_notice()
    {
        if (contract_pilot()->queue()->get_next("contract_pilot_run_update_callback")) { ?>
            <div class="notice notice-info is-dismissible">
                <p><?php  ?></p>
            </div>
        <?php }

        $migration_error = get_option(
            "contract_pilot_documents_type_number_unique_error",
            [],
        );
        if (!is_array($migration_error) || empty($migration_error["message"])) {
            return;
        }
        ?>
        <div class="notice notice-error">
            <p><?php echo esc_html($migration_error["message"]); ?></p>
            <?php if (!empty($migration_error["details"])) : ?>
                <p><code><?php echo esc_html($migration_error["details"]); ?></code></p>
            <?php endif; ?>
        </div>
        <?php
    }


    public function activation_redirect()
    {
        if (
            !get_transient("contract_pilot_installed") ||
            !current_user_can("contract_pilot_manage_options")
        ) {
            return;
        }
        delete_transient("contract_pilot_installed");
        flush_rewrite_rules();
        wp_safe_redirect(
            add_query_arg("page", "contract-pilot", admin_url("admin.php")),
        );
        exit();
    }


    public function run_update_callback($callback, $version)
    {
        require_once __DIR__ . "/Functions/updates.php";
        if (is_callable($callback)) {
            $result = (bool) call_user_func($callback);
            if ($result) {
                contract_pilot()
                    ->queue()
                    ->add("contract_pilot_run_update_callback", [
                        "callback" => $callback,
                        "version" => $version,
                    ]);
            }
        }
    }


    public function update_db_version($version)
    {
        contract_pilot()->update_db_version($version);
    }


    public static function install()
    {
        if (!is_blog_installed()) {
            return;
        }
        $is_fresh_install = false === get_option("contract_pilot_install_date", false);
        self::create_tables();
        self::create_roles();
        self::create_cron_jobs();
        self::save_settings();
        if ($is_fresh_install) {
            self::create_default_accounts();
        }
        contract_pilot()->add_db_version();


        add_option("contract_pilot_install_date", wp_date("U"));
        set_transient("contract_pilot_installed", 1, 60);


        if (!has_action("contract_pilot_flush_rewrite_rules")) {
            flush_rewrite_rules();
        }


        do_action("contract_pilot_installed");
    }


    public static function create_tables()
    {
        global $wpdb;
        $wpdb->hide_errors();
        $collate = $wpdb->has_cap("collation")
            ? $wpdb->get_charset_collate()
            : "";

        $tables = "
CREATE TABLE {$wpdb->prefix}pilot_accounts (
id BIGINT(20) NOT NULL AUTO_INCREMENT,
type VARCHAR(50) NOT NULL DEFAULT 'account',
name VARCHAR(191) NOT NULL,
number VARCHAR(100) NOT NULL,
balance DOUBLE(15, 4) NOT NULL DEFAULT 0.00,
currency VARCHAR(3) NOT NULL DEFAULT 'USD',
date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
date_updated DATETIME DEFAULT NULL,
PRIMARY KEY (id),
UNIQUE KEY number (number),
KEY bank_name (name),
KEY bank_type (type)
) $collate;

CREATE TABLE {$wpdb->prefix}pilot_contactmeta (
meta_id BIGINT(20) NOT NULL AUTO_INCREMENT,
pilot_contact_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
meta_key VARCHAR(191) DEFAULT NULL,
meta_value LONGTEXT,
PRIMARY KEY (meta_id),
KEY pilot_contact_id (pilot_contact_id),
KEY meta_key (meta_key(191))
) $collate;

CREATE TABLE {$wpdb->prefix}pilot_contacts (
id BIGINT(20) NOT NULL AUTO_INCREMENT,
type VARCHAR(30) DEFAULT 'customer',
name VARCHAR(100) NOT NULL,
company VARCHAR(100) NOT NULL,
email VARCHAR(100) DEFAULT NULL,
phone VARCHAR(50) DEFAULT NULL,
website VARCHAR(100) DEFAULT NULL,
address VARCHAR(191) DEFAULT NULL,
city VARCHAR(50) DEFAULT NULL,
state VARCHAR(50) DEFAULT NULL,
postcode VARCHAR(20) DEFAULT NULL,
country VARCHAR(3) DEFAULT NULL,
tax_number VARCHAR(50) DEFAULT NULL,
currency VARCHAR(3) NOT NULL DEFAULT 'USD',
user_id BIGINT(20) UNSIGNED DEFAULT NULL,
created_via VARCHAR(20) DEFAULT 'manual',
date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
date_updated DATETIME DEFAULT NULL,
PRIMARY KEY (id),
KEY name (name(100)),
KEY type (type),
KEY email (email(100)),
KEY phone (phone(50)),
KEY currency (currency),
KEY user_id (user_id)
) $collate;

CREATE TABLE {$wpdb->prefix}pilot_document_items (
id BIGINT(20) NOT NULL AUTO_INCREMENT,
document_id BIGINT(20) UNSIGNED DEFAULT NULL,
item_id BIGINT(20) UNSIGNED DEFAULT NULL,
type VARCHAR(20) NOT NULL DEFAULT 'standard',
name VARCHAR(191) NOT NULL,
description VARCHAR(160) DEFAULT NULL,
unit VARCHAR(20) DEFAULT NULL,
price DOUBLE(15, 4) NOT NULL DEFAULT 0.00,
quantity DOUBLE(7, 2) NOT NULL DEFAULT 1,
subtotal DOUBLE(15, 4) NOT NULL DEFAULT 0.00,
discount DOUBLE(15, 4) NOT NULL DEFAULT 0.00,
tax DOUBLE(15, 4) NOT NULL DEFAULT 0.00,
total DOUBLE(15, 4) NOT NULL DEFAULT 0.00,
currency VARCHAR(3) NOT NULL DEFAULT 'USD',
PRIMARY KEY (id),
KEY type (type),
KEY name (name),
KEY price (price),
KEY quantity (quantity),
KEY subtotal (subtotal),
KEY total (total)
) $collate;

CREATE TABLE {$wpdb->prefix}pilot_document_taxes (
id BIGINT(20) NOT NULL AUTO_INCREMENT,
document_id BIGINT(20) UNSIGNED NOT NULL,
document_item_id BIGINT(20) UNSIGNED NOT NULL,
tax_id BIGINT(20) UNSIGNED NOT NULL,
name VARCHAR(191) NOT NULL,
rate DOUBLE(15, 4) NOT NULL,
compound TINYINT(1) NOT NULL DEFAULT 0,
amount DOUBLE(15, 4) NOT NULL DEFAULT 0.00,
currency VARCHAR(3) NOT NULL DEFAULT 'USD',
PRIMARY KEY (id),
KEY document_id (document_id),
KEY document_item_id (document_item_id),
KEY tax_id (tax_id)
) $collate;

CREATE TABLE {$wpdb->prefix}pilot_documentmeta (
meta_id BIGINT(20) NOT NULL AUTO_INCREMENT,
pilot_document_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
meta_key VARCHAR(191) DEFAULT NULL,
meta_value LONGTEXT,
PRIMARY KEY (meta_id),
KEY pilot_document_id (pilot_document_id),
KEY meta_key (meta_key(191))
) $collate;

CREATE TABLE {$wpdb->prefix}pilot_documents (
id BIGINT(20) NOT NULL AUTO_INCREMENT,
type VARCHAR(20) NOT NULL DEFAULT 'invoice',
status VARCHAR(20) NOT NULL DEFAULT 'draft',
number VARCHAR(30) NOT NULL,
reference VARCHAR(191) DEFAULT NULL,
issue_date DATETIME  NOT NULL DEFAULT CURRENT_TIMESTAMP,
due_date DATETIME DEFAULT NULL,
sent_date DATETIME DEFAULT NULL,
payment_date DATETIME DEFAULT NULL,
discount_value DOUBLE(15, 4) DEFAULT 0,
discount_type ENUM('fixed', 'percentage') DEFAULT 'fixed',
subtotal DOUBLE(15, 4) DEFAULT 0,
discount DOUBLE(15, 4) DEFAULT 0,
tax DOUBLE(15, 4) DEFAULT 0,
total DOUBLE(15, 4) DEFAULT 0,
currency VARCHAR(3) NOT NULL DEFAULT 'USD',
exchange_rate DOUBLE(15, 4) NOT NULL DEFAULT 1.0,
contact_name VARCHAR(100) NOT NULL,
contact_company VARCHAR(100) NOT NULL,
contact_email VARCHAR(100) DEFAULT NULL,
contact_phone VARCHAR(50) DEFAULT NULL,
contact_address TEXT DEFAULT NULL,
contact_city VARCHAR(50) DEFAULT NULL,
contact_state VARCHAR(50) DEFAULT NULL,
contact_postcode VARCHAR(20) DEFAULT NULL,
contact_country VARCHAR(3) DEFAULT NULL,
contact_tax_number VARCHAR(50) DEFAULT NULL,
note TEXT DEFAULT NULL,
terms TEXT DEFAULT NULL,
attachment_id BIGINT(20) UNSIGNED DEFAULT NULL,
contact_id BIGINT(20) UNSIGNED NOT NULL,
parent_id BIGINT(20) UNSIGNED DEFAULT NULL,
author_id BIGINT(20) UNSIGNED DEFAULT NULL,
editable TINYINT(1) NOT NULL DEFAULT 1,
created_via VARCHAR(20) DEFAULT 'manual',
uuid VARCHAR(36) DEFAULT NULL,
date_created DATETIME  NOT NULL DEFAULT CURRENT_TIMESTAMP,
date_updated DATETIME DEFAULT NULL,
PRIMARY KEY (id),
UNIQUE KEY uuid (uuid),
UNIQUE KEY uq_pilot_documents_type_number (type, number),
KEY type (type),
KEY status (status),
KEY total (total),
KEY contact_id (contact_id),
KEY contact_name (contact_name),
KEY contact_email (contact_email),
KEY contact_phone (contact_phone),
KEY contact_city (contact_city)
) $collate;

CREATE TABLE {$wpdb->prefix}pilot_items (
id BIGINT(20) NOT NULL AUTO_INCREMENT,
type VARCHAR(50) NOT NULL DEFAULT 'standard',
name VARCHAR(191) NOT NULL,
description TEXT DEFAULT NULL,
unit VARCHAR(50) DEFAULT NULL,
price DOUBLE(15, 4) NOT NULL,
cost DOUBLE(15, 4) NOT NULL,
tax_ids VARCHAR(191) DEFAULT NULL,
category_id INT(11) DEFAULT NULL,
created_via VARCHAR(20) DEFAULT 'manual',
date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
date_updated DATETIME DEFAULT NULL,
PRIMARY KEY (id),
KEY name (name),
KEY type (type),
KEY price (price),
KEY cost (cost)
) $collate;

CREATE TABLE {$wpdb->prefix}pilot_itemmeta (
meta_id BIGINT(20) NOT NULL AUTO_INCREMENT,
pilot_item_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
meta_key VARCHAR(191) DEFAULT NULL,
meta_value LONGTEXT,
PRIMARY KEY (meta_id),
KEY pilot_item_id (pilot_item_id),
KEY meta_key (meta_key(191))
) $collate;

CREATE TABLE {$wpdb->prefix}pilot_notes (
id BIGINT(20) NOT NULL AUTO_INCREMENT,
parent_id BIGINT(20) UNSIGNED NOT NULL,
parent_type VARCHAR(20) NOT NULL,
content TEXT DEFAULT NULL,
author_id BIGINT(20) UNSIGNED DEFAULT NULL,
date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
date_updated DATETIME DEFAULT NULL,
PRIMARY KEY (id),
KEY parent_id (parent_id),
KEY parent_type (parent_type)
) $collate;

CREATE TABLE {$wpdb->prefix}pilot_terms (
id BIGINT(20) NOT NULL AUTO_INCREMENT,
taxonomy VARCHAR(20) NOT NULL DEFAULT 'category',
name VARCHAR(191) NOT NULL,
description TEXT DEFAULT NULL,
type VARCHAR(20) DEFAULT NULL,
parent_id BIGINT(20) UNSIGNED DEFAULT NULL,
date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
date_updated DATETIME DEFAULT NULL,
PRIMARY KEY (id),
KEY name (name),
KEY type (type),
KEY taxonomy (taxonomy),
KEY parent_id (parent_id)
) $collate;

CREATE TABLE {$wpdb->prefix}pilot_termmeta (
meta_id BIGINT(20) NOT NULL AUTO_INCREMENT,
pilot_term_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
meta_key VARCHAR(191) DEFAULT NULL,
meta_value LONGTEXT,
PRIMARY KEY (meta_id),
KEY pilot_term_id (pilot_term_id),
KEY meta_key (meta_key(191))
) $collate;

CREATE TABLE {$wpdb->prefix}pilot_transactionmeta (
meta_id BIGINT(20) NOT NULL AUTO_INCREMENT,
pilot_transaction_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
meta_key VARCHAR(191) DEFAULT NULL,
meta_value LONGTEXT,
PRIMARY KEY (meta_id),
KEY pilot_transaction_id (pilot_transaction_id),
KEY meta_key (meta_key(191))
) $collate;

CREATE TABLE {$wpdb->prefix}pilot_transactions (
id BIGINT(20) NOT NULL AUTO_INCREMENT,
type VARCHAR(20) DEFAULT NULL,
number VARCHAR(30) NOT NULL,
payment_date DATETIME  NOT NULL DEFAULT CURRENT_TIMESTAMP,
amount DOUBLE(15, 4) NOT NULL,
currency VARCHAR(3) NOT NULL DEFAULT 'USD',
exchange_rate DOUBLE(15, 4) NOT NULL DEFAULT 1.0,
reference VARCHAR(191) DEFAULT NULL,
note TEXT DEFAULT NULL,
payment_method VARCHAR(100) DEFAULT NULL,
account_id BIGINT(20) UNSIGNED NOT NULL,
contact_id BIGINT(20) UNSIGNED DEFAULT NULL,
document_id BIGINT(20) UNSIGNED DEFAULT NULL,
category_id BIGINT(20) UNSIGNED NOT NULL,
attachment_id BIGINT(20) UNSIGNED DEFAULT NULL,
author_id BIGINT(20) UNSIGNED DEFAULT NULL,
parent_id BIGINT(20) UNSIGNED DEFAULT NULL,
editable TINYINT(1) NOT NULL DEFAULT 1,
created_via VARCHAR(20) DEFAULT 'manual',
uuid VARCHAR(36) DEFAULT NULL,
date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
date_updated DATETIME DEFAULT NULL,
PRIMARY KEY (id),
UNIQUE KEY uuid (uuid),
KEY type (type),
KEY number (number),
KEY amount (amount),
KEY currency (currency),
KEY exchange_rate (exchange_rate),
KEY account_id (account_id),
KEY category_id (category_id),
KEY contact_id (contact_id)
) $collate;

CREATE TABLE {$wpdb->prefix}pilot_transfers (
id BIGINT(20) NOT NULL AUTO_INCREMENT,
payment_id BIGINT(20) UNSIGNED NOT NULL,
expense_id BIGINT(20) UNSIGNED NOT NULL,
transfer_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
amount DOUBLE(15, 4) NOT NULL DEFAULT 0.00,
currency VARCHAR(3) NOT NULL DEFAULT 'USD',
payment_method VARCHAR(100) DEFAULT NULL,
reference VARCHAR(191) DEFAULT NULL,
note TEXT DEFAULT NULL,
date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
date_updated DATETIME DEFAULT NULL,
PRIMARY KEY (id),
KEY payment_id (payment_id),
KEY expense_id (expense_id)
) $collate;
";
        DatabaseUtil::create_missing_tables($tables);
    }


    public static function create_roles()
    {
        global $wp_roles;

        if (!class_exists("WP_Roles")) {
            return;
        }

        if (!isset($wp_roles)) {
            $wp_roles = new \WP_Roles();
        }


        _x("Accounting Auditor", "User role", "contract-pilot");
        _x("Accounting Manager", "User role", "contract-pilot");
        _x("Accountant", "User role", "contract-pilot");


        add_role("contract_pilot_auditor", "Accounting Auditor", [
            "contract_pilot_access" => true,
            "contract_pilot_read_accounts" => true,
            "contract_pilot_read_payments" => true,
            "contract_pilot_read_expenses" => true,
            "contract_pilot_read_transfers" => true,
            "contract_pilot_read_categories" => true,
            "contract_pilot_read_items" => true,
            "contract_pilot_read_customers" => true,
            "contract_pilot_read_invoices" => true,
            "contract_pilot_read_bills" => true,
            "contract_pilot_read_taxes" => true,
            "contract_pilot_read_notes" => true,
            "contract_pilot_read_reports" => true,
            "read" => true,
        ]);


        add_role("contract_pilot_accountant", "Accountant", [
            "contract_pilot_access" => true,
            "contract_pilot_manage_notes" => true,
            "contract_pilot_read_accounts" => true,
            "contract_pilot_edit_accounts" => true,
            "contract_pilot_delete_accounts" => true,
            "contract_pilot_read_payments" => true,
            "contract_pilot_edit_payments" => true,
            "contract_pilot_delete_payments" => true,
            "contract_pilot_read_expenses" => true,
            "contract_pilot_edit_expenses" => true,
            "contract_pilot_delete_expenses" => true,
            "contract_pilot_read_transfers" => true,
            "contract_pilot_edit_transfers" => true,
            "contract_pilot_delete_transfers" => true,
            "contract_pilot_read_categories" => true,
            "contract_pilot_edit_categories" => true,
            "contract_pilot_delete_categories" => true,
            "contract_pilot_manage_currency" => true,
            "contract_pilot_read_items" => true,
            "contract_pilot_edit_items" => true,
            "contract_pilot_delete_items" => true,
            "contract_pilot_read_customers" => true,
            "contract_pilot_edit_customers" => true,
            "contract_pilot_delete_customers" => true,
            "contract_pilot_read_invoices" => true,
            "contract_pilot_edit_invoices" => true,
            "contract_pilot_delete_invoices" => true,
            "contract_pilot_read_bills" => true,
            "contract_pilot_edit_bills" => true,
            "contract_pilot_delete_bills" => true,
            "contract_pilot_read_taxes" => true,
            "contract_pilot_edit_taxes" => true,
            "contract_pilot_delete_taxes" => true,
            "contract_pilot_read_notes" => true,
            "contract_pilot_edit_notes" => true,
            "read" => true,
        ]);


        add_role("contract_pilot_manager", "Accounting Manager", [
            "contract_pilot_access" => true,
            "contract_pilot_manage_notes" => true,
            "contract_pilot_read_reports" => true,
            "contract_pilot_manage_options" => true,
            "contract_pilot_read_accounts" => true,
            "contract_pilot_edit_accounts" => true,
            "contract_pilot_delete_accounts" => true,
            "contract_pilot_read_payments" => true,
            "contract_pilot_edit_payments" => true,
            "contract_pilot_delete_payments" => true,
            "contract_pilot_read_expenses" => true,
            "contract_pilot_edit_expenses" => true,
            "contract_pilot_delete_expenses" => true,
            "contract_pilot_read_transfers" => true,
            "contract_pilot_edit_transfers" => true,
            "contract_pilot_delete_transfers" => true,
            "contract_pilot_read_categories" => true,
            "contract_pilot_edit_categories" => true,
            "contract_pilot_delete_categories" => true,
            "contract_pilot_manage_currency" => true,
            "contract_pilot_read_items" => true,
            "contract_pilot_edit_items" => true,
            "contract_pilot_delete_items" => true,
            "contract_pilot_read_customers" => true,
            "contract_pilot_edit_customers" => true,
            "contract_pilot_delete_customers" => true,
            "contract_pilot_read_invoices" => true,
            "contract_pilot_edit_invoices" => true,
            "contract_pilot_delete_invoices" => true,
            "contract_pilot_read_bills" => true,
            "contract_pilot_edit_bills" => true,
            "contract_pilot_delete_bills" => true,
            "contract_pilot_read_taxes" => true,
            "contract_pilot_edit_taxes" => true,
            "contract_pilot_delete_taxes" => true,
            "contract_pilot_read_notes" => true,
            "contract_pilot_edit_notes" => true,
            "contract_pilot_delete_notes" => true,
            "read" => true,
        ]);


        global $wp_roles;

        if (is_object($wp_roles)) {
            $wp_roles->add_cap("administrator", "contract_pilot_access");
            $wp_roles->add_cap("administrator", "contract_pilot_manage_notes");
            $wp_roles->add_cap("administrator", "contract_pilot_read_reports");
            $wp_roles->add_cap("administrator", "contract_pilot_manage_options");
            $wp_roles->add_cap("administrator", "contract_pilot_read_customers");
            $wp_roles->add_cap("administrator", "contract_pilot_edit_customers");
            $wp_roles->add_cap("administrator", "contract_pilot_delete_customers");
            $wp_roles->add_cap("administrator", "contract_pilot_read_accounts");
            $wp_roles->add_cap("administrator", "contract_pilot_edit_accounts");
            $wp_roles->add_cap("administrator", "contract_pilot_delete_accounts");
            $wp_roles->add_cap("administrator", "contract_pilot_read_payments");
            $wp_roles->add_cap("administrator", "contract_pilot_edit_payments");
            $wp_roles->add_cap("administrator", "contract_pilot_delete_payments");
            $wp_roles->add_cap("administrator", "contract_pilot_read_expenses");
            $wp_roles->add_cap("administrator", "contract_pilot_edit_expenses");
            $wp_roles->add_cap("administrator", "contract_pilot_delete_expenses");
            $wp_roles->add_cap("administrator", "contract_pilot_read_transfers");
            $wp_roles->add_cap("administrator", "contract_pilot_edit_transfers");
            $wp_roles->add_cap("administrator", "contract_pilot_delete_transfers");
            $wp_roles->add_cap("administrator", "contract_pilot_read_categories");
            $wp_roles->add_cap("administrator", "contract_pilot_edit_categories");
            $wp_roles->add_cap("administrator", "contract_pilot_delete_categories");
            $wp_roles->add_cap("administrator", "contract_pilot_manage_currency");
            $wp_roles->add_cap("administrator", "contract_pilot_read_items");
            $wp_roles->add_cap("administrator", "contract_pilot_edit_items");
            $wp_roles->add_cap("administrator", "contract_pilot_delete_items");
            $wp_roles->add_cap("administrator", "contract_pilot_read_invoices");
            $wp_roles->add_cap("administrator", "contract_pilot_edit_invoices");
            $wp_roles->add_cap("administrator", "contract_pilot_delete_invoices");
            $wp_roles->add_cap("administrator", "contract_pilot_read_bills");
            $wp_roles->add_cap("administrator", "contract_pilot_edit_bills");
            $wp_roles->add_cap("administrator", "contract_pilot_delete_bills");
            $wp_roles->add_cap("administrator", "contract_pilot_read_taxes");
            $wp_roles->add_cap("administrator", "contract_pilot_edit_taxes");
            $wp_roles->add_cap("administrator", "contract_pilot_delete_taxes");
            $wp_roles->add_cap("administrator", "contract_pilot_read_notes");
            $wp_roles->add_cap("administrator", "contract_pilot_edit_notes");
            $wp_roles->add_cap("administrator", "contract_pilot_delete_notes");
        }
    }


    public static function save_settings()
    {
        $pages = Settings::get_pages();
        foreach ($pages as $page) {
            if (
                !is_subclass_of($page, Admin\Settings\Page::class) ||
                !method_exists($page, "get_sections")
            ) {
                continue;
            }

            $sections = array_unique(
                array_merge([""], array_keys($page->get_sections())),
            );
            foreach ($sections as $section) {
                $settings = $page->get_section_settings($section);
                foreach ($settings as $setting) {
                    if (isset($setting["default"]) && isset($setting["id"])) {
                        $autoload = isset($setting["autoload"])
                            ? (bool) $setting["autoload"]
                            : true;
                        add_option(
                            $setting["id"],
                            $setting["default"],
                            "",
                            $autoload ? "yes" : "no",
                        );
                    }
                }
            }
        }
    }

    /**
     * Seed default accounts on first activation.
     *
     * @return void
     */
    private static function create_default_accounts()
    {
        global $wpdb;

        $table = $wpdb->prefix . "pilot_accounts";
        $default_accounts = [
            [
                "type" => "bank",
                "name" => "Account-Default",
                "number" => "26001",
                "balance" => 0,
                "currency" => "USD",
            ],
        ];

        foreach ($default_accounts as $account) {
            $existing_key = self::build_db_cache_key(__FUNCTION__, [
                "table" => $table,
                "number" => (string) $account["number"],
            ]);
            $existing_id = self::db_cache_get($existing_key, $existing_found);
            if (!$existing_found) {
                $existing_id = $wpdb->get_var(
                    $wpdb->prepare(
                        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name uses trusted $wpdb->prefix + static suffix; value remains parameterized.
                        "SELECT id FROM {$wpdb->prefix}pilot_accounts WHERE number = %s LIMIT 1",
                        $account["number"],
                    ),
                );
                self::db_cache_set($existing_key, $existing_id);
            }

            if (!empty($existing_id)) {
                continue;
            }

            $wpdb->insert(
                $table,
                $account,
                ["%s", "%s", "%s", "%f", "%s"]
            );
            self::invalidate_db_cache();
        }
    }


    public static function create_cron_jobs()
    {

        if (!wp_next_scheduled("contract_pilot_hourly_event")) {
            wp_schedule_event(time(), "hourly", "contract_pilot_hourly_event");
        }
    }


    public static function uninstall()
    {
        global $wpdb;
        if (!is_blog_installed()) {
            return;
        }


        remove_role("contract_pilot_auditor");
        remove_role("contract_pilot_accountant");
        remove_role("contract_pilot_manager");



        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                $wpdb->esc_like("contract_pilot_") . "%",
            ),
        );
        wp_cache_delete("alloptions", "options");
        self::invalidate_db_cache();


        wp_clear_scheduled_hook("contract_pilot_hourly_event");
        wp_clear_scheduled_hook("contract_pilot_daily_event");
        wp_clear_scheduled_hook("contract_pilot_weekly_event");
        wp_clear_scheduled_hook("contract_pilot_run_update_callback");
        wp_clear_scheduled_hook("contract_pilot_update_db_version");


        DatabaseUtil::drop_plugin_tables();
        self::invalidate_db_cache();
    }
}
