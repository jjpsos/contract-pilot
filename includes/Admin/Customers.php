<?php

namespace Otto\Admin;

use Otto\Models\Customer;
use Otto\Utilities\ReportsUtil;

defined("ABSPATH") || exit();


class Customers
{
    
    public function __construct()
    {
        add_filter("eac_sales_page_tabs", [__CLASS__, "register_tabs"]);
        add_action("admin_post_eac_edit_customer", [__CLASS__, "handle_edit"]);
        add_action("eac_sales_page_customers_loaded", [
            __CLASS__,
            "page_loaded",
        ]);
        add_action("eac_sales_page_customers_content", [
            __CLASS__,
            "page_content",
        ]);
        add_action("eac_customer_profile_section_overview", [
            __CLASS__,
            "overview_section",
        ]);
        add_action("eac_customer_profile_section_payments", [
            __CLASS__,
            "payments_section",
        ]);
        add_action("eac_customer_profile_section_invoices", [
            __CLASS__,
            "invoices_section",
        ]);
        add_action("eac_customer_profile_section_notes", [
            __CLASS__,
            "notes_section",
        ]);
    }

    
    public static function register_tabs($tabs)
    {
        if (current_user_can("eac_read_customers")) {
            
            $tabs["customers"] = __("Clients", "otto-contracts"); 
        }

        return $tabs;
    }

    
    public static function handle_edit()
    {
        check_admin_referer("eac_edit_customer");
        if (!current_user_can("eac_edit_customers")) {
            
            wp_die(
                esc_html__(
                    "You do not have permission to edit customers.",
                    "otto-contracts",
                ),
            );
        }

        $referer = wp_get_referer();
        $data = [
            "id" => isset($_POST["id"])
                ? sanitize_text_field(wp_unslash($_POST["id"]))
                : "",
            "name" => isset($_POST["name"])
                ? sanitize_text_field(wp_unslash($_POST["name"]))
                : "",
            "company" => isset($_POST["company"])
                ? sanitize_text_field(wp_unslash($_POST["company"]))
                : "",
            "email" => isset($_POST["email"])
                ? sanitize_email(wp_unslash($_POST["email"]))
                : "",
            "phone" => isset($_POST["phone"])
                ? sanitize_text_field(wp_unslash($_POST["phone"]))
                : "",
            "website" => isset($_POST["website"])
                ? esc_url_raw(wp_unslash($_POST["website"]))
                : "",
            "address" => isset($_POST["address"])
                ? sanitize_text_field(wp_unslash($_POST["address"]))
                : "",
            "city" => isset($_POST["city"])
                ? sanitize_text_field(wp_unslash($_POST["city"]))
                : "",
            "state" => isset($_POST["state"])
                ? sanitize_text_field(wp_unslash($_POST["state"]))
                : "",
            "postcode" => isset($_POST["postcode"])
                ? sanitize_text_field(wp_unslash($_POST["postcode"]))
                : "",
            "country" => isset($_POST["country"])
                ? sanitize_text_field(wp_unslash($_POST["country"]))
                : "",
            "tax_number" => isset($_POST["tax_number"])
                ? sanitize_text_field(wp_unslash($_POST["tax_number"]))
                : "",
            "currency" => isset($_POST["currency"])
                ? sanitize_text_field(wp_unslash($_POST["currency"]))
                : "",
        ];

        $customer = EAC()->customers->insert($data);

        if (is_wp_error($customer)) {
            EAC()->flash->error($customer->get_error_message());
        } else {
            EAC()->flash->success(
                __("Customer saved successfully.", "otto-contracts"),
            );
            $referer = add_query_arg("id", $customer->id, $referer);
            $referer = add_query_arg("action", "view", $referer);
            $referer = remove_query_arg(["add"], $referer);
        }

        wp_safe_redirect($referer);
        exit();
    }

    
    public static function page_loaded($action)
    {
        global $eac_list_table;
        switch ($action) {
            case "add":
                if (!current_user_can("eac_edit_customers")) {
                    
                    wp_die(
                        esc_html__(
                            "You do not have permission to add customers.",
                            "otto-contracts",
                        ),
                    );
                }
                break;

            case "view":
            case "edit":
                $id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
                if (!EAC()->customers->get($id)) {
                    wp_die(
                        esc_html__(
                            "You attempted to retrieve a customer that does not exist. Perhaps it was deleted?",
                            "otto-contracts",
                        ),
                    );
                }
                if (
                    "edit" === $action &&
                    !current_user_can("eac_edit_customers")
                ) {
                    
                    wp_die(
                        esc_html__(
                            "You do not have permission to edit customers.",
                            "otto-contracts",
                        ),
                    );
                }
                break;

            default:
                $screen = get_current_screen();
                $eac_list_table = new ListTables\Customers();
                $eac_list_table->prepare_items();
                $screen->add_option("per_page", [
                    "label" => __(
                        "Number of items per page:",
                        "otto-contracts",
                    ),
                    "default" => 20,
                    "option" => "eac_customers_per_page",
                ]);
                break;
        }
    }

    
    public static function page_content($action)
    {
        switch ($action) {
            case "add":
            case "edit":
                include __DIR__ . "/views/customer-edit.php";
                break;

            case "view":
                include __DIR__ . "/views/customer-view.php";
                break;

            default:
                include __DIR__ . "/views/customer-list.php";
                break;
        }
    }

    
    public static function overview_section($customer)
    {
        global $wpdb;
        wp_enqueue_script("eac-chartjs");
        $year_start_date = ReportsUtil::get_year_start_date();
        $year_end_date = ReportsUtil::get_year_end_date();
        $date_column = ReportsUtil::get_localized_time_sql("t.payment_date");
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT SUM(t.amount/t.exchange_rate) AS amount,
		        MONTH($date_column) AS month,
		        YEAR($date_column) AS year
		 	    FROM {$wpdb->prefix}otto_transactions AS t
		 		WHERE t.contact_id = %d
		   	 	AND t.type = 'payment'
		   		AND t.payment_date BETWEEN %s AND %s
		 		GROUP BY YEAR($date_column), MONTH($date_column)
		 		ORDER BY YEAR($date_column), MONTH($date_column)",
                $customer->id,
                get_gmt_from_date($year_start_date),
                get_gmt_from_date($year_end_date),
            ),
        );

        $invoices = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(total/exchange_rate) as total FROM {$wpdb->prefix}otto_documents WHERE contact_id = %d AND contact_id !='' AND type='invoice' AND status != 'draft'",
                $customer->id,
            ),
        );

        $paid = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(amount/exchange_rate) as total FROM {$wpdb->prefix}otto_transactions WHERE contact_id = %d AND contact_id != '' AND type='payment'",
                $customer->id,
            ),
        );

        $due = empty($invoices) ? 0 : max($invoices - $paid, 0);
        $chart_data = ReportsUtil::annualize_data($results);
        $chart = [
            "type" => "line",
            "labels" => array_keys($chart_data),
            "datasets" => [
                [
                    "label" => __("Payments", "otto-contracts"),
                    "backgroundColor" => "#3644ff",
                    "borderColor" => "#3644ff",
                    "fill" => false,
                    "data" => array_values($chart_data),
                ],
            ],
        ];
        ?>

		<h2 class="has--border"><?php esc_html_e(
      "Overview",
      "otto-contracts",
  ); ?></h2>
		<div class="eac-chart">
			<canvas class="eac-chart" id="eac-customer-chart" style="height: 300px;margin-bottom: 20px;" data-datasets="<?php echo esc_attr(
       wp_json_encode($chart),
   ); ?>" data-currency="<?php echo esc_attr(EAC()->currencies->get_symbol(eac_base_currency())); ?>"></canvas>
		</div>
		<div class="eac-stats stats--2">
			<div class="eac-stat">
				<div class="eac-stat__label"><?php esc_html_e(
        "Due",
        "otto-contracts",
    ); ?></div>
				<div class="eac-stat__value"><?php echo esc_html(
        eac_format_amount($due),
    ); ?></div>
			</div>
			<div class="eac-stat">
				<div class="eac-stat__label"><?php esc_html_e(
        "Paid",
        "otto-contracts",
    ); ?></div>
				<div class="eac-stat__value"><?php echo esc_html(
        eac_format_amount($paid),
    ); ?></div>
			</div>
		</div>

		<?php $attributes = [
      [
          "label" => __("Name", "otto-contracts"),
          "value" => $customer->name,
      ],
      [
          "label" => __("Company", "otto-contracts"),
          "value" => $customer->company,
      ],
      [
          "label" => __("Email", "otto-contracts"),
          "value" => $customer->email,
      ],
      [
          "label" => __("Phone", "otto-contracts"),
          "value" => $customer->phone,
      ],
      [
          "label" => __("Website", "otto-contracts"),
          "value" => $customer->website,
      ],
      [
          "label" => __("Address", "otto-contracts"),
          "value" => $customer->address,
      ],
      [
          "label" => __("City", "otto-contracts"),
          "value" => $customer->city,
      ],
      [
          "label" => __("State", "otto-contracts"),
          "value" => $customer->state,
      ],
      [
          "label" => __("Postcode", "otto-contracts"),
          "value" => $customer->postcode,
      ],
      [
          "label" => __("Country", "otto-contracts"),
          "value" => $customer->country_name,
      ],
      [
          "label" => __("Tax Number", "otto-contracts"),
          "value" => $customer->tax_number,
      ],
      [
          "label" => __("Currency", "otto-contracts"),
          "value" => $customer->currency,
      ],
      [
          "label" => __("Created", "otto-contracts"),
          "value" => $customer->date_created
              ? eac_format_datetime($customer->date_created, eac_date_format())
              : "&mdash;",
      ],
      [
          "label" => __("Updated", "otto-contracts"),
          "value" => $customer->date_updated
              ? eac_format_datetime($customer->date_updated, eac_date_format())
              : "&mdash;",
      ],
  ]; ?>
		<h2><?php esc_html_e("Details", "otto-contracts"); ?></h2>
		<table class="eac-table is--striped is--bordered">
			<tbody>
			<?php foreach ($attributes as $attribute): ?>
				<tr>
					<th><?php echo esc_html($attribute["label"]); ?></th>
					<td><?php echo esc_html(
         empty($attribute["value"]) ? "&mdash;" : $attribute["value"],
     ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
    }

    
    public static function payments_section($customer)
    {
        $payments = EAC()->payments->query([
            "contact_id" => $customer->id,
            "contact_id__not" => "",
            "limit" => 20,
            "orderby" => "payment_date",
            "order" => "DESC",
        ]); ?>
		<h2 class="has--border"><?php esc_html_e(
      "Recent Payments",
      "otto-contracts",
  ); ?></h2>
		<table class="widefat fixed striped">
			<thead>
			<tr>
				<th><?php esc_html_e("Number", "otto-contracts"); ?></th>
				<th><?php esc_html_e("Date", "otto-contracts"); ?></th>
				<th><?php esc_html_e("Reference", "otto-contracts"); ?></th>
				<th><?php esc_html_e("Amount", "otto-contracts"); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php if ($payments): ?>
				<?php foreach ($payments as $payment): ?>
					<tr>
						<td>
							<a href="<?php echo esc_url($payment->get_view_url()); ?>">
								<?php echo esc_html($payment->number); ?>
							</a>
						</td>
						<td><?php echo esc_html(
          $payment->payment_date
              ? wp_date(eac_date_format(), strtotime($payment->payment_date))
              : "&mdash;",
      ); ?></td>
						<td><?php echo esc_html(
          $payment->reference ? $payment->reference : "&mdash;",
      ); ?></td>
						<td><?php echo esc_html($payment->formatted_amount); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php else: ?>
				<tr>
					<td colspan="4"><?php esc_html_e(
         "No payments found.",
         "otto-contracts",
     ); ?></td>
				</tr>
			<?php endif; ?>
			</tbody>
		</table>
		<?php
    }

    
    public static function invoices_section($customer)
    {
        $invoices = EAC()->invoices->query([
            "contact_id" => $customer->id,
            "contact_id__neq" => "",
            "limit" => 20,
            "orderby" => "date",
            "order" => "DESC",
        ]); ?>
		<h2 class="has--border"><?php esc_html_e(
      "Recent Invoices",
      "otto-contracts",
  ); ?></h2>
		<table class="widefat fixed striped">
			<thead>
			<tr>
				<th><?php esc_html_e("Number", "otto-contracts"); ?></th>
				<th><?php esc_html_e("Date", "otto-contracts"); ?></th>
				<th><?php esc_html_e("Total", "otto-contracts"); ?></th>
				<th><?php esc_html_e("Status", "otto-contracts"); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php if ($invoices): ?>
				<?php foreach ($invoices as $invoice): ?>
					<tr>
						<td>
							<a href="<?php echo esc_url($invoice->get_view_url()); ?>">
								<?php echo esc_html($invoice->number); ?>
							</a>
						<td><?php echo esc_html($invoice->issue_date); ?></td>
						<td><?php echo esc_html($invoice->formatted_total); ?></td>
						<td><?php echo esc_html($invoice->status_label); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php else: ?>
				<tr>
					<td colspan="4"><?php esc_html_e(
         "No invoices found.",
         "otto-contracts",
     ); ?></td>
				</tr>
			<?php endif; ?>
			</tbody>
		</table>
		<?php
    }

    
    public static function notes_section($customer)
    {
        $notes = EAC()->notes->query([
            "parent_id" => $customer->id,
            "parent_type" => "customer",
            "orderby" => "date_created",
            "order" => "DESC",
            "limit" => 20,
        ]); ?>

		<h2 class="has--border"><?php esc_html_e("Notes", "otto-contracts"); ?></h2>

		<?php if (
      current_user_can("eac_edit_notes")
  ):
       ?>
			<div class="eac-form-field">
				<label for="eac-note"><?php esc_html_e(
        "Add Note",
        "otto-contracts",
    ); ?></label>
				<textarea id="eac-note" cols="30" rows="2" placeholder="<?php esc_attr_e(
        "Enter Note",
        "otto-contracts",
    ); ?>"></textarea>
			</div>
			<button id="eac-add-note" type="button" class="button tw-mb-[20px]" data-parent_id="<?php echo esc_attr(
       $customer->id,
   ); ?>" data-parent_type="customer" data-nonce="<?php echo esc_attr(
    wp_create_nonce("eac_add_note"),
); ?>">
				<?php esc_html_e("Add Note", "otto-contracts"); ?>
			</button>
		<?php endif; ?>

		<?php include __DIR__ . "/views/note-list.php"; ?>
		<?php
    }
}
