<?php

namespace Otto\ByteKit\Models\Relations;

use Otto\ByteKit\Models\Model;
use Otto\ByteKit\Models\Query;
/**
 * A model class for handling relational model.
 *
 * @since   1.0.0
 * @version 1.0.5
 * @author  Sultan Nasir Uddin <manikdrmc@gmail.com>
 * @package \ByteKit/Models
 * @license GPL-3.0+
 */
abstract class Relation
{
    /**
     * The parent model instance.
     *
     * @var Model
     * @since 1.0.0
     */
    protected $parent;
    /**
     * The related model instance.
     *
     * @var Model
     * @since 1.0.0
     */
    protected $related;
    /**
     * The Query instance.
     *
     * @var Query
     * @since 1.0.0
     */
    protected $query;
    /**
     * Create a new relation instance.
     *
     * @param Model $_parent Parent model instance.
     * @param Model $related Related model instance.
     *
     * @return void
     */
    public function __construct($_parent, $related)
    {
        $this->parent = $_parent;
        $this->related = $related;
        $this->query = $related->query();
        $this->set_constraints();
    }
    /**
     * Handle dynamic method calls to the relationship.
     *
     * @param  string $method The method name.
     * @param  array  $parameters The method parameters.
     * @return mixed
     *
     * @throws \BadMethodCallException If the method does not exist.
     */
    public function __call($method, $parameters)
    {
        if (is_callable($this, $method)) {
            return $this->{$method}(...$parameters);
        } elseif (method_exists($this->query, $method)) {
            return $this->query->{$method}(...$parameters);
        }
        throw new \BadMethodCallException(
            esc_html("Method {$method} does not exist."),
        );
    }
    /**
     * Set the constraints for the relation.
     *
     * @since 1.0.0
     * @return void
     */
    abstract protected function set_constraints();
    /**
     * Get the results of the relationship.
     *
     * @since 1.0.0
     * @return Model[] The results.
     */
    abstract public function get_results();
    /**
     * Get the key value of the parent's local key.
     *
     * @since 1.0.0
     * @return mixed
     */
    public function get_parent_key()
    {
        return $this->parent->get($this->local_key);
    }
    /**
     * Get the key value of the related model's foreign key.
     *
     * @since 1.0.0
     * @return mixed
     */
    public function get_related_key()
    {
        return $this->related->get($this->foreign_key);
    }
    /**
     * Sets query variable.
     *
     * @param string|array $key Query variable key.
     * @param string|array $value Query variable value.
     *
     * @since 1.0.0
     *
     * @return $this
     */
    public function set($key, $value = null)
    {
        $this->query->set($key, $value);
        return $this;
    }
    /**
     * Get the name of the relation.
     *
     * @since 1.0.0
     * @return string The name of the relation.
     */
    public function get_relation_name()
    {
        $name = $this->related->get_object_type();
        return $this->related->pluralize($name);
    }
    /**
     * Delete the related model.
     *
     * @since 1.0.0
     * @return void
     */
    public function delete()
    {
        $items = $this->get_results();
        $items = is_array($items) ? $items : [$items];
        foreach ($items as $item) {
            if (is_subclass_of($item, Model::class) && $item->exists()) {
                $item->delete();
            }
        }
    }
}
