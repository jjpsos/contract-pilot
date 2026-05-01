<?php

namespace Otto\Utilities;


class DatabaseUtil
{
    
    public static function drop_tables($tables)
    {
        global $wpdb;
        $tables = wp_parse_list($tables);
        $tables = array_filter(array_unique($tables));

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$table}"); 
        }
    }

    
    public static function drop_columns($table, $columns)
    {
        global $wpdb;
        $table = $wpdb->prefix . $table;
        $columns = wp_parse_list($columns);
        $columns = array_filter(array_unique($columns));
        $cols = $wpdb->get_col("DESC {$table}", 0); 

        
        $columns = array_intersect($columns, $cols);
        
        if (!empty($columns)) {
            $query = "";
            foreach ($columns as $column) {
                $query .= "DROP COLUMN `{$column}`,";
            }
            $query = rtrim($query, ",");

            $wpdb->query("ALTER TABLE {$table} {$query}"); 
        }
    }
}
