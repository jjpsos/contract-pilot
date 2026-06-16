<?php

namespace Jjpsos\ContractPilot\Admin\Concerns;

/**
 * Bridges WP_List_Table between split admin hooks (`*_page_loaded` / `*_page_content`)
 * without exporting a PHP global.
 */
trait ContractPilotListTableScreen
{
    /** @var \WP_List_Table|null */
    private static $contract_pilot_screen_wp_list_table = null;

    protected static function contract_pilot_reset_list_table(): void
    {
        self::$contract_pilot_screen_wp_list_table = null;
    }

    /**
     * @param \WP_List_Table $contract_pilot_wp_list_table
     */
    protected static function contract_pilot_store_list_table($contract_pilot_wp_list_table): void
    {
        self::$contract_pilot_screen_wp_list_table = $contract_pilot_wp_list_table;
    }

    /**
     * @return \WP_List_Table|null
     */
    protected static function contract_pilot_fetch_list_table()
    {
        return self::$contract_pilot_screen_wp_list_table;
    }
}
