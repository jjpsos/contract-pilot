<?php

namespace Jjpsos\ContractPilot\Repositories;

use Jjpsos\ContractPilot\Models\Document;

defined("ABSPATH") || exit();

/**
 * Repository-style facade for document records.
 *
 * Not registered on the plugin container; instantiate or register if needed.
 * Same get/query/insert/delete pattern as other domain repositories.
 */
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
            "invoice" => __("Contract/Bill", "contract-pilot"),
            "receipt" => __("Receipt", "contract-pilot"),
            "contract" => __("Contract", "contract-pilot"),
        ];

        return apply_filters("contract_pilot_document_types", $document_types);
    }
}
