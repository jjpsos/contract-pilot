<?php

defined('ABSPATH') || exit();


function contract_pilot_get_template_part($slug, $name = null)
{
    $templates = array();
    if ($name) {
        $templates[] = "{$slug}-{$name}.php";
    }

    $templates[] = "{$slug}.php";


    $templates = apply_filters('contract_pilot_get_template_part', $templates, $slug, $name);

    foreach ($templates as $template) {
        $located = contract_pilot_locate_template($template);

        if (! empty($located)) {
            load_template($located, false);
            break;
        }
    }
}


function contract_pilot_locate_template($template_name, $template_path = '', $default_path = '')
{
    if (! $template_path) {
        $template_path = contract_pilot()->get_template_path();
    }

    if (! $default_path) {
        $default_path = contract_pilot()->get_template_path();
    }


    $template = locate_template(
        array(
            trailingslashit($template_path) . $template_name,
            'contract-pilot/' . $template_name,
        )
    );


    if (! $template) {
        $template = $default_path . $template_name;
    }


    return apply_filters('contract_pilot_locate_template', $template, $template_name, $template_path);
}


function contract_pilot_get_template($template_name, $args = array(), $template_path = '', $default_path = '')
{
    $template = contract_pilot_locate_template($template_name, $template_path, $default_path);


    $filter_template = apply_filters('contract_pilot_get_template', $template, $template_name, $args, $template_path, $default_path);

    if ($filter_template !== $template) {
        if (! file_exists($filter_template)) {
            $filter_template = $template;
        }
    }

    $action_args = array(
        'template_name' => $template_name,
        'template_path' => $template_path,
        'located'       => $template,
        'args'          => $args,
    );

    if (! empty($args) && is_array($args)) {
        extract($args);
    }

    do_action('contract_pilot_before_template_part', $action_args['template_name'], $action_args['template_path'], $action_args['located'], $action_args['args']);
    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Deprecated pre-2.0 hook; contract_pilot_before_template_part is canonical.
    if (has_action('pilot_accounting_before_template_part')) {
        do_action_deprecated(
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Deprecated pre-2.0 hook; contract_pilot_before_template_part is canonical.
            'pilot_accounting_before_template_part',
            array($action_args['template_name'], $action_args['template_path'], $action_args['located'], $action_args['args']),
            '2.0.0',
            'contract_pilot_before_template_part'
        );
    }

    include $action_args['located'];

    do_action('contract_pilot_after_template_part', $action_args['template_name'], $action_args['template_path'], $action_args['located'], $action_args['args']);
    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Deprecated pre-2.0 hook; contract_pilot_after_template_part is canonical.
    if (has_action('pilot_accounting_after_template_part')) {
        do_action_deprecated(
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Deprecated pre-2.0 hook; contract_pilot_after_template_part is canonical.
            'pilot_accounting_after_template_part',
            array($action_args['template_name'], $action_args['template_path'], $action_args['located'], $action_args['args']),
            '2.0.0',
            'contract_pilot_after_template_part'
        );
    }
}


function contract_pilot_get_template_html($template_name, $args = array(), $template_path = '', $default_path = '')
{
    ob_start();
    contract_pilot_get_template($template_name, $args, $template_path, $default_path);

    return ob_get_clean();
}


/**
 * Resolve an admin view partial or screen by slug (e.g. "partials/note-item").
 *
 * @param string $slug View path without extension, relative to includes/Admin/views/.
 * @return string Absolute path, or empty string if not found.
 */
function contract_pilot_locate_admin_view($slug)
{
    $slug = ltrim(str_replace('\\', '/', (string) $slug), '/');

    if ('' === $slug || false !== strpos($slug, '..')) {
        return '';
    }

    $base = defined('CONTRACT_PILOT_ADMIN_VIEWS_DIR')
        ? CONTRACT_PILOT_ADMIN_VIEWS_DIR
        : '';

    if ('' === $base) {
        return '';
    }

    $located = $base . $slug . '.php';

    if (! is_file($located)) {
        return '';
    }

    return (string) apply_filters('contract_pilot_locate_admin_view', $located, $slug);
}


/**
 * Render an admin view with an explicit data array.
 *
 * Variables in $data are available in the template scope (single extract point).
 *
 * @param string               $slug View slug passed to contract_pilot_locate_admin_view().
 * @param array<string, mixed> $data Template variables.
 * @return void
 */
function contract_pilot_render_admin_view($slug, $data = array())
{
    $located = contract_pilot_locate_admin_view($slug);

    if ('' === $located) {
        return;
    }

    $data = is_array($data) ? $data : array();

    $action_args = array(
        'slug'    => $slug,
        'located' => $located,
        'data'    => $data,
    );

    do_action('contract_pilot_before_admin_view', $action_args['slug'], $action_args['located'], $action_args['data']);

    if (! empty($data)) {
        // phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- Sole sanctioned extract; callers pass explicit arrays.
        extract($data, EXTR_SKIP);
    }

    include $action_args['located'];

    do_action('contract_pilot_after_admin_view', $action_args['slug'], $action_args['located'], $action_args['data']);
}


/**
 * Render an admin view and return HTML (for Ajax fragments, emails, etc.).
 *
 * @param string               $slug View slug passed to contract_pilot_locate_admin_view().
 * @param array<string, mixed> $data Template variables.
 * @return string
 */
function contract_pilot_render_admin_view_html($slug, $data = array())
{
    ob_start();
    contract_pilot_render_admin_view($slug, $data);

    return (string) ob_get_clean();
}


/**
 * Build invoice view data for template rendering.
 *
 * @param object $invoice Invoice model.
 * @return array<string, mixed>
 */
function contract_pilot_build_invoice_view_data($invoice)
{
    $columns = contract_pilot()->invoices->get_columns();
    if (! $invoice->is_taxed()) {
        unset($columns['tax']);
    }

    $doc_title = contract_pilot_invoice_heading_status_label($invoice);
    if ('' === $doc_title) {
        $doc_title = __('Contract', 'contract-pilot');
    }

    $business = array(
        'logo'     => get_option('contract_pilot_business_logo', get_site_icon_url(55)),
        'phone'    => get_option('contract_pilot_business_phone'),
        'email'    => get_option('contract_pilot_business_email', get_option('admin_email')),
        'name'     => get_option('contract_pilot_business_name', get_bloginfo('name')),
        'site_url' => site_url(),
    );

    $bill_from_address = contract_pilot_get_formatted_address(
        array(
            'name'       => get_option('contract_pilot_business_name', get_bloginfo('name')),
            'address'    => get_option('contract_pilot_business_address'),
            'city'       => get_option('contract_pilot_business_city'),
            'state'      => get_option('contract_pilot_business_state'),
            'postcode'   => get_option('contract_pilot_business_postcode'),
            'country'    => get_option('contract_pilot_business_country'),
            'email'      => get_option('contract_pilot_business_email'),
            'phone'      => get_option('contract_pilot_business_phone'),
            'tax_number' => get_option('contract_pilot_business_tax_number'),
        )
    );

    $bill_to_address = contract_pilot_get_formatted_address(
        array(
            'name'       => $invoice->contact_name,
            'company'    => $invoice->contact_company,
            'address'    => $invoice->contact_address,
            'city'       => $invoice->contact_city,
            'state'      => $invoice->contact_state,
            'postcode'   => $invoice->contact_postcode,
            'country'    => $invoice->contact_country,
            'email'      => $invoice->contact_email,
            'phone'      => $invoice->contact_phone,
            'tax_number' => $invoice->contact_tax_number,
        )
    );

    $item_rows = array();
    $invoice_items = is_array($invoice->items) ? $invoice->items : array();
    foreach ($invoice_items as $item) {
        $cells = array();

        foreach ($columns as $column_key => $column_label) {
            switch ($column_key) {
                case 'item':
                    $cells[ $column_key ] = array(
                        'value'    => $item->name,
                        'subvalue' => $item->description ? $item->description : '',
                    );
                    break;
                case 'quantity':
                    $quantity_value = (string) $item->quantity;
                    if ($item->unit) {
                        $quantity_value .= ' x ' . $item->unit;
                    }
                    $cells[ $column_key ] = array(
                        'value'    => $quantity_value,
                        'subvalue' => '',
                    );
                    break;
                case 'price':
                    $cells[ $column_key ] = array(
                        'value'    => contract_pilot_format_amount($item->price, $invoice->currency),
                        'subvalue' => '',
                    );
                    break;
                case 'tax':
                    $cells[ $column_key ] = array(
                        'value'    => contract_pilot_format_amount($item->tax, $invoice->currency),
                        'subvalue' => '',
                    );
                    break;
                case 'subtotal':
                    $cells[ $column_key ] = array(
                        'value'    => contract_pilot_format_amount($item->subtotal, $invoice->currency),
                        'subvalue' => '',
                    );
                    break;
            }
        }

        $item_rows[] = $cells;
    }

    $totals = array();
    $totals[] = array(
        'label'  => __('Subtotal', 'contract-pilot'),
        'amount' => contract_pilot_format_amount($invoice->subtotal, $invoice->currency),
        'is_due' => false,
    );

    if ($invoice->is_taxed()) {
        if ('single' === get_option('contract_pilot_tax_total_display')) {
            $totals[] = array(
                'label'  => __('Tax', 'contract-pilot'),
                'amount' => contract_pilot_format_amount($invoice->tax, $invoice->currency),
                'is_due' => false,
            );
        } else {
            foreach ($invoice->get_itemized_taxes() as $tax) {
                $totals[] = array(
                    'label'  => $tax->formatted_name,
                    'amount' => contract_pilot_format_amount($tax->amount, $invoice->currency),
                    'is_due' => false,
                );
            }
        }
    }

    $totals[] = array(
        'label'  => __('Total', 'contract-pilot'),
        'amount' => $invoice->formatted_total,
        'is_due' => false,
    );

    if ($invoice->get_due_amount() > 0) {
        $totals[] = array(
            'label'  => __('Due', 'contract-pilot'),
            'amount' => contract_pilot_format_amount($invoice->get_due_amount(), $invoice->currency),
            'is_due' => true,
        );
    }

    $payments = array();
    $invoice_payments = is_array($invoice->payments) ? $invoice->payments : array();
    foreach ($invoice_payments as $payment) {
        $payments[] = array(
            'url'    => is_admin() ? $payment->get_view_url() : $payment->get_public_url(),
            'number' => $payment->number,
            'date'   => $payment->payment_date
                ? wp_date(get_option('date_format'), strtotime($payment->payment_date))
                : 'N/A',
            'method' => $payment->payment_method_label ? $payment->payment_method_label : 'N/A',
            'amount' => contract_pilot_format_amount(
                contract_pilot_convert_currency($payment->amount, $payment->currency, $invoice->currency),
                $invoice->currency
            ),
        );
    }

    return array(
        'doc_title'         => $doc_title,
        'invoice_number'    => $invoice->number,
        'order_number'      => $invoice->order_number,
        'issue_date'        => $invoice->issue_date ? contract_pilot_format_datetime($invoice->issue_date, contract_pilot_date_format()) : '',
        'due_date'          => $invoice->due_date ? contract_pilot_format_datetime($invoice->due_date, contract_pilot_date_format()) : '',
        'business'          => $business,
        'bill_from_address' => $bill_from_address,
        'bill_to_address'   => $bill_to_address,
        'columns'           => $columns,
        'item_rows'         => $item_rows,
        'totals'            => $totals,
        'note'              => $invoice->note,
        'payments'          => $payments,
        'terms'             => $invoice->terms,
    );
}


function contract_pilot_header()
{
    contract_pilot_get_template_part('header');
}


function contract_pilot_footer()
{
    contract_pilot_get_template_part('footer');
}
