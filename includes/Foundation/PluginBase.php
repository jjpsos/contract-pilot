<?php

namespace Jjpsos\ContractPilot\Foundation;

use Jjpsos\ContractPilot\Admin\Flash;
use Jjpsos\ContractPilot\Foundation\Interfaces\Pluginable;

defined("ABSPATH") || exit();
/**
 * Template for encapsulating some of the most often required abilities of a plugin instance.
 *
 * @since   1.0.0
 * @version 1.0.2
 * @author  Sultan Nasir Uddin <manikdrmc@gmail.com>
 * @package ContractPilot
 * @license GPL-3.0+
 *
 * @property Flash   $flash The flash message handler.
 * @property AssetRegistry $scripts The scripts' handler.
 *
 * @property-read string $name The plugin name.
 * @property-read string $plugin_uri The plugin URI.
 * @property-read string $version The plugin version.
 * @property-read string $description The plugin description.
 * @property-read string $author The plugin author.
 * @property-read string $author_uri The plugin author URI.
 * @property-read string $text_domain The plugin text domain.
 * @property-read string $domain_path The plugin domain path.
 * @property-read boolean $network The plugin network.
 * @property-read string $requires_wp The plugin requires at least.
 * @property-read string $requires_php The plugin requires PHP.
 * @property-read string $requires_plugins The plugin requires plugins.
 * @property-read string $support_url The plugin support URL.
 * @property-read string $docs_url The plugin docs URL.
 * @property-read string $review_url The plugin review URL.
 * @property-read string $settings_url The plugin settings URL.
 * @property-read string $file The plugin file.
 * @property-read string $slug The plugin slug.
 */
abstract class PluginBase implements Pluginable
{
    private const DB_VERSION_OPTION = 'contract_pilot_version';

    /**
     * The plugin data store.
     *
     * @since 1.0.0
     * @var array
     */
    protected $data = [];
    /**
     * The plugin services.
     *
     * @since 1.0.0
     * @var ServiceContainer
     */
    public $services;
    /**
     * The single instance of the class.
     *
     * @since 1.0.0
     * @var self
     */
    protected static $instances = [];
    /**
     * Creates a new instance of the class.
     * This method is used to create a new instance of the class.
     *
     * @param string|array $data The plugin data.
     *
     * @throws \Exception If the plugin file is not provided.
     * @since 1.0.0
     * @return static
     */
    final public static function create($data = null)
    {
        $plugin = get_called_class();
        if (!isset(static::$instances[$plugin])) {
            if (is_scalar($data)) {
                $file = $data;
                $data = [];
                $data["file"] = $file;
            }
            if (empty($data["file"])) {
                throw new \Exception("Plugin file is required.");
            }
            $file = $data["file"];
            $plugin_data = wp_cache_get($file, $file);
            if (false === $plugin_data) {
                $headers = [
                    "name" => "Plugin Name",
                    "plugin_uri" => "Plugin URI",
                    "version" => "Version",
                    "description" => "Description",
                    "author" => "Author",
                    "author_uri" => "Author URI",
                    "text_domain" => "Text Domain",
                    "domain_path" => "Domain Path",
                    "network" => "Network",
                    "requires_wp" => "Requires at least",
                    "requires_php" => "Requires PHP",
                    "requires_plugins" => "Requires Plugins",
                    "support_url" => "Support URL",
                    "docs_url" => "Docs URL",
                    "api_url" => "API URL",
                    "review_url" => "Review URL",
                    "settings_url" => "Settings URL",
                    "item_id" => "Item ID",
                ];
                $plugin_data = get_file_data($data["file"], $headers, "plugin");
                $plugin_data = array_change_key_case($plugin_data);
                // if prefix is not set, set it to the plugin slug.
                if (!isset($plugin_data["prefix"])) {
                    $plugin_data["prefix"] = str_replace(
                        "-",
                        "_",
                        dirname(self::resolve_plugin_basename($file)),
                    );
                }
                // if version is not set, set it to 1.0.0.
                if (!isset($plugin_data["version"])) {
                    $plugin_data["version"] = "1.0.0";
                }
                // Cache the plugin data.
                wp_cache_set($data["file"], $plugin_data, $file);
            }
            $plugin_data = array_merge($plugin_data, $data);
            static::$instances[$plugin] = new $plugin($plugin_data);
        }
        return static::$instances[$plugin];
    }
    /**
     * Gets the instance of the class.
     *
     * @since 1.0.0
     *
     * @return static
     */
    final public static function instance()
    {
        $plugin = get_called_class();
        if (!isset(static::$instances[$plugin])) {
            _doing_it_wrong(
                __FUNCTION__,
                "Plugin instance called before initiating the instance.",
                "1.0.0",
            );
        }
        return static::$instances[$plugin];
    }
    /**
     * Plugin constructor.
     *
     * @param array $data The plugin data.
     *
     * @since 1.0.0
     */
    protected function __construct($data)
    {
        $this->data = array_merge($this->data, $data);
        $this->services = new ServiceContainer();
        $this->services->add("flash", new Flash($this));
        $this->services->add("scripts", new AssetRegistry($this));
        // Register hooks.
        add_filter("plugin_row_meta", [$this, "_plugin_row_meta"], 10, 2);
        add_filter("plugin_action_links_" . $this->get_basename(), [
            $this,
            "_plugin_action_links",
        ]);
    }
    /**
     * Magic method to get the value associated with the given key.
     *
     * @param string $key The key to retrieve the value for.
     *
     * @since 1.0.0
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }
    /**
     * Magic method to set the value associated with the given key.
     *
     * @param string $key The key to set the value for.
     * @param mixed  $value The value to set.
     *
     * @since 1.0.0
     * @return void
     */
    public function __set($key, $value)
    {
        $this->set($key, $value);
    }
    /**
     * Magic method to check if value exists for the specified key.
     *
     * @param string $key The key to retrieve the value for.
     *
     * @since 1.0.0
     * @return bool
     */
    public function __isset($key)
    {
        return !is_null($this->get($key));
    }

    /**
     * Get the value of a property.
     *
     * @param string $key The name of the property.
     *
     * @since 1.0.0
     * @return mixed
     */
    public function get($key)
    {
        if (is_callable([$this, "get_{$key}"])) {
            return $this->{"get_{$key}"}();
        } elseif (isset($this->services[$key])) {
            return $this->services[$key];
        } elseif (isset($this->data[$key])) {
            return $this->data[$key];
        }
        return null;
    }
    /**
     * Set the value of a property.
     *
     * @param string|array|object $key The name of the property.
     * @param mixed               $value The value of the property.
     *
     * @since 1.0.0
     * @return void
     */
    public function set($key, $value = null)
    {
        // Allow setting multiple properties at once.
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->set($k, $v);
            }
            return;
        }
        $value = is_null($value) ? $key : $value;
        if (is_callable([$this, $key])) {
            return $this->{$key}($value);
        } elseif (
            is_object($value) ||
            (is_string($value) && class_exists($value))
        ) {
            $this->services->add($key, $value);
        } elseif (is_string($key) && !isset($this->data[$key])) {
            $this->data[$key] = $value;
        }
    }
    /**
     * Gets the plugin name.
     *
     * @since 1.0.0
     * @return string
     */
    public function get_name()
    {
        return $this->data["name"];
    }
    /**
     * Gets the plugin file.
     *
     * @since 1.0.0
     * @return string
     */
    public function get_file()
    {
        return $this->data["file"];
    }
    /**
     * Gets the plugin version.
     *
     * @since 1.0.0
     * @return string
     */
    public function get_version()
    {
        return $this->data["version"];
    }
    /**
     * Get the plugin prefix.
     *
     * @since 1.0.0
     * @return string
     */
    public function get_prefix()
    {
        return $this->data["prefix"];
    }

    /**
     * Apply a plugin filter hook with the plugin prefix.
     *
     * @since 1.0.0
     *
     * @param string $suffix Hook suffix (for example `_plugin_meta_links`).
     * @param mixed  $value  Value to filter.
     * @param mixed  ...$args Additional arguments passed to the filter.
     * @return mixed
     */
    protected function apply_plugin_filter($suffix, $value, ...$args)
    {
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- Uses plugin prefix via get_prefix().
        return apply_filters($this->get_prefix() . $suffix, $value, ...$args);
    }
    /**
     * Get the 'basename' for the plugin (e.g. my-plugin/my-plugin.php).
     *
     * @since  1.0.0
     * @return string The plugin basename.
     */
    public function get_basename()
    {
        return self::resolve_plugin_basename($this->get_file());
    }

    /**
     * Resolve a plugin basename without loading wp-admin/includes/plugin.php.
     *
     * @param string $file Absolute or relative plugin file path.
     * @return string
     */
    private static function resolve_plugin_basename($file)
    {
        $file = wp_normalize_path($file);
        $plugin_dir = wp_normalize_path(WP_PLUGIN_DIR);
        $mu_plugin_dir = wp_normalize_path(WPMU_PLUGIN_DIR);

        if (0 === strpos($file, $plugin_dir . "/")) {
            return substr($file, strlen($plugin_dir) + 1);
        }

        if (0 === strpos($file, $mu_plugin_dir . "/")) {
            return substr($file, strlen($mu_plugin_dir) + 1);
        }

        return $file;
    }
    /**
     * Gets the plugin slug.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_slug()
    {
        return dirname($this->get_basename());
    }
    /**
     * Gets the plugin language directory.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_lang_path()
    {
        return $this->get_slug() . rtrim($this->domain_path, "/");
    }
    /**
     *
     * Get the plugin dir path.
     *
     * @param string $path Optional. Path relative to the plugin dir path.
     *
     * @since 1.0.0
     * @return string
     */
    public function get_dir_path($path = "")
    {
        $dir = rtrim(
            str_replace("\\", "/", plugin_dir_path($this->get_file())),
            "/",
        );
        $path = ltrim($path, "/");
        $full = wp_normalize_path($dir . "/" . $path);
        return is_dir($full) ? trailingslashit($full) : $full;
    }
    /**
     * Get the plugin dir url.
     *
     * @param string $path Optional. Path relative to the plugin dir url.
     *
     * @since 1.0.0
     * @return string
     */
    public function get_dir_url($path = "")
    {
        $dir = rtrim(
            str_replace("\\", "/", plugin_dir_url($this->get_file())),
            "/",
        );
        $path = ltrim($path, "/");
        return wp_normalize_path($dir . "/" . $path);
    }
    /**
     * Get template path.
     *
     * @param string $file Optional. File name.
     *
     * @since 1.0.0
     * @return string
     */
    public function get_template_path($file = "")
    {
        return $this->get_dir_path("templates/" . $file);
    }
    /**
     * Get assets path.
     *
     * @param string $file Optional. File name.
     *
     * @since 1.0.0
     * @return string
     */
    public function get_assets_path($file = "")
    {
        return $this->get_dir_path("build/" . $file);
    }
    /**
     * Get assets url.
     *
     * @param string $file Optional. File name.
     *
     * @since 1.0.0
     * @return string
     */
    public function get_assets_url($file = "")
    {
        return $this->get_dir_url("build/" . $file);
    }
    /**
     * Get meta links.
     *
     * @since 1.0.0
     * @return array
     */
    public function get_plugin_meta_links()
    {
        $links = [];
        if (!empty($this->get_docs_url())) {
            $links["docs"] = [
                "label" => __("Documentation", "contract-pilot"),
                "url" => $this->get_docs_url(),
            ];
        }
        if (!empty($this->get_support_url())) {
            $links["support"] = [
                "label" => __("Support", "contract-pilot"),
                "url" => $this->get_support_url(),
            ];
        }
        if (!empty($this->get_review_url())) {
            $links["review"] = [
                "label" => __("Review", "contract-pilot"),
                "url" => $this->get_review_url(),
            ];
        }
        /**
         * Filter the plugin meta links.
         *
         * @param array $links The plugin meta links.
         *
         * @since 1.0.0
         */
        return $this->apply_plugin_filter('_plugin_meta_links', $links);
    }
    /**
     * Get action links.
     *
     * @since 1.0.0
     * @return array
     */
    public function get_plugin_action_links()
    {
        $links = [];
        if (!empty($this->get_settings_url())) {
            $links["settings"] = [
                "label" => __("Settings", "contract-pilot"),
                "url" => $this->get_settings_url(),
            ];
        }
        /**
         * Filter the plugin action links.
         *
         * @param array $links The plugin action links.
         *
         * @since 1.0.0
         */
        return $this->apply_plugin_filter('_plugin_action_links', $links);
    }
    /**
     * Get Settings URL.
     *
     * @since 1.0.0
     * @return string
     */
    public function get_settings_url()
    {
        $settings_url = isset($this->data["settings_url"])
            ? $this->data["settings_url"]
            : "";
        // If relative URL, make it absolute.
        if (!empty($settings_url) && false === strpos($settings_url, "http")) {
            $settings_url = admin_url($settings_url);
        }
        return $settings_url;
    }
    /**
     * Get the plugin URI.
     *
     * @since 1.0.0
     * @return string
     */
    public function get_plugin_uri()
    {
        return $this->data["plugin_uri"];
    }
    /**
     * Get the author URI.
     *
     * @since 1.0.0
     * @return string
     */
    public function get_author_uri()
    {
        return $this->data["author_uri"];
    }
    /**
     * Get the support URI for this plugin.
     *
     * @since  1.0.0
     * @return string (URI)
     */
    public function get_support_url()
    {
        return isset($this->data["support_url"])
            ? $this->data["support_url"]
            : "";
    }
    /**
     * Get the documentation URI for this plugin.
     *
     * @since  1.0.0
     * @return string (URI)
     */
    public function get_docs_url()
    {
        return isset($this->data["docs_url"]) ? $this->data["docs_url"] : "";
    }
    /**
     * Get the review URI for this plugin.
     *
     * @since  1.0.0
     * @return string (URI)
     */
    public function get_review_url()
    {
        return isset($this->data["review_url"])
            ? $this->data["review_url"]
            : "";
    }
    /**
     * Get plugin database version.
     *
     * @since 1.0.0
     * @return string (version)
     */
    public function get_db_version()
    {
        return get_option(self::DB_VERSION_OPTION, "1.0.0");
    }
    /**
     * Add plugin database version.
     *
     * @param string $version Version.
     *
     * @since 1.0.0
     * @return void
     */
    public function add_db_version($version = null)
    {
        if (empty($version)) {
            $version = $this->get_version();
        }
        add_option(self::DB_VERSION_OPTION, $version);
    }
    /**
     * Update plugin database version.
     *
     * @param string $version Version.
     *
     * @since 1.0.0
     * @return void
     */
    public function update_db_version($version = null)
    {
        if (empty($version)) {
            $version = $this->get_version();
        }
        update_option(self::DB_VERSION_OPTION, $version);
    }

    /**
     * Add plugin meta links.
     *
     * @param array  $links Plugin meta links.
     * @param string $file Plugin file.
     *
     * @since 1.0.0
     * @return array
     */
    public function _plugin_row_meta($links, $file)
    {
        // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
        if ($file !== $this->get_basename()) {
            return $links;
        }
        foreach ($this->get_plugin_meta_links() as $key => $link) {
            $links[$key] = sprintf(
                '<a href="%1$s" target="_blank">%2$s</a>',
                esc_url($link["url"]),
                esc_html($link["label"]),
            );
        }
        return $links;
    }
    /**
     * Add plugin action links.
     *
     * @param array $links Plugin action links.
     *
     * @since 1.0.0
     * @return array
     */
    public function _plugin_action_links($links)
    {
        // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
        $actions = [];
        foreach ($this->get_plugin_action_links() as $key => $link) {
            $actions[$key] = sprintf(
                '<a href="%1$s">%2$s</a>',
                esc_url($link["url"]),
                wp_kses_post($link["label"]),
            );
        }
        // add the actions to beginning of the links.
        return array_merge($actions, $links);
    }
    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    | Helper methods to perform common tasks.
    |--------------------------------------------------------------------------
    */
    /**
     * Check if a plugin is installed.
     *
     * @param string $plugin The plugin slug or basename.
     *
     * @since 1.0.0
     * @return bool
     */
    public function is_plugin_installed($plugin)
    {
        // Check if the $plugin is a basename or a slug. If it's a slug, convert it to a basename.
        if (!str_contains($plugin, "/")) {
            $plugin = $plugin . "/" . $plugin . ".php";
        }
        $plugin_path = wp_normalize_path(WP_PLUGIN_DIR . "/" . $plugin);
        if (file_exists($plugin_path)) {
            return true;
        }

        return file_exists(wp_normalize_path(WPMU_PLUGIN_DIR . "/" . basename($plugin)));
    }
    /**
     * Check if a plugin is active.
     *
     * @param string $plugin The plugin slug or basename.
     *
     * @since 1.0.0
     * @return bool
     */
    public function is_plugin_active($plugin)
    {
        // Check if the $plugin is a basename or a slug. If it's a slug, convert it to a basename.
        if (false === strpos($plugin, "/")) {
            $plugin = $plugin . "/" . $plugin . ".php";
        }
        $active_plugins = (array) get_option("active_plugins", []);
        if (is_multisite()) {
            $active_plugins = array_merge(
                $active_plugins,
                get_site_option("active_sitewide_plugins", []),
            );
        }
        return in_array($plugin, $active_plugins, true) ||
            array_key_exists($plugin, $active_plugins);
    }
    /**
     * Get plugin install url.
     *
     * @param string $plugin The plugin slug or basename.
     *
     * @since 1.0.0
     * @return string
     */
    public function get_installation_url($plugin)
    {
        if (false !== strpos($plugin, "/")) {
            // get only first part of the plugin name.
            $plugin = explode("/", $plugin);
            $plugin = $plugin[0];
        }
        return wp_nonce_url(
            self_admin_url(
                "update.php?action=install-plugin&plugin=" . $plugin,
            ),
            "install-plugin_" . $plugin,
        );
    }
    /**
     * Get plugin activate url.
     *
     * @param string $plugin The plugin slug or basename.
     *
     * @since 1.0.0
     * @return string
     */
    public function get_activation_url($plugin)
    {
        if (false === strpos($plugin, "/")) {
            $plugin = $plugin . "/" . $plugin . ".php";
        }
        $url = wp_nonce_url(
            self_admin_url("plugins.php?action=activate&plugin=" . $plugin),
            "activate-plugin_" . $plugin,
        );
        return $url;
    }
    /**
     * Log an error.
     *
     * Description of levels:
     * 'emergency': System is unusable.
     * 'alert': Action must be taken immediately.
     * 'critical': Critical conditions.
     * 'error': Error conditions.
     * 'warning': Warning conditions.
     * 'notice': Normal but significant condition.
     * 'info': Informational messages.
     * 'debug': Debug-level messages.
     *
     * @param mixed  $message The error message.
     * @param string $level The error level.
     * @param array  $data Optional. Data to log.
     *
     * @return void
     */
    public function log($message, $level = "debug", $data = [])
    {
        if (
            !defined("WP_DEBUG")
            || !WP_DEBUG
            || !defined("WP_DEBUG_LOG")
            || !WP_DEBUG_LOG
        ) {
            return;
        }
        if (is_object($message) || is_array($message)) {
            $encoded = wp_json_encode($message);
            $message = false !== $encoded ? $encoded : "[unserializable]";
        } elseif (is_bool($message)) {
            $message = $message ? "true" : "false";
        } elseif (is_null($message)) {
            $message = "null";
        } else {
            $message = (string) $message;
        }
        $line = sprintf("[%s] %s", strtoupper($level), $message);
        if (!empty($data)) {
            $line .= " " . wp_json_encode($data);
        }
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentional debug logging when WP_DEBUG and WP_DEBUG_LOG are enabled.
        error_log($line);
    }
}
