<?php

namespace Jjpsos\ContractPilot\Database\Traits;

/**
 * A trait to manage meta data for models.
 *
 * @since   1.0.0
 * @version 1.0.5
 * @author  Sultan Nasir Uddin <manikdrmc@gmail.com>
 * @package \ByteKit/Models
 * @license GPL-3.0+
 */
trait HasMetaData
{
    /**
     * Meta type declaration for the object.
     *
     * @since 1.0.0
     * @var string
     */
    protected $meta_type = false;
    /**
     * Metadata for the object.
     *
     * @since 1.0.0
     * @var array
     */
    protected $metadata = [];
    /**
     * Get metadata type.
     *
     * @since 1.0.0
     * @return string
     */
    public function get_meta_type()
    {
        return $this->meta_type;
    }
    /**
     * Get a meta value.
     *
     * @param string $key The meta key.
     *
     * @since 1.0.0
     * @return mixed
     */
    public function get_meta($key)
    {
        if (empty($key)) {
            return null;
        }
        $getter = "get_" . $key . "_meta";
        if (method_exists($this, $getter)) {
            return $this->{$getter}();
        }
        if (array_key_exists($key, $this->metadata)) {
            return $this->cast($key, $this->metadata[$key]);
        }
        return null;
    }
    /**
     * Set a meta value.
     *
     * @param string $key The meta key.
     * @param mixed  $value The meta value.
     *
     * @since 1.0.0
     * @return $this
     */
    public function set_meta($key, $value)
    {
        if (empty($key)) {
            return $this;
        }
        $setter = "set_" . $key . "_meta";
        if (method_exists($this, $setter)) {
            $this->{$setter}($value);
        } else {
            $this->metadata[$key] = $this->cast($key, $value);
        }
        return $this;
    }
    /**
     * Delete a meta value.
     *
     * @param string $key The meta key.
     *
     * @since 1.0.0
     * @return $this
     */
    public function delete_meta($key)
    {
        if (array_key_exists($key, $this->metadata)) {
            $this->metadata[$key] = null;
        }
        return $this;
    }
    /**
     * Set metadata attributes.
     *
     * @param array $metadata The metadata attributes.
     *
     * @since 1.0.0
     * @return $this
     */
    public function set_metadata($metadata)
    {
        foreach ($metadata as $key => $value) {
            $this->set_meta($key, $value);
        }
        return $this;
    }
    /**
     * Get metadata attributes.
     *
     * @since 1.0.0
     * @return array
     */
    public function get_metadata()
    {
        return $this->metadata;
    }
    /**
     * Loads the metadata.
     *
     * @since 1.0.0
     * @return $this
     */
    public function read_metadata()
    {
        if ($this->get_meta_type() && $this->exists()) {
            $raw_meta = get_metadata($this->meta_type, $this->get_key_value());
            $metadata = [];
            foreach ($raw_meta as $key => $value) {
                $value = is_array($value) ? $value[0] : $value;
                $value = maybe_unserialize($value);
                $metadata[$key] = $value;
            }
            /**
             * Filters the meta data for a specific meta object.
             *
             * @param array  $metadata Array of metadata for the given object.
             * @param static $object Object object.
             *
             * @since 1.0.0
             */
            $metadata = $this->apply_model_filter('_metadata', $metadata, $this);
            foreach ($metadata as $key => $value) {
                $this->set_meta($key, $value);
            }
        }
        return $this;
    }
    /**
     * Saves the metadata.
     *
     * @since 1.0.0
     * @return $this
     */
    public function save_metadata()
    {
        if ($this->get_meta_type() && $this->exists()) {
            foreach ($this->metadata as $key => $value) {
                if (is_null($value)) {
                    delete_metadata(
                        $this->get_meta_type(),
                        $this->get_key_value(),
                        $key,
                    );
                } else {
                    update_metadata(
                        $this->get_meta_type(),
                        $this->get_key_value(),
                        $key,
                        $value,
                    );
                }
            }
        }
        return $this;
    }
    /**
     * Deletes the metadata.
     *
     * @since 1.0.0
     */
    public function delete_metadata()
    {
        if ($this->get_meta_type() && $this->exists()) {
            global $wpdb;
            $meta_table = $this->get_meta_type() . "meta";
            $field_name = $this->get_meta_type() . "_id";
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin-owned meta table row delete on model trash.
            $wpdb->delete($wpdb->prefix . $meta_table, [
                $field_name => $this->get_key_value(),
            ]);
            $this->metadata = [];
            wp_cache_delete(
                $this->get_key_value(),
                $this->get_meta_type() . "_meta",
            );
        }
        return $this;
    }
}
