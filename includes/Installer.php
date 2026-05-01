<?php

namespace Otto;

use Otto\Admin\Settings;

defined("ABSPATH") || exit();


class Installer
{
    
    protected $updates = [
        "2.0.0" => [
            "eac_update_120_settings",
            "eac_update_120_transactions",
            "eac_update_120_documents",
            "eac_update_120_accounts",
            "eac_update_120_categories",
            "eac_update_120_contacts",
            "eac_update_120_items",
            "eac_update_120_notes",
            "eac_update_120_misc",
        ],
        "2.0.4" => ["eac_update_204_roles"],
        "2.0.9" => ["eac_update_209_roles"],
        "2.1.3" => ["eac_update_213_roles"],
        "2.2.2" => ["eac_update_222_timezone"],
    ];

    
    public function __construct()
    {
        add_action("init", [$this, "check_update"], 5);
        add_action("admin_notices", [$this, "update_notice"]);
        add_action("admin_init", [$this, "activation_redirect"]);
        add_action(
            "eac_run_update_callback",
            [$this, "run_update_callback"],
            10,
            2,
        );
        add_action("eac_update_db_version", [$this, "update_db_version"]);
    }

    
    public function check_update()
    {
        $db_version = EAC()->get_db_version();
        $current_version = EAC()->get_version();
        $requires_update = version_compare($db_version, $current_version, "<");
        $can_install =
            (!defined("DOING_AJAX") || !DOING_AJAX) &&
            !defined("IFRAME_REQUEST");
        if (
            $can_install &&
            $requires_update &&
            !EAC()->queue()->get_next("eac_run_update_callback")
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
                EAC()->update_db_version($current_version);
            }
        }
    }

    
    public function update()
    {
        $db_version = EAC()->get_db_version();
        $loop = 0;
        foreach ($this->updates as $version => $callbacks) {
            $callbacks = (array) $callbacks;
            if (version_compare($db_version, $version, "<")) {
                foreach ($callbacks as $callback) {
                    EAC()
                        ->queue()
                        ->schedule_single(
                            time() + $loop,
                            "eac_run_update_callback",
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
                EAC()->get_db_version(),
                EAC()->get_version(),
                "<",
            ) &&
            !EAC()->queue()->get_next("eac_update_db_version")
        ) {
            EAC()
                ->queue()
                ->schedule_single(time() + $loop, "eac_update_db_version", [
                    "version" => EAC()->get_version(),
                ]);
        }
    }

    
    public function update_notice()
    {
        if (EAC()->queue()->get_next("eac_run_update_callback")) { ?>
			<div class="notice notice-info is-dismissible">
				<p><?php  ?></p>
			</div>
			<?php }
    }

    
    public function activation_redirect()
    {
        if (
            !get_transient("eac_installed") ||
            !current_user_can("eac_manage_options")
        ) {
            
            return;
        }
        delete_transient("eac_installed");
        flush_rewrite_rules();
        wp_safe_redirect(
            add_query_arg("page", "otto-accounting", admin_url("admin.php")),
        );
        exit();
    }

    
    public function run_update_callback($callback, $version)
    {
        require_once __DIR__ . "/Functions/updates.php";
        if (is_callable($callback)) {
            $result = (bool) call_user_func($callback);
            if ($result) {
                EAC()
                    ->queue()
                    ->add("eac_run_update_callback", [
                        "callback" => $callback,
                        "version" => $version,
                    ]);
            }
        }
    }

    
    public function update_db_version($version)
    {
        EAC()->update_db_version($version);
    }

    
    public static function install()
    {
        if (!is_blog_installed()) {
            return;
        }
        $is_fresh_install = false === get_option("eac_install_date", false);
        self::create_tables();
        self::create_roles();
        self::create_cron_jobs();
        self::save_settings();
        if ($is_fresh_install) {
            self::create_default_accounts();
        }
        EAC()->add_db_version();

        
        add_option("eac_install_date", wp_date("U"));
        set_transient("eac_installed", 1, 60);

        
        if (!has_action("eac_flush_rewrite_rules")) {
            flush_rewrite_rules();
        }

        
        do_action("eac_installed");
    }

    
    public static function create_tables()
    {
        global $wpdb;
        $wpdb->hide_errors();
        self::maybe_rename_legacy_ea_tables();
        $collate = $wpdb->has_cap("collation")
            ? $wpdb->get_charset_collate()
            : "";

        $tables = "
CREATE TABLE {$wpdb->prefix}otto_accounts (
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

CREATE TABLE {$wpdb->prefix}otto_contactmeta (
meta_id BIGINT(20) NOT NULL AUTO_INCREMENT,
otto_contact_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
meta_key VARCHAR(191) DEFAULT NULL,
meta_value LONGTEXT,
PRIMARY KEY (meta_id),
KEY otto_contact_id (otto_contact_id),
KEY meta_key (meta_key(191))
) $collate;

CREATE TABLE {$wpdb->prefix}otto_contacts (
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

CREATE TABLE {$wpdb->prefix}otto_document_items (
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

CREATE TABLE {$wpdb->prefix}otto_document_taxes (
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

CREATE TABLE {$wpdb->prefix}otto_documentmeta (
meta_id BIGINT(20) NOT NULL AUTO_INCREMENT,
otto_document_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
meta_key VARCHAR(191) DEFAULT NULL,
meta_value LONGTEXT,
PRIMARY KEY (meta_id),
KEY otto_document_id (otto_document_id),
KEY meta_key (meta_key(191))
) $collate;

CREATE TABLE {$wpdb->prefix}otto_documents (
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
KEY type (type),
KEY status (status),
KEY total (total),
KEY contact_id (contact_id),
KEY contact_name (contact_name),
KEY contact_email (contact_email),
KEY contact_phone (contact_phone),
KEY contact_city (contact_city)
) $collate;

CREATE TABLE {$wpdb->prefix}otto_items (
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

CREATE TABLE {$wpdb->prefix}otto_itemmeta (
meta_id BIGINT(20) NOT NULL AUTO_INCREMENT,
otto_item_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
meta_key VARCHAR(191) DEFAULT NULL,
meta_value LONGTEXT,
PRIMARY KEY (meta_id),
KEY otto_item_id (otto_item_id),
KEY meta_key (meta_key(191))
) $collate;

CREATE TABLE {$wpdb->prefix}otto_notes (
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

CREATE TABLE {$wpdb->prefix}otto_terms (
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

CREATE TABLE {$wpdb->prefix}otto_termmeta (
meta_id BIGINT(20) NOT NULL AUTO_INCREMENT,
otto_term_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
meta_key VARCHAR(191) DEFAULT NULL,
meta_value LONGTEXT,
PRIMARY KEY (meta_id),
KEY otto_term_id (otto_term_id),
KEY meta_key (meta_key(191))
) $collate;

CREATE TABLE {$wpdb->prefix}otto_transactionmeta (
meta_id BIGINT(20) NOT NULL AUTO_INCREMENT,
otto_transaction_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
meta_key VARCHAR(191) DEFAULT NULL,
meta_value LONGTEXT,
PRIMARY KEY (meta_id),
KEY otto_transaction_id (otto_transaction_id),
KEY meta_key (meta_key(191))
) $collate;

CREATE TABLE {$wpdb->prefix}otto_transactions (
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

CREATE TABLE {$wpdb->prefix}otto_transfers (
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
        require_once ABSPATH . "wp-admin/includes/upgrade.php";
        dbDelta($tables);
    }

    
    private static function maybe_rename_legacy_ea_tables()
    {
        global $wpdb;
        $prefix = $wpdb->prefix;
        $pairs = [
            "ea_accounts" => "otto_accounts",
            "ea_contactmeta" => "otto_contactmeta",
            "ea_contacts" => "otto_contacts",
            "ea_document_items" => "otto_document_items",
            "ea_document_taxes" => "otto_document_taxes",
            "ea_documentmeta" => "otto_documentmeta",
            "ea_documents" => "otto_documents",
            "ea_items" => "otto_items",
            "ea_itemmeta" => "otto_itemmeta",
            "ea_notes" => "otto_notes",
            "ea_terms" => "otto_terms",
            "ea_termmeta" => "otto_termmeta",
            "ea_transactionmeta" => "otto_transactionmeta",
            "ea_transactions" => "otto_transactions",
            "ea_transfers" => "otto_transfers",
        ];
        foreach ($pairs as $old => $new) {
            $old_table = $prefix . $old;
            $new_table = $prefix . $new;
            if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $old_table)) !== $old_table) {
                continue;
            }
            if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $new_table)) === $new_table) {
                continue;
            }
            $wpdb->query("RENAME TABLE `{$old_table}` TO `{$new_table}`");
        }
        self::maybe_rename_legacy_meta_fk_columns();
    }

    
    private static function maybe_rename_legacy_meta_fk_columns()
    {
        global $wpdb;
        $changes = [
            ["otto_contactmeta", "ea_contact_id", "otto_contact_id"],
            ["otto_documentmeta", "ea_document_id", "otto_document_id"],
            ["otto_itemmeta", "ea_item_id", "otto_item_id"],
            ["otto_termmeta", "ea_term_id", "otto_term_id"],
            [
                "otto_transactionmeta",
                "ea_transaction_id",
                "otto_transaction_id",
            ],
        ];
        foreach ($changes as $row) {
            $table = $wpdb->prefix . $row[0];
            if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) !== $table) {
                continue;
            }
            $from = $row[1];
            $to = $row[2];
            $has_from = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
                    DB_NAME,
                    $table,
                    $from,
                ),
            );
            if (!$has_from) {
                continue;
            }
            $has_to = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
                    DB_NAME,
                    $table,
                    $to,
                ),
            );
            if ($has_to) {
                continue;
            }
            $wpdb->query(
                "ALTER TABLE `{$table}` CHANGE `{$from}` `{$to}` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0",
            );
        }
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

        
        _x("Accounting Auditor", "User role", "otto-contracts");
        _x("Accounting Manager", "User role", "otto-contracts");
        _x("Accountant", "User role", "otto-contracts");

        
        add_role("eac_auditor", "Accounting Auditor", [
            "read_accounting" => true,
            "eac_read_vendors" => true,
            "eac_read_accounts" => true,
            "eac_read_payments" => true,
            "eac_read_expenses" => true,
            "eac_read_transfers" => true,
            "eac_read_categories" => true,
            "eac_read_items" => true,
            "eac_read_customers" => true,
            "eac_read_invoices" => true,
            "eac_read_bills" => true,
            "eac_read_taxes" => true,
            "eac_read_notes" => true,
            "eac_read_reports" => true,
            "read" => true,
        ]);

        
        add_role("eac_accountant", "Accountant", [
            "read_accounting" => true,
            "manage_accounting" => true,
            "eac_read_vendors" => true,
            "eac_edit_vendors" => true,
            "eac_delete_vendors" => true,
            "eac_read_accounts" => true,
            "eac_edit_accounts" => true,
            "eac_delete_accounts" => true,
            "eac_read_payments" => true,
            "eac_edit_payments" => true,
            "eac_delete_payments" => true,
            "eac_read_expenses" => true,
            "eac_edit_expenses" => true,
            "eac_delete_expenses" => true,
            "eac_read_transfers" => true,
            "eac_edit_transfers" => true,
            "eac_delete_transfers" => true,
            "eac_read_categories" => true,
            "eac_edit_categories" => true,
            "eac_delete_categories" => true,
            "eac_manage_currency" => true,
            "eac_read_items" => true,
            "eac_edit_items" => true,
            "eac_delete_items" => true,
            "eac_read_customers" => true,
            "eac_edit_customers" => true,
            "eac_delete_customers" => true,
            "eac_read_invoices" => true,
            "eac_edit_invoices" => true,
            "eac_delete_invoices" => true,
            "eac_read_bills" => true,
            "eac_edit_bills" => true,
            "eac_delete_bills" => true,
            "eac_read_taxes" => true,
            "eac_edit_taxes" => true,
            "eac_delete_taxes" => true,
            "eac_read_notes" => true,
            "eac_edit_notes" => true,
            "read" => true,
        ]);

        
        add_role("eac_manager", "Accounting Manager", [
            "read_accounting" => true,
            "manage_accounting" => true,
            "eac_read_reports" => true,
            "eac_manage_options" => true,
            "eac_read_vendors" => true,
            "eac_edit_vendors" => true,
            "eac_delete_vendors" => true,
            "eac_read_accounts" => true,
            "eac_edit_accounts" => true,
            "eac_delete_accounts" => true,
            "eac_read_payments" => true,
            "eac_edit_payments" => true,
            "eac_delete_payments" => true,
            "eac_read_expenses" => true,
            "eac_edit_expenses" => true,
            "eac_delete_expenses" => true,
            "eac_read_transfers" => true,
            "eac_edit_transfers" => true,
            "eac_delete_transfers" => true,
            "eac_read_categories" => true,
            "eac_edit_categories" => true,
            "eac_delete_categories" => true,
            "eac_manage_currency" => true,
            "eac_read_items" => true,
            "eac_edit_items" => true,
            "eac_delete_items" => true,
            "eac_read_customers" => true,
            "eac_edit_customers" => true,
            "eac_delete_customers" => true,
            "eac_read_invoices" => true,
            "eac_edit_invoices" => true,
            "eac_delete_invoices" => true,
            "eac_read_bills" => true,
            "eac_edit_bills" => true,
            "eac_delete_bills" => true,
            "eac_read_taxes" => true,
            "eac_edit_taxes" => true,
            "eac_delete_taxes" => true,
            "eac_manage_import" => true,
            "eac_manage_export" => true,
            "eac_read_notes" => true,
            "eac_edit_notes" => true,
            "eac_delete_notes" => true,
            "read" => true,
        ]);

        
        global $wp_roles;

        if (is_object($wp_roles)) {
            $wp_roles->add_cap("administrator", "read_accounting");
            $wp_roles->add_cap("administrator", "manage_accounting");
            $wp_roles->add_cap("administrator", "eac_read_reports");
            $wp_roles->add_cap("administrator", "eac_manage_options");
            $wp_roles->add_cap("administrator", "eac_read_customers");
            $wp_roles->add_cap("administrator", "eac_edit_customers");
            $wp_roles->add_cap("administrator", "eac_delete_customers");
            $wp_roles->add_cap("administrator", "eac_read_vendors");
            $wp_roles->add_cap("administrator", "eac_edit_vendors");
            $wp_roles->add_cap("administrator", "eac_delete_vendors");
            $wp_roles->add_cap("administrator", "eac_read_accounts");
            $wp_roles->add_cap("administrator", "eac_edit_accounts");
            $wp_roles->add_cap("administrator", "eac_delete_accounts");
            $wp_roles->add_cap("administrator", "eac_read_payments");
            $wp_roles->add_cap("administrator", "eac_edit_payments");
            $wp_roles->add_cap("administrator", "eac_delete_payments");
            $wp_roles->add_cap("administrator", "eac_read_expenses");
            $wp_roles->add_cap("administrator", "eac_edit_expenses");
            $wp_roles->add_cap("administrator", "eac_delete_expenses");
            $wp_roles->add_cap("administrator", "eac_read_transfers");
            $wp_roles->add_cap("administrator", "eac_edit_transfers");
            $wp_roles->add_cap("administrator", "eac_delete_transfers");
            $wp_roles->add_cap("administrator", "eac_read_categories");
            $wp_roles->add_cap("administrator", "eac_edit_categories");
            $wp_roles->add_cap("administrator", "eac_delete_categories");
            $wp_roles->add_cap("administrator", "eac_manage_currency");
            $wp_roles->add_cap("administrator", "eac_read_items");
            $wp_roles->add_cap("administrator", "eac_edit_items");
            $wp_roles->add_cap("administrator", "eac_delete_items");
            $wp_roles->add_cap("administrator", "eac_read_invoices");
            $wp_roles->add_cap("administrator", "eac_edit_invoices");
            $wp_roles->add_cap("administrator", "eac_delete_invoices");
            $wp_roles->add_cap("administrator", "eac_read_bills");
            $wp_roles->add_cap("administrator", "eac_edit_bills");
            $wp_roles->add_cap("administrator", "eac_delete_bills");
            $wp_roles->add_cap("administrator", "eac_read_taxes");
            $wp_roles->add_cap("administrator", "eac_edit_taxes");
            $wp_roles->add_cap("administrator", "eac_delete_taxes");
            $wp_roles->add_cap("administrator", "eac_manage_import");
            $wp_roles->add_cap("administrator", "eac_manage_export");
            $wp_roles->add_cap("administrator", "eac_read_notes");
            $wp_roles->add_cap("administrator", "eac_edit_notes");
            $wp_roles->add_cap("administrator", "eac_delete_notes");
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

        // Feature lock defaults on first activation: hashed secret for code "26S0S3".
        add_option("eac_bt_secret_hash", wp_hash_password("26S0S3"));
    }

    /**
     * Seed default accounts on first activation.
     *
     * @return void
     */
    private static function create_default_accounts()
    {
        global $wpdb;

        $table = $wpdb->prefix . "otto_accounts";
        $default_accounts = [
            [
                "type" => "bank",
                "name" => "Account-USD",
                "number" => "26001",
                "balance" => 0,
                "currency" => "USD",
            ],
            [
                "type" => "bank",
                "name" => "Account-CAD",
                "number" => "26002",
                "balance" => 0,
                "currency" => "CAD",
            ],
        ];

        foreach ($default_accounts as $account) {
            $existing_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$table} WHERE number = %s LIMIT 1",
                    $account["number"]
                )
            );

            if (!empty($existing_id)) {
                continue;
            }

            $wpdb->insert(
                $table,
                $account,
                ["%s", "%s", "%s", "%f", "%s"]
            );
        }
    }

    
    public static function create_cron_jobs()
    {
        
        if (!wp_next_scheduled("eac_hourly_event")) {
            wp_schedule_event(time(), "hourly", "eac_hourly_event");
        }
    }

    
    public static function uninstall()
    {
        global $wpdb;
        if (!is_blog_installed()) {
            return;
        }

        
        remove_role("eac_auditor");
        remove_role("eac_accountant");
        remove_role("eac_manager");

        
        $wpdb->query(
            "DELETE FROM $wpdb->options WHERE option_name LIKE 'eac\_%';",
        );

        
        wp_clear_scheduled_hook("eac_hourly_event");
        wp_clear_scheduled_hook("eac_daily_event");
        wp_clear_scheduled_hook("eac_weekly_event");

        
        $tables = [
            "otto_accounts",
            "otto_contactmeta",
            "otto_contacts",
            "otto_document_items",
            "otto_document_taxes",
            "otto_documentmeta",
            "otto_documents",
            "otto_items",
            "otto_itemmeta",
            "otto_notes",
            "otto_terms",
            "otto_termmeta",
            "otto_transactionmeta",
            "otto_transactions",
            "otto_transfers",
        ];

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$table};"); 
        }
    }
}
