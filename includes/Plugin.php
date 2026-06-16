<?php


namespace Jjpsos\ContractPilot;

defined("ABSPATH") || exit();


use Jjpsos\ContractPilot\Repositories\Accounts;

use Jjpsos\ContractPilot\Repositories\Business;
use Jjpsos\ContractPilot\Repositories\Categories;
use Jjpsos\ContractPilot\Repositories\Currencies;
use Jjpsos\ContractPilot\Repositories\Customers;
use Jjpsos\ContractPilot\Repositories\Expenses;
use Jjpsos\ContractPilot\Repositories\Invoices;
use Jjpsos\ContractPilot\Repositories\Items;
use Jjpsos\ContractPilot\Repositories\Notes;
use Jjpsos\ContractPilot\Repositories\Payments;
use Jjpsos\ContractPilot\Repositories\Taxes;
use Jjpsos\ContractPilot\Repositories\Terms;
use Jjpsos\ContractPilot\Repositories\Transfers;



class Plugin extends \Jjpsos\ContractPilot\Foundation\PluginBase
{
    protected function __construct($data)
    {
        $data = array_merge($data, [
            "prefix" => "contract_pilot",
            "settings_url" => admin_url("admin.php?page=contract-pilot-settings"),
        ]);

        parent::__construct($data);
        $this->define_constants();
        $this->includes();
        $this->init_hooks();
    }


    public function define_constants()
    {
        define("CONTRACT_PILOT_VERSION", $this->get_version());
        define("CONTRACT_PILOT_PLUGIN_FILE", $this->get_file());
        define("CONTRACT_PILOT_PLUGIN_BASENAME", $this->get_basename());
        define("CONTRACT_PILOT_PLUGIN_PATH", $this->get_dir_path() . "/");
        define("CONTRACT_PILOT_PLUGIN_URL", $this->get_dir_url() . "/");
        define("CONTRACT_PILOT_ADMIN_PATH", $this->get_dir_path() . "/admin/");
        define("CONTRACT_PILOT_ASSETS_URL", $this->get_assets_url() . "/");
        define("CONTRACT_PILOT_ASSETS_DIR", $this->get_assets_path() . "/");
        define("CONTRACT_PILOT_TEMPLATES_DIR", $this->get_template_path() . "/");
        define(
            "CONTRACT_PILOT_ADMIN_VIEWS_DIR",
            $this->get_dir_path() . "/includes/Admin/views/",
        );
    }


    public function includes()
    {
        require_once __DIR__ . "/functions.php";
    }


    public function init_hooks()
    {
        register_activation_hook($this->get_file(), [
            Installer::class,
            "install",
        ]);
        add_action("plugins_loaded", [$this, "plugins_loaded"], -1);
        add_action("plugins_loaded", [$this, "load_compatibilities"]);
    }

    public function plugins_loaded()
    {
        $this->services->add("installer", new Installer());

        // Repository facades: get/query/delete and domain lookups. Registered as
        // contract_pilot()->accounts, ->invoices, etc. Save workflows use Services\*
        // instead — see ARCHITECTURE.md.
        $repositories = [
            "Jjpsos\ContractPilot\Repositories\Accounts",
            "Jjpsos\ContractPilot\Repositories\Business",
            "Jjpsos\ContractPilot\Repositories\Categories",
            "Jjpsos\ContractPilot\Repositories\Currencies",
            "Jjpsos\ContractPilot\Repositories\Customers",
            "Jjpsos\ContractPilot\Repositories\Expenses",
            "Jjpsos\ContractPilot\Repositories\Invoices",
            "Jjpsos\ContractPilot\Repositories\Items",
            "Jjpsos\ContractPilot\Repositories\Notes",
            "Jjpsos\ContractPilot\Repositories\Payments",
            "Jjpsos\ContractPilot\Repositories\Taxes",
            "Jjpsos\ContractPilot\Repositories\Transfers",
            "Jjpsos\ContractPilot\Repositories\Terms",
        ];

        foreach ($repositories as $repository) {
            $repository_name = substr(
                $repository,
                strrpos($repository, "\\") + 1,
            );
            $this->services->add(strtolower($repository_name), $repository);
        }

        // Application services: business workflows (save, status transitions). Admin
        // handlers sanitize input then call contract_pilot()->{domain}_service.
        $services = [
            "invoice_service" => "Jjpsos\ContractPilot\Services\InvoiceService",
            "payment_service" => "Jjpsos\ContractPilot\Services\PaymentService",
            "expense_service" => "Jjpsos\ContractPilot\Services\ExpenseService",
            "transfer_service" => "Jjpsos\ContractPilot\Services\TransferService",
            "account_service" => "Jjpsos\ContractPilot\Services\AccountService",
            "customer_service" => "Jjpsos\ContractPilot\Services\CustomerService",
            "item_service" => "Jjpsos\ContractPilot\Services\ItemService",
            "category_service" => "Jjpsos\ContractPilot\Services\CategoryService",
            "tax_service" => "Jjpsos\ContractPilot\Services\TaxService",
        ];
        foreach ($services as $service_key => $service_class) {
            $this->services->add($service_key, $service_class);
        }

        $handlers = [
            "Jjpsos\ContractPilot\Crons",
            "Jjpsos\ContractPilot\Documents",
            "Jjpsos\ContractPilot\Banking",
            "Jjpsos\ContractPilot\Shortcodes",
            "Jjpsos\ContractPilot\Transactions",
            "Jjpsos\ContractPilot\Transfers",
            "Jjpsos\ContractPilot\Caches",
            "Jjpsos\ContractPilot\Frontend\Frontend",
            "Jjpsos\ContractPilot\Frontend\Rewrites",
        ];
        foreach ($handlers as $handler) {
            $this->services->add($handler);
        }

        if (is_admin()) {
            $handles = [
                "Jjpsos\ContractPilot\Admin\Request",
                "Jjpsos\ContractPilot\Admin\Admin",
                "Jjpsos\ContractPilot\Admin\Menus",
                "Jjpsos\ContractPilot\Admin\Scripts",
                "Jjpsos\ContractPilot\Admin\Ajax",
                "Jjpsos\ContractPilot\Admin\Dashboard",
                "Jjpsos\ContractPilot\Admin\Items",
                "Jjpsos\ContractPilot\Admin\Payments",
                "Jjpsos\ContractPilot\Admin\Invoices",
                "Jjpsos\ContractPilot\Admin\Customers",
                "Jjpsos\ContractPilot\Admin\Expenses",
                "Jjpsos\ContractPilot\Admin\Accounts",
                "Jjpsos\ContractPilot\Admin\Transfers",
                "Jjpsos\ContractPilot\Admin\Reports",
                "Jjpsos\ContractPilot\Admin\Settings",
                "Jjpsos\ContractPilot\Admin\Taxes",
                "Jjpsos\ContractPilot\Admin\Categories",
            ];
            foreach ($handles as $handle) {
                $this->services->add($handle);
            }
        }


        do_action("contract_pilot_init");
    }


    public function load_compatibilities()
    {
        $compatibilities = ["Jjpsos\ContractPilot\Compatibility\Plugins\WooCommerce"];

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
