<?php

namespace Otto\Admin\Settings;


class Emails extends Page
{
    
    public function __construct()
    {
        parent::__construct("emails", __("Emails", "otto-contracts"));
    }

    
    public function get_default_section_settings()
    {
        return [
            
            [
                "type" => "title",
                "title" => __("New Payment [Customer]", "otto-contracts"),
                "desc" => __(
                    "Email sent to the customer when a new payment is received.",
                    "otto-contracts",
                ),
                "id" => "new_payment_customer_email",
            ],
            
            [
                "title" => __("Subject", "otto-contracts"),
                "type" => "text",
                "id" => "new_payment_customer_email_subject",
                "default" => __(
                    "Payment Receipt from {company_name}",
                    "otto-contracts",
                ),
                "desc" => __(
                    "Enter the subject for this email.",
                    "otto-contracts",
                ),
            ],
            
            [
                "title" => __("Content", "otto-contracts"),
                "type" => "wp_editor",
                "id" => "new_payment_customer_email_content",
                "sanitize_cb" => "sanitize_textarea_field",
                "default" => __(
                    "Hello {customer_name},<br><br>We have received your payment of {payment_amount} on {payment_date}.<br><br> You can view your payment details by clicking the link below:<br>{payment_link}<br><br>Thank you for your business.<br><br>{business_name}",
                    "otto-contracts",
                ),
                "desc" =>
                    __("Available template tags:", "otto-contracts") .
                    "<br>{payment_amount} - " .
                    __("Payment amount.", "otto-contracts") .
                    "<br>{payment_date} - " .
                    __("Payment date.", "otto-contracts") .
                    "<br>{payment_number} - " .
                    __("Payment number.", "otto-contracts") .
                    "<br>{payment_link} - " .
                    __("Payment link.", "otto-contracts") .
                    "<br>{customer_name} - " .
                    __("Customer name.", "otto-contracts") .
                    "<br>{customer_company} - " .
                    __("Customer company.", "otto-contracts") .
                    "<br>{customer_email} - " .
                    __("Customer email.", "otto-contracts") .
                    "<br>{customer_phone} - " .
                    __("Customer phone.", "otto-contracts") .
                    "<br>{customer_address} - " .
                    __("Customer address.", "otto-contracts") .
                    "<br>{business_name} - " .
                    __("Business name.", "otto-contracts") .
                    "<br>{business_address} - " .
                    __("Business address.", "otto-contracts"),
            ],
            
            [
                "type" => "sectionend",
                "id" => "new_payment_customer_email",
            ],
            [
                "type" => "title",
                "title" => __("New Payment [Admin]", "otto-contracts"),
                "desc" => __(
                    "Email sent to the admin when a new payment is received.",
                    "otto-contracts",
                ),
                "id" => "new_payment_admin_email",
            ],
            
            [
                "title" => __("Enable", "otto-contracts"),
                "type" => "checkbox",
                "id" => "new_payment_admin_email_enable",
                "default" => "yes",
                "desc" => __(
                    "Enable this email notification.",
                    "otto-contracts",
                ),
            ],
            
            [
                "title" => __("Subject", "otto-contracts"),
                "type" => "text",
                "id" => "new_payment_admin_email_subject",
                "default" => __(
                    "New Payment Received from {customer_name}",
                    "otto-contracts",
                ),
                "desc" => __(
                    "Enter the subject for this email.",
                    "otto-contracts",
                ),
            ],
            
            [
                "title" => __("Content", "otto-contracts"),
                "type" => "wp_editor",
                "id" => "new_payment_admin_email_content",
                "sanitize_cb" => "sanitize_textarea_field",
                "default" => __(
                    "Hello,<br><br>A new payment of {payment_amount} has been received from {customer_name} on {payment_date}.<br><br> You can view the payment details by clicking the link below:<br>{payment_link}<br><br>{business_name}",
                    "otto-contracts",
                ),
                "desc" =>
                    __("Available template tags:", "otto-contracts") .
                    "<br>{payment_amount} - " .
                    __("Payment amount.", "otto-contracts") .
                    "<br>{payment_date} - " .
                    __("Payment date.", "otto-contracts") .
                    "<br>{payment_number} - " .
                    __("Payment number.", "otto-contracts") .
                    "<br>{payment_link} - " .
                    __("Payment link.", "otto-contracts") .
                    "<br>{customer_name} - " .
                    __("Customer name.", "otto-contracts") .
                    "<br>{customer_company} - " .
                    __("Customer company.", "otto-contracts") .
                    "<br>{customer_email} - " .
                    __("Customer email.", "otto-contracts") .
                    "<br>{customer_phone} - " .
                    __("Customer phone.", "otto-contracts") .
                    "<br>{customer_address} - " .
                    __("Customer address.", "otto-contracts") .
                    "<br>{business_name} - " .
                    __("Business name.", "otto-contracts") .
                    "<br>{business_address} - " .
                    __("Business address.", "otto-contracts"),
            ],
            
            [
                "type" => "sectionend",
                "id" => "new_payment_admin_email",
            ],
        ];
    }
}
