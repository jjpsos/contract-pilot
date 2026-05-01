<?php


defined("ABSPATH") || exit();
$business_logo = get_option("eac_business_logo", get_site_icon_url(55));
$business_phone = get_option("eac_business_phone");
$business_email = get_option("eac_business_email", get_option("admin_email"));
$business_name = get_option("eac_business_name", get_bloginfo("name"));
?>
<div class="eac-document">
	<div class="eac-document__header">
		<?php if ($business_logo && filter_var($business_logo, FILTER_VALIDATE_URL)): ?>
			<div class="eac-document__logo">
				<img src="<?php echo esc_url($business_logo); ?>" alt="<?php esc_attr_e(
    "Logo",
    "otto-contracts",
); ?>"/>
			</div>
		<?php endif; ?>
		<div class="eac-document__info">
			<?php if (!empty($business_name)): ?>
				<h2><?php echo esc_html($business_name); ?></h2>
			<?php endif; ?>
			<?php if (!empty($business_phone)): ?>
				<p><?php echo esc_html($business_phone); ?></p>
			<?php endif; ?>
			<?php if (!empty($business_email)): ?>
				<p><?php echo esc_html($business_email); ?></p>
			<?php endif; ?>
			<p>
				<?php echo esc_html(site_url()); ?>
			</p>
		</div>
		<div class="eac-document__title">
			<h1><?php esc_html_e("Payment Receipt", "otto-contracts"); ?></h1>
			<p>
				#<?php echo esc_html($payment->number); ?>
			</p>
		</div>
	</div>
	<div class="eac-document__divider"></div>
	<div class="eac-document__billings">
		<div class="eac-document__billing">
			<h3><?php esc_html_e("From", "otto-contracts"); ?></h3>
			<p>
				<?php
    $customer = $payment->customer;
    if ($customer) {
        $address = eac_get_formatted_address([
            "name" => $customer->name,
            "company" => $customer->company,
            "address" => $customer->address,
            "city" => $customer->city,
            "state" => $customer->state,
            "postcode" => $customer->postcode,
            "country" => $customer->country,
            "tax_number" => $customer->tax_number,
        ]);
        echo wp_kses_post($address);
    } else {
        echo esc_html("N/A");
    }
    ?>
			</p>
		</div>
		<div class="eac-document__billing">
			<h3><?php esc_html_e("To", "otto-contracts"); ?></h3>
			<p>
				<?php
    $address = eac_get_formatted_address([
        "name" => get_option("eac_business_name", get_bloginfo("name")),
        "address" => get_option("eac_business_address"),
        "city" => get_option("eac_business_city"),
        "state" => get_option("eac_business_state"),
        "postcode" => get_option("eac_business_postcode"),
        "country" => get_option("eac_business_country"),
        "email" => get_option("eac_business_email"),
        "phone" => get_option("eac_business_phone"),
        "tax_number" => get_option("eac_business_tax_number"),
    ]);

    echo wp_kses_post($address);
    ?>
			</p>
		</div>
	</div>
	<div class="eac-document__divider"></div>
	<div class="eac-document__summary">
		<h3><?php esc_html_e("Payment Summary", "otto-contracts"); ?></h3>
		<table>
			<tbody>
			<tr>
				<th scope="row"><?php esc_html_e("Amount", "otto-contracts"); ?></th>
				<td><?php echo esc_html($payment->formatted_amount); ?></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e("Date", "otto-contracts"); ?></th>
				<td><?php echo esc_html(
        $payment->payment_date
            ? eac_format_datetime($payment->payment_date, eac_date_format())
            : "N/A",
    ); ?></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e("Method", "otto-contracts"); ?></th>
				<td><?php echo esc_html(
        $payment->payment_method ? $payment->payment_method_label : "N/A",
    ); ?></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e("Reference", "otto-contracts"); ?></th>
				<td><?php echo esc_html(
        $payment->reference ? $payment->reference : "N/A",
    ); ?></td>
			</tr>
			<?php if ($payment->invoice_id && $payment->invoice): ?>
				<tr>
					<th scope="row"><?php esc_html_e("Invoice", "otto-contracts"); ?></th>
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
			<?php endif; ?>
			</tbody>
		</table>
	</div>

	<?php if ($payment->notes): ?>
		<div class="eac-document__notes">
			<h3><?php esc_html_e("Notes", "otto-contracts"); ?></h3>
			<p><?php echo wp_kses_post($payment->notes); ?></p>
		</div>
	<?php endif; ?>
</div>
