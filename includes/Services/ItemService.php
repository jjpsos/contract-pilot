<?php

namespace Jjpsos\ContractPilot\Services;

use Jjpsos\ContractPilot\Models\Item;

defined("ABSPATH") || exit();

/**
 * Application service for service/item catalog workflows.
 *
 * Request concerns (nonces, capabilities, flash messages, redirects) remain
 * in the admin layer; this service owns persistence orchestration.
 * For reads and listings, use contract_pilot()->items.
 */
class ItemService
{
    /**
     * Create or update an item from sanitized input.
     *
     * @param array $data Sanitized item fields (including tax_ids).
     *
     * @return Item|\WP_Error Saved item on success, WP_Error on failure.
     */
    public function save(array $data)
    {
        return Item::insert($data);
    }
}
