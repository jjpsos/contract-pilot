<?php


use Otto\Utilities\DatabaseUtil;

defined("ABSPATH") || exit();


function eac_update_120_settings()
{
    $settings = get_option("eaccounting_settings", []);
    $settings_map = [
        "eac_business_name" => "company_name",
        "eac_business_email" => "company_email",
        "eac_business_phone" => "company_phone",
        "eac_business_logo" => "company_logo",
        "eac_business_tax_number" => "company_vat_number",
        "eac_base_currency" => "default_currency",
        "eac_year_start_date" => "financial_year_start",
        "eac_business_address" => "company_address",
        "eac_business_city" => "company_city",
        "eac_business_state" => "company_state",
        "eac_business_postcode" => "company_postcode",
        "eac_business_country" => "company_country",
        "eac_tax_enabled" => "tax_enabled",
        "eac_tax_subtotal_rounding" => "tax_subtotal_rounding",
        "eac_tax_total_display" => "tax_display_totals",
        "eac_default_sales_account_id" => "default_account",
        "eac_default_sales_payment_method" => "default_payment_method",
        "eac_invoice_prefix" => "invoice_prefix",
        "eac_invoice_digits" => "invoice_digit",
        "eac_invoice_due_date" => "invoice_due",
        "eac_invoice_note" => "invoice_notes",
        "eac_invoice_item_label" => "invoice_item_label",
        "eac_invoice_price_label" => "invoice_price_label",
        "eac_invoice_quantity_label" => "invoice_quantity_label",
        "eac_bill_prefix" => "bill_prefix",
        "eac_bill_digits" => "bill_digit",
        "eac_bill_note" => "bill_notes",
        "eac_bill_due_date" => "bill_due",
        "eac_bill_item_label" => "bill_item_label",
        "eac_bill_price_label" => "bill_price_label",
        "eac_bill_quantity_label" => "bill_quantity_label",
    ];

    foreach ($settings_map as $new_key => $old_key) {
        if (isset($settings[$old_key])) {
            update_option($new_key, $settings[$old_key]);
        }
    }

    $currencies = get_option("eac_exchange_rates", []);
    $o_currencies = get_option("eaccounting_currencies", []);
    if (is_array($o_currencies) && !empty($o_currencies)) {
        $o_currencies = wp_list_pluck($o_currencies, "rate", "code");
        foreach ($o_currencies as $code => $rate) {
            if (
                !empty($code) &&
                !empty($rate) &&
                eac_base_currency() !== $code
            ) {
                $currencies[$code] = $rate;
            }
        }
    }
    update_option("eac_exchange_rates", $currencies);
}


function eac_update_120_transactions()
{
    global $wpdb;
    $wpdb->otto_transactions = $wpdb->prefix . "otto_transactions";
    $wpdb->query(
        "UPDATE $wpdb->otto_transactions SET type = 'payment' WHERE type = 'income'",
    );
    $wpdb->query(
        "UPDATE $wpdb->otto_transactions SET currency = currency_code, note = description, exchange_rate = currency_rate, uuid = UUID(), author_id = creator_id",
    );
    $wpdb->query("UPDATE $wpdb->otto_transactions SET author_id = creator_id");
    $wpdb->query(
        "UPDATE $wpdb->otto_transactions JOIN (SELECT @rank := 0) r SET number=CONCAT('PAY-',LPAD(@rank:=@rank+1, 5, '0')) WHERE type='payment' AND number = ''",
    );
    $wpdb->query(
        "UPDATE $wpdb->otto_transactions JOIN (SELECT @rank := 0) r SET number=CONCAT('EXP-',LPAD(@rank:=@rank+1, 5, '0')) WHERE type='expense' AND number = ''",
    );

    $wpdb->query(
        "ALTER TABLE $wpdb->otto_transactions MODIFY number VARCHAR(30) NOT NULL AFTER type",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->otto_transactions MODIFY currency VARCHAR(3) NOT NULL DEFAULT 'USD' AFTER amount",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->otto_transactions MODIFY exchange_rate DOUBLE(15, 8) NOT NULL DEFAULT 1.0 AFTER currency",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->otto_transactions MODIFY reference VARCHAR(191) DEFAULT NULL AFTER exchange_rate",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->otto_transactions MODIFY note TEXT DEFAULT NULL AFTER reference",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->otto_transactions MODIFY payment_method VARCHAR(100) DEFAULT NULL AFTER note",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->otto_transactions MODIFY date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER uuid",
    );

    
    DatabaseUtil::drop_columns(
        "otto_transactions",
        "currency_code, currency_rate, description, reconciled, creator_id",
    );

    $wpdb->otto_transfers = $wpdb->prefix . "otto_transfers";
    $wpdb->query("UPDATE $wpdb->otto_transfers SET payment_id = income_id");
    $wpdb->query(
        "ALTER TABLE $wpdb->otto_transfers MODIFY date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER note",
    );
    $wpdb->query(
        "UPDATE $wpdb->otto_transfers
	JOIN $wpdb->otto_transactions AS payment ON payment.id = $wpdb->otto_transfers.payment_id
	JOIN $wpdb->otto_transactions AS expense ON expense.id = $wpdb->otto_transfers.expense_id
	SET $wpdb->otto_transfers.transfer_date = expense.payment_date,
		$wpdb->otto_transfers.amount = expense.amount,
		$wpdb->otto_transfers.currency = expense.currency,
		$wpdb->otto_transfers.payment_method = expense.payment_method,
		$wpdb->otto_transfers.reference = expense.reference,
		$wpdb->otto_transfers.note = expense.note",
    );

    
    DatabaseUtil::drop_columns("otto_transfers", "creator_id,income_id");

    
    $table = $wpdb->prefix . "otto_transactions";
    $wpdb->query(
        "UPDATE $table JOIN {$wpdb->prefix}otto_transfers AS transfer ON transfer.expense_id = $table.id OR transfer.payment_id = $table.id SET $table.editable = 0", 
    );
}


function eac_update_120_documents()
{
    global $wpdb;
    $wpdb->otto_documents = $wpdb->prefix . "otto_documents";
    $wpdb->query("UPDATE $wpdb->otto_documents SET number = document_number");
    $wpdb->query("UPDATE $wpdb->otto_documents SET reference = order_number");
    $wpdb->query("UPDATE $wpdb->otto_documents SET currency = currency_code");
    $wpdb->query(
        "UPDATE $wpdb->otto_documents SET exchange_rate = currency_rate",
    );
    $wpdb->query("UPDATE $wpdb->otto_documents SET tax = total_tax");
    $wpdb->query("UPDATE $wpdb->otto_documents SET discount_value = discount");
    $wpdb->query("UPDATE $wpdb->otto_documents SET discount = total_discount");
    $wpdb->query("UPDATE $wpdb->otto_documents SET author_id = creator_id");
    $wpdb->query("UPDATE $wpdb->otto_documents SET uuid = UUID()");

    $wpdb->query(
        "ALTER TABLE $wpdb->otto_documents MODIFY COLUMN `number` VARCHAR(30) NOT NULL AFTER status",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->otto_documents MODIFY COLUMN reference VARCHAR(191) DEFAULT NULL AFTER `number`",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->otto_documents MODIFY COLUMN sent_date DATETIME DEFAULT NULL AFTER due_date",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->otto_documents MODIFY COLUMN discount DOUBLE(15, 4) DEFAULT 0 AFTER subtotal",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->otto_documents MODIFY COLUMN tax DOUBLE(15, 4) DEFAULT 0 AFTER discount",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->otto_documents MODIFY COLUMN discount_value DOUBLE(15, 4) DEFAULT 0 AFTER payment_date",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->otto_documents MODIFY COLUMN date_created DATETIME  NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER uuid",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->otto_documents MODIFY COLUMN note TEXT DEFAULT NULL AFTER contact_tax_number",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->otto_documents MODIFY COLUMN terms TEXT DEFAULT NULL AFTER note",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->otto_documents MODIFY COLUMN contact_id BIGINT(20) UNSIGNED NOT NULL AFTER attachment_id",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->otto_documents MODIFY COLUMN  parent_id BIGINT(20) UNSIGNED DEFAULT NULL AFTER author_id",
    );

    $documents = $wpdb->get_results(
        "SELECT id, address FROM $wpdb->otto_documents WHERE address IS NOT NULL AND address != ''",
    );
    foreach ($documents as $document) {
        $address = maybe_unserialize($document->address);
        $address = wp_parse_args($address, [
            "name" => "",
            "company" => "",
            "street" => "",
            "city" => "",
            "state" => "",
            "postcode" => "",
            "country" => "",
            "email" => "",
            "phone" => "",
            "vat_number" => "",
        ]);
        $mapping = [
            "contact_name" => "name",
            "contact_company" => "company",
            "contact_address" => "street",
            "contact_city" => "city",
            "contact_state" => "state",
            "contact_postcode" => "postcode",
            "contact_country" => "country",
            "contact_email" => "email",
            "contact_phone" => "phone",
            "contact_tax_number" => "vat_number",
        ];

        $data = [];
        foreach ($mapping as $new => $old) {
            $data[$new] = $address[$old];
        }
        $data = array_filter($data);
        if (empty($data)) {
            continue;
        }
        $wpdb->update($wpdb->otto_documents, $data, ["id" => $document->id]);
    }

    
    DatabaseUtil::drop_columns(
        "otto_documents",
        "document_number, order_number, currency_code, currency_rate, total_tax, total_discount, total_shipping, total_fees, tax_inclusive, category_id, key, creator_id, address",
    );

    
    $wpdb->otto_document_items = $wpdb->prefix . "otto_document_items";
    $wpdb->query("UPDATE $wpdb->otto_document_items SET name = item_name");
    $wpdb->query(
        "UPDATE $wpdb->otto_document_items SET currency = currency_code",
    );

    $wpdb->query("ALTER TABLE $wpdb->otto_document_items DROP COLUMN item_name");
    $wpdb->query("ALTER TABLE $wpdb->otto_document_items DROP COLUMN extra");
    $wpdb->query("ALTER TABLE $wpdb->otto_document_items DROP COLUMN tax_rate");
    $wpdb->query(
        "ALTER TABLE $wpdb->otto_document_items DROP COLUMN currency_code",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->otto_document_items DROP COLUMN date_created",
    );

    $wpdb->query(
        "ALTER TABLE $wpdb->otto_document_items MODIFY COLUMN unit VARCHAR(20) DEFAULT NULL AFTER item_id",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->otto_document_items MODIFY COLUMN description VARCHAR(160) DEFAULT NULL AFTER item_id",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->otto_document_items MODIFY COLUMN name VARCHAR(191) NOT NULL AFTER item_id",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->otto_document_items MODIFY COLUMN type VARCHAR(20) NOT NULL DEFAULT 'standard' AFTER item_id",
    );
}


function eac_update_120_accounts()
{
    global $wpdb;
    $wpdb->otto_accounts = $wpdb->prefix . "otto_accounts";
    $wpdb->query("UPDATE $wpdb->otto_accounts SET currency = currency_code");
    $wpdb->query(
        "ALTER TABLE $wpdb->otto_accounts MODIFY COLUMN type VARCHAR(50) NOT NULL DEFAULT 'account' AFTER id",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->otto_accounts MODIFY COLUMN date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER currency",
    );

    
    $accounts = $wpdb->get_results(
        "SELECT id, opening_balance, currency, date_created FROM $wpdb->otto_accounts WHERE opening_balance != ''",
    );
    foreach ($accounts as $account) {
        $payment = new \Otto\Models\Payment();
        $payment->fill([
            "account_id" => $account->id,
            "amount" => $account->opening_balance,
            "currency" => $account->currency,
            "exchange_rate" => EAC()->currencies->get_rate($account->currency),
            "payment_date" => !empty($account->date_created)
                ? $account->date_created
                : current_time("mysql"),
            "note" => __("Opening Balance", "otto-contracts"),
        ]);
        $payment->save();
    }

    
    $wpdb->query("ALTER TABLE $wpdb->otto_accounts DROP COLUMN opening_balance");

    
    $accounts = EAC()->accounts->query(["limit" => -1]);
    foreach ($accounts as $account) {
        $account->update_balance();
    }

    
    DatabaseUtil::drop_columns(
        "otto_accounts",
        "creator_id, currency_code, enabled, thumbnail_id, bank_name, bank_phone, bank_address",
    );

    wp_cache_flush();
}


function eac_update_120_categories()
{
    global $wpdb;
    $wpdb->otto_categories = $wpdb->prefix . "otto_categories";
    $wpdb->otto_terms = $wpdb->prefix . "otto_terms";
    $wpdb->query(
        "UPDATE $wpdb->otto_categories SET type = 'payment' WHERE type = 'income'",
    );
    $wpdb->query("DELETE FROM $wpdb->otto_categories WHERE type = 'other'");
    $wpdb->query(
        "INSERT INTO $wpdb->otto_terms (id, name, type, date_created) SELECT id, name, type, date_created FROM $wpdb->otto_categories",
    );
    DatabaseUtil::drop_tables("otto_categories");
}


function eac_update_120_contacts()
{
    global $wpdb;
    $wpdb->otto_contacts = $wpdb->prefix . "otto_contacts";
    $wpdb->query("UPDATE $wpdb->otto_contacts SET tax_number = vat_number");
    $wpdb->query("UPDATE $wpdb->otto_contacts SET currency = currency_code");
    $wpdb->query(
        "ALTER TABLE $wpdb->otto_contacts MODIFY COLUMN type VARCHAR(30) DEFAULT 'customer' AFTER id",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->otto_contacts MODIFY COLUMN website VARCHAR(191) DEFAULT NULL AFTER phone",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->otto_contacts MODIFY COLUMN country VARCHAR(3) DEFAULT NULL AFTER postcode",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->otto_contacts MODIFY COLUMN date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER created_via",
    );

    
    DatabaseUtil::drop_columns(
        "otto_contacts",
        "vat_number, currency_code, attachment, enabled, creator_id, thumbnail_id, user_id, street, birth_date",
    );

    
    $wpdb->otto_contactmeta = $wpdb->prefix . "otto_contactmeta";
    $wpdb->query("UPDATE $wpdb->otto_contactmeta SET otto_contact_id = contact_id");
    $wpdb->query(
        "ALTER TABLE $wpdb->otto_contactmeta MODIFY COLUMN otto_contact_id INT(11) NOT NULL AFTER meta_id",
    );

    
    DatabaseUtil::drop_columns("otto_contactmeta", "contact_id");
}


function eac_update_120_items()
{
    global $wpdb;
    $wpdb->otto_items = $wpdb->prefix . "otto_items";
    $wpdb->query(
        "UPDATE $wpdb->otto_items SET price = sale_price, cost = purchase_price",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->otto_items MODIFY type VARCHAR(50) NOT NULL AFTER id",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->otto_items MODIFY unit VARCHAR(50) DEFAULT NULL AFTER description",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->otto_items MODIFY price DECIMAL(10,2) DEFAULT NULL AFTER unit",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->otto_items MODIFY cost DOUBLE(15, 4) NOT NULL AFTER price",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->otto_items MODIFY tax_ids VARCHAR(191) DEFAULT NULL AFTER cost",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->otto_items MODIFY created_via VARCHAR(20) DEFAULT 'manual' AFTER category_id",
    );

    
    DatabaseUtil::drop_columns(
        "otto_items",
        "sale_price, purchase_price, sku, quantity, sales_tax, purchase_tax, enabled, creator_id, thumbnail_id",
    );
}


function eac_update_120_notes()
{
    global $wpdb;
    $wpdb->otto_notes = $wpdb->prefix . "otto_notes";
    $wpdb->query("UPDATE $wpdb->otto_notes SET author_id = creator_id");
    $wpdb->query("UPDATE $wpdb->otto_notes SET parent_type = type");
    $wpdb->query("UPDATE $wpdb->otto_notes SET content = note");
    $wpdb->query(
        "ALTER TABLE $wpdb->otto_notes MODIFY date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER author_id",
    );

    
    DatabaseUtil::drop_columns("otto_notes", "type, note, extra, creator_id");
}


function eac_update_120_misc()
{
    global $wpdb;
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}ea_currencies");
    update_option("eac_setup_wizard_completed", "yes");
}


function eac_update_204_roles()
{
    
    require_once ABSPATH . "wp-admin/includes/user.php";
    $roles = get_editable_roles();
    foreach ($roles as $role => $details) {
        $role = get_role($role);
        foreach ($role->capabilities as $cap => $value) {
            if (strpos($cap, "ea_") === 0) {
                $role->remove_cap($cap);
            }
        }
    }
    
    $users = get_users([
        "role" => "ea_accountant",
    ]);
    foreach ($users as $user) {
        $user->remove_role("ea_accountant");
        $user->add_role("eac_accountant");
    }
    
    $users = get_users([
        "role" => "ea_manager",
    ]);
    foreach ($users as $user) {
        $user->remove_role("ea_manager");
        $user->add_role("eac_manager");
    }

    
    $roles = wp_roles()->roles;
    foreach ($roles as $role => $details) {
        if (strpos($role, "ea_") === 0) {
            remove_role($role);
        }
    }
}


function eac_update_209_roles()
{
    require_once ABSPATH . "wp-admin/includes/user.php";
    $caps = [
        "manage_accounting" => ["read_accounting"],
        "eac_manage_item" => [
            "eac_read_items",
            "eac_edit_items",
            "eac_delete_items",
        ],
        "eac_manage_customer" => [
            "eac_read_customers",
            "eac_edit_customers",
            "eac_delete_customers",
        ],
        "eac_manage_account" => [
            "eac_read_accounts",
            "eac_edit_accounts",
            "eac_delete_accounts",
        ],
        "eac_manage_bill" => [
            "eac_read_bills",
            "eac_edit_bills",
            "eac_delete_bills",
        ],
        "eac_manage_category" => [
            "eac_read_categories",
            "eac_edit_categories",
            "eac_delete_categories",
        ],
        "eac_manage_expense" => [
            "eac_read_expenses",
            "eac_edit_expenses",
            "eac_delete_expenses",
        ],
        "eac_manage_invoice" => [
            "eac_read_invoices",
            "eac_edit_invoices",
            "eac_delete_invoices",
        ],
        "eac_manage_payment" => [
            "eac_read_payments",
            "eac_edit_payments",
            "eac_delete_payments",
        ],
        "eac_manage_tax" => [
            "eac_read_taxes",
            "eac_edit_taxes",
            "eac_delete_taxes",
        ],
        "eac_manage_transfer" => [
            "eac_read_transfers",
            "eac_edit_transfers",
            "eac_delete_transfers",
        ],
        "eac_manage_vendor" => [
            "eac_read_vendors",
            "eac_edit_vendors",
            "eac_delete_vendors",
        ],
        "eac_read_reports" => [],
        "eac_manage_options" => [],
        "eac_manage_currency" => [],
        "eac_manage_import" => [],
        "eac_manage_export" => [],
    ];

    foreach (wp_roles()->roles as $r => $details) {
        foreach ($caps as $cap => $children) {
            if (isset($details["capabilities"][$cap])) {
                $role = get_role($r);
                foreach ($children as $child) {
                    $role->add_cap($child);
                }
            }
        }
    }
}


function eac_update_213_roles()
{
    require_once ABSPATH . "wp-admin/includes/user.php";

    
    $add_caps = [
        "read_accounting" => ["eac_read_reports", "eac_read_notes"],
        "manage_accounting" => ["eac_edit_notes", "eac_delete_notes"],
    ];

    
    $remove_caps = ["eac_manage_report"];

    foreach (wp_roles()->roles as $r => $details) {
        
        foreach ($add_caps as $cap => $caps) {
            if (isset($details["capabilities"][$cap])) {
                $role = get_role($r);

                
                if ("eac_accountant" === $r) {
                    $role->add_cap("eac_read_notes");
                    $role->add_cap("eac_edit_notes");
                    break;
                }

                if ($role) {
                    foreach ($caps as $c) {
                        $role->add_cap($c);
                    }
                }
            }
        }

        
        foreach ($remove_caps as $cap) {
            if (isset($details["capabilities"][$cap])) {
                $role = get_role($r);
                if ($role) {
                    $role->remove_cap($cap);
                }
            }
        }
    }
}


function eac_update_222_timezone()
{
    global $wpdb;

    
    $timezone_string = get_option("timezone_string");

    if ($timezone_string) {
        $datetime = new DateTime("now", new DateTimeZone($timezone_string));
        $offset_seconds = $datetime->getOffset();
        $hours =
            $offset_seconds >= 0
                ? floor($offset_seconds / 3600)
                : ceil($offset_seconds / 3600);
        $minutes = abs($offset_seconds % 3600) / 60;
    } else {
        $offset_raw = get_option("gmt_offset");
        $hours = $offset_raw >= 0 ? floor($offset_raw) : ceil($offset_raw);
        $minutes = abs($offset_raw - $hours) * 60;
    }

    $offset = sprintf("%+03d:%02d", $hours, $minutes);

    

    
    $wpdb->query(
        "
	UPDATE {$wpdb->prefix}otto_transactions
	SET payment_date = CONCAT(DATE(payment_date), ' ', TIME(date_created))
	WHERE payment_date IS NOT NULL AND date_created IS NOT NULL
",
    );

    
    $wpdb->query(
        "
	UPDATE {$wpdb->prefix}otto_transfers
	SET transfer_date = CONCAT(DATE(transfer_date), ' ', TIME(date_created))
	WHERE transfer_date IS NOT NULL AND date_created IS NOT NULL
",
    );

    
    $wpdb->query(
        "
	UPDATE {$wpdb->prefix}otto_documents
	SET
		issue_date = CASE WHEN issue_date IS NOT NULL THEN CONCAT(DATE(issue_date), ' ', TIME(date_created)) ELSE issue_date END,
		due_date = CASE WHEN due_date IS NOT NULL THEN CONCAT(DATE(due_date), ' ', TIME(date_created)) ELSE due_date END,
		sent_date = CASE WHEN sent_date IS NOT NULL THEN CONCAT(DATE(sent_date), ' ', TIME(date_created)) ELSE sent_date END,
		payment_date = CASE WHEN payment_date IS NOT NULL THEN CONCAT(DATE(payment_date), ' ', TIME(date_created)) ELSE payment_date END
	WHERE date_created IS NOT NULL
",
    );

    

    $conversion_map = [
        "otto_accounts" => ["date_created", "date_updated"],
        "otto_contacts" => ["date_created", "date_updated"],
        "otto_documents" => [
            "issue_date",
            "due_date",
            "sent_date",
            "payment_date",
        ],
        "otto_items" => ["date_created", "date_updated"],
        "otto_notes" => ["date_created", "date_updated"],
        "otto_terms" => ["date_created", "date_updated"],
        "otto_transactions" => ["payment_date", "date_created", "date_updated"],
        "otto_transfers" => ["transfer_date", "date_created", "date_updated"],
    ];

    foreach ($conversion_map as $table => $columns) {
        $set_clauses = [];

        foreach ($columns as $column) {
            $set_clauses[] = "$column = CASE WHEN $column IS NOT NULL THEN CONVERT_TZ($column, %s, '+00:00') ELSE $column END";
        }

        $sql =
            "UPDATE {$wpdb->prefix}$table SET " .
            implode(",\n\t\t", $set_clauses);
        $params = array_fill(0, count($columns), $offset);

        $wpdb->query($wpdb->prepare($sql, ...$params)); 
    }
}
