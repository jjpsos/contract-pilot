<?php


use Jjpsos\ContractPilot\Utilities\DatabaseUtil;

defined("ABSPATH") || exit();

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Version migrations: direct $wpdb DDL/DML (`ALTER`/`DROP`/etc.) on custom `pilot_*` tables; dbDelta-style refactors would change upgrade semantics.

function contract_pilot_update_120_transactions()
{
    global $wpdb;
    $wpdb->pilot_transactions = $wpdb->prefix . "pilot_transactions";
    $wpdb->query(
        "UPDATE $wpdb->pilot_transactions SET type = 'payment' WHERE type = 'income'",
    );
    $wpdb->query(
        "UPDATE $wpdb->pilot_transactions SET currency = currency_code, note = description, exchange_rate = currency_rate, uuid = UUID(), author_id = creator_id",
    );
    $wpdb->query("UPDATE $wpdb->pilot_transactions SET author_id = creator_id");
    $wpdb->query(
        "UPDATE $wpdb->pilot_transactions JOIN (SELECT @rank := 0) r SET number=CONCAT('PAY-',LPAD(@rank:=@rank+1, 5, '0')) WHERE type='payment' AND number = ''",
    );
    $wpdb->query(
        "UPDATE $wpdb->pilot_transactions JOIN (SELECT @rank := 0) r SET number=CONCAT('EXP-',LPAD(@rank:=@rank+1, 5, '0')) WHERE type='expense' AND number = ''",
    );

    $wpdb->query(
        "ALTER TABLE $wpdb->pilot_transactions MODIFY number VARCHAR(30) NOT NULL AFTER type",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->pilot_transactions MODIFY currency VARCHAR(3) NOT NULL DEFAULT 'USD' AFTER amount",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->pilot_transactions MODIFY exchange_rate DOUBLE(15, 8) NOT NULL DEFAULT 1.0 AFTER currency",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->pilot_transactions MODIFY reference VARCHAR(191) DEFAULT NULL AFTER exchange_rate",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->pilot_transactions MODIFY note TEXT DEFAULT NULL AFTER reference",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->pilot_transactions MODIFY payment_method VARCHAR(100) DEFAULT NULL AFTER note",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->pilot_transactions MODIFY date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER uuid",
    );


    DatabaseUtil::drop_columns(
        "pilot_transactions",
        "currency_code, currency_rate, description, reconciled, creator_id",
    );

    $wpdb->pilot_transfers = $wpdb->prefix . "pilot_transfers";
    $wpdb->query("UPDATE $wpdb->pilot_transfers SET payment_id = income_id");
    $wpdb->query(
        "ALTER TABLE $wpdb->pilot_transfers MODIFY date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER note",
    );
    $wpdb->query(
        "UPDATE $wpdb->pilot_transfers
	JOIN $wpdb->pilot_transactions AS payment ON payment.id = $wpdb->pilot_transfers.payment_id
	JOIN $wpdb->pilot_transactions AS expense ON expense.id = $wpdb->pilot_transfers.expense_id
	SET $wpdb->pilot_transfers.transfer_date = expense.payment_date,
		$wpdb->pilot_transfers.amount = expense.amount,
		$wpdb->pilot_transfers.currency = expense.currency,
		$wpdb->pilot_transfers.payment_method = expense.payment_method,
		$wpdb->pilot_transfers.reference = expense.reference,
		$wpdb->pilot_transfers.note = expense.note",
    );


    DatabaseUtil::drop_columns("pilot_transfers", "creator_id,income_id");

    $wpdb->query(
        "UPDATE $wpdb->pilot_transactions JOIN {$wpdb->prefix}pilot_transfers AS transfer ON transfer.expense_id = $wpdb->pilot_transactions.id OR transfer.payment_id = $wpdb->pilot_transactions.id SET $wpdb->pilot_transactions.editable = 0",
    );
}


function contract_pilot_update_120_documents()
{
    global $wpdb;
    $wpdb->pilot_documents = $wpdb->prefix . "pilot_documents";
    $wpdb->query("UPDATE $wpdb->pilot_documents SET number = document_number");
    $wpdb->query("UPDATE $wpdb->pilot_documents SET reference = order_number");
    $wpdb->query("UPDATE $wpdb->pilot_documents SET currency = currency_code");
    $wpdb->query(
        "UPDATE $wpdb->pilot_documents SET exchange_rate = currency_rate",
    );
    $wpdb->query("UPDATE $wpdb->pilot_documents SET tax = total_tax");
    $wpdb->query("UPDATE $wpdb->pilot_documents SET discount_value = discount");
    $wpdb->query("UPDATE $wpdb->pilot_documents SET discount = total_discount");
    $wpdb->query("UPDATE $wpdb->pilot_documents SET author_id = creator_id");
    $wpdb->query("UPDATE $wpdb->pilot_documents SET uuid = UUID()");

    $wpdb->query(
        "ALTER TABLE $wpdb->pilot_documents MODIFY COLUMN `number` VARCHAR(30) NOT NULL AFTER status",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->pilot_documents MODIFY COLUMN reference VARCHAR(191) DEFAULT NULL AFTER `number`",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->pilot_documents MODIFY COLUMN sent_date DATETIME DEFAULT NULL AFTER due_date",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->pilot_documents MODIFY COLUMN discount DOUBLE(15, 4) DEFAULT 0 AFTER subtotal",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->pilot_documents MODIFY COLUMN tax DOUBLE(15, 4) DEFAULT 0 AFTER discount",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->pilot_documents MODIFY COLUMN discount_value DOUBLE(15, 4) DEFAULT 0 AFTER payment_date",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->pilot_documents MODIFY COLUMN date_created DATETIME  NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER uuid",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->pilot_documents MODIFY COLUMN note TEXT DEFAULT NULL AFTER contact_tax_number",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->pilot_documents MODIFY COLUMN terms TEXT DEFAULT NULL AFTER note",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->pilot_documents MODIFY COLUMN contact_id BIGINT(20) UNSIGNED NOT NULL AFTER attachment_id",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->pilot_documents MODIFY COLUMN  parent_id BIGINT(20) UNSIGNED DEFAULT NULL AFTER author_id",
    );

    $documents = $wpdb->get_results(
        "SELECT id, address FROM $wpdb->pilot_documents WHERE address IS NOT NULL AND address != ''",
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
        $wpdb->update($wpdb->pilot_documents, $data, ["id" => $document->id]);
    }


    DatabaseUtil::drop_columns(
        "pilot_documents",
        "document_number, order_number, currency_code, currency_rate, total_tax, total_discount, total_shipping, total_fees, tax_inclusive, category_id, key, creator_id, address",
    );


    $wpdb->pilot_document_items = $wpdb->prefix . "pilot_document_items";
    $wpdb->query("UPDATE $wpdb->pilot_document_items SET name = item_name");
    $wpdb->query(
        "UPDATE $wpdb->pilot_document_items SET currency = currency_code",
    );

    $wpdb->query("ALTER TABLE $wpdb->pilot_document_items DROP COLUMN item_name");
    $wpdb->query("ALTER TABLE $wpdb->pilot_document_items DROP COLUMN extra");
    $wpdb->query("ALTER TABLE $wpdb->pilot_document_items DROP COLUMN tax_rate");
    $wpdb->query(
        "ALTER TABLE $wpdb->pilot_document_items DROP COLUMN currency_code",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->pilot_document_items DROP COLUMN date_created",
    );

    $wpdb->query(
        "ALTER TABLE $wpdb->pilot_document_items MODIFY COLUMN unit VARCHAR(20) DEFAULT NULL AFTER item_id",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->pilot_document_items MODIFY COLUMN description VARCHAR(160) DEFAULT NULL AFTER item_id",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->pilot_document_items MODIFY COLUMN name VARCHAR(191) NOT NULL AFTER item_id",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->pilot_document_items MODIFY COLUMN type VARCHAR(20) NOT NULL DEFAULT 'standard' AFTER item_id",
    );
}


function contract_pilot_update_120_accounts()
{
    global $wpdb;
    $wpdb->pilot_accounts = $wpdb->prefix . "pilot_accounts";
    $wpdb->query("UPDATE $wpdb->pilot_accounts SET currency = currency_code");
    $wpdb->query(
        "ALTER TABLE $wpdb->pilot_accounts MODIFY COLUMN type VARCHAR(50) NOT NULL DEFAULT 'account' AFTER id",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->pilot_accounts MODIFY COLUMN date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER currency",
    );


    $accounts = $wpdb->get_results(
        "SELECT id, opening_balance, currency, date_created FROM $wpdb->pilot_accounts WHERE opening_balance != ''",
    );
    foreach ($accounts as $account) {
        $payment = new \Jjpsos\ContractPilot\Models\Payment();
        $payment->fill([
            "account_id" => $account->id,
            "amount" => $account->opening_balance,
            "currency" => $account->currency,
            "exchange_rate" => contract_pilot()->currencies->get_rate($account->currency),
            "payment_date" => !empty($account->date_created)
                ? $account->date_created
                : current_time("mysql"),
            "note" => __("Opening Balance", "contract-pilot"),
        ]);
        $payment->save();
    }


    $wpdb->query("ALTER TABLE $wpdb->pilot_accounts DROP COLUMN opening_balance");


    $accounts = contract_pilot()->accounts->query(["limit" => -1]);
    foreach ($accounts as $account) {
        $account->update_balance();
    }


    DatabaseUtil::drop_columns(
        "pilot_accounts",
        "creator_id, currency_code, enabled, thumbnail_id, bank_name, bank_phone, bank_address",
    );

    wp_cache_flush();
}


function contract_pilot_update_120_categories()
{
    global $wpdb;
    $wpdb->pilot_categories = $wpdb->prefix . "pilot_categories";
    $wpdb->pilot_terms = $wpdb->prefix . "pilot_terms";
    $wpdb->query(
        "UPDATE $wpdb->pilot_categories SET type = 'payment' WHERE type = 'income'",
    );
    $wpdb->query("DELETE FROM $wpdb->pilot_categories WHERE type = 'other'");
    $wpdb->query(
        "INSERT INTO $wpdb->pilot_terms (id, name, type, date_created) SELECT id, name, type, date_created FROM $wpdb->pilot_categories",
    );
    DatabaseUtil::drop_tables("pilot_categories");
}


function contract_pilot_update_120_contacts()
{
    global $wpdb;
    $wpdb->pilot_contacts = $wpdb->prefix . "pilot_contacts";
    $wpdb->query("UPDATE $wpdb->pilot_contacts SET tax_number = vat_number");
    $wpdb->query("UPDATE $wpdb->pilot_contacts SET currency = currency_code");
    $wpdb->query(
        "ALTER TABLE $wpdb->pilot_contacts MODIFY COLUMN type VARCHAR(30) DEFAULT 'customer' AFTER id",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->pilot_contacts MODIFY COLUMN website VARCHAR(191) DEFAULT NULL AFTER phone",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->pilot_contacts MODIFY COLUMN country VARCHAR(3) DEFAULT NULL AFTER postcode",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->pilot_contacts MODIFY COLUMN date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER created_via",
    );


    DatabaseUtil::drop_columns(
        "pilot_contacts",
        "vat_number, currency_code, attachment, enabled, creator_id, thumbnail_id, user_id, street, birth_date",
    );


    $wpdb->pilot_contactmeta = $wpdb->prefix . "pilot_contactmeta";
    $wpdb->query("UPDATE $wpdb->pilot_contactmeta SET pilot_contact_id = contact_id");
    $wpdb->query(
        "ALTER TABLE $wpdb->pilot_contactmeta MODIFY COLUMN pilot_contact_id INT(11) NOT NULL AFTER meta_id",
    );


    DatabaseUtil::drop_columns("pilot_contactmeta", "contact_id");
}


function contract_pilot_update_120_items()
{
    global $wpdb;
    $wpdb->pilot_items = $wpdb->prefix . "pilot_items";
    $wpdb->query(
        "UPDATE $wpdb->pilot_items SET price = sale_price, cost = purchase_price",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->pilot_items MODIFY type VARCHAR(50) NOT NULL AFTER id",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->pilot_items MODIFY unit VARCHAR(50) DEFAULT NULL AFTER description",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->pilot_items MODIFY price DECIMAL(10,2) DEFAULT NULL AFTER unit",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->pilot_items MODIFY cost DOUBLE(15, 4) NOT NULL AFTER price",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->pilot_items MODIFY tax_ids VARCHAR(191) DEFAULT NULL AFTER cost",
    );
    $wpdb->query(
        "ALTER TABLE $wpdb->pilot_items MODIFY created_via VARCHAR(20) DEFAULT 'manual' AFTER category_id",
    );


    DatabaseUtil::drop_columns(
        "pilot_items",
        "sale_price, purchase_price, sku, quantity, sales_tax, purchase_tax, enabled, creator_id, thumbnail_id",
    );
}


function contract_pilot_update_120_notes()
{
    global $wpdb;
    $wpdb->pilot_notes = $wpdb->prefix . "pilot_notes";
    $wpdb->query("UPDATE $wpdb->pilot_notes SET author_id = creator_id");
    $wpdb->query("UPDATE $wpdb->pilot_notes SET parent_type = type");
    $wpdb->query("UPDATE $wpdb->pilot_notes SET content = note");
    $wpdb->query(
        "ALTER TABLE $wpdb->pilot_notes MODIFY date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER author_id",
    );


    DatabaseUtil::drop_columns("pilot_notes", "type, note, extra, creator_id");
}


function contract_pilot_update_120_misc()
{
    update_option("contract_pilot_setup_wizard_completed", "yes");
}


function contract_pilot_update_209_roles()
{
    $caps = [
        "contract_pilot_manage_notes" => ["contract_pilot_access"],
        "contract_pilot_manage_item" => [
            "contract_pilot_read_items",
            "contract_pilot_edit_items",
            "contract_pilot_delete_items",
        ],
        "contract_pilot_manage_customer" => [
            "contract_pilot_read_customers",
            "contract_pilot_edit_customers",
            "contract_pilot_delete_customers",
        ],
        "contract_pilot_manage_account" => [
            "contract_pilot_read_accounts",
            "contract_pilot_edit_accounts",
            "contract_pilot_delete_accounts",
        ],
        "contract_pilot_manage_bill" => [
            "contract_pilot_read_bills",
            "contract_pilot_edit_bills",
            "contract_pilot_delete_bills",
        ],
        "contract_pilot_manage_category" => [
            "contract_pilot_read_categories",
            "contract_pilot_edit_categories",
            "contract_pilot_delete_categories",
        ],
        "contract_pilot_manage_expense" => [
            "contract_pilot_read_expenses",
            "contract_pilot_edit_expenses",
            "contract_pilot_delete_expenses",
        ],
        "contract_pilot_manage_invoice" => [
            "contract_pilot_read_invoices",
            "contract_pilot_edit_invoices",
            "contract_pilot_delete_invoices",
        ],
        "contract_pilot_manage_payment" => [
            "contract_pilot_read_payments",
            "contract_pilot_edit_payments",
            "contract_pilot_delete_payments",
        ],
        "contract_pilot_manage_tax" => [
            "contract_pilot_read_taxes",
            "contract_pilot_edit_taxes",
            "contract_pilot_delete_taxes",
        ],
        "contract_pilot_manage_transfer" => [
            "contract_pilot_read_transfers",
            "contract_pilot_edit_transfers",
            "contract_pilot_delete_transfers",
        ],
        "contract_pilot_read_reports" => [],
        "contract_pilot_manage_options" => [],
        "contract_pilot_manage_currency" => [],
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


function contract_pilot_update_213_roles()
{


    $add_caps = [
        "contract_pilot_access" => ["contract_pilot_read_reports", "contract_pilot_read_notes"],
        "contract_pilot_manage_notes" => ["contract_pilot_edit_notes", "contract_pilot_delete_notes"],
    ];


    $remove_caps = ["contract_pilot_manage_report"];

    foreach (wp_roles()->roles as $r => $details) {
        foreach ($add_caps as $cap => $caps) {
            if (isset($details["capabilities"][$cap])) {
                $role = get_role($r);


                if ("contract_pilot_accountant" === $r) {
                    $role->add_cap("contract_pilot_read_notes");
                    $role->add_cap("contract_pilot_edit_notes");
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


function contract_pilot_update_222_timezone()
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
	UPDATE {$wpdb->prefix}pilot_transactions
	SET payment_date = CONCAT(DATE(payment_date), ' ', TIME(date_created))
	WHERE payment_date IS NOT NULL AND date_created IS NOT NULL
",
    );


    $wpdb->query(
        "
	UPDATE {$wpdb->prefix}pilot_transfers
	SET transfer_date = CONCAT(DATE(transfer_date), ' ', TIME(date_created))
	WHERE transfer_date IS NOT NULL AND date_created IS NOT NULL
",
    );


    $wpdb->query(
        "
	UPDATE {$wpdb->prefix}pilot_documents
	SET
		issue_date = CASE WHEN issue_date IS NOT NULL THEN CONCAT(DATE(issue_date), ' ', TIME(date_created)) ELSE issue_date END,
		due_date = CASE WHEN due_date IS NOT NULL THEN CONCAT(DATE(due_date), ' ', TIME(date_created)) ELSE due_date END,
		sent_date = CASE WHEN sent_date IS NOT NULL THEN CONCAT(DATE(sent_date), ' ', TIME(date_created)) ELSE sent_date END,
		payment_date = CASE WHEN payment_date IS NOT NULL THEN CONCAT(DATE(payment_date), ' ', TIME(date_created)) ELSE payment_date END
	WHERE date_created IS NOT NULL
",
    );



    $conversion_map = [
        "pilot_accounts" => ["date_created", "date_updated"],
        "pilot_contacts" => ["date_created", "date_updated"],
        "pilot_documents" => [
            "issue_date",
            "due_date",
            "sent_date",
            "payment_date",
        ],
        "pilot_items" => ["date_created", "date_updated"],
        "pilot_notes" => ["date_created", "date_updated"],
        "pilot_terms" => ["date_created", "date_updated"],
        "pilot_transactions" => ["payment_date", "date_created", "date_updated"],
        "pilot_transfers" => ["transfer_date", "date_created", "date_updated"],
    ];

    foreach ($conversion_map as $table_suffix => $columns) {
        $qualified = DatabaseUtil::full_table_name($table_suffix);
        if (null === $qualified) {
            continue;
        }

        $set_clauses = [];
        $params = [];

        foreach ($columns as $column) {
            if (
                !is_string($column) ||
                "" === $column ||
                !preg_match("/^[A-Za-z0-9_]+$/", $column)
            ) {
                continue;
            }

            $col = esc_sql($column);
            $set_clauses[] =
                "`{$col}` = CASE WHEN `{$col}` IS NOT NULL THEN CONVERT_TZ(`{$col}`, %s, '+00:00') ELSE `{$col}` END";
            $params[] = $offset;
        }

        if ([] === $set_clauses) {
            continue;
        }

        $sql =
            "UPDATE `" .
            esc_sql($qualified) .
            "` SET " .
            implode(",\n\t\t", $set_clauses);
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- UPDATE SET built from allowlisted column names; values bound via prepare() and %s placeholders only.
        $wpdb->query($wpdb->prepare($sql, ...$params));
    }
}

function contract_pilot_update_940_documents_type_number_unique()
{
    global $wpdb;

    $table = $wpdb->prefix . "pilot_documents";
    $duplicate_groups = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM (SELECT 1 FROM `" .
            esc_sql($table) .
            "` GROUP BY type, number HAVING COUNT(*) > 1) duplicate_groups",
    );

    if ($duplicate_groups > 0) {
        $sample_rows = (array) $wpdb->get_results(
            "SELECT type, number, COUNT(*) AS duplicate_count FROM `" .
                esc_sql($table) .
                "` GROUP BY type, number HAVING COUNT(*) > 1 ORDER BY duplicate_count DESC, type ASC, number ASC LIMIT 10",
        );
        $sample_parts = [];
        foreach ($sample_rows as $sample_row) {
            $sample_parts[] = sprintf(
                "%s/%s x%s",
                sanitize_text_field((string) $sample_row->type),
                sanitize_text_field((string) $sample_row->number),
                absint($sample_row->duplicate_count),
            );
        }

        update_option(
            "contract_pilot_documents_type_number_unique_error",
            [
                "message" => __(
                    "Database upgrade paused: duplicate Contract/Bill numbers exist. Resolve duplicate (type, number) rows before retrying.",
                    "contract-pilot",
                ),
                "details" => sprintf(
                    "duplicate_groups=%d sample=%s sql=SELECT type, number, COUNT(*) FROM %s GROUP BY type, number HAVING COUNT(*) > 1;",
                    $duplicate_groups,
                    implode("; ", $sample_parts),
                    $table,
                ),
                "detected_at" => current_time("mysql"),
            ],
            false,
        );
        return;
    }

    $index_exists = (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(1) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND INDEX_NAME = %s",
            DB_NAME,
            $table,
            "uq_pilot_documents_type_number",
        ),
    );
    if ($index_exists > 0) {
        delete_option("contract_pilot_documents_type_number_unique_error");
        return;
    }

    $result = $wpdb->query(
        "ALTER TABLE `" .
            esc_sql($table) .
            "` ADD UNIQUE KEY uq_pilot_documents_type_number (type, number)",
    );
    if (false === $result) {
        update_option(
            "contract_pilot_documents_type_number_unique_error",
            [
                "message" => __(
                    "Database upgrade failed while adding a unique index for Contract/Bill numbers.",
                    "contract-pilot",
                ),
                "details" => sanitize_text_field((string) $wpdb->last_error),
                "detected_at" => current_time("mysql"),
            ],
            false,
        );
        return;
    }

    delete_option("contract_pilot_documents_type_number_unique_error");
    DatabaseUtil::invalidate_query_cache();
}
