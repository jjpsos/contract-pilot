<?php

defined('ABSPATH') || exit;

/**
 * Invoice client address block and contact hidden fields.
 *
 * @var \Jjpsos\ContractPilot\Models\Invoice $invoice
 */

if (! isset($invoice) || ! is_object($invoice)) {
    return;
}

echo wp_kses_post(
    contract_pilot_get_formatted_address([
        'name'       => $invoice->contact_name,
        'company'    => $invoice->contact_company,
        'address'    => $invoice->contact_address,
        'city'       => $invoice->contact_city,
        'state'      => $invoice->contact_state,
        'postcode'   => $invoice->contact_postcode,
        'country'    => $invoice->contact_country,
        'phone'      => $invoice->contact_phone,
        'email'      => $invoice->contact_email,
        'tax_number' => $invoice->contact_tax_number,
    ]),
);

printf(
    '<input type="hidden" name="contact_name" value="%s">',
    esc_attr($invoice->contact_name),
);
printf(
    '<input type="hidden" name="contact_company" value="%s">',
    esc_attr($invoice->contact_company),
);
printf(
    '<input type="hidden" name="contact_address" value="%s">',
    esc_attr($invoice->contact_address),
);
printf(
    '<input type="hidden" name="contact_city" value="%s">',
    esc_attr($invoice->contact_city),
);
printf(
    '<input type="hidden" name="contact_state" value="%s">',
    esc_attr($invoice->contact_state),
);
printf(
    '<input type="hidden" name="contact_postcode" value="%s">',
    esc_attr($invoice->contact_postcode),
);
printf(
    '<input type="hidden" name="contact_country" value="%s">',
    esc_attr($invoice->contact_country),
);
printf(
    '<input type="hidden" name="contact_phone" value="%s">',
    esc_attr($invoice->contact_phone),
);
printf(
    '<input type="hidden" name="contact_email" value="%s">',
    esc_attr($invoice->contact_email),
);
printf(
    '<input type="hidden" name="contact_tax_number" value="%s">',
    esc_attr($invoice->contact_tax_number),
);
