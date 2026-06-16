<?php

namespace Jjpsos\ContractPilot\Admin;

use Jjpsos\ContractPilot\Foundation\PluginBase;

defined("ABSPATH") || exit();

/**
 * Redirect flash messages for admin screens.
 */
class Flash
{
    private const FLASH_MESSAGES_OPTION = 'contract_pilot_flash_messages';

    /**
     * @var PluginBase
     */
    protected $plugin;

    /**
     * @var array
     */
    protected $messages = [];

    public function __construct($plugin)
    {
        $this->plugin = $plugin;
        add_action("admin_init", [$this, "load_messages"], 5);
        add_filter("wp_redirect", [$this, "save_messages"], 1);
        add_action("admin_notices", [$this, "display_messages"]);
    }

    public function load_messages()
    {
        if (1 === Request::get_int('_flash')) {
            $messages = get_option(self::FLASH_MESSAGES_OPTION, []);
            if (!empty($messages) && is_array($messages)) {
                foreach ($messages as $message) {
                    $this->message($message["type"], $message["message"]);
                }
            }
            update_option(self::FLASH_MESSAGES_OPTION, []);
        }
    }

    public function save_messages($location)
    {
        if (!empty($this->messages)) {
            update_option(self::FLASH_MESSAGES_OPTION, $this->messages);
            $location = add_query_arg("_flash", "yes", $location);
        }
        return $location;
    }

    public function display_messages()
    {
        if (empty($this->messages)) {
            return;
        }
        foreach ($this->messages as $message_id => $message) {
            printf(
                '<div class="notice notice-%1$s is-dismissible">%2$s</div>',
                esc_attr($message["type"]),
                wp_kses_post(wpautop($message["message"])),
            );
            unset($this->messages[$message_id]);
        }
    }

    public function message($type, $message)
    {
        if (
            empty($message) &&
            !in_array($type, ["success", "info", "warning", "error"], true)
        ) {
            return;
        }
        $id = substr(md5($message . $type), 0, 8);
        $this->messages[$id] = ["message" => $message, "type" => $type];
    }

    public function error($message)
    {
        $this->message("error", $message);
    }

    public function warning($message)
    {
        $this->message("warning", $message);
    }

    public function info($message)
    {
        $this->message("info", $message);
    }

    public function success($message)
    {
        $this->message("success", $message);
    }

    public function get_messages()
    {
        return $this->messages;
    }

    public function clear_messages()
    {
        $this->messages = [];
        update_option(self::FLASH_MESSAGES_OPTION, []);
    }
}
