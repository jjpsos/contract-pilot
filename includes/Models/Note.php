<?php

namespace Otto\Models;

defined("ABSPATH") || exit();


class Note extends Model
{
    
    protected $table = "otto_notes";

    
    protected $columns = [
        "id",
        "parent_id",
        "parent_type",
        "content",
        "author_id",
    ];

    
    protected $casts = [
        "id" => "int",
        "parent_id" => "int",
        "author_id" => "int",
        "content" => "sanitize_textarea",
    ];

    
    protected $searchable = ["content"];

    
    protected $has_timestamps = true;

    

    
    
    public function save()
    {
        if (!$this->parent_id) {
            return new \WP_Error(
                "missing_required",
                __("Missing parent ID.", "otto-contracts"),
            );
        }
        if (!$this->parent_type) {
            return new \WP_Error(
                "missing_required",
                __("Missing parent type.", "otto-contracts"),
            );
        }

        if (empty($this->author_id) && is_user_logged_in()) {
            $this->author_id = get_current_user_id();
        }

        return parent::save();
    }

    
}
