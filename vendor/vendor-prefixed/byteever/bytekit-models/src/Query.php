<?php

namespace Otto\ByteKit\Models;

/**
 * Query class.
 *
 * @since   1.0.0
 * @version 1.0.5
 * @author  Sultan Nasir Uddin <manikdrmc@gmail.com>
 * @package \ByteKit/Models
 * @license GPL-3.0+
 */
class Query
{
    /**
     * Model instance.
     *
     * @since 1.0.0
     * @var Model
     */
    protected $model;
    /**
     * Default values for query vars.
     *
     * @since 1.0.0
     * @var array
     */
    public $query_var_defaults;
    /**
     * Query vars, after parsing
     *
     * @since 1.0.0
     * @var array
     */
    protected $query_vars = [];
    /**
     * SQL clauses.
     *
     * @since 1.0.0
     * @var array
     */
    protected $clauses = [
        "select" => "",
        "from" => "",
        "join" => "",
        "where" => "",
        "groupby" => "",
        "having" => "",
        "orderby" => "",
        "limits" => "",
    ];
    /**
     * SQL query.
     *
     * @since 1.0.0
     * @var string
     */
    protected $request;
    /**
     * Query constructor.
     *
     * @param Model|string $model Model instance.
     *
     * @since 1.0.0
     */
    public function __construct($model)
    {
        $this->model = is_object($model) ? $model : new $model();
        $defaults = [
            "fields" => "*",
            "limit" => 100,
            "page" => 1,
            "orderby" => "",
            "order" => "DESC",
            "include" => "",
            "exclude" => "",
            "search" => "",
            "search_columns" => [],
            "no_found_rows" => true,
            "count" => false,
            "where_query" => "",
            "meta_query" => "",
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
            "meta_key" => "",
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
            "meta_value" => "",
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
            "meta_type" => "",
            "meta_compare" => "",
            // Caching.
            "update_item_cache" => true,
            "update_meta_cache" => true,
        ];
        $this->query_vars = [];
        $this->query_var_defaults = $defaults;
    }
    /**
     * Retrieves query variable.
     *
     * @param string $query_var Query variable key.
     * @param mixed  $fallback Fallback value.
     *
     * @since 1.0.0
     * @return mixed
     */
    public function get($query_var, $fallback = null)
    {
        if (isset($this->query_vars[$query_var])) {
            return $this->query_vars[$query_var];
        }
        return $fallback;
    }
    /**
     * Sets query variable.
     *
     * @param string|array $query_var Query variable key.
     * @param string|array $value Query variable value.
     * @param bool         $append Whether to append the value.
     *
     * @since 1.0.0
     *
     * @return Query
     */
    public function set($query_var, $value = null, $append = false)
    {
        if (is_array($query_var)) {
            foreach ($query_var as $k => $v) {
                $this->set($k, $v, $append);
            }
            return $this;
        }
        if ($append && isset($this->query_vars[$query_var])) {
            $this->query_vars[$query_var] = array_merge(
                (array) $this->query_vars[$query_var],
                (array) $value,
            );
        } else {
            $this->query_vars[$query_var] = $value;
        }
        return $this;
    }
    /**
     * Sets up the WordPress query for retrieving items.
     *
     * @param string|array $query Array or string of query parameters.
     *
     * @since 1.0.0
     * @return array|int List of items or number of items found.
     */
    public function query($query = [])
    {
        global $wpdb;
        if (!empty($query)) {
            $this->query_vars = wp_parse_args($query, $this->query_vars);
        }
        $qv = &$this->query_vars;
        $clauses = &$this->clauses;
        $qv = wp_parse_args($qv, $this->query_var_defaults);
        $clauses = array_fill_keys(array_keys($clauses), "");
        /**
         * Fires before the query is executed.
         *
         * @param array  $qv Query vars.
         * @param static $query Query instance.
         *
         * @since 1.0.0
         */
        $qv = apply_filters(
            $this->model->get_hook_prefix() . "_query_args",
            $qv,
            $this,
        );
        $qv["fields"] = wp_parse_list($qv["fields"]);
        $modifiers = [
            // Non-numeric operators.
            "" => "=",
            "__eq" => "=",
            // Alias for '='.
            "__neq" => "!=",
            "__in" => "IN",
            "__not_in" => "NOT IN",
            "__like" => "LIKE",
            "__not_like" => "NOT LIKE",
            "__starts_with" => "STARTS WITH",
            "__ends_with" => "ENDS WITH",
            "__in_set" => "FIND IN SET",
            "__not_in_set" => "NOT FIND IN SET",
            "__exists" => "IS NOT NULL",
            "__not_exists" => "IS NULL",
            "__null" => "IS NULL",
            "__not_null" => "IS NOT NULL",
            // Numeric operators.
            "__lt" => "<",
            "__lte" => "<=",
            "__gt" => ">",
            "__gte" => ">=",
            "__not" => "!=",
            "__between" => "BETWEEN",
            "__not_between" => "NOT BETWEEN",
            "__regexp" => "REGEXP",
            "__not_regexp" => "NOT REGEXP",
        ];
        $qv["where_query"] = is_array($qv["where_query"])
            ? $qv["where_query"]
            : [];
        foreach (
            array_merge(
                $this->model->get_columns(),
                $this->model->get_aliases(),
            )
            as $column
        ) {
            $column = $this->model->get_unaliased($column);
            foreach ($modifiers as $modifier => $operator) {
                if (
                    isset($qv[$column . $modifier]) &&
                    (is_null($qv[$column . $modifier]) ||
                        !empty($qv[$column . $modifier]))
                ) {
                    $compare_value = $qv[$column . $modifier];
                    // If the value is empty array, then skip.
                    if ([] === $compare_value) {
                        continue;
                    }
                    $qv["where_query"][] = [
                        "column" => $column,
                        "compare" =>
                            empty($modifier) && is_array($compare_value)
                                ? "IN"
                                : $operator,
                        "value" => $compare_value,
                    ];
                    unset($qv[$column . $modifier]);
                }
            }
        }
        // $args can be anything. Only use the args defined in defaults to compute the key.
        $_args = wp_array_slice_assoc(
            $this->query_vars,
            array_keys($this->query_var_defaults),
        );
        // Ignore the $fields, $update_item_cache, $update_meta_cache argument as the queried result will be the same regardless.
        unset(
            $_args["count"],
            $_args["fields"],
            $_args["update_item_cache"],
            $_args["update_meta_cache"],
            $_args["no_found_rows"],
        );
        $query_key = md5(maybe_serialize($_args));
        $cache_group = $this->model->get_cache_group();
        $last_changed = wp_cache_get_last_changed($cache_group);
        $cache_key = "{$cache_group}:{$query_key}:{$last_changed}";
        $cache_value = wp_cache_get($cache_key, $cache_group);
        // Fields.
        $clauses["select"] = $qv["count"]
            ? "COUNT(*)"
            : "{$this->model->get_table()}.*";
        // From.
        $clauses[
            "from"
        ] = "FROM {$wpdb->prefix}{$this->model->get_table()} AS {$this->model->get_table()}";
        // Where.
        foreach ($qv["where_query"] as $clause) {
            $where_clause = $this->get_where_clause_sql($clause);
            if (!empty(trim($where_clause))) {
                $clauses["where"] .= " AND " . $where_clause;
            }
        }
        if (!empty($qv["include"])) {
            $include_sql = $this->get_where_clause_sql([
                "column" => $this->model->get_key_name(),
                "compare" => "IN",
                "value" => wp_parse_list($qv["include"]),
            ]);
            if (!empty($include_sql)) {
                $clauses["where"] .= " AND " . $include_sql;
            }
        }
        if (!empty($qv["exclude"])) {
            $exclude_sql = $this->get_where_clause_sql([
                "column" => $this->model->get_key_name(),
                "compare" => "NOT IN",
                "value" => wp_parse_list($qv["exclude"]),
            ]);
            if (!empty($exclude_sql)) {
                $clauses["where"] .= " AND " . $exclude_sql;
            }
        }
        if (strlen($qv["search"])) {
            $search_columns = is_array($qv["search_columns"])
                ? $qv["search_columns"]
                : $this->model->get_columns();
            $search_columns = array_map(
                [$this->model, "get_unaliased"],
                $search_columns,
            );
            $search_columns = array_intersect(
                $search_columns,
                $this->model->get_columns(),
            );
            $search = "%" . $wpdb->esc_like($qv["search"]) . "%";
            $search_clauses = [];
            foreach ($search_columns as $column) {
                $search_clauses[] = $wpdb->prepare(
                    "{$this->model->get_table()}.{$column} LIKE %s",
                    $search,
                );
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Prepared in the previous line.
            }
            if (!empty($search_clauses)) {
                $clauses["where"] .=
                    " AND (" . implode(" OR ", $search_clauses) . ")";
            }
        }
        if ($this->model->get_meta_type()) {
            $meta_query = new \WP_Meta_Query();
            $meta_query->parse_query_vars($qv);
            if (!empty($meta_query->queries) && $this->model->get_meta_type()) {
                $meta_clauses = $meta_query->get_sql(
                    $this->model->get_meta_type(),
                    $this->model->get_table(),
                    $this->model->get_key_name(),
                    $this,
                );
                $clauses["join"] .= $meta_clauses["join"];
                $clauses["where"] .= $meta_clauses["where"];
                if ($meta_query->has_or_relation()) {
                    $clauses[
                        "groupby"
                    ] = "{$this->model->get_table()}.{$this->model->get_key_name()}";
                }
            }
        }
        if (!empty($qv["orderby"])) {
            $order = in_array(strtoupper($qv["order"]), ["ASC", "DESC"], true)
                ? strtoupper($qv["order"])
                : "DESC";
            $orderby = $this->model->get_unaliased($qv["orderby"]);
            $orderby = in_array($orderby, $this->model->get_columns(), true)
                ? $orderby
                : $this->model->get_key_name();
            $clauses[
                "orderby"
            ] = "ORDER BY {$this->model->get_table()}.{$orderby} {$order}";
        } else {
            $clauses[
                "orderby"
            ] = "ORDER BY {$this->model->get_table()}.{$this->model->get_key_name()} DESC";
        }
        if (intval($qv["limit"]) > 0) {
            $page = empty($qv["page"]) ? 1 : absint(trim($qv["page"], "/"));
            $page = max(1, $page);
            $pgstrt = absint(($page - 1) * $qv["limit"]) . ", ";
            $clauses["limits"] = $wpdb->prepare(
                "LIMIT %d, %d",
                $pgstrt,
                $qv["limit"],
            );
        }
        /**
         * Fires after the query is prepared.
         *
         * @param array $clauses The query clauses.
         * @param array $args The query arguments.
         * @param Query $query The query object.
         *
         * @since 1.0.0
         */
        $clauses = apply_filters(
            $this->model->get_hook_prefix() . "_query_clauses",
            $clauses,
            $qv,
            $this,
        );
        $clauses["where"] = preg_replace("/^ AND /", "", $clauses["where"], 1);
        $clauses["where"] = empty($clauses["where"])
            ? ""
            : "WHERE " . $clauses["where"];
        $clauses["groupby"] = empty($clauses["groupby"])
            ? ""
            : "GROUP BY " . $clauses["groupby"];
        $clauses["having"] = empty($clauses["having"])
            ? ""
            : "HAVING " . $clauses["having"];
        $found_rows = $qv["no_found_rows"] ? "" : "SQL_CALC_FOUND_ROWS";
        $this->request = "SELECT COUNT(*)\n\t\t\t\t{$clauses["from"]}\n\t\t\t\t{$clauses["join"]}\n\t\t\t\t{$clauses["where"]}\n\t\t\t\t{$clauses["groupby"]}";
        if (
            $qv["count"] &&
            (false === $cache_value || !isset($cache_value["count"]))
        ) {
            $cache_value = is_array($cache_value) ? $cache_value : [];
            // If we have not counted the results last time, then count the results.
            if (!empty($clauses["groupby"])) {
                $cache_value["count"] = (int) $wpdb->get_results(
                    $this->request,
                );
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Prepared in the previous lines.
            } else {
                $cache_value["count"] = (int) $wpdb->get_var($this->request);
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Prepared in the previous lines.
            }
            wp_cache_set($cache_key, $cache_value, $cache_group);
        }
        // if count is requested, then return the count.
        if ($qv["count"]) {
            return $cache_value["count"];
        }
        $this->request = "SELECT {$found_rows} {$clauses["select"]}\n\t\t\t\t{$clauses["from"]}\n\t\t\t\t{$clauses["join"]}\n\t\t\t\t{$clauses["where"]}\n\t\t\t\t{$clauses["groupby"]}\n\t\t\t\t{$clauses["having"]}\n\t\t\t\t{$clauses["orderby"]}\n\t\t\t\t{$clauses["limits"]}";
        // If items are requested, process the request.
        if (false === $cache_value || !isset($cache_value["items"])) {
            $cache_value = is_array($cache_value) ? $cache_value : [];
            $cache_value["items"] = $wpdb->get_results($this->request);
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Prepared in the previous lines.
            if (
                $cache_value["items"] &&
                !$qv["no_found_rows"] &&
                !empty($clauses["limits"])
            ) {
                /**
                 * Filters the query used to retrieve the found object count.
                 *
                 * @param string $request The query string.
                 *
                 * @since 1.0.0
                 */
                $count_request = apply_filters(
                    $this->model->get_hook_prefix() . "_count_request",
                    "SELECT FOUND_ROWS()",
                );
                $cache_value["count"] = (int) $wpdb->get_var($count_request);
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Prepared in the previous lines.
            }
            wp_cache_set($cache_key, $cache_value, $cache_group);
        }
        $items = $cache_value["items"];
        // If only ids are requested, then return the ids.
        if (in_array("ids", $qv["fields"], true)) {
            $ids = wp_list_pluck($items, $this->model->get_key_name());
            return array_map("intval", $ids);
        }
        // Prime the caches .
        if ($qv["update_item_cache"]) {
            $this->update_cache($items, $qv["update_meta_cache"]);
        }
        // Prepare the items.
        $items = array_map(function ($item) {
            $data = get_object_vars($item);
            $data = array_map("maybe_unserialize", $data);
            /**
             * Filters the data before returning.
             *
             * @param array $attributes The attributes array.
             * @param array $original The original attributes array.
             *
             * @since 1.0.0
             */
            $attributes = apply_filters(
                "{$this->model->get_hook_prefix()}_attributes",
                $data,
                $data,
            );
            // allowing filtering each attribute.
            foreach ($attributes as $key => $value) {
                /**
                 * Filters the data before returning.
                 *
                 * @param mixed $value The value of the data.
                 * @param array $attributes The attributes array.
                 *
                 * @since 1.0.0
                 */
                $attributes[$key] = apply_filters(
                    "{$this->model->get_hook_prefix()}_attribute_{$key}",
                    $value,
                    $attributes,
                );
            }
            return $attributes;
        }, $items);
        // If specific fields are requested, then return only those fields.
        $columns = array_intersect($qv["fields"], $this->model->get_columns());
        $columns = array_map([$this->model, "get_unaliased"], $columns);
        if (!empty($columns) && is_array($columns)) {
            $items = array_map(function ($item) use ($columns) {
                return wp_array_slice_assoc($item, $columns);
            }, $items);
            // cast the data.
            return array_map([$this->model, "cast"], $items);
        }
        // Finally, return the items.
        return array_map(function ($item) {
            return $this->model
                ->new_instance($item)
                ->read_metadata()
                ->sync_original();
        }, $items);
    }
    /**
     * Get a list of items matching the query vars.
     *
     * @since 1.0.0
     *
     * @return array List of items.
     */
    public function get_results()
    {
        unset($this->query_vars["count"]);
        return $this->query();
    }
    /**
     * Get the first item matching the query vars.
     *
     * @since 1.0.0
     * @return object|null The first item or null if not found.
     */
    public function get_result()
    {
        $this->query_vars["limit"] = 1;
        $items = $this->query();
        return empty($items) ? null : $items[0];
    }
    /**
     * Get the count of the query.
     *
     * @since 1.0.0
     *
     * @return int The count of the query.
     */
    public function get_count()
    {
        $this->query_vars["count"] = true;
        return $this->query();
    }
    /**
     * Get the SQL query.
     *
     * @since 1.0.0
     * @return string
     */
    public function get_request()
    {
        $this->query();
        return $this->request;
    }
    /**
     * Generates WHERE clauses SQL.
     *
     * @param array $clause Query clause.
     *
     * @since 1.0.0
     * @return string
     */
    protected function get_where_clause_sql($clause)
    {
        global $wpdb;
        $column = $clause["column"];
        $compare = isset($clause["compare"])
            ? strtoupper($clause["compare"])
            : "=";
        $value = isset($clause["value"]) ? $clause["value"] : "";
        $cast = isset($clause["type"]) ? strtoupper($clause["type"]) : "CHAR";
        // If column is not a known column, then return empty string.
        if (
            empty($column) ||
            !in_array($column, $this->model->get_columns(), true)
        ) {
            return "";
        }
        switch ($compare) {
            case "IN":
            case "NOT IN":
                $value = is_array($value)
                    ? $value
                    : preg_split("/[,\s]+/", $value);
                $compare_string =
                    "(" . substr(str_repeat(",%s", count($value)), 1) . ")";
                $where = $wpdb->prepare($compare_string, $value);
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Prepared in the previous line.
                break;
            case "BETWEEN":
            case "NOT BETWEEN":
                $value = is_array($value)
                    ? $value
                    : preg_split("/[,\s]+/", $value);
                // values must be two. if not we will duplicate the first value.
                $value =
                    count($value) < 2
                        ? array_merge($value, [$value[0]])
                        : $value;
                $where = $wpdb->prepare("%s AND %s", min($value), max($value));
                break;
            case "LIKE":
            case "NOT LIKE":
                $value = "%" . $wpdb->esc_like($value) . "%";
                $where = $wpdb->prepare("%s", $value);
                break;
            case "ENDS WITH":
                $compare = "LIKE";
                $value = "%" . $wpdb->esc_like($value);
                $where = $wpdb->prepare("%s", $value);
                break;
            case "STARTS WITH":
                $compare = "LIKE";
                $value = $wpdb->esc_like($value) . "%";
                $where = $wpdb->prepare("%s", $value);
                break;
            case "FIND IN SET":
                $compare = is_array($value) ? "REGEXP" : "FIND_IN_SET";
                $value = is_array($value) ? implode("|", $value) : $value;
                $compare_string =
                    "REGEXP" === $compare
                        ? "{$this->model->get_table()}.{$column} REGEXP %s"
                        : "FIND_IN_SET( %s, {$this->model->get_table()}.{$column} ) > 0";
                $where = $wpdb->prepare($compare_string, $value);
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Prepared in the previous line.
                break;
            case "IS NULL":
            case "IS NOT NULL":
                $where = $compare;
                $compare = "";
                break;
            case "REGEXP":
            case "NOT REGEXP":
            case ">=":
            case "<=":
            case "!=":
            case ">":
            case "<":
            case "<>":
            case "=":
                $placeholder = is_numeric($value) ? "%d" : "%s";
                $where = $wpdb->prepare($placeholder, $value);
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Prepared in the previous line.
                break;
            default:
                $where = "";
                break;
        }
        $sql = "";
        if (!empty($where)) {
            if (
                !preg_match(
                    '/^(?:BINARY|SIGNED|UNSIGNED|NUMERIC(?:\(\d+(?:,\s?\d+)?\))?|DECIMAL(?:\(\d+(?:,\s?\d+)?\))?)|(YEAR|DATE|DATETIME|TIME|TIMESTAMP|DATETIME(?:\(\d+\))?)$/',
                    $cast,
                )
            ) {
                $sql = "({$this->model->get_table()}.{$column} {$compare} {$where})";
            } elseif ("NUMERIC" === $cast) {
                $sql = "(CAST( {$this->model->get_table()}.{$column} AS SIGNED ) {$compare} {$where})";
            } else {
                $sql = "(CAST( {$this->model->get_table()}.{$column} AS {$cast} ) {$compare} {$where})";
            }
        }
        return $sql;
    }
    /**
     * Update caches.
     *
     * @param object[] $items List of items to update.
     * @param bool     $update_meta_cache Optional. Whether to update the meta cache. Default true.
     *
     * @since 1.0.0
     * @return void
     */
    protected function update_cache($items, $update_meta_cache = true)
    {
        $data = [];
        $primary = $this->model->get_key_name();
        $items = is_array($items) ? $items : [$items];
        foreach ($items as $item) {
            $id = $item->{$primary};
            $data[$id] = $item;
        }
        /**
         * Fires before the cache is updated.
         *
         * @param object[] $items List of items.
         *
         * @since 1.0.0
         */
        do_action($this->model->get_hook_prefix() . "_pre_prime_cache", $items);
        wp_cache_add_multiple($data, $this->model->get_cache_group());
        if ($update_meta_cache && $this->model->get_meta_type()) {
            $ids = wp_list_pluck($items, $primary);
            update_meta_cache($this->model->get_meta_type(), $ids);
        }
        /**
         * Fires after the cache has been updated.
         *
         * @param object[] $items List of items.
         *
         * @since 1.0.0
         */
        do_action($this->model->get_hook_prefix() . "_prime_cached", $items);
    }
}
