<?php

namespace Otto\ByteKit\Models\Relations;

use Otto\ByteKit\Models\Model;
/**
 * Has one relation class.
 *
 * @since   1.0.0
 * @version 1.0.5
 * @author  Sultan Nasir Uddin <manikdrmc@gmail.com>
 * @package \ByteKit/Models
 * @license GPL-3.0+
 */
class HasOne extends Relation
{
    /**
     * The foreign key of the parent model.
     *
     * @since 1.0.0
     * @var string
     */
    protected $foreign_key;
    /**
     * The local key of the parent model.
     *
     * @since 1.0.0
     * @var string
     */
    protected $local_key;
    /**
     * Create a new has one or many relationship instance.
     *
     * @param Model  $_parent Parent model instance.
     * @param Model  $related Related model instance.
     * @param string $foreign_key Foreign key of the parent model.
     * @param string $local_key Local key of the parent model.
     *
     * @return void
     */
    public function __construct($_parent, $related, $foreign_key, $local_key)
    {
        $this->local_key = $local_key;
        $this->foreign_key = $foreign_key;
        parent::__construct($_parent, $related);
    }
    /**
     * Add the constraints for the relation.
     *
     * @since 1.0.0
     * @return void
     */
    protected function set_constraints()
    {
        $this->set([
            $this->foreign_key => $this->get_parent_key(),
            "{$this->foreign_key}__exists" => true,
            "limit" => 1,
        ]);
    }
    /**
     * Get the results of the relationship.
     *
     * @since 1.0.0
     * @return Model The results.
     */
    public function get_results()
    {
        if (empty($this->get_parent_key())) {
            return null;
        }
        $items = $this->query->get_results();
        return $items ? $items[0] : null;
    }
    /**
     * Find or create an un-saved instance of the related model.
     *
     * @param array $attributes Properties to set on the related model.
     *
     * @return Model|mixed The related model instance.
     */
    public function make($attributes = [])
    {
        $attributes = array_merge($attributes, $this->get_foreign_attributes());
        return $this->related->make($attributes);
    }
    /**
     * Insert a new instance of the related model.
     *
     * @param array $attributes Attributes to set on the related model.
     *
     * @return Model The related model instance.
     */
    public function insert($attributes = [])
    {
        $relation = $this->get_results();
        $instance = $relation
            ? $relation->fill($attributes)
            : $this->make($attributes);
        return $this->save($instance);
    }
    /**
     * Save a new model and attach it to the parent model.
     *
     * @param Model|mixed $model The model to save.
     *
     * @since 1.0.0
     * @return Model|mixed The saved model.
     */
    public function save($model)
    {
        return $model->fill($this->get_foreign_attributes())->save();
    }
    /**
     * Get foreign properties for the related model.
     *
     * @since 1.0.0
     * @return array of the properties to set on the related model.
     */
    protected function get_foreign_attributes()
    {
        return [$this->foreign_key => $this->get_parent_key()];
    }
}
