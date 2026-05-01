<?php

namespace Otto\Compatibility\Plugins;

defined("ABSPATH") || exit();


class WooCommerce extends Plugin
{
    
    public function is_active(): bool
    {
        return class_exists("WooCommerce");
    }

    
    protected function register_events(): void
    {
        add_filter("woocommerce_prevent_admin_access", [
            $this,
            "allow_admin_access",
        ]);
    }

    
    public function allow_admin_access($prevent_access): bool
    {
        $allowed_roles = ["eac_auditor", "eac_accountant", "eac_manager"];
        foreach ($allowed_roles as $role) {
            if (current_user_can($role)) {
                return false;
            }
        }

        return $prevent_access;
    }
}
