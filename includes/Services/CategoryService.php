<?php

namespace Jjpsos\ContractPilot\Services;

use Jjpsos\ContractPilot\Models\Category;

defined("ABSPATH") || exit();

/**
 * Application service for category workflows.
 *
 * Request concerns (nonces, capabilities, flash messages, redirects) remain
 * in the admin layer; this service owns persistence orchestration.
 * For reads and listings, use contract_pilot()->categories.
 */
class CategoryService
{
    /**
     * Create or update a category from sanitized input.
     *
     * @param array $data Sanitized category fields.
     *
     * @return Category|\WP_Error Saved category on success, WP_Error on failure.
     */
    public function save(array $data)
    {
        return Category::insert($data);
    }
}
