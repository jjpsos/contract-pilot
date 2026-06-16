<?php

namespace Jjpsos\ContractPilot\Repositories;

use Jjpsos\ContractPilot\Models\Payment;

defined("ABSPATH") || exit();

/**
 * Repository-style facade for payment records.
 *
 * Container: contract_pilot()->payments. Use for get(), query(), delete().
 * For admin save/update workflows, use {@see \Jjpsos\ContractPilot\Services\PaymentService}.
 */
class Payments
{
    public function get($payment)
    {
        return Payment::find($payment);
    }


    public function insert($data, $wp_error = true)
    {
        return Payment::insert($data, $wp_error);
    }


    public function delete($id)
    {
        $payment = $this->get($id);
        if (!$payment) {
            return false;
        }

        return $payment->delete();
    }


    public function query($args = [], $count = false)
    {
        if ($count) {
            return Payment::count($args);
        }

        return Payment::results($args);
    }
}
