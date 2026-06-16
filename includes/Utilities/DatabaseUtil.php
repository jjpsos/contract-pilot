<?php

namespace Jjpsos\ContractPilot\Utilities;

class DatabaseUtil
{
    private const CACHE_GROUP = "contract_pilot_db";
    private const CACHE_KEY_VERSION = "query_cache_version";
    private const CACHE_TTL = 300;

    /**
     * Qualified table name (prefix + suffix) when suffix is alphanumeric/underscore only.
     *
     * @param string $suffix Table name without $wpdb->prefix (e.g. pilot_accounts).
     * @return string|null
     */
    public static function full_table_name($suffix)
    {
        global $wpdb;

        if (!is_string($suffix)) {
            return null;
        }

        $suffix = trim($suffix);
        if ("" === $suffix || !preg_match("/^[A-Za-z0-9_]+$/", $suffix)) {
            return null;
        }

        return $wpdb->prefix . $suffix;
    }

    /**
     * Create a table when it does not already exist.
     *
     * @param string $create_ddl CREATE TABLE statement.
     * @return bool
     */
    public static function create_table_if_missing($create_ddl)
    {
        global $wpdb;

        if (
            !preg_match(
                '/CREATE TABLE\s+(?:IF NOT EXISTS\s+)?`?([a-zA-Z0-9_]+)`?/i',
                $create_ddl,
                $matches,
            )
        ) {
            return false;
        }

        $table_name = $matches[1];
        $like_query = $wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $wpdb->esc_like($table_name),
        );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Install-time schema bootstrap for plugin-owned tables.
        if ($wpdb->get_var($like_query) === $table_name) {
            return true;
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Install-time CREATE TABLE DDL from plugin-owned schema; table name validated above.
        $wpdb->query($create_ddl);

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Install-time schema bootstrap for plugin-owned tables.
        return $wpdb->get_var($like_query) === $table_name;
    }

    /**
     * Run CREATE TABLE statements only for tables that are missing.
     *
     * @param string $tables_sql One or more CREATE TABLE statements.
     * @return void
     */
    public static function create_missing_tables($tables_sql)
    {
        $queries = array_filter(array_map("trim", explode(";", $tables_sql)));
        foreach ($queries as $query) {
            if ("" !== $query) {
                self::create_table_if_missing($query);
            }
        }
    }

    /**
     * @param string $full_table Already-qualified table name.
     */
    private static function escaped_table_identifier($full_table)
    {
        return "`" . esc_sql($full_table) . "`";
    }

    /**
     * `wpdb::get_results()` may return null; list UI and reports expect an iterable array.
     *
     * @param mixed $rows
     * @return array<int, object>
     */
    private static function normalize_result_rows($rows)
    {
        return is_array($rows) ? $rows : [];
    }

    /**
     * Query-cache version key used to invalidate all cached SQL reads.
     *
     * @return int
     */
    private static function query_cache_version()
    {
        $version = wp_cache_get(self::CACHE_KEY_VERSION, self::CACHE_GROUP);
        if (false === $version) {
            $version = (int) get_option("contract_pilot_db_cache_version", 1);
            wp_cache_set(self::CACHE_KEY_VERSION, $version, self::CACHE_GROUP);
        }

        return max(1, (int) $version);
    }

    /**
     * Build a stable cache key for SQL read results.
     *
     * @param string $scope
     * @param array<string, mixed> $parts
     * @return string
     */
    private static function build_cache_key($scope, $parts = [])
    {
        return $scope .
            ":" .
            self::query_cache_version() .
            ":" .
            md5((string) wp_json_encode($parts));
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param int $ttl
     * @return void
     */
    private static function cache_set_value($key, $value, $ttl = self::CACHE_TTL)
    {
        wp_cache_set($key, ["value" => $value], self::CACHE_GROUP, $ttl);
    }

    /**
     * @param string $key
     * @param bool &$found
     * @return mixed
     */
    private static function cache_get_value($key, &$found)
    {
        $payload = wp_cache_get($key, self::CACHE_GROUP);
        if (is_array($payload) && array_key_exists("value", $payload)) {
            $found = true;
            return $payload["value"];
        }

        $found = false;
        return null;
    }

    /**
     * Bump SQL cache version so subsequent reads bypass stale entries.
     *
     * @return void
     */
    public static function invalidate_query_cache()
    {
        $version = self::query_cache_version() + 1;
        wp_cache_set(self::CACHE_KEY_VERSION, $version, self::CACHE_GROUP);
        update_option("contract_pilot_db_cache_version", $version, false);
    }

    /**
     * Backtick-wrapped, esc_sql()-hardened table identifier for Contract Pilot core tables only.
     *
     * @param string $suffix Table name without $wpdb->prefix (e.g. pilot_documents).
     * @return string|null Null if suffix is not allowlisted.
     */
    public static function pilot_escaped_table_identifier($suffix)
    {
        static $allowed = [
            "pilot_documents" => true,
            "pilot_transactions" => true,
            "pilot_transfers" => true,
        ];

        if (!is_string($suffix) || !isset($allowed[$suffix])) {
            return null;
        }

        $full = self::full_table_name($suffix);
        if (null === $full) {
            return null;
        }

        return self::escaped_table_identifier($full);
    }

    /**
     * @return string
     */
    private static function sql_site_timezone_offset_for_convert_tz()
    {
        $timezone_string = get_option("timezone_string");

        if ($timezone_string) {
            $datetime = new \DateTime(
                "now",
                new \DateTimeZone($timezone_string),
            );
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

        if (!preg_match("/^[+-][0-9]{2}:[0-9]{2}$/", $offset)) {
            return "+00:00";
        }

        return $offset;
    }

    /**
     * CONVERT_TZ(...) SQL fragment for allowlisted column names only.
     *
     * Returns a prepare()-ready fragment; bind the site offset with %s.
     *
     * @param string $column Column key (see allowlist).
     * @return string
     */
    public static function sql_localized_datetime_expression($column)
    {
        static $cache = [];

        $allowed_columns = [
            "date_created" => "date_created",
            "date_updated" => "date_updated",
            "issue_date" => "issue_date",
            "payment_date" => "payment_date",
            "transfer_date" => "transfer_date",
            "sent_date" => "sent_date",
            "due_date" => "due_date",
            "t.payment_date" => "t.payment_date",
        ];
        if (!is_string($column) || !isset($allowed_columns[$column])) {
            $column = "date_created";
        } else {
            $column = $allowed_columns[$column];
        }

        if (isset($cache[$column])) {
            return $cache[$column];
        }

        $expr = "CONVERT_TZ({$column}, '+00:00', %s)";
        $cache[$column] = $expr;

        return $expr;
    }

    /**
     * Plugin-owned table suffixes removed on uninstall.
     *
     * @return array<int, string>
     */
    public static function plugin_table_suffixes()
    {
        return [
            "pilot_accounts",
            "pilot_contactmeta",
            "pilot_contacts",
            "pilot_document_items",
            "pilot_document_taxes",
            "pilot_documentmeta",
            "pilot_documents",
            "pilot_items",
            "pilot_itemmeta",
            "pilot_notes",
            "pilot_terms",
            "pilot_termmeta",
            "pilot_transactionmeta",
            "pilot_transactions",
            "pilot_transfers",
        ];
    }

    /**
     * Drop a single plugin table when the suffix passes validation.
     *
     * @param string $suffix Table name without $wpdb->prefix.
     * @return void
     */
    public static function drop_table_if_exists($suffix)
    {
        $full = self::full_table_name($suffix);
        if (null === $full) {
            return;
        }

        self::db_query("DROP TABLE IF EXISTS " . self::escaped_table_identifier($full));
    }

    /**
     * Drop all plugin tables on uninstall.
     *
     * @return void
     */
    public static function drop_plugin_tables()
    {
        foreach (self::plugin_table_suffixes() as $suffix) {
            self::drop_table_if_exists($suffix);
        }

        self::invalidate_query_cache();
    }

    /**
     * @param int $vendor_id
     * @param string $start_gmt
     * @param string $end_gmt
     * @return string|null
     */
    private static function prepare_vendor_expense_chart_sql(
        $vendor_id,
        $start_gmt,
        $end_gmt
    ) {
        global $wpdb;

        $txn = self::pilot_escaped_table_identifier("pilot_transactions");
        if (null === $txn) {
            return null;
        }

        $offset = self::sql_site_timezone_offset_for_convert_tz();

        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- Table identifier from pilot_escaped_table_identifier(); values bound below.
        return $wpdb->prepare(
            'SELECT SUM(t.amount/t.exchange_rate) AS amount,'
                . ' MONTH(CONVERT_TZ(t.payment_date, \'+00:00\', %s)) AS month,'
                . ' YEAR(CONVERT_TZ(t.payment_date, \'+00:00\', %s)) AS year'
                . ' FROM ' . $txn . ' AS t'
                . " WHERE t.contact_id = %d AND t.type = 'expense' AND t.payment_date BETWEEN %s AND %s"
                . ' GROUP BY YEAR(CONVERT_TZ(t.payment_date, \'+00:00\', %s)), MONTH(CONVERT_TZ(t.payment_date, \'+00:00\', %s))'
                . ' ORDER BY YEAR(CONVERT_TZ(t.payment_date, \'+00:00\', %s)), MONTH(CONVERT_TZ(t.payment_date, \'+00:00\', %s))',
            $offset,
            $offset,
            $vendor_id,
            $start_gmt,
            $end_gmt,
            $offset,
            $offset,
            $offset,
            $offset,
        );
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
    }

    /**
     * @param int $customer_id
     * @param string $start_gmt
     * @param string $end_gmt
     * @return string|null
     */
    private static function prepare_customer_payment_chart_sql(
        $customer_id,
        $start_gmt,
        $end_gmt
    ) {
        global $wpdb;

        $txn = self::pilot_escaped_table_identifier("pilot_transactions");
        if (null === $txn) {
            return null;
        }

        $offset = self::sql_site_timezone_offset_for_convert_tz();

        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- Table identifier from pilot_escaped_table_identifier(); values bound below.
        return $wpdb->prepare(
            'SELECT SUM(t.amount/t.exchange_rate) AS amount,'
                . ' MONTH(CONVERT_TZ(t.payment_date, \'+00:00\', %s)) AS month,'
                . ' YEAR(CONVERT_TZ(t.payment_date, \'+00:00\', %s)) AS year'
                . ' FROM ' . $txn . ' AS t'
                . " WHERE t.contact_id = %d AND t.type = 'payment' AND t.payment_date BETWEEN %s AND %s"
                . ' GROUP BY YEAR(CONVERT_TZ(t.payment_date, \'+00:00\', %s)), MONTH(CONVERT_TZ(t.payment_date, \'+00:00\', %s))'
                . ' ORDER BY YEAR(CONVERT_TZ(t.payment_date, \'+00:00\', %s)), MONTH(CONVERT_TZ(t.payment_date, \'+00:00\', %s))',
            $offset,
            $offset,
            $customer_id,
            $start_gmt,
            $end_gmt,
            $offset,
            $offset,
            $offset,
            $offset,
        );
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
    }

    /**
     * @param int $account_id
     * @param string $start_gmt
     * @param string $end_gmt
     * @return string|null
     */
    private static function prepare_account_overview_transactions_sql(
        $account_id,
        $start_gmt,
        $end_gmt
    ) {
        global $wpdb;

        $txn = self::pilot_escaped_table_identifier("pilot_transactions");
        $xfr = self::pilot_escaped_table_identifier("pilot_transfers");
        if (null === $txn || null === $xfr) {
            return null;
        }

        $offset = self::sql_site_timezone_offset_for_convert_tz();

        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- Table identifiers from pilot_escaped_table_identifier(); values bound below.
        return $wpdb->prepare(
            'SELECT t.amount amount, MONTH(CONVERT_TZ(t.payment_date, \'+00:00\', %s)) AS month, YEAR(CONVERT_TZ(t.payment_date, \'+00:00\', %s)) AS year, t.type'
                . ' FROM ' . $txn . ' AS t'
                . ' LEFT JOIN ' . $xfr . ' AS it ON t.id = it.payment_id OR t.id = it.expense_id'
                . ' WHERE it.payment_id IS NULL'
                . ' AND it.expense_id IS NULL'
                . ' AND t.account_id = %d'
                . ' AND t.payment_date BETWEEN %s AND %s'
                . ' ORDER BY t.payment_date ASC',
            $offset,
            $offset,
            $account_id,
            $start_gmt,
            $end_gmt,
        );
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
    }

    /**
     * @param string $txn Escaped pilot_transactions identifier.
     * @param string $xfr Escaped pilot_transfers identifier.
     * @param string $start_gmt
     * @param string $end_gmt
     * @param string|null $transaction_type payment, expense, or null for both.
     * @param bool $include_transaction_type_column
     * @return string|null
     */
    private static function prepare_report_transactions_joined_sql(
        $start_gmt,
        $end_gmt,
        $transaction_type,
        $include_transaction_type_column
    ) {
        global $wpdb;

        $txn = self::pilot_escaped_table_identifier("pilot_transactions");
        $xfr = self::pilot_escaped_table_identifier("pilot_transfers");
        if (null === $txn || null === $xfr) {
            return null;
        }

        $offset = self::sql_site_timezone_offset_for_convert_tz();
        $join =
            ' FROM ' .
            $txn .
            ' AS t LEFT JOIN ' .
            $xfr .
            ' AS it ON t.id = it.payment_id OR t.id = it.expense_id'
            . ' WHERE it.payment_id IS NULL AND it.expense_id IS NULL';

        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- Table identifiers from pilot_escaped_table_identifier(); values bound below.
        if ("payment" === $transaction_type) {
            return $wpdb->prepare(
                'SELECT (t.amount/t.exchange_rate) amount, MONTH(CONVERT_TZ(t.payment_date, \'+00:00\', %s)) AS month, YEAR(CONVERT_TZ(t.payment_date, \'+00:00\', %s)) AS year, t.category_id'
                    . $join
                    . " AND t.type = 'payment' AND t.payment_date BETWEEN %s AND %s ORDER BY t.payment_date ASC",
                $offset,
                $offset,
                $start_gmt,
                $end_gmt,
            );
        }

        if ("expense" === $transaction_type) {
            return $wpdb->prepare(
                'SELECT (t.amount/t.exchange_rate) amount, MONTH(CONVERT_TZ(t.payment_date, \'+00:00\', %s)) AS month, YEAR(CONVERT_TZ(t.payment_date, \'+00:00\', %s)) AS year, t.category_id'
                    . $join
                    . " AND t.type = 'expense' AND t.payment_date BETWEEN %s AND %s ORDER BY t.payment_date ASC",
                $offset,
                $offset,
                $start_gmt,
                $end_gmt,
            );
        }

        if ($include_transaction_type_column) {
            return $wpdb->prepare(
                'SELECT (t.amount/t.exchange_rate) amount, MONTH(CONVERT_TZ(t.payment_date, \'+00:00\', %s)) AS month, YEAR(CONVERT_TZ(t.payment_date, \'+00:00\', %s)) AS year, t.category_id, t.type'
                    . $join
                    . ' AND t.payment_date BETWEEN %s AND %s ORDER BY t.payment_date ASC',
                $offset,
                $offset,
                $start_gmt,
                $end_gmt,
            );
        }

        return $wpdb->prepare(
            'SELECT (t.amount/t.exchange_rate) amount, MONTH(CONVERT_TZ(t.payment_date, \'+00:00\', %s)) AS month, YEAR(CONVERT_TZ(t.payment_date, \'+00:00\', %s)) AS year, t.category_id'
                . $join
                . ' AND t.payment_date BETWEEN %s AND %s ORDER BY t.payment_date ASC',
            $offset,
            $offset,
            $start_gmt,
            $end_gmt,
        );
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
    }

    /**
     * @param string $docs Escaped pilot_documents identifier.
     * @param string $document_type
     * @return string|null
     */
    private static function prepare_document_year_month_filter_sql($document_type)
    {
        global $wpdb;

        $docs = self::pilot_escaped_table_identifier("pilot_documents");
        if (null === $docs) {
            return null;
        }

        $offset = self::sql_site_timezone_offset_for_convert_tz();

        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- Table identifier from pilot_escaped_table_identifier(); values bound below.
        return $wpdb->prepare(
            'SELECT DISTINCT YEAR(CONVERT_TZ(issue_date, \'+00:00\', %s)) AS year, MONTH(CONVERT_TZ(issue_date, \'+00:00\', %s)) AS month FROM '
                . $docs
                . ' WHERE type = %s AND issue_date IS NOT NULL ORDER BY issue_date DESC',
            $offset,
            $offset,
            $document_type,
        );
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
    }

    /**
     * @param string $txn Escaped pilot_transactions identifier.
     * @param string $transaction_type
     * @return string|null
     */
    private static function prepare_transaction_year_month_filter_sql($transaction_type)
    {
        global $wpdb;

        $txn = self::pilot_escaped_table_identifier("pilot_transactions");
        if (null === $txn) {
            return null;
        }

        $offset = self::sql_site_timezone_offset_for_convert_tz();

        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- Table identifier from pilot_escaped_table_identifier(); values bound below.
        return $wpdb->prepare(
            'SELECT DISTINCT YEAR(CONVERT_TZ(payment_date, \'+00:00\', %s)) AS year, MONTH(CONVERT_TZ(payment_date, \'+00:00\', %s)) AS month FROM '
                . $txn
                . ' WHERE type = %s AND payment_date IS NOT NULL ORDER BY payment_date DESC',
            $offset,
            $offset,
            $transaction_type,
        );
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
    }

    /**
     * @param string $xfer Escaped pilot_transfers identifier.
     * @return string|null
     */
    private static function prepare_transfer_year_month_filter_sql()
    {
        global $wpdb;

        $xfer = self::pilot_escaped_table_identifier("pilot_transfers");
        if (null === $xfer) {
            return null;
        }

        $offset = self::sql_site_timezone_offset_for_convert_tz();

        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- Table identifier from pilot_escaped_table_identifier(); values bound below.
        return $wpdb->prepare(
            'SELECT DISTINCT YEAR(CONVERT_TZ(transfer_date, \'+00:00\', %s)) AS year, MONTH(CONVERT_TZ(transfer_date, \'+00:00\', %s)) AS month FROM '
                . $xfer
                . ' WHERE transfer_date IS NOT NULL ORDER BY transfer_date DESC',
            $offset,
            $offset,
        );
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
    }

    /**
     * @param string $sql
     * @return array<int, object>|object|null
     */
    private static function db_get_results($sql)
    {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- SQL built only by literal prepare_* helpers; callers cache via wp_cache_get/set.
        return $wpdb->get_results($sql);
    }

    /**
     * @param string $sql
     * @return string|null
     */
    private static function db_get_var($sql)
    {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- SQL built only by literal prepare_* helpers; callers cache via wp_cache_get/set.
        return $wpdb->get_var($sql);
    }

    /**
     * @param string $sql
     * @return array<int, string>|null
     */
    private static function db_get_col($sql)
    {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table identifier validated and escaped before DESCRIBE.
        return $wpdb->get_col($sql, 0);
    }

    /**
     * @param string $sql
     * @return int|bool
     */
    private static function db_query($sql)
    {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- DDL on plugin-owned tables; identifiers validated and escaped.
        return $wpdb->query($sql);
    }

    /**
     * @param int $vendor_id
     * @param string $start_gmt
     * @param string $end_gmt
     * @return array<int, object>
     */
    public static function get_results_vendor_expense_chart_rows(
        $vendor_id,
        $start_gmt,
        $end_gmt
    ) {
        global $wpdb;

        $sql = self::prepare_vendor_expense_chart_sql(
            $vendor_id,
            $start_gmt,
            $end_gmt,
        );

        if (!$sql) {
            return [];
        }

        $cache_key = self::build_cache_key(__FUNCTION__, [
            "vendor_id" => (int) $vendor_id,
            "start_gmt" => (string) $start_gmt,
            "end_gmt" => (string) $end_gmt,
        ]);
        $cached = self::cache_get_value($cache_key, $found);
        if ($found) {
            return self::normalize_result_rows($cached);
        }

        $rows = self::normalize_result_rows(self::db_get_results($sql));
        self::cache_set_value($cache_key, $rows);
        return $rows;
    }

    /**
     * @param int $customer_id
     * @param string $start_gmt
     * @param string $end_gmt
     * @return array<int, object>
     */
    public static function get_results_customer_payment_chart_rows(
        $customer_id,
        $start_gmt,
        $end_gmt
    ) {
        global $wpdb;

        $sql = self::prepare_customer_payment_chart_sql(
            $customer_id,
            $start_gmt,
            $end_gmt,
        );

        if (!$sql) {
            return [];
        }

        $cache_key = self::build_cache_key(__FUNCTION__, [
            "customer_id" => (int) $customer_id,
            "start_gmt" => (string) $start_gmt,
            "end_gmt" => (string) $end_gmt,
        ]);
        $cached = self::cache_get_value($cache_key, $found);
        if ($found) {
            return self::normalize_result_rows($cached);
        }

        $rows = self::normalize_result_rows(self::db_get_results($sql));
        self::cache_set_value($cache_key, $rows);
        return $rows;
    }

    /**
     * @param int $account_id
     * @param string $start_gmt
     * @param string $end_gmt
     * @return array<int, object>
     */
    public static function get_results_account_overview_transactions(
        $account_id,
        $start_gmt,
        $end_gmt
    ) {
        global $wpdb;

        $sql = self::prepare_account_overview_transactions_sql(
            $account_id,
            $start_gmt,
            $end_gmt,
        );

        if (!$sql) {
            return [];
        }

        $cache_key = self::build_cache_key(__FUNCTION__, [
            "account_id" => (int) $account_id,
            "start_gmt" => (string) $start_gmt,
            "end_gmt" => (string) $end_gmt,
        ]);
        $cached = self::cache_get_value($cache_key, $found);
        if ($found) {
            return self::normalize_result_rows($cached);
        }

        $rows = self::normalize_result_rows(self::db_get_results($sql));
        self::cache_set_value($cache_key, $rows);
        return $rows;
    }

    /**
     * @param string $start_gmt
     * @param string $end_gmt
     * @return array<int, object>
     */
    public static function get_results_payments_report_transaction_rows(
        $start_gmt,
        $end_gmt
    ) {
        return self::get_results_report_transactions_joined(
            $start_gmt,
            $end_gmt,
            "payment",
            false,
        );
    }

    /**
     * @param string $start_gmt
     * @param string $end_gmt
     * @return array<int, object>
     */
    public static function get_results_expenses_report_transaction_rows(
        $start_gmt,
        $end_gmt
    ) {
        return self::get_results_report_transactions_joined(
            $start_gmt,
            $end_gmt,
            "expense",
            false,
        );
    }

    /**
     * @param string $start_gmt
     * @param string $end_gmt
     * @return array<int, object>
     */
    public static function get_results_profits_report_transaction_rows(
        $start_gmt,
        $end_gmt
    ) {
        return self::get_results_report_transactions_joined(
            $start_gmt,
            $end_gmt,
            null,
            true,
        );
    }

    /**
     * @param string $start_gmt
     * @param string $end_gmt
     * @param string|null $transaction_type payment, expense, or null for both (profits).
     * @param bool $include_transaction_type_column When true, selects t.type (profits report).
     * @return array<int, object>
     */
    private static function get_results_report_transactions_joined(
        $start_gmt,
        $end_gmt,
        $transaction_type,
        $include_transaction_type_column
    ) {
        global $wpdb;

        $sql = self::prepare_report_transactions_joined_sql(
            $start_gmt,
            $end_gmt,
            $transaction_type,
            $include_transaction_type_column,
        );

        if (!$sql) {
            return [];
        }

        $cache_key = self::build_cache_key(__FUNCTION__, [
            "start_gmt" => (string) $start_gmt,
            "end_gmt" => (string) $end_gmt,
            "transaction_type" => (string) $transaction_type,
            "include_type_col" => (bool) $include_transaction_type_column,
        ]);
        $cached = self::cache_get_value($cache_key, $found);
        if ($found) {
            return self::normalize_result_rows($cached);
        }

        $rows = self::normalize_result_rows(self::db_get_results($sql));
        self::cache_set_value($cache_key, $rows);
        return $rows;
    }

    /**
     * @param string $document_type bill|invoice
     * @return array<int, object>
     */
    public static function get_results_list_table_year_month_document_filters(
        $document_type
    ) {
        global $wpdb;

        if (!in_array($document_type, ["bill", "invoice"], true)) {
            return [];
        }

        $sql = self::prepare_document_year_month_filter_sql($document_type);

        if (!$sql) {
            return [];
        }

        $cache_key = self::build_cache_key(__FUNCTION__, [
            "document_type" => (string) $document_type,
        ]);
        $cached = self::cache_get_value($cache_key, $found);
        if ($found) {
            return self::normalize_result_rows($cached);
        }

        $rows = self::normalize_result_rows(self::db_get_results($sql));
        self::cache_set_value($cache_key, $rows);
        return $rows;
    }

    /**
     * @param string $transaction_type payment|expense
     * @return array<int, object>
     */
    public static function get_results_list_table_year_month_transaction_filters(
        $transaction_type
    ) {
        global $wpdb;

        if (!in_array($transaction_type, ["payment", "expense"], true)) {
            return [];
        }

        $sql = self::prepare_transaction_year_month_filter_sql($transaction_type);

        if (!$sql) {
            return [];
        }

        $cache_key = self::build_cache_key(__FUNCTION__, [
            "transaction_type" => (string) $transaction_type,
        ]);
        $cached = self::cache_get_value($cache_key, $found);
        if ($found) {
            return self::normalize_result_rows($cached);
        }

        $rows = self::normalize_result_rows(self::db_get_results($sql));
        self::cache_set_value($cache_key, $rows);
        return $rows;
    }

    /**
     * @return array<int, object>
     */
    public static function get_results_list_table_year_month_transfers()
    {
        global $wpdb;

        $xfer = self::pilot_escaped_table_identifier("pilot_transfers");
        if (null === $xfer) {
            return [];
        }

        $cache_key = self::build_cache_key(__FUNCTION__, ["xfer" => $xfer]);
        $cached = self::cache_get_value($cache_key, $found);
        if ($found) {
            return self::normalize_result_rows($cached);
        }

        $sql = self::prepare_transfer_year_month_filter_sql();

        if (!$sql) {
            return [];
        }

        $rows = self::normalize_result_rows(self::db_get_results($sql));
        self::cache_set_value($cache_key, $rows);
        return $rows;
    }

    /**
     * @param int $contact_id
     * @return string|null
     */
    public static function get_var_vendor_bills_total($contact_id)
    {
        global $wpdb;

        $sql = self::prepare_vendor_bills_total_sql($contact_id);

        if (!$sql) {
            return null;
        }

        $cache_key = self::build_cache_key(__FUNCTION__, ["contact_id" => (int) $contact_id]);
        $cached = self::cache_get_value($cache_key, $found);
        if ($found) {
            return $cached;
        }

        $value = self::db_get_var($sql);
        self::cache_set_value($cache_key, $value);
        return $value;
    }

    /**
     * @param int $contact_id
     * @return string|null
     */
    public static function get_var_vendor_expenses_total($contact_id)
    {
        global $wpdb;

        $sql = self::prepare_vendor_expenses_total_sql($contact_id);

        if (!$sql) {
            return null;
        }

        $cache_key = self::build_cache_key(__FUNCTION__, ["contact_id" => (int) $contact_id]);
        $cached = self::cache_get_value($cache_key, $found);
        if ($found) {
            return $cached;
        }

        $value = self::db_get_var($sql);
        self::cache_set_value($cache_key, $value);
        return $value;
    }

    /**
     * @param int $contact_id
     * @return string|null
     */
    public static function get_var_customer_invoices_total($contact_id)
    {
        global $wpdb;

        $sql = self::prepare_customer_invoices_total_sql($contact_id);

        if (!$sql) {
            return null;
        }

        $cache_key = self::build_cache_key(__FUNCTION__, ["contact_id" => (int) $contact_id]);
        $cached = self::cache_get_value($cache_key, $found);
        if ($found) {
            return $cached;
        }

        $value = self::db_get_var($sql);
        self::cache_set_value($cache_key, $value);
        return $value;
    }

    /**
     * @param int $contact_id
     * @return string|null
     */
    public static function get_var_customer_payments_total($contact_id)
    {
        global $wpdb;

        $sql = self::prepare_customer_payments_total_sql($contact_id);

        if (!$sql) {
            return null;
        }

        $cache_key = self::build_cache_key(__FUNCTION__, ["contact_id" => (int) $contact_id]);
        $cached = self::cache_get_value($cache_key, $found);
        if ($found) {
            return $cached;
        }

        $value = self::db_get_var($sql);
        self::cache_set_value($cache_key, $value);
        return $value;
    }

    /**
     * @param string $type
     * @return string|null
     */
    public static function get_var_max_document_number($type)
    {
        global $wpdb;

        $sql = self::prepare_max_document_number_sql($type);

        if (!$sql) {
            return null;
        }

        $cache_key = self::build_cache_key(__FUNCTION__, ["type" => (string) $type]);
        $cached = self::cache_get_value($cache_key, $found);
        if ($found) {
            return $cached;
        }

        $value = self::db_get_var($sql);
        self::cache_set_value($cache_key, $value);
        return $value;
    }

    /**
     * @param string $type
     * @return string|null
     */
    public static function get_var_max_transaction_number($type)
    {
        global $wpdb;

        $sql = self::prepare_max_transaction_number_sql($type);

        if (!$sql) {
            return null;
        }

        $cache_key = self::build_cache_key(__FUNCTION__, ["type" => (string) $type]);
        $cached = self::cache_get_value($cache_key, $found);
        if ($found) {
            return $cached;
        }

        $value = self::db_get_var($sql);
        self::cache_set_value($cache_key, $value);
        return $value;
    }

    /**
     * Prepared SQL for next document number (allowlisted `pilot_documents` only).
     *
     * @param string $type Document type.
     * @return string|null
     */
    public static function prepare_max_document_number_sql($type)
    {
        global $wpdb;

        $table = self::pilot_escaped_table_identifier("pilot_documents");
        if (null === $table) {
            return null;
        }

        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- Table identifier from pilot_escaped_table_identifier(); values bound below.
        return $wpdb->prepare(
            'SELECT number FROM ' . $table . " WHERE type = %s AND number IS NOT NULL AND number != '' ORDER BY number DESC",
            $type,
        );
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
    }

    /**
     * Prepared SQL for next transaction number (allowlisted `pilot_transactions` only).
     *
     * @param string $type Transaction type.
     * @return string|null
     */
    public static function prepare_max_transaction_number_sql($type)
    {
        global $wpdb;

        $table = self::pilot_escaped_table_identifier("pilot_transactions");
        if (null === $table) {
            return null;
        }

        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- Table identifier from pilot_escaped_table_identifier(); values bound below.
        return $wpdb->prepare(
            'SELECT `number` FROM ' . $table . ' WHERE `type` = %s ORDER BY `number` DESC',
            $type,
        );
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
    }

    /**
     * Prepared SUM for invoices (non-draft) for a contact on `pilot_documents`.
     *
     * @param int $contact_id
     * @return string|null
     */
    public static function prepare_customer_invoices_total_sql($contact_id)
    {
        global $wpdb;

        $table = self::pilot_escaped_table_identifier("pilot_documents");
        if (null === $table) {
            return null;
        }

        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- Table identifier from pilot_escaped_table_identifier(); values bound below.
        return $wpdb->prepare(
            'SELECT SUM(total/exchange_rate) as total FROM ' . $table . " WHERE contact_id = %d AND contact_id !='' AND type='invoice' AND status != 'draft'",
            $contact_id,
        );
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
    }

    /**
     * Prepared SUM for payments for a contact on `pilot_transactions`.
     *
     * @param int $contact_id
     * @return string|null
     */
    public static function prepare_customer_payments_total_sql($contact_id)
    {
        global $wpdb;

        $table = self::pilot_escaped_table_identifier("pilot_transactions");
        if (null === $table) {
            return null;
        }

        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- Table identifier from pilot_escaped_table_identifier(); values bound below.
        return $wpdb->prepare(
            'SELECT SUM(amount/exchange_rate) as total FROM ' . $table . " WHERE contact_id = %d AND contact_id != '' AND type='payment'",
            $contact_id,
        );
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
    }

    /**
     * Prepared SUM for bills (non-draft) for a contact on `pilot_documents`.
     *
     * @param int $contact_id
     * @return string|null
     */
    public static function prepare_vendor_bills_total_sql($contact_id)
    {
        global $wpdb;

        $table = self::pilot_escaped_table_identifier("pilot_documents");
        if (null === $table) {
            return null;
        }

        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- Table identifier from pilot_escaped_table_identifier(); values bound below.
        return $wpdb->prepare(
            'SELECT SUM(total/exchange_rate) as total FROM ' . $table . " WHERE contact_id = %d AND contact_id !='' AND type='bill' AND status != 'draft'",
            $contact_id,
        );
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
    }

    /**
     * Prepared SUM for expenses for a contact on `pilot_transactions`.
     *
     * @param int $contact_id
     * @return string|null
     */
    public static function prepare_vendor_expenses_total_sql($contact_id)
    {
        global $wpdb;

        $table = self::pilot_escaped_table_identifier("pilot_transactions");
        if (null === $table) {
            return null;
        }

        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- Table identifier from pilot_escaped_table_identifier(); values bound below.
        return $wpdb->prepare(
            'SELECT SUM(amount/exchange_rate) as total FROM ' . $table . " WHERE contact_id = %d AND contact_id != '' AND type='expense'",
            $contact_id,
        );
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
    }

    public static function drop_tables($tables)
    {
        $tables = wp_parse_list($tables);
        $tables = array_filter(array_unique($tables));

        foreach ($tables as $table) {
            self::drop_table_if_exists($table);
        }

        self::invalidate_query_cache();
    }

    public static function drop_columns($table, $columns)
    {
        global $wpdb;
        $full_table = self::full_table_name($table);
        if (null === $full_table) {
            return;
        }

        $columns = wp_parse_list($columns);
        $columns = array_filter(array_unique($columns));
        $columns = array_values(
            array_filter($columns, static function ($column) {
                return is_string($column) &&
                    "" !== $column &&
                    preg_match("/^[A-Za-z0-9_]+$/", $column);
            }),
        );

        if ([] === $columns) {
            return;
        }

        $wrapped = self::escaped_table_identifier($full_table);
        $cols_cache_key = self::build_cache_key(__FUNCTION__ . "_describe", [
            "table" => (string) $full_table,
        ]);
        $cached_cols = self::cache_get_value($cols_cache_key, $has_cached_cols);
        $cols = $has_cached_cols
            ? (array) $cached_cols
            : (array) self::db_get_col('DESCRIBE ' . $wrapped);
        if (!$has_cached_cols) {
            self::cache_set_value($cols_cache_key, $cols);
        }

        $columns = array_intersect($columns, $cols);

        if (!empty($columns)) {
            $query = "";
            foreach ($columns as $column) {
                $query .=
                    "DROP COLUMN " .
                    self::escaped_table_identifier($column) .
                    ",";
            }
            $query = rtrim($query, ",");

            self::db_query('ALTER TABLE ' . $wrapped . ' ' . $query);
            self::invalidate_query_cache();
        }
    }
}
