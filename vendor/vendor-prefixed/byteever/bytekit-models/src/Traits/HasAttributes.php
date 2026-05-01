<?php

namespace Otto\ByteKit\Models\Traits;

/**
 * A trait to manage model's attributes.
 *
 * @since   1.0.0
 * @version 1.0.5
 * @author  Sultan Nasir Uddin <manikdrmc@gmail.com>
 * @package \ByteKit/Models
 * @license GPL-3.0+
 */
trait HasAttributes
{
    /**
     * The model's attributes.
     *
     * This array holds the key-value pairs representing the current state of the model's attributes.
     * Each key corresponds to an attribute name, and its value is the attribute's current value.
     *
     * @since 1.0.0
     * @var array
     */
    protected $attributes = [];
    /**
     * The original attributes values.
     *
     * This array stores the original values of the attributes before any modifications were made.
     * It is useful for tracking changes and determining what has been altered in the model.
     *
     * @since 1.0.0
     * @var array
     */
    protected $original = [];
    /**
     * The attributes that should be cast.
     *
     * This array defines how certain attributes should be cast to specific data types.
     * For example, you can specify that a date attribute should always be cast to a DateTime object.
     *
     * @since 1.0.0
     * @var array
     */
    protected $casts = [];
    /**
     * The attributes that have aliases.
     *
     * This array maps attribute names to their aliases.
     * Aliases allow you to reference attributes by different names, which can be useful for readability or compatibility.
     *
     * @since 1.0.0
     * @var array
     */
    protected $aliases = [];
    /**
     * The accessors to append to the model's array form.
     *
     * This array lists the attribute names that should be included when the model is converted to an array.
     * It is particularly useful for including computed attributes that are not part of the database schema.
     *
     * @since 1.0.0
     * @var array
     */
    protected $appends = [];
    /**
     * The attributes that should be hidden for serialization.
     *
     * This array specifies which attributes should be excluded from the model's serialized representation.
     * It is often used to prevent sensitive information from being exposed in JSON or other serialized formats.
     *
     * @since 1.0.0
     * @var array
     */
    protected $hidden = [];
    /**
     * Indicates if the model should be timestamped.
     *
     * This boolean flag determines if the model should automatically manage `created_at` and `updated_at` timestamps.
     *
     * @since 1.0.0
     * @var bool
     */
    protected $has_timestamps = false;
    /**
     * Indicates if the model has an author.
     *
     * This boolean flag determines if the model should automatically manage a `author_id` attribute,
     * typically used to track the user who created the model instance.
     *
     * @since 1.0.0
     * @var bool
     */
    protected $has_author = false;
    /**
     * Get the value of an attribute.
     *
     * @param string $key The name of the attribute.
     *
     * @since 1.0.0
     * @return mixed|void The value of the attribute or void if not found.
     */
    public function get($key)
    {
        // if key is empty, return null.
        if (empty($key)) {
            return;
        }
        $getter = "get_" . $key . "_attribute";
        if (method_exists($this, $getter)) {
            return $this->{$getter}();
        }
        // metadata handler.
        if ("metadata" === $key && method_exists($this, "get_metadata")) {
            return $this->get_metadata();
        }
        if (
            array_key_exists($key, $this->attributes) ||
            array_key_exists($key, $this->aliases) ||
            method_exists(self::class, "get_" . $key . "_attribute")
        ) {
            $key = $this->get_unaliased($key);
            $getter = "get_" . $key . "_attribute";
            if (method_exists($this, $getter)) {
                return $this->{$getter}();
            }
            $value = array_key_exists($key, $this->attributes)
                ? $this->attributes[$key]
                : null;
            return $this->cast($key, $value);
        }
        // if starts with __ then get meta value.
        if (str_starts_with($key, "__")) {
            $meta_key = substr($key, 2);
            return $this->get_meta($meta_key);
        }
        // Here we will determine if the model base class itself contains this given key
        // since we don't want to treat any of those methods as relationships because
        // they are all intended as helper methods and none of these are relations.
        if (method_exists(self::class, $key)) {
            return;
        }
        return $this->get_relation_value($key);
    }
    /**
     * Set the value of an attribute.
     *
     * @param string $key The name of the attribute.
     * @param mixed  $value The value of the attribute.
     *
     * @since 1.0.0
     * @return $this
     */
    public function set($key, $value)
    {
        // if key is empty, return null.
        if (empty($key)) {
            return $this;
        }
        $setter = "set_" . $key . "_attribute";
        if (method_exists($this, $setter)) {
            $this->{$setter}($value);
            return $this;
        }
        // metadata handler.
        if (
            "metadata" === $key &&
            method_exists($this, "set_metadata") &&
            is_array($value)
        ) {
            return $this->set_metadata($value);
        }
        if (
            array_key_exists($key, $this->attributes) ||
            array_key_exists($key, $this->aliases) ||
            method_exists(self::class, "set_" . $key . "_attribute")
        ) {
            $key = $this->get_unaliased($key);
            $setter = "set_" . $key . "_attribute";
            if (method_exists($this, $setter)) {
                $this->{$setter}($value);
                return $this;
            }
            $this->attributes[$key] = $this->cast($key, $value);
            return $this;
        }
        // if starts with __ then set meta value.
        if (str_starts_with($key, "__")) {
            $meta_key = substr($key, 2);
            return $this->set_meta($meta_key, $value);
        }
        // Here we will determine if the model base class itself contains this given key
        // since we don't want to treat any of those methods as relationships because
        // they are all intended as helper methods and none of these are relations.
        if (method_exists(self::class, $key)) {
            return $this;
        }
        $this->attributes[$key] = $this->cast($key, $value);
        return $this;
    }
    /**
     * Get the model's attributes.
     *
     * @since 1.0.0
     * @return array
     */
    public function get_attributes()
    {
        return $this->attributes;
    }
    /**
     * Get the original data of the model.
     *
     * @param string $key The name of the attribute.
     *
     * @since 1.0.0
     * @return array
     */
    public function get_original($key = null)
    {
        if (!is_null($key)) {
            return array_key_exists($key, $this->original)
                ? $this->original[$key]
                : null;
        }
        return $this->original;
    }
    /**
     * Apply changes to the model attributes.
     *
     * @since 1.0.0
     * @return $this
     */
    public function sync_original()
    {
        $this->original = $this->attributes;
        return $this;
    }
    /**
     * Get the props that have been changed since the last sync.
     *
     * @since 1.0.0
     * @return array
     */
    public function get_changes()
    {
        $changed = [];
        foreach (array_keys($this->attributes) as $key) {
            if ($this->is_dirty($key)) {
                $changed[$key] = $this->get($key);
            }
        }
        return $changed;
    }
    /**
     * Determine if the model or given prop has been modified.
     *
     * @param string $key The prop key.
     *
     * @since 1.0.0
     * @return bool
     */
    public function is_dirty($key = null)
    {
        if (is_null($key)) {
            return count($this->get_changes()) > 0;
        }
        if (!array_key_exists($key, $this->original)) {
            return true;
        }
        $current = array_key_exists($key, $this->attributes)
            ? $this->attributes[$key]
            : null;
        $original = array_key_exists($key, $this->original)
            ? $this->original[$key]
            : null;
        if ($current === $original) {
            return false;
        } elseif (is_null($current)) {
            return true;
        } elseif (
            is_numeric($current) &&
            is_numeric($original) &&
            strcmp((string) $current, (string) $original) !== 0
        ) {
            return true;
        }
        return $this->cast($key, $current) !== $this->cast($key, $original);
    }
    /**
     * Get the props that should be cast.
     *
     * @since 1.0.0
     * @return array
     */
    public function get_casts()
    {
        return $this->casts;
    }
    /**
     * Set the casts for the model.
     *
     * @param array $casts The casts to set.
     *
     * @since 1.0.0
     * @return $this
     */
    public function set_casts($casts)
    {
        $this->casts = array_merge($this->casts, $casts);
        return $this;
    }
    /**
     * Cast an attribute to a native PHP type.
     *
     * @param string|array $key The attribute key.
     * @param mixed        $value The value to cast.
     *
     * @since 1.0.0
     * @return mixed
     */
    public function cast($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $key[$k] = $this->cast($k, $v);
            }
            return $key;
        }
        if (is_null($value)) {
            return $value;
        }
        $cast = array_key_exists($key, $this->casts)
            ? $this->casts[$key]
            : null;
        switch ($cast) {
            case "int":
            case "integer":
                $value = (int) $value;
                break;
            case "real":
            case "float":
            case "double":
                $value = (float) $value;
                break;
            case "bool":
            case "boolean":
                $value = (bool) filter_var($value, FILTER_VALIDATE_BOOLEAN);
                break;
            case "tinyint":
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
                break;
            case "object":
                $value = (object) maybe_unserialize($value);
                break;
            case "array":
                $value = (array) maybe_unserialize($value);
                $value = is_array($value) ? $value : [];
                $value = wp_parse_list(array_filter($value));
                break;
            case "date":
                $value = !empty($this->cast_timestamp($value))
                    ? (new \DateTime($value))->format("Y-m-d")
                    : null;
                break;
            case "time":
                $value = !empty($this->cast_timestamp($value))
                    ? (new \DateTime($value))->format("H:i:s")
                    : null;
                break;
            case "datetime":
                $value = !empty($this->cast_timestamp($value))
                    ? (new \DateTime($value))->format("Y-m-d H:i:s")
                    : null;
                break;
            case "timestamp":
                $value = !empty($this->cast_timestamp($value))
                    ? (int) $this->cast_timestamp($value)
                    : null;
                break;
            case "sanitize_textarea":
                $value = sanitize_textarea_field($value);
                break;
            case "sanitize_email":
                $value = sanitize_email($value);
                break;
            case "sanitize_url":
                $value = esc_url_raw($value);
                break;
            case "sanitize_title":
                $value = sanitize_title($value);
                break;
            case "sanitize_key":
                $value = sanitize_key($value);
                break;
            case "id_list":
                $value = wp_parse_id_list($value);
                $value = implode(",", $value);
                break;
            case "list":
                $value = wp_parse_list($value);
                $value = implode(",", $value);
                break;
            case "sanitize_text":
            default:
                if (is_callable($cast)) {
                    $value = call_user_func($cast, $value);
                } elseif (is_string($cast) && function_exists($cast)) {
                    $value = $cast($value);
                } elseif (is_string($cast) && strpos($cast, "::") === 0) {
                    $method = substr($cast, 2);
                    if (method_exists($this, $method)) {
                        $value = $this->{$method}($value);
                    }
                } elseif (is_array($cast)) {
                    $value = map_deep($value, "sanitize_text_field");
                } else {
                    $value = is_scalar($value)
                        ? sanitize_text_field($value)
                        : $value;
                }
                break;
        }
        return $value;
    }
    /**
     * Cast the given attribute to a date.
     *
     * @param mixed $value The value to cast.
     *
     * @since 1.0.0
     * @return string|null The cast timestamp.
     */
    public function cast_timestamp($value)
    {
        if (empty($value)) {
            return null;
        }
        $datetime = date_parse($value);
        // if it contains any errors or warnings, return null.
        if (
            !empty($datetime["error_count"]) ||
            !empty($datetime["warning_count"])
        ) {
            return null;
        }
        // if it is not a valid date, return null.
        if (
            !checkdate($datetime["month"], $datetime["day"], $datetime["year"])
        ) {
            return null;
        }
        // if it is not a valid time, return null.
        if (
            !empty($datetime["hour"]) &&
            !empty($datetime["minute"]) &&
            !empty($datetime["second"])
        ) {
            if (
                $datetime["hour"] < 0 ||
                $datetime["hour"] >= 24 ||
                $datetime["minute"] < 0 ||
                $datetime["minute"] >= 60 ||
                $datetime["second"] < 0 ||
                $datetime["second"] >= 60
            ) {
                return null;
            }
        }
        return strtotime($value);
    }
    /**
     * Get the props that have aliases.
     *
     * @since 1.0.0
     * @return array
     */
    public function get_aliases()
    {
        return $this->aliases;
    }
    /**
     * Get the attribute name for the alias.
     *
     * @param string|array $alias The alias.
     *
     * @since 1.0.0
     * @return string|array The attribute name or array of attribute names.
     */
    public function get_unaliased($alias)
    {
        if (is_array($alias)) {
            $unaliased = [];
            foreach ($alias as $column => $value) {
                $unaliased[$this->get_unaliased($column)] = $value;
            }
            return $unaliased;
        }
        return isset($this->aliases[$alias]) ? $this->aliases[$alias] : $alias;
    }
    /**
     * Get the accessors to append to the model's array form.
     *
     * @since 1.0.0
     * @return array
     */
    public function get_appends()
    {
        return $this->appends;
    }
    /**
     * Append attributes to the model's array form.
     *
     * @param array|string $attributes The attributes to append.
     *
     * @return $this
     */
    public function append($attributes)
    {
        $this->appends = array_unique(
            array_merge(
                $this->appends,
                is_string($attributes) ? func_get_args() : $attributes,
            ),
        );
        return $this;
    }
    /**
     * Get the hidden attributes for the model.
     *
     * @since 1.0.0
     * @return array
     */
    public function get_hidden()
    {
        return $this->hidden;
    }
    /**
     * Set the hidden attributes for the model.
     *
     * @param array|string $hidden The attributes to hide.
     *
     * @since 1.0.0
     * @return $this
     */
    public function set_hidden($hidden)
    {
        $this->hidden = array_unique(
            array_merge(
                $this->hidden,
                is_string($hidden) ? func_get_args() : $hidden,
            ),
        );
        return $this;
    }
    /**
     * Update the creation and update timestamps.
     *
     * @since 1.0.0
     * @return void
     */
    public function set_timestamps()
    {
        $now = current_time("mysql", true);
        $date_created = $this->get(static::DATE_CREATED);
        // Set date_created if creating and not set already.
        if (!$this->exists() && empty($date_created)) {
            $this->set(static::DATE_CREATED, $now);
            $this->set(static::DATE_UPDATED, null);
        }
        // Set date_updated if updating and not set already.
        if ($this->exists() && $this->get_changes()) {
            $this->set(static::DATE_UPDATED, $now);
        }
    }
    /**
     * Update the creator ID.
     *
     * @since 1.0.0
     * @return void
     */
    public function set_author()
    {
        $author_id = $this->get(static::AUTHOR_ID);
        // Set author_id if creating and not set already.
        if (!$this->exists() && empty($author_id)) {
            $this->set(static::AUTHOR_ID, get_current_user_id());
        }
    }
}
