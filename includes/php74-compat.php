<?php

defined("ABSPATH") || exit();

if (!function_exists("str_contains")) {
    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- PHP polyfill; name matches PHP core (Plugin Check).
    function str_contains($haystack, $needle)
    {
        if ($needle === "") {
            return true;
        }

        return strpos((string) $haystack, (string) $needle) !== false;
    }
}

if (!function_exists("str_starts_with")) {
    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- PHP polyfill; name matches PHP core (Plugin Check).
    function str_starts_with($haystack, $needle)
    {
        if ($needle === "") {
            return true;
        }

        return strncmp((string) $haystack, (string) $needle, strlen($needle)) === 0;
    }
}

if (!function_exists("str_ends_with")) {
    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- PHP polyfill; name matches PHP core (Plugin Check).
    function str_ends_with($haystack, $needle)
    {
        if ($needle === "") {
            return true;
        }

        $haystack = (string) $haystack;
        $needle = (string) $needle;
        $len = strlen($needle);

        if ($len > strlen($haystack)) {
            return false;
        }

        return substr_compare($haystack, $needle, -$len, $len) === 0;
    }
}
