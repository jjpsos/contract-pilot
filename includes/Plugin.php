<?php


namespace Otto;

defined("ABSPATH") || exit();


use Otto\Controllers\Accounts;

use Otto\Controllers\Business;
use Otto\Controllers\Categories;
use Otto\Controllers\Currencies;
use Otto\Controllers\Customers;
use Otto\Controllers\Expenses;
use Otto\Controllers\Invoices;
use Otto\Controllers\Items;
use Otto\Controllers\Notes;
use Otto\Controllers\Payments;
use Otto\Controllers\Taxes;
use Otto\Controllers\Terms;
use Otto\Controllers\Transfers;



class Plugin extends \Otto\ByteKit\Plugin
{
    
    protected function __construct($data)
    {
        $data = array_merge($data, [
            "prefix" => "eac",
            "settings_url" => admin_url("admin.php?page=eac-settings"),
        ]);

        parent::__construct($data);
        $this->define_constants();
        $this->includes();
        $this->init_hooks();
    }

    
    public function define_constants()
    {
        $upload_dir = wp_upload_dir(null, false);

        define("EAC_VERSION", $this->get_version());
        define("EAC_PLUGIN_FILE", $this->get_file());
        define("EAC_PLUGIN_BASENAME", $this->get_basename());
        define("EAC_PLUGIN_PATH", $this->get_dir_path() . "/");
        define("EAC_PLUGIN_URL", $this->get_dir_url() . "/");
        define("EAC_ADMIN_PATH", $this->get_dir_path() . "/admin/");
        define("EAC_UPLOADS_BASEDIR", $upload_dir["basedir"] . "/eac/");
        define("EAC_UPLOADS_DIR", $upload_dir["basedir"] . "/eac/");
        define("EAC_UPLOADS_URL", $upload_dir["baseurl"] . "/eac/");
        define("EAC_LOG_DIR", $upload_dir["basedir"] . "/eac-logs/");
        define("EAC_ASSETS_URL", $this->get_assets_url() . "/");
        define("EAC_ASSETS_DIR", $this->get_assets_path() . "/");
        define("EAC_TEMPLATES_DIR", $this->get_template_path() . "/");
    }

    
    public function includes()
    {
        require_once __DIR__ . "/functions.php";
        require_once dirname(__DIR__) .
            "/vendor/woocommerce/action-scheduler/action-scheduler.php";
    }

    
    public function init_hooks()
    {
        register_activation_hook($this->get_file(), [
            Installer::class,
            "install",
        ]);
        add_action("plugins_loaded", [$this, "plugins_loaded"], -1);
        add_action("plugins_loaded", [$this, "load_compatibilities"]);
        add_action("rest_api_init", [$this, "register_routes"]);
    }

    
    public function plugins_loaded()
    {
        $this->services->add("installer", new Installer());

        $controllers = [
            "Otto\Controllers\Accounts",
            "Otto\Controllers\Bills",
            "Otto\Controllers\Business",
            "Otto\Controllers\Categories",
            "Otto\Controllers\Currencies",
            "Otto\Controllers\Customers",
            "Otto\Controllers\Expenses",
            "Otto\Controllers\Invoices",
            "Otto\Controllers\Items",
            "Otto\Controllers\Notes",
            "Otto\Controllers\Payments",
            "Otto\Controllers\Taxes",
            "Otto\Controllers\Transfers",
            "Otto\Controllers\Terms",
            "Otto\Controllers\Vendors",
        ];

        foreach ($controllers as $controller) {
            $controller_name = substr(
                $controller,
                strrpos($controller, "\\") + 1,
            );
            $this->services->add(strtolower($controller_name), $controller);
        }

        $handlers = [
            "Otto\Currencies",
            "Otto\Contacts",
            "Otto\Crons",
            "Otto\Documents",
            "Otto\Banking",
            "Otto\Shortcodes",
            "Otto\Transactions",
            "Otto\Transfers",
            "Otto\Caches",
            "Otto\Frontend\Frontend",
            "Otto\Frontend\Rewrites",
        ];
        foreach ($handlers as $handler) {
            $this->services->add($handler);
        }

        if (is_admin()) {
            $handles = [
                "Otto\Admin\Admin",
                "Otto\Admin\Menus",
                "Otto\Admin\Scripts",
                "Otto\Admin\Ajax",
                "Otto\Admin\Dashboard",
                "Otto\Admin\Items",
                "Otto\Admin\Payments",
                "Otto\Admin\Invoices",
                "Otto\Admin\Customers",
                "Otto\Admin\Expenses",
                "Otto\Admin\Importers",
                "Otto\Admin\Exporters",
                "Otto\Admin\Bills",
                "Otto\Admin\Vendors",
                "Otto\Admin\Accounts",
                "Otto\Admin\Transfers",
                "Otto\Admin\Reports",
                "Otto\Admin\Settings",
                "Otto\Admin\Currencies",
                "Otto\Admin\Taxes",
                "Otto\Admin\Categories",
                
                "Otto\Admin\Notices",
            ];
            foreach ($handles as $handle) {
                $this->services->add($handle);
            }
        }

        
        do_action("eac_init");
    }

    
    public function register_routes()
    {
        $handlers = apply_filters("eac_rest_handlers", [
            "Otto\API\Items",
            "Otto\API\Taxes",
            "Otto\API\Categories",
            "Otto\API\Currencies",
            "Otto\API\Customers",
            "Otto\API\Vendors",
            "Otto\API\Customers",
            "Otto\API\Accounts",
            "Otto\API\Notes",
            "Otto\API\Expenses",
            "Otto\API\Payments",
            "Otto\API\Utilities",
            "Otto\API\Invoices",
            "Otto\API\Bills",
        ]);
        foreach ($handlers as $controller) {
            if (class_exists($controller)) {
                $this->$controller = new $controller();
                $this->$controller->register_routes();
            }
        }
    }

    
    public function load_compatibilities()
    {
        $compatibilities = ["Otto\Compatibility\Plugins\WooCommerce"];

        foreach ($compatibilities as $compatibility) {
            if (class_exists($compatibility)) {
                new $compatibility();
            }
        }
    }

    
    public function queue()
    {
        return Foundation\Queue::instance();
    }

    
    public function get_assets_path($file = "")
    {
        return $this->get_dir_path("assets/" . $file);
    }

    
    public function get_assets_url($file = "")
    {
        return $this->get_dir_url("assets/" . $file);
    }
}
