<?php

namespace Jjpsos\ContractPilot\Repositories;

use Jjpsos\ContractPilot\Models\Note;

defined("ABSPATH") || exit();

/**
 * Repository-style facade for note records.
 *
 * Container: contract_pilot()->notes. Use for get(), query(), insert(), delete().
 * Notes are often added via Ajax; there is no dedicated NoteService yet.
 */
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
