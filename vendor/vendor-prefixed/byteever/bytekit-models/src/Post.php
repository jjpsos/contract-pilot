<?php

namespace Otto\ByteKit\Models;

/**
 * Abstract post class for models.
 *
 * @since   1.0.0
 * @version 1.0.5
 * @author  Sultan Nasir Uddin <manikdrmc@gmail.com>
 * @package \ByteKit/Models
 * @license GPL-3.0+
 */
abstract class Post extends Model
{
    /**
     * The table associated with the model.
     *
     * @since 1.0.0
     * @var string
     */
    protected $table = "posts";
    /**
     * The primary key for the model.
     *
     * @since 1.0.0
     * @var string
     */
    protected $primary_key = "ID";
    /**
     * Post type.
     *
     * @since 1.0.0
     * @var string
     */
    protected $post_type = "";
    /**
     * Meta type declaration for the object.
     *
     * @since 1.0.0
     * @var string
     */
    protected $meta_type = "post";
    /**
     * The table columns of the model.
     *
     * @since 1.0.0
     * @var array
     */
    protected $columns = [
        "ID",
        "post_author",
        "post_date",
        "post_date_gmt",
        "post_content",
        "post_title",
        "post_excerpt",
        "post_status",
        "comment_status",
        "ping_status",
        "post_password",
        "post_name",
        "to_ping",
        "pinged",
        "post_modified",
        "post_modified_gmt",
        "post_content_filtered",
        "post_parent",
        "guid",
        "menu_order",
        "post_type",
        "post_mime_type",
        "comment_count",
    ];
    /**
     * The model's attributes.
     *
     * @since 1.0.0
     * @var array
     */
    protected $attributes = [];
    /**
     * The attributes that should be cast.
     *
     * @since 1.0.0
     * @var array
     */
    protected $casts = [];
    /**
     * The attributes that have aliases.
     *
     * @since 1.0.0
     * @var array
     */
    protected $aliases = [];
    /**
     * The searchable attributes.
     *
     * @since 1.0.0
     * @var array
     */
    protected $searchable = [];
    /**
     * Create a new model instance.
     *
     * @param string|array|object $attributes The attributes to set on the model.
     */
    public function __construct($attributes = [])
    {
        $_attributes = [
            "post_type" => $this->post_type,
            "post_status" => "publish",
            "comment_status" => "open",
            "ping_status" => "open",
        ];
        $_casts = [
            "ID" => "integer",
            "post_author" => "integer",
            "post_parent" => "integer",
            "menu_order" => "integer",
            "comment_count" => "integer",
            "post_date" => "datetime",
            "post_date_gmt" => "datetime",
            "post_modified" => "datetime",
            "post_modified_gmt" => "datetime",
        ];
        $_aliases = [
            "id" => "ID",
            "author_id" => "post_author",
            "date" => "post_date",
            "date_gmt" => "post_date_gmt",
            "content" => "post_content",
            "title" => "post_title",
            "excerpt" => "post_excerpt",
            "status" => "post_status",
        ];
        $this->attributes = array_merge($this->attributes, $_attributes);
        $this->casts = array_merge($this->casts, $_casts);
        $this->aliases = array_merge($this->aliases, $_aliases);
        $this->searchable = array_merge($this->searchable, [
            "post_title",
            "post_content",
            "post_excerpt",
        ]);
        $this->query_vars["post_type"] = $this->post_type;
        parent::__construct($attributes);
    }
}
