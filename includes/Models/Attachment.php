<?php

namespace Otto\Models;

defined("ABSPATH") || exit();


class Attachment extends Model
{
    
    protected $table = "posts";

    
    protected $primary_key = "ID";

    
    protected $columns = ["ID", "post_title", "post_type"];

    
    protected $aliases = [
        "title" => "post_title",
        "id" => "ID",
    ];

    
    protected $query_vars = [
        "post_type" => "attachment",
    ];

    

    
    protected function get_url_attribute()
    {
        return wp_get_attachment_url($this->ID);
    }

    
    protected function get_path_attribute()
    {
        return get_attached_file($this->ID);
    }

    
    protected function get_filesize_attribute()
    {
        $meta = wp_get_attachment_metadata($this->ID);

        return isset($meta["filesize"])
            ? $meta["filesize"]
            : filesize(get_attached_file($this->ID));
    }
}
