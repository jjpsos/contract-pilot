<?php

namespace Jjpsos\ContractPilot\Database\Traits;

use Jjpsos\ContractPilot\Database\Relations\BelongsTo;
use Jjpsos\ContractPilot\Database\Relations\BelongsToMany;
use Jjpsos\ContractPilot\Database\Relations\HasMany;
use Jjpsos\ContractPilot\Database\Relations\HasOne;
use Jjpsos\ContractPilot\Database\Relations\Relation;

/**
 * A trait to manage relations between models.
 *
 * @since   1.0.0
 * @version 1.0.5
 * @author  Sultan Nasir Uddin <manikdrmc@gmail.com>
 * @package \ByteKit/Models
 * @license GPL-3.0+
 */
trait HasRelations
{
    /**
     * Relations data.
     *
     * @since 1.0.0
     * @var array
     */
    protected $relations = [];
    /**
     * Has one Relation.
     *
     * @param string $related Related model class name.
     * @param string $foreign_key Optional. e.g. 'product_id' in the related model.
     * @param string $local_key Optional.  e.g. 'id' in the parent model.
     *
     * @since 1.0.0
     *
     * @return HasOne
     */
    protected function has_one($related, $foreign_key = null, $local_key = null)
    {
        $instance = new $related();
        $foreign_key =
            $foreign_key ??
            $this->get_object_type() . "_" . $this->get_key_name();
        $local_key = $local_key ?? $this->get_key_name();
        return new HasOne($this, $instance, $foreign_key, $local_key);
    }
    /**
     * Has many Relation.
     *
     * @param string $related Related model class name.
     * @param string $foreign_key Optional. e.g. 'product_id' in the related model.
     * @param string $local_key Optional.  e.g. 'id' in the parent model.
     *
     * @since 1.0.0
     *
     * @return HasMany
     */
    protected function has_many(
        $related,
        $foreign_key = null,
        $local_key = null
    ) {
        $instance = new $related();
        $foreign_key =
            $foreign_key ??
            $this->get_object_type() . "_" . $this->get_key_name();
        $local_key = $local_key ?? $this->get_key_name();
        return new HasMany($this, $instance, $foreign_key, $local_key);
    }
    /**
     * Belongs to Relation.
     *
     * @param string $related Related model class name.
     * @param string $foreign_key Optional. e.g. 'order_id' in the parent model.
     * @param string $parent_key Optional. e.g. 'id' in the related model.
     *
     * @since 1.0.0
     *
     * @return BelongsTo
     */
    protected function belongs_to(
        $related,
        $foreign_key = null,
        $parent_key = null
    ) {
        $instance = new $related();
        $foreign_key =
            $foreign_key ??
            $instance->get_object_type() . "_" . $instance->get_key_name();
        $parent_key = $parent_key ?? $instance->get_key_name();
        return new BelongsTo($instance, $this, $foreign_key, $parent_key);
    }
    /**
     * Belongs to many Relation.
     *
     * @param string $related Related model class name.
     * @param string $foreign_key Optional. e.g. 'product_id' in the related model.
     * @param string $parent_key Optional. e.g. 'id' in the parent model.
     *
     * @since 1.0.0
     *
     * @return BelongsToMany
     */
    protected function belongs_to_many(
        $related,
        $foreign_key = null,
        $parent_key = null
    ) {
        $instance = new $related();
        $foreign_key =
            $foreign_key ??
            $instance->get_object_type() .
                "_" .
                $this->pluralize($instance->get_key_name());
        $parent_key = $parent_key ?? $instance->get_key_name();
        return new BelongsToMany($instance, $this, $foreign_key, $parent_key);
    }
    /**
     * Get all the loaded relations for the instance.
     *
     * @since 1.0.0
     * @return array
     */
    public function get_relations()
    {
        return $this->relations;
    }
    /**
     * Set the entire relations array on the model.
     *
     * @param array $relations The relations array.
     *
     * @return $this
     */
    public function set_relations(array $relations)
    {
        $this->relations = $relations;
        return $this;
    }
    /**
     * Get the given relationship value.
     *
     * @param string $key The relation key.
     *
     * @since 1.0.0
     * @return mixed
     */
    public function get_relation($key)
    {
        return $this->relations[$key] ?? null;
    }
    /**
     * Set the given relationship on the model.
     *
     * @param string         $relation The relation name.
     * @param mixed|callable $value The value to set.
     *
     * @since 1.0.0
     * @return $this
     */
    public function set_relation($relation, $value)
    {
        if (is_callable($value)) {
            $this->relations[$relation] = $value(
                $this->get_relation($relation),
            );
        } else {
            $this->relations[$relation] = $value;
        }
        return $this;
    }
    /**
     * Unset the given relation on the model.
     *
     * @param string $relation The relation name.
     *
     * @since 1.0.0
     * @return $this
     */
    public function unset_relation($relation)
    {
        unset($this->relations[$relation]);
        return $this;
    }
    /**
     * Determine if the given relation is loaded.
     *
     * @param string $key The relation key.
     *
     * @return bool
     */
    public function relation_loaded($key)
    {
        return array_key_exists($key, $this->relations);
    }
    /**
     * Determine if the given key is a Relation method on the model.
     *
     * @param string $key The key.
     *
     * @since 1.0.0
     * @return bool
     */
    public function has_relation($key)
    {
        return !method_exists($this, "get_{$key}_attribute") &&
            method_exists($this, $key) &&
            is_a($this->{$key}(), Relation::class);
    }
    /**
     * Get a relationship.
     *
     * @param string $key The relation key.
     *
     * @return mixed|void The relation value or null.
     */
    public function get_relation_value($key)
    {
        if ($this->relation_loaded($key)) {
            return $this->relations[$key];
        }
        if (!$this->has_relation($key)) {
            return;
        }
        $relation = $this->{$key}();
        if (!$relation instanceof Relation) {
            return null;
        }
        $results = $relation->get_results();
        $this->set_relation($key, $results);
        return $results;
    }
}
