<?php

defined('ABSPATH') || exit;

(static function ($payment_tpl) {
    if (!$payment_tpl || !is_object($payment_tpl)) {
        return;
    }
    $payment = $payment_tpl;
    $business_logo = get_option('contract_pilot_business_logo', get_site_icon_url(55));
    $business_phone = get_option('contract_pilot_business_phone');
    $business_email = get_option('contract_pilot_business_email', get_option('admin_email'));
    $business_name = get_option('contract_pilot_business_name', get_bloginfo('name'));
    ?>
<div class="contract-pilot-document">
    <div class="contract-pilot-document__header">
        <?php if ($business_logo && filter_var($business_logo, FILTER_VALIDATE_URL)) { ?>
            <div class="contract-pilot-document__logo">
                <img src="<?php echo esc_url($business_logo); ?>" alt="<?php esc_attr_e(
                    'Logo',
                    'contract-pilot',
                ); ?>"/>
            </div>
        <?php } ?>
        <div class="contract-pilot-document__info">
            <?php if (!empty($business_name)) { ?>
                <h2><?php echo esc_html($business_name); ?></h2>
            <?php } ?>
            <?php if (!empty($business_phone)) { ?>
                <p><?php echo esc_html($business_phone); ?></p>
            <?php } ?>
            <?php if (!empty($business_email)) { ?>
                <p><?php echo esc_html($business_email); ?></p>
            <?php } ?>
            <p>
                <?php echo esc_html(site_url()); ?>
            </p>
        </div>
        <div class="contract-pilot-document__title">
            <h1><?php esc_html_e('Payment Receipt', 'contract-pilot'); ?></h1>
            <p>
                #<?php echo esc_html($payment->number); ?>
            </p>
        </div>
    </div>
    <div class="contract-pilot-document__divider"></div>
    <div class="contract-pilot-document__billings">
        <div class="contract-pilot-document__billing">
            <h3><?php esc_html_e('From', 'contract-pilot'); ?></h3>
            <p>
                <?php
                    $customer = $payment->customer;
    if ($customer) {
        $address = contract_pilot_get_formatted_address([
            'name' => $customer->name,
            'company' => $customer->company,
            'address' => $customer->address,
            'city' => $customer->city,
            'state' => $customer->state,
            'postcode' => $customer->postcode,
            'country' => $customer->country,
            'tax_number' => $customer->tax_number,
        ]);
        echo wp_kses_post($address);
    } else {
        echo esc_html('N/A');
    }
    ?>
            </p>
        </div>
        <div class="contract-pilot-document__billing">
            <h3><?php esc_html_e('To', 'contract-pilot'); ?></h3>
            <p>
                <?php
    $address = contract_pilot_get_formatted_address([
        'name' => get_option('contract_pilot_business_name', get_bloginfo('name')),
        'address' => get_option('contract_pilot_business_address'),
        'city' => get_option('contract_pilot_business_city'),
        'state' => get_option('contract_pilot_business_state'),
        'postcode' => get_option('contract_pilot_business_postcode'),
        'country' => get_option('contract_pilot_business_country'),
        'email' => get_option('contract_pilot_business_email'),
        'phone' => get_option('contract_pilot_business_phone'),
        'tax_number' => get_option('contract_pilot_business_tax_number'),
    ]);

    echo wp_kses_post($address);
    ?>
            </p>
        </div>
    </div>
    <div class="contract-pilot-document__divider"></div>
    <div class="contract-pilot-document__summary">
        <h3><?php esc_html_e('Payment Summary', 'contract-pilot'); ?></h3>
        <table>
            <tbody>
            <tr>
                <th scope="row"><?php esc_html_e('Amount', 'contract-pilot'); ?></th>
                <td><?php echo esc_html($payment->formatted_amount); ?></td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Date', 'contract-pilot'); ?></th>
                <td><?php echo esc_html(
                    $payment->payment_date
                        ? contract_pilot_format_datetime($payment->payment_date, contract_pilot_date_format())
                        : 'N/A',
                ); ?></td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Method', 'contract-pilot'); ?></th>
                <td><?php echo esc_html(
                    $payment->payment_method ? $payment->payment_method_label : 'N/A',
                ); ?></td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Reference', 'contract-pilot'); ?></th>
                <td><?php echo esc_html(
                    $payment->reference ? $payment->reference : 'N/A',
                ); ?></td>
            </tr>
            <?php if ($payment->invoice_id && $payment->invoice) { ?>
                <tr>
                    <th scope="row"><?php esc_html_e('Invoice', 'contract-pilot'); ?></th>
                    <td>
                        <a href="<?php echo esc_url(
                            is_admin()
                                ? $payment->invoice->get_view_url()
                                : $payment->invoice->get_public_url(),
                        ); ?>">
                            <?php echo esc_html($payment->invoice->number); ?>
                        </a>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>

    <?php if ($payment->notes) { ?>
        <div class="contract-pilot-document__notes">
            <h3><?php esc_html_e('Notes', 'contract-pilot'); ?></h3>
            <p><?php echo wp_kses_post($payment->notes); ?></p>
        </div>
    <?php } ?>
</div>
<?php
})(isset($payment) ? $payment : null);
