<?php

namespace Otto\ByteKit\Models\Relations;

use Otto\ByteKit\Models\Model;
/**
 * Has many relation class.
 *
 * @since   1.0.0
 * @version 1.0.5
 * @author  Sultan Nasir Uddin <manikdrmc@gmail.com>
 * @package \ByteKit/Models
 * @license GPL-3.0+
 */
class HasMany extends HasOne
{
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
            "limit" => 0,
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
        return !empty($this->get_parent_key())
            ? $this->query->get_results()
            : [];
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
        $instance = $this->related->make($attributes);
        return $this->save($instance);
    }
}
