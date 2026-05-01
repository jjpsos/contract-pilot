<?php

namespace Otto\Controllers;

use Otto\Models\Document;

defined("ABSPATH") || exit();


class Documents
{
    
    public function get($document)
    {
        return Document::find($document);
    }

    
    public function insert($data, $wp_error = true)
    {
        return Document::insert($data, $wp_error);
    }

    
    public function delete($id)
    {
        $document = $this->get($id);
        if (!$document) {
            return false;
        }

        return $document->delete();
    }

    
    public function query($args = [], $count = false)
    {
        if ($count) {
            return Document::count($args);
        }

        return Document::results($args);
    }

    
    public function get_types()
    {
        $document_types = [
            "invoice" => __("Contract/Bill", "otto-contracts"),
            "receipt" => __("Receipt", "otto-contracts"),
            "contract" => __("Contract", "otto-contracts"),
        ];

        return apply_filters("eac_document_types", $document_types);
    }
}
