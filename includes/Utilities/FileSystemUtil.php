<?php

namespace Otto\Utilities;

defined("ABSPATH") || exit();


class FileSystemUtil
{
    
    public static function get_fs()
    {
        if (empty($GLOBALS["wp_filesystem"])) {
            global $wp_filesystem;

            if (empty($wp_filesystem)) {
                require_once ABSPATH . "wp-admin/includes/file.php";
                WP_Filesystem();
            }
        }

        return $GLOBALS["wp_filesystem"];
    }

    
    public static function file_exists($file)
    {
        
        $file = self::sanitize_file_path($file);
        if (!self::is_direct()) {
            return file_exists($file);
        }

        return self::get_fs()->exists($file);
    }

    
    public static function fopen($file, $mode)
    {
        $file = self::sanitize_file_path($file);

        return @fopen($file, $mode); 
    }

    
    public static function size($file)
    {
        $file = self::sanitize_file_path($file);

        return self::get_fs()->size($file);
    }

    
    public static function get_contents($file)
    {
        $file = self::sanitize_file_path($file);
        return self::get_fs()->get_contents($file);
    }

    
    public static function put_contents($file, $contents)
    {
        $file = self::sanitize_file_path($file);
        return self::get_fs()->put_contents($file, $contents);
    }

    
    public static function file($file)
    {
        $file = self::sanitize_file_path($file);
        if (!self::is_direct()) {
            return file($file);
        }

        return self::get_fs()->get_contents_array($file);
    }

    
    public static function filemtime($file)
    {
        $file = self::sanitize_file_path($file);

        return self::get_fs()->mtime($file);
    }

    
    public static function delete($file)
    {
        $file = self::sanitize_file_path($file);

        return self::get_fs()->delete($file);
    }

    
    public static function sanitize_file_path($file)
    {
        
        if (
            false === strpos($file, "://") &&
            false === strpos($file, rawurlencode("://"))
        ) {
            return $file;
        }

        $restricted_protocols = self::get_restricted_file_protocols();

        foreach ($restricted_protocols as $protocol) {
            
            $pattern = "#^" . preg_quote($protocol, "#") . "#i";
            $file = preg_replace($pattern, "", $file);
        }

        return $file;
    }

    
    private static function get_restricted_file_protocols()
    {
        
        $protocols = (array) apply_filters(
            "eac_file_system_restricted_protocols",
            [
                "phar://",
                "php://",
                "glob://",
                "data://",
                "expect://",
                "zip://",
                "rar://",
                "zlib://",
            ],
        );

        
        return array_merge($protocols, array_map("urlencode", $protocols));
    }

    
    private static function is_direct()
    {
        return self::get_fs() instanceof \WP_Filesystem_Direct;
    }
}
