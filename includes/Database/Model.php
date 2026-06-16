<?php

namespace Jjpsos\ContractPilot\Database;

use Jjpsos\ContractPilot\Database\Traits\HasAttributes;
use Jjpsos\ContractPilot\Database\Traits\HasMetaData;
use Jjpsos\ContractPilot\Database\Traits\HasRelations;

/**
 * Abstract class Model.
 *
 * @since   1.0.0
 * @version 1.0.5
 * @author  Sultan Nasir Uddin <manikdrmc@gmail.com>
 * @package \ByteKit/Models
 * @license GPL-3.0+
 */
abstract class Model
{
    use HasAttributes;
    use HasMetaData;
    use HasRelations;

    /**
     * The table associated with the model.
     *
     * This string indicates the name of the database table that the model is associated with.
     * It is used for building database queries and interactions with the underlying data store.
     *
     * @since 1.0.0
     * @var string
     */
    protected $table;
    /**
     * The primary key for the model.
     *
     * This string specifies the primary key column for the model's table.
     * By default, it is set to 'id', but it can be customized to match the primary key used in the table.
     *
     * @since 1.0.0
     * @var string
     */
    protected $primary_key = "id";
    /**
     * The type of the object. Used for actions and filters. e.g. post, user, etc.
     *
     * This string represents the type of the object, which is useful for categorizing and filtering objects
     * within the application. Examples include 'post', 'user', and other custom types.
     *
     * @since 1.0.0
     * @var string
     */
    protected $object_type;
    /**
     * Cache group to cache queried items. Default is table name.
     *
     * This string specifies the cache group used for caching queried items.
     * By default, it is set to the table name, but it can be customized to group related caches together.
     *
     * @since 1.0.0
     * @var string
     */
    protected $cache_group;
    /**
     * The table columns of the model.
     *
     * This array contains the names of the columns in the model's table.
     * It is used to map attribute names to their corresponding database columns.
     *
     * @since 1.0.0
     * @var array
     */
    protected $columns = [];
    /**
     * The name of the "date created" column.
     *
     * This constant defines the name of the column used to store the creation date of the model.
     * It is typically set to 'date_created'.
     *
     * @since 1.0.0
     * @var string
     */
    const DATE_CREATED = "date_created";
    /**
     * The name of the "date updated" column.
     *
     * This constant defines the name of the column used to store the last update date of the model.
     * It is typically set to 'date_updated'.
     *
     * @since 1.0.0
     * @var string
     */
    const DATE_UPDATED = "date_updated";
    /**
     * The name of the "author_id" column.
     *
     * This constant defines the name of the column used to store the ID of the user who created the model instance.
     * It is typically set to 'author_id'.
     *
     * @since 1.0.0
     * @var string
     */
    const AUTHOR_ID = "author_id";
    /**
     * Default query variables passed to Query class.
     *
     * This array contains default variables that are passed to the Query class when performing queries.
     * These default values can be customized or overridden as needed.
     *
     * @since 1.0.0
     * @var array
     */
    protected $query_vars = [];
    /**
     * The searchable attributes.
     *
     * This array lists the properties that can be searched when querying the model.
     * It is used to filter results based on user input or other criteria.
     *
     * @since 1.0.0
     * @var array
     */
    protected $searchable = [];
    /**
     * Attributes that have transition effects when changed.
     *
     * This array lists attributes that should trigger transition effects when their values change.
     * It is often used for managing state changes or triggering animations in user interfaces.
     *
     * @since 1.0.0
     * @var array
     */
    protected $transitionable = [];
    /**
     * The array of booted models.
     *
     * This static array keeps track of models that have been booted, meaning they have been initialized and are ready for use.
     * It helps ensure that models are only booted once during their lifecycle.
     *
     * @since 1.0.0
     * @var array
     */
    protected static $booted = [];
    /**
     * Find an object by its primary key or query.
     *
     * @param mixed $id The ID or query to search by.
     *
     * @since 1.0.0
     * @return static|null The model instance, or null if not found.
     */
    public static function find($id)
    {
        $item = new static();
        $data = $item->read($id);
        return empty($data)
            ? null
            : $item->fill($data)->read_metadata()->sync_original();
    }
    /**
     * Find an object by its primary key or create a new instance.
     *
     * @param mixed $attributes Attributes to set.
     *
     * @since 1.0.0
     * @return static The model instance.
     */
    public static function make($attributes = null)
    {
        $model = new static($attributes);
        $primary_key = $model->get_key_name();
        $key_value = null;
        // Normalize attributes.
        if ($attributes instanceof static && $attributes->exists()) {
            $attributes = $attributes->get_attributes();
        } elseif (is_object($attributes)) {
            $attributes = get_object_vars($attributes);
        }
        // Extract the primary key value from the attributes.
        if (is_array($attributes) && !empty($attributes[$primary_key])) {
            $key_value = $attributes[$primary_key];
        } elseif (is_scalar($attributes) && !empty($attributes)) {
            $key_value = $attributes;
            $attributes = null;
        }
        // Attempt to find the existing item by key value.
        $item = !empty($key_value) ? static::find($key_value) : false;
        // Return the found item or fill a new model instance.
        if (!empty($item)) {
            return $item->fill($attributes);
        }
        return $model->fill([$primary_key => null]);
    }
    /**
     * Create an object.
     *
     * @param array|Model $data Item data.
     * @param bool        $wp_error Whether to return a WP_Error on failure.
     *
     * @since 1.0.0
     * @return static|false|\WP_Error The model instance on success, false on failure.
     */
    public static function insert($data, $wp_error = true)
    {
        $item = static::make($data);
        $return = $item->save();
        if (is_wp_error($return)) {
            return $wp_error ? $return : false;
        }
        return $return;
    }
    /**
     * Query the database for a list of items.
     *
     * @param array $args The query arguments.
     *
     * @since 1.0.0
     * @return array|static[] List of items, or ids if 'fields' is 'ids'.
     */
    public static function results($args = [])
    {
        return (new static())->query($args);
    }
    /**
     * Get the total number of items in the database.
     *
     * @param array $args The query arguments.
     *
     * @since 1.0.0
     * @return int
     */
    public static function count($args = [])
    {
        $args = wp_parse_args($args, ["count" => true]);
        return (int) (new static())->query($args);
    }
    /**
     * Create a new model instance.
     *
     * @param mixed $attributes The attributes to fill the model with.
     */
    public function __construct($attributes = null)
    {
        $this->boot();
        if (!empty($attributes)) {
            $this->fill($attributes);
        }
    }
    /**
     * Destroy the object.
     *
     * @since 1.0.0
     */
    public function __destruct()
    {
        $this->attributes = array_fill_keys($this->columns, null);
    }
    /**
     * Only store the object primary key to avoid serializing the data object instance.
     *
     * @since 1.0.0
     * @return array
     */
    public function __sleep()
    {
        return ["attributes"];
    }
    /**
     * Re-run the constructor with the object primary key.
     *
     * If the object no longer exists, remove the ID.
     *
     * @since 1.0.0
     * @return void
     */
    public function __wakeup()
    {
        try {
            $this->__construct($this->get_key_name());
        } catch (\Exception $e) {
            $this->set($this->get_key_name(), null);
        }
    }
    /**
     * When the object is cloned, make sure meta is duplicated correctly.
     *
     * @since 1.0.0
     * @return $this
     */
    public function __clone()
    {
        $this->set($this->get_key_name(), null);
        $this->original = $this->attributes;
        return $this;
    }
    /**
     * Magic method to get the value of a property.
     *
     * @param string $key The name of the property.
     *
     * @since 1.0.0
     * @return mixed
     */
    public function &__get($key)
    {
        $value = $this->get($key);
        return $value;
    }
    /**
     * Magic method to set the value of an attribute.
     *
     * @param string $key The name of the attribute.
     * @param mixed  $value The value of the attribute.
     *
     * @since 1.0.0
     * @return void
     */
    public function __set($key, $value)
    {
        $this->set($key, $value);
    }
    /**
     * Magic method to check if an attribute is set.
     *
     * @param string $key The name of the attribute.
     *
     * @since 1.0.0
     * @return bool
     */
    public function __isset($key)
    {
        return !is_null($this->get($key));
    }
    /**
     * Magic method to unset an attribute.
     *
     * @param string $key The name of the attribute.
     *
     * @since 1.0.0
     * @return void
     */
    public function __unset($key)
    {
        $this->set($key, null);
    }
    /**
     * Boot the model and set up the object.
     *
     * @since 1.0.0
     * @return void
     */
    protected function boot()
    {
        $class = static::class;
        if (!isset(static::$booted[$class])) {
            $this->booting();
        }
        foreach (static::$booted[$class] as $key => $value) {
            // dont overwrite the attributes, as it might have unique values like uuid.
            if ("attributes" !== $key) {
                $this->{$key} = $value;
            }
        }
    }
    /**
     * Perform any actions required before the model boots.
     *
     * @since 1.0.0
     * @return void
     */
    protected function booting()
    {
        global $wpdb;
        // Set object type.
        if (empty($this->object_type)) {
            $class = get_called_class();
            $parts = explode("\\", $class);
            $this->object_type = strtolower(end($parts));
        }
        // Set table name.
        if (empty($this->table)) {
            $this->table = $this->pluralize($this->object_type);
        }
        // Set meta type.
        if (false !== $this->meta_type) {
            $this->meta_type = $this->singularize($this->table);
            if (!_get_meta_table($this->meta_type)) {
                $meta_table = $this->meta_type . "meta";
                $wpdb->tables[] = $meta_table;
                $wpdb->{$meta_table} = $wpdb->prefix . $meta_table;
            }
        }
        // Set cache group.
        if (empty($this->cache_group)) {
            $this->cache_group = $this->table;
        }
        // Setup columns.
        if (empty($this->columns)) {
            $schema_group = 'contract_pilot_schema';
            $cache_key = 'columns:' . $wpdb->prefix . $this->table;
            $columns = wp_cache_get($cache_key, $schema_group);
            if (false === $columns && preg_match('/^[A-Za-z0-9_]+$/', $this->table)) {
                $qualified_table = esc_sql($wpdb->prefix . $this->table);
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name validated and escaped above.
                $columns = $wpdb->get_col("DESCRIBE `{$qualified_table}`");
                wp_cache_set($cache_key, $columns, $schema_group);
            }
            $this->columns = is_array($columns) ? $columns : [];
        }
        // Setup primary key.
        if (!in_array($this->primary_key, $this->columns, true)) {
            $this->columns[] = $this->primary_key;
        }
        // Set the primary key to int if not set.
        if (!isset($this->casts[$this->primary_key])) {
            $this->casts[$this->primary_key] = "int";
        }
        // Set author_id column.
        if ($this->has_author) {
            if (!in_array(static::AUTHOR_ID, $this->columns, true)) {
                $this->columns[] = static::AUTHOR_ID;
            }
            if (!isset($this->casts[static::AUTHOR_ID])) {
                $this->casts[static::AUTHOR_ID] = "int";
            }
        }
        // Set timestamps columns.
        if ($this->has_timestamps) {
            if (!in_array(static::DATE_CREATED, $this->columns, true)) {
                $this->columns[] = static::DATE_CREATED;
            }
            if (!in_array(static::DATE_UPDATED, $this->columns, true)) {
                $this->columns[] = static::DATE_UPDATED;
            }
            if (!isset($this->casts[static::DATE_CREATED])) {
                $this->casts[static::DATE_CREATED] = "datetime";
            }
            if (!isset($this->casts[static::DATE_UPDATED])) {
                $this->casts[static::DATE_UPDATED] = "datetime";
            }
        }
        $attributes = array_merge(
            array_fill_keys($this->columns, null),
            $this->attributes,
        );
        $this->attributes = $this->cast($attributes);
        $this->original = $this->attributes;
        /**
         * Perform any actions required after the model boots.
         *
         * @param static $this The model instance.
         *
         * @since 1.0.0
         */
        $this->do_model_action('_booted', $this);
        // Set the booted flag.
        static::$booted[static::class] = get_object_vars($this);
    }
    /*
    |--------------------------------------------------------------------------
    | Accessors, Mutators, and Helpers
    |--------------------------------------------------------------------------
    | This section includes methods for accessing, modifying, and assisting with
    | the model's properties.
    | - Getters: Retrieve property values.
    | - Setters: Update property values.
    | - Helpers: Helper methods of the getter and setter methods.
    |
    | These methods ensure data integrity and encapsulation.
    |--------------------------------------------------------------------------
    */
    /**
     * Get the table associated with the model.
     *
     * @since 1.0.0
     * @return string
     */
    public function get_table()
    {
        return $this->table;
    }
    /**
     * Get the primary key for the model.
     *
     * @since 1.0.0
     * @return string
     */
    public function get_key_name()
    {
        return $this->primary_key;
    }
    /**
     * Get the value of the primary key.
     *
     * @since 1.0.0
     * @return mixed
     */
    public function get_key_value()
    {
        return $this->get($this->primary_key);
    }
    /**
     * Get the type of the object.
     *
     * @since 1.0.0
     * @return string
     */
    public function get_object_type()
    {
        return $this->object_type;
    }
    /**
     * Get the cache group.
     *
     * @since 1.0.0
     * @return string
     */
    public function get_cache_group()
    {
        return $this->cache_group;
    }
    /**
     * Get the table columns of the model.
     *
     * @since 1.0.0
     * @return array
     */
    public function get_columns()
    {
        return $this->columns;
    }
    /**
     * Get default query variables passed to Query.
     *
     * @since 1.0.0
     * @return array
     */
    public function get_query_vars()
    {
        return $this->query_vars;
    }
    /**
     * Set query variable and its value.
     *
     * @param string $key The query variable key.
     * @param mixed  $value The query variable value.
     *
     * @since 1.0.0
     * @return $this
     */
    public function set_query_var($key, $value)
    {
        $this->query_vars[$key] = $value;
        return $this;
    }
    /**
     * Get the searchable properties.
     *
     * @since 1.0.0
     * @return array
     */
    public function get_searchable()
    {
        return $this->searchable;
    }
    /**
     * Get the transitionable properties.
     *
     * @since 1.0.0
     * @return array
     */
    public function get_transitionable()
    {
        return $this->transitionable;
    }
    /**
     * Fill the model with an array of attributes.
     *
     * @param mixed $attributes The attributes to fill the model with.
     *
     * @return $this
     */
    public function fill($attributes)
    {
        if (is_object($attributes)) {
            $attributes = get_object_vars($attributes);
        }
        if (is_array($attributes)) {
            foreach ($attributes as $key => $value) {
                $this->set($key, $value);
            }
        }
        return $this;
    }
    /**
     * Convert the model instance to an array.
     *
     * @since 1.0.0
     * @return array
     */
    public function to_array()
    {
        $attributes = $this->get_attributes();
        $appends = $this->get_appends();
        $hidden = $this->get_hidden();
        $relations = $this->get_relations();
        $data = array_diff_key($attributes, array_flip($hidden));
        foreach ($appends as $key) {
            $data[$key] = $this->get($key);
        }
        foreach ($relations as $key => $relation) {
            $rel_value = $this->get_relation($key);
            if (is_array($rel_value)) {
                $data[$key] = array_map(function ($item) {
                    return $item->to_array();
                }, $rel_value);
            } else {
                $data[$key] = $rel_value->to_array();
            }
        }
        return $data;
    }
    /*
    |--------------------------------------------------------------------------
    | CRUD Methods
    |--------------------------------------------------------------------------
    | This section contains methods for creating, reading, updating, and deleting
    | objects in the database.
    |--------------------------------------------------------------------------
    */
    /**
     *  Create an item in the database.
     *
     * @param array $data Data to be inserted.
     *
     * @since 1.0.0
     * @return \WP_Error|int The ID of the inserted item, or WP_Error on failure.
     * @global \wpdb $wpdb WordPress database abstraction object.
     */
    protected function create($data)
    {
        global $wpdb;
        $data = wp_unslash($data);
        $data = array_map("maybe_serialize", $data);
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table insert on plugin-owned schema.
        if (false === $wpdb->insert($wpdb->prefix . $this->table, $data)) {
            return new \WP_Error(
                "db_insert_error",
                sprintf(
                    "Could not insert item into the database error %s",
                    esc_html($wpdb->last_error),
                ),
            );
        }
        return $wpdb->insert_id;
    }
    /**
     * Read an item from the database.
     *
     * @param int|string $id ID of the item to read.
     *
     * @since 1.0.0
     * @return array|null The item data, or null if not found.
     * @global \wpdb     $wpdb WordPress database abstraction object.
     */
    protected function read($id)
    {
        if (empty($id)) {
            return null;
        }
        // it must be scalar or array.
        if (!is_scalar($id) && !is_array($id)) {
            return null;
        }
        // if scalar, convert to array.
        if (is_scalar($id)) {
            $id = [$this->primary_key => $id];
        }
        // remove any keys that have empty values but keep null values.
        $id = array_filter($id, function ($value) {
            return !empty($value) || is_null($value);
        });
        // if no keys left, return null.
        if (empty($id)) {
            return null;
        }
        $args = $this->get_unaliased($id);
        $args = wp_array_slice_assoc(
            wp_parse_args($args, $this->query_vars),
            $this->columns,
        );
        $_args = array_diff_assoc($args, $this->query_vars);
        $cache_key = implode(":", array_values(array_filter($_args)));
        $data = wp_cache_get($cache_key, $this->get_cache_group());
        if (false === $data) {
            global $wpdb;
            $where = [];
            foreach ($args as $key => $value) {
                if (!in_array($key, $this->columns, true)) {
                    continue;
                }
                $column = esc_sql($key);
                if (is_array($value)) {
                    $value = array_values($value);
                    if ([] === $value) {
                        continue;
                    }
                    $in_list = $wpdb->prepare(
                        '(' . implode(',', array_fill(0, count($value), '%s')) . ')',
                        ...$value
                    );
                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Column validated against model schema; IN values prepared above.
                    $where[] = '`' . $column . '` IN ' . $in_list;
                } elseif (is_null($value)) {
                    $where[] = "`{$column}` IS NULL";
                } elseif (is_numeric($value)) {
                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Column validated against model schema; value bound below.
                    $where[] = $wpdb->prepare("`{$column}` = %d", $value);
                } else {
                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Column validated against model schema; value bound below.
                    $where[] = $wpdb->prepare("`{$column}` = %s", $value);
                }
            }
            // we must have at least one where clause.
            if (empty($where)) {
                return null;
            }
            $where_sql = implode(" AND ", $where);
            $read_sql = $this->build_read_sql($where_sql);
            if ('' === $read_sql) {
                return null;
            }
            $data = $this->db_get_row($read_sql);
            // Bail if no data found.
            if (empty($data)) {
                return null;
            }
            wp_cache_set($cache_key, $data, $this->cache_group);
            wp_cache_add(
                $data->{$this->primary_key},
                $data,
                $this->cache_group,
            );
        }
        $data = array_map("maybe_unserialize", get_object_vars($data));
        /**
         * Filters the data before returning.
         *
         * @param array $attributes The attributes array.
         * @param array $original The original attributes array.
         *
         * @since 1.0.0
         */
        $attributes = $this->apply_model_filter('_attributes', $data, $data);
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
            $attributes[$key] = $this->apply_model_filter(
                '_attribute_' . $key,
                $value,
                $attributes,
            );
        }
        return wp_unslash($attributes);
    }
    /**
     * Update an item in the database.
     *
     * @param int|string $id ID of the item to update.
     * @param array      $data Data to be updated.
     *
     * @since 1.0.0
     * @return bool|\WP_Error True on success, WP_Error on failure.
     * @global \wpdb     $wpdb WordPress database abstraction object.
     */
    protected function update($id, $data)
    {
        global $wpdb;
        $data = wp_unslash($data);
        $data = array_map("maybe_serialize", $data);
        if (
            false ===
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table write; object cache updated by save flow.
            $wpdb->update($wpdb->prefix . $this->table, $data, [
                $this->primary_key => $id,
            ])
        ) {
            return new \WP_Error(
                "db_update_error",
                sprintf(
                    "Could not update item in the database error %s",
                    esc_html($wpdb->last_error),
                ),
            );
        }
        return true;
    }
    /**
     * Delete an item from the database.
     *
     * @param int|string $id ID of the item to delete.
     *
     * @since 1.0.0
     * @return true|\WP_Error True on success, WP_Error on failure.
     * @global \wpdb     $wpdb WordPress database abstraction object.
     */
    protected function trash($id)
    {
        global $wpdb;
        if (
            false ===
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table delete; object cache cleared by caller.
            $wpdb->delete($wpdb->prefix . $this->table, [
                $this->primary_key => $id,
            ])
        ) {
            return new \WP_Error(
                "db_delete_error",
                sprintf(
                    "Could not delete item from the database error %s",
                    esc_html($wpdb->last_error),
                ),
            );
        }
        return true;
    }
    /**
     * Save the object to the database.
     *
     * @since 1.0.0
     * @return \WP_Error|static WP_Error on failure, or the object on success.
     */
    public function save()
    {
        /**
         * Filters before saving an item for sanity checks.
         *
         * @param false|null $check Whether to go forward with saving.
         * @param array      $data Data to be saved.
         * @param static     $item Model object.
         *
         * @since 1.0.0
         */
        $check = $this->apply_model_filter('_check_save', null, $this);
        if (null !== $check) {
            return $check;
        }
        // If creator is enabled, set creator.
        if ($this->has_author) {
            $this->set_author();
        }
        // If timestamps are enabled, set date_created and date_updated.
        if ($this->has_timestamps) {
            $this->set_timestamps();
        }
        /**
         * Fires before saving an item to the database.
         *
         * @param static $item Model object.
         *
         * @since 1.0.0
         */
        $this->do_model_action('_pre_save', $this);
        foreach ($this->get_transitionable() as $transitionable) {
            $old_value = array_key_exists($transitionable, $this->original)
                ? $this->original[$transitionable]
                : null;
            $new_value = $this->get($transitionable);
            if ($old_value !== $new_value) {
                /**
                 * Fires before a transitionable property is updated.
                 *
                 * @param Model $item Model object.
                 * @param mixed $new_value New value.
                 * @param mixed $old_value Old value.
                 *
                 * @since 1.0.0
                 */
                $this->do_model_action(
                    '_pre_' . $transitionable . '_transition',
                    $this,
                    $new_value,
                    $old_value,
                );
                /**
                 * Fires before a transitionable property is updated.
                 *
                 * @param Model $item Model object.
                 * @param mixed $old_value Old value.
                 *
                 * @since 1.0.0
                 */
                $this->do_model_action(
                    '_pre_' . $transitionable . '_' . $new_value,
                    $this,
                    $old_value,
                );
            }
        }
        if (!$this->exists()) {
            $data = wp_array_slice_assoc(
                $this->get_attributes(),
                $this->get_columns(),
            );
            /**
             * Fires before an item is inserted in the database.
             *
             * @param Model $item Model object.
             * @param array $data Data to be inserted.
             *
             * @since 1.0.0
             */
            $this->do_model_action('_pre_insert', $this, $data);
            /**
             * Filters the data to be inserted.
             *
             * @param array $data Data to be inserted.
             * @param Model $item Model object.
             *
             * @since 1.0.0
             */
            $data = $this->apply_model_filter('_insert_data', $data, $this);
            $insert_id = $this->create($data);
            if (is_wp_error($insert_id)) {
                return $insert_id;
            }
            $this->set($this->primary_key, $insert_id);
            $data[$this->primary_key] = $insert_id;
            /**
             * Fires after an item is inserted in the database.
             *
             * @param Model $item Model object.
             * @param array $data Data inserted.
             *
             * @since 1.0.0
             */
            $this->do_model_action('_inserted', $this, $data);
        } elseif (
            !empty(
                array_intersect_key(
                    $this->get_changes(),
                    array_flip($this->get_columns()),
                )
            )
        ) {
            $updates = $this->get_changes();
            /**
             * Fires before an item is updated in the database.
             *
             * @param Model $item Model object.
             * @param array $updates Data to be updated.
             *
             * @since 1.0.0
             */
            $this->do_model_action('_pre_update', $this, $updates);
            /**
             * Filters the data to be updated.
             *
             * @param array $updates Data to be updated.
             * @param Model $item Model object.
             *
             * @since 1.0.0
             */
            $updates = $this->apply_model_filter('_update_data', $updates, $this);
            $data = array_intersect_key(
                $updates,
                array_flip($this->get_columns()),
            );
            // using wp_array_slice_assoc will ignore null values.
            unset($data[$this->primary_key]);
            $return = $this->update($this->get_key_value(), $data);
            if (is_wp_error($return)) {
                return $return;
            }
            /**
             * Fires after an item is updated in the database.
             *
             * @param Model $item Model object.
             * @param array $updates Data updated.
             *
             * @since 1.0.0
             */
            $this->do_model_action('_updated', $this, $updates);
        }
        // Transition effects.
        foreach ($this->get_transitionable() as $transitionable) {
            $old_value = array_key_exists($transitionable, $this->original)
                ? $this->original[$transitionable]
                : null;
            $new_value = $this->get($transitionable);
            if ($this->is_dirty($transitionable)) {
                /**
                 * Fires when a transitionable property is updated.
                 *
                 * @param Model $item Model object.
                 * @param mixed $new_value New value.
                 * @param mixed $old_value Old value.
                 *
                 * @since 1.0.0
                 */
                $this->do_model_action(
                    '_' . $transitionable . '_transition',
                    $this,
                    $new_value,
                    $old_value,
                );
                /**
                 * Fires when a transitionable property is updated.
                 *
                 * @param Model $item Model object.
                 * @param mixed $old_value Old value.
                 *
                 * @since 1.0.0
                 */
                $this->do_model_action(
                    '_' . $transitionable . '_' . $new_value,
                    $this,
                    $old_value,
                );
            }
        }
        // Save metadata.
        $this->save_metadata();
        $this->sync_original();
        $this->flush_cache();
        /**
         * Fires after an item is saved.
         *
         * @param static $item Model object.
         *
         * @since 1.0.0
         */
        $this->do_model_action('_saved', $this);
        // Now save the relations.
        if (!empty($this->relations)) {
            foreach ($this->relations as $relation) {
                if (
                    is_subclass_of($relation, static::class) &&
                    $relation->is_dirty()
                ) {
                    $relation->save();
                }
            }
        }
        return $this;
    }
    /**
     * Delete the object from the database.
     *
     * @since 1.0.0
     * @return true|\WP_Error True on success, WP_Error on failure.
     */
    public function delete()
    {
        if (!$this->exists()) {
            return new \WP_Error(
                "invalid_object",
                "Cannot delete an object that does not exist.",
            );
        }
        /**
         * Filters whether an item should be deleted.
         *
         * @param false|null $delete Whether to go forward with deletion.
         * @param static     $item Model object.
         *
         * @since 1.0.0
         */
        $check = $this->apply_model_filter('_check_delete', null, $this);
        if (null !== $check) {
            return $check;
        }
        $data = $this->to_array();
        /**
         * Fires immediately before an item is deleted from the database.
         *
         * @param static $item Model object.
         * @param array  $data Model data array.
         *
         * @since 1.0.0
         */
        $this->do_model_action('_pre_delete', $this, $data);
        $return = $this->trash($this->get_key_value());
        if (is_wp_error($return)) {
            return $return;
        }
        /**
         * Fires after an item is deleted from the database.
         *
         * @param static $item Model object.
         * @param array  $data Model data array.
         *
         * @since 1.0.0
         */
        $this->do_model_action('_deleted', $this, $data);
        // Delete metadata.
        $this->delete_metadata();
        $this->flush_cache();
        $this->attributes = array_fill_keys($this->attributes, null);
        return true;
    }
    /*
    |--------------------------------------------------------------------------
    | Query Methods
    |--------------------------------------------------------------------------
    | This section contains methods for querying the model, such as retrieving
    | query objects, results, counts, and finding instances.
    |--------------------------------------------------------------------------
    */
    /**
     * Get the query object for the model.
     *
     * @param array $args Optional. The query arguments.
     *
     * @since 1.0.0
     * @return Query|array|static[]|int Query object, or list of items, or count.
     */
    public function query($args = null)
    {
        $query = new Query($this);
        $query->set($this->get_query_vars());
        $query->set("search_columns", $this->get_searchable());
        if (!is_null($args)) {
            return $query->query($args);
        }
        return $query;
    }
    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    | This section contains utility methods that are not directly related to this
    | object but can be used to support its functionality.
    |--------------------------------------------------------------------------
    */
    /**
     * Determine if the object exists in the database.
     *
     * @since 1.0.0
     * @return bool
     */
    public function exists()
    {
        return !empty($this->get_key_value());
    }

    /**
     * Build a single-row SELECT SQL statement for read().
     *
     * @since 1.0.0
     *
     * @param string $where_sql Prepared WHERE clause fragments joined with AND.
     * @return string
     */
    protected function build_read_sql($where_sql)
    {
        global $wpdb;

        $table = $this->table;
        if (!is_string($table) || !preg_match('/^[A-Za-z0-9_]+$/', $table)) {
            return '';
        }

        $qualified_table = esc_sql($wpdb->prefix . $table);

        return "SELECT * FROM `{$qualified_table}` WHERE 1=1 AND {$where_sql}";
    }

    /**
     * Execute a read-only SQL query returning a single row.
     *
     * User-supplied values are bound via $wpdb->prepare(); column identifiers are
     * allowlisted on the model.
     *
     * @since 1.0.0
     *
     * @param string $sql SQL query string.
     * @return object|null
     */
    protected function db_get_row($sql)
    {
        global $wpdb;

        // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- User values bound via $wpdb->prepare(); read() caches results with wp_cache_get()/wp_cache_set().
        return $wpdb->get_row($sql);
    }

    /**
     * Fire a model action hook with the contract_pilot_ prefix.
     *
     * @since 1.0.0
     *
     * @param string $suffix Hook suffix (for example `_saved`).
     * @param mixed  ...$args Arguments passed to the hook.
     * @return void
     */
    public function do_model_action($suffix, ...$args)
    {
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- Uses contract_pilot_ prefix via get_hook_prefix().
        do_action($this->get_hook_prefix() . $suffix, ...$args);
    }

    /**
     * Apply a model filter hook with the contract_pilot_ prefix.
     *
     * @since 1.0.0
     *
     * @param string $suffix Hook suffix (for example `_attributes`).
     * @param mixed  $value  Value to filter.
     * @param mixed  ...$args Additional arguments passed to the filter.
     * @return mixed
     */
    public function apply_model_filter($suffix, $value, ...$args)
    {
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- Uses contract_pilot_ prefix via get_hook_prefix().
        return apply_filters($this->get_hook_prefix() . $suffix, $value, ...$args);
    }

    /**
     * Get hook prefix. Default is the object type.
     *
     * @since 1.0.0
     * @return string
     */
    public function get_hook_prefix()
    {
        return 'contract_pilot_' . $this->get_object_type();
    }
    /**
     * Reload the object from the database.
     *
     * @since 1.0.0
     * @return static The model instance.
     */
    public function reload()
    {
        if (!$this->exists()) {
            return $this;
        }
        // unset cached data.
        wp_cache_delete($this->get_key_value(), $this->get_cache_group());
        $data = $this->read($this->get_key_value());
        if (!empty($data)) {
            $this->fill($data)->read_metadata()->sync_original();
        }
        return $this;
    }
    /**
     * Flush cache.
     *
     * @since 1.0.0
     * @return void
     */
    public function flush_cache()
    {
        wp_cache_flush_group($this->cache_group);
        wp_cache_set_last_changed($this->cache_group);
    }
    /**
     * Get a new instance of the model.
     *
     * @param mixed $attributes The model attributes.
     *
     * @since 1.0.0
     * @return static
     */
    public function new_instance($attributes = null)
    {
        return new static($attributes);
    }
    /**
     * Singularize a string.
     *
     * @param string $subject The string to singularize.
     *
     * @since 1.0.0
     * @return string
     */
    public function singularize($subject)
    {
        return preg_replace(
            ['/ies$/', '/ves$/', '/(?!s)es$/', '/s$/'],
            ["y", "f", "", ""],
            $subject,
        );
    }
    /**
     * Pluralize a string.
     *
     * @param string $subject The string to pluralize.
     *
     * @since 1.0.0
     * @return string
     */
    public function pluralize($subject)
    {
        $subject = $this->singularize($subject);
        return preg_replace(
            ['/y$/', '/f$/', '/fe$/', '/o$/', '/$/'],
            ["ies", "ves", "ves", "oes", "s"],
            $subject,
        );
    }
}
