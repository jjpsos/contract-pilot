<?php

namespace Jjpsos\ContractPilot\Repositories;

use Jjpsos\ContractPilot\Models\Expense;
use Jjpsos\ContractPilot\Models\Payment;
use Jjpsos\ContractPilot\Models\Transfer;

defined("ABSPATH") || exit();

/**
 * Repository-style facade for transfer records.
 *
 * Container: contract_pilot()->transfers. Use for get(), query(), delete().
 * For admin save workflows, use {@see \Jjpsos\ContractPilot\Services\TransferService}.
 */
class Transfers
{
    public function get($transfer)
    {
        return Transfer::find($transfer);
    }


    public function insert($data, $wp_error = true)
    {
        return Transfer::insert($data, $wp_error);
    }


    public function delete($id)
    {
        $transfer = $this->get($id);
        if (!$transfer) {
            return false;
        }

        return $transfer->delete();
    }


    public function query($args = [], $count = false)
    {
        if ($count) {
            return Transfer::count($args);
        }

        return Transfer::results($args);
    }
}
