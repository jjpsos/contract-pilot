<?php

namespace Jjpsos\ContractPilot\Services;

use Jjpsos\ContractPilot\Models\Transfer;

defined("ABSPATH") || exit();

/**
 * Application service for transfer workflows.
 *
 * Holds the persistence/business orchestration that admin request handlers
 * previously performed inline. The Transfer model itself coordinates the
 * paired expense/payment legs and account currencies during save(); this
 * service is the entry point the admin layer calls with sanitized input.
 * Request concerns (nonces, capabilities, flash messages, redirects) remain
 * in the admin layer.
 * For reads and listings, use contract_pilot()->transfers.
 */
class TransferService
{
    /**
     * Create or update a transfer from sanitized input.
     *
     * @param array $data Sanitized transfer fields (including the transient
     *                     from_/to_ account and exchange-rate inputs the
     *                     Transfer model consumes during save()).
     *
     * @return Transfer|\WP_Error Saved transfer on success, WP_Error on failure.
     */
    public function save(array $data)
    {
        return Transfer::insert($data);
    }
}
