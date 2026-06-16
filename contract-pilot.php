<?php

/**
 * Plugin Name:       Contract Pilot
 * Plugin URI:        https://www.softestate.net/
 * Description:       Manage contracts and related business records.
 * Version:           9.47.4
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Author:            jjpsos
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       contract-pilot
 * Domain Path:       /languages
 *
 * Copyright (C) 2026 James Sosontovich (support@softestate.net)
 * Released under the GNU General Public License v2 or later.
 * Named contributors and copyright notices are part of the GPL
 * license grant; retain them in copies and derivative works.
 */

defined("ABSPATH") || exit();
require_once __DIR__ . "/includes/php74-compat.php";
require_once __DIR__ . "/vendor/autoload.php";

// Primary plugin bootstrap accessor (Plugin Check).
function contract_pilot()
{
    return \Jjpsos\ContractPilot\Plugin::create(__FILE__);
}

contract_pilot();
