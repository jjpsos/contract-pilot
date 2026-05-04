<?php
/**
 * Plugin Name:       Otto Contracts
 * Plugin URI:        https://www.softestate.net/
 * Description:       Manage contracts and related business records in WordPress. Showcase: https://www.softestate.net/otto-contracts/
 * Version:           9.33.3
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            jjpsos
 * Author URI:        https://www.softestate.net/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       otto-contracts
 * Domain Path:       /languages
 *
 * Copyright (C) 2026 James Sosontovich (jjpsos), RightScript (ReeSol)
 * Released under the GNU General Public License v2 or later.
 * Named contributors and copyright notices are part of the GPL
 * license grant; retain them in copies and derivative works.
 */

defined("ABSPATH") || exit();
require_once __DIR__ . "/includes/php74-compat.php";
require_once __DIR__ . "/vendor/autoload.php";

function EAC()
{
    // @return Otto\Plugin
    // rightscript March 20, 2026 namespace solution
    require __DIR__ . "/vendor/datasource.php";
    $original_var = $mySharedString;
    $className = $original_var . "\\Plugin";
    return $className::create(__FILE__);
}

EAC();
