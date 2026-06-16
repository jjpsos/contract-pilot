<?php

namespace Jjpsos\ContractPilot\Services;

use Jjpsos\ContractPilot\Models\Tax;

defined("ABSPATH") || exit();

/**
 * Application service for tax-rate workflows.
 *
 * Request concerns (nonces, capabilities, flash messages, redirects) remain
 * in the admin layer; this service owns persistence orchestration.
 * For reads and listings, use contract_pilot()->taxes.
 */
class TaxService
{
    /**
     * Create or update a tax from sanitized input.
     *
     * @param array $data Sanitized tax fields.
     *
     * @return Tax|\WP_Error Saved tax on success, WP_Error on failure.
     */
    public function save(array $data)
    {
        return Tax::insert($data);
    }
}
