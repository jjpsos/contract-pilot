<?php

namespace Jjpsos\ContractPilot\Repositories;

use Jjpsos\ContractPilot\Models\Tax;

defined("ABSPATH") || exit();

/**
 * Repository-style facade for tax rate records.
 *
 * Container: contract_pilot()->taxes. Use for get(), query(), delete().
 * For admin save workflows, use {@see \Jjpsos\ContractPilot\Services\TaxService}.
 */
class Taxes
{
    public function get($tax)
    {
        return Tax::find($tax);
    }


    public function insert($data, $wp_error = true)
    {
        return Tax::insert($data, $wp_error);
    }


    public function delete($id)
    {
        $tax = $this->get($id);
        if (!$tax) {
            return false;
        }

        return $tax->delete();
    }


    public function query($args = [], $count = false)
    {
        if ($count) {
            return Tax::count($args);
        }

        return Tax::results($args);
    }
}
