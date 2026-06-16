<?php

namespace Jjpsos\ContractPilot\Database\Relations;

use Jjpsos\ContractPilot\Database\Model;

/**
 * A class to manage belongs to many relationship.
 *
 * @since   1.0.0
 * @version 1.0.5
 * @author  Sultan Nasir Uddin <manikdrmc@gmail.com>
 * @package \ByteKit/Models
 * @license GPL-3.0+
 */
class BelongsToMany extends Relation
{
    /**
     * The child model instance of the relation.
     *
     * @since 1.0.0
     * @var Model
     */
    protected $child;
    /**
     * The foreign key of the parent model.
     *
     * @var string
     */
    protected $foreign_key;
    /**
     * The local key of the parent model.
     *
     * @var string
     */
    protected $owner_key;
    /**
     * Create a new belongs to relationship instance.
     *
     * @param Model  $_parent Parent model instance.
     * @param Model  $child Related model instance.
     * @param string $foreign_key Foreign key of the parent model.
     * @param string $owner_key Owner key of the related model.
     *
     * @return void
     */
    public function __construct($_parent, $child, $foreign_key, $owner_key)
    {
        $this->owner_key = $owner_key;
        $this->foreign_key = $foreign_key;
        $this->child = $child;
        parent::__construct($child, $_parent);
        // In this case, the parent is the child.
    }
    /**
     * Set the constraints for the relation.
     *
     * @since 1.0.0
     * @return void
     */
    protected function set_constraints()
    {
        $this->set([
            "{$this->owner_key}__in" => $this->child->get($this->foreign_key),
            "{$this->owner_key}__exists" => true,
        ]);
    }
    /**
     * Get the results of the relationship.
     *
     * @since 1.0.0
     * @return Model[] The results.
     */
    public function get_results()
    {
        if (!$this->child->exists() || empty($this->get_child_key())) {
            return [];
        }
        return $this->query->get_results();
    }
    /**
     * Find or create an un-saved instance of the related model.
     *
     * @param array $attributes Attributes to set on the related model.
     *
     * @return Model The related model instance.
     */
    public function make($attributes = [])
    {
        return $this->related->make($attributes);
    }
    /**
     * Insert a new instance of the related model.
     *
     * @param array $attributes Properties to set on the related model.
     *
     * @return Model The related model instance.
     */
    public function insert($attributes = [])
    {
        $item = $this->related->new_instance($attributes);
        return $this->save($item);
    }
    /**
     * Save a new model and attach it to the parent model.
     *
     * @param Model $_parent The model to save.
     *
     * @since 1.0.0
     * @return Model The saved model.
     */
    public function save($_parent)
    {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Transaction control; must not be cached.
        $wpdb->query("START TRANSACTION");
        $retval = $_parent->save();
        if (is_wp_error($retval)) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Transaction control; must not be cached.
            $wpdb->query("ROLLBACK");
            return $retval;
        }
        $retval = $this->attach($_parent);
        if (is_wp_error($retval)) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Transaction control; must not be cached.
            $wpdb->query("ROLLBACK");
            return $retval;
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Transaction control; must not be cached.
        $wpdb->query("COMMIT");
        return $_parent;
    }
    /**
     * Attach a model to the parent.
     *
     * @param Model|string|int $_parent The related model instance.
     *
     * @since 1.0.0
     * @return \WP_Error|Model The related model instance or WP_Error on failure.
     */
    public function attach($_parent)
    {
        $owner_key =
            $_parent instanceof Model
                ? $_parent->get($this->owner_key)
                : $_parent;
        $values = $this->child->get($this->foreign_key);
        $values = wp_parse_list($values);
        $values[] = $owner_key;
        $this->child->set($this->foreign_key, $values);
        return $this->child->save();
    }
    /**
     * Detach a model from the parent.
     *
     * @param Model|string|int $related The related model instance.
     *
     * @since 1.0.0
     * @return \WP_Error|Model The related model instance or WP_Error on failure.
     */
    public function detach($related)
    {
        $owner_key =
            $related instanceof Model
                ? $related->get($this->owner_key)
                : $related;
        $values = $this->child->get($this->foreign_key);
        $values = wp_parse_list($values);
        if (!in_array($owner_key, $values, true)) {
            return $related;
        }
        $values = array_diff($values, [$owner_key]);
        $this->child->set($this->foreign_key, $values);
        return $this->child->save();
    }
    /**
     * Get the key value of the related model's foreign key.
     *
     * @since 1.0.0
     * @return mixed
     */
    public function get_child_key()
    {
        return $this->child->get($this->foreign_key);
    }
}
