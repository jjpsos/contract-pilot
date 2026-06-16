<?php

namespace Jjpsos\ContractPilot\Repositories;

use Jjpsos\ContractPilot\Models\Item;

defined("ABSPATH") || exit();

/**
 * Repository-style facade for item (service) records.
 *
 * Container: contract_pilot()->items. Use for get(), query(), delete().
 * For admin save workflows, use {@see \Jjpsos\ContractPilot\Services\ItemService}.
 */
class Items
{
    public function get($item)
    {
        return Item::find($item);
    }


    public function insert($data, $wp_error = true)
    {
        return Item::insert($data, $wp_error);
    }


    public function delete($id)
    {
        $item = $this->get($id);
        if (!$item) {
            return false;
        }

        return $item->delete();
    }


    public function query($args = [], $count = false)
    {
        if ($count) {
            return Item::count($args);
        }

        return Item::results($args);
    }


    public function get_types()
    {
        return apply_filters("contract_pilot_item_types", [
            "standard" => __("Standard", "contract-pilot"),
            "fee" => __("Fee", "contract-pilot"),
        ]);
    }


    public function get_units()
    {
        return apply_filters("contract_pilot_item_units", [
            "hr" => __("Hour", "contract-pilot"),
            "day" => __("Day", "contract-pilot"),
            "week" => __("Week", "contract-pilot"),
            "month" => __("Month", "contract-pilot"),
            "year" => __("Year", "contract-pilot"),
        ]);
    }
}
