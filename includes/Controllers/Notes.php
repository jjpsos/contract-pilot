<?php

namespace Otto\Controllers;

use Otto\Models\Note;

defined("ABSPATH") || exit();


class Notes
{
    
    public function get($note)
    {
        return Note::find($note);
    }

    
    public function insert($data, $wp_error = true)
    {
        return Note::insert($data, $wp_error);
    }

    
    public function delete($id)
    {
        $note = $this->get($id);
        if (!$note) {
            return false;
        }

        return $note->delete();
    }

    
    public function query($args = [], $count = false)
    {
        if ($count) {
            return Note::count($args);
        }

        return Note::results($args);
    }
}
