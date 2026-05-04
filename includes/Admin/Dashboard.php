<?php

namespace Otto\Admin; 

use Otto\Utilities\NumberUtil;
use Otto\Utilities\ReportsUtil;

defined("ABSPATH") || exit();


class Dashboard
{
    
    public function __construct()
    {
        add_action("eac_dashboard_page_content", [__CLASS__, "page_content"]);
        add_action("admin_init", [__CLASS__, "save_dashboard_message_toggle"]);
        add_action("eac_dashboard_overview_widgets", [
            __CLASS__,
            "overview_widget",
        ]);
        add_filter("eac_dashboard_overview_stats", [
            __CLASS__,
            "overview_stats",
        ]);
        add_action("eac_dashboard_widgets", [__CLASS__, "recent_payments"]);
        add_action("eac_dashboard_widgets", [__CLASS__, "recent_expenses"]);
        add_action("eac_dashboard_widgets", [__CLASS__, "recent_invoices"]);
        add_action("eac_dashboard_widgets", [__CLASS__, "top_items"]);
        add_action("eac_dashboard_widgets", [__CLASS__, "top_customers"]);
        add_action("eac_dashboard_widgets", [__CLASS__, "top_vendors"]);
    }

    
    public static function render_page()
    {
        include __DIR__ . "/views/dashboard.php";
    }

    /**
     * Render Sales/Expenses/Profits tabs on dashboard page.
     *
     * @return void
     */
    public static function render_reports_tabs_section()
    {
        if (!current_user_can("eac_read_reports")) {
            return;
        }

        $tabs = [
            "sales" => __("Sales", "otto-contracts"),
            "expenses" => __("Expenses", "otto-contracts"),
            "profits" => __("Profits", "otto-contracts"),
        ];
        $current_tab = !empty($_GET["dashboard_report_tab"])
            ? sanitize_key(wp_unslash($_GET["dashboard_report_tab"]))
            : "sales";
        if (!isset($tabs[$current_tab])) {
            $current_tab = "sales";
        }

        $base_url = admin_url("admin.php?page=otto-accounting");
        ?>
		<div class="eac-card" style="margin-top: 0;">
			<div class="eac-card__body">
				<nav class="nav-tab-wrapper eac-navbar">
					<?php foreach ($tabs as $tab_key => $tab_label): ?>
						<a href="<?php echo esc_url(
          add_query_arg("dashboard_report_tab", $tab_key, $base_url),
      ); ?>" class="nav-tab <?php echo esc_attr(
    $current_tab === $tab_key ? "nav-tab-active" : "",
); ?>">
							<?php echo esc_html($tab_label); ?>
						</a>
					<?php endforeach; ?>
				</nav>
				<div style="margin-top: 12px;">
					<?php
     switch ($current_tab) {
         case "expenses":
             Reports\Expenses::render();
             break;
         case "profits":
             Reports\Profits::render();
             break;
         case "sales":
         default:
             Reports\Sales::render();
             break;
     }
     ?>
				</div>
			</div>
		</div>
		<?php
    }

    /**
     * Save dashboard info message visibility.
     *
     * @return void
     */
    public static function save_dashboard_message_toggle()
    {
        if (
            !is_admin() ||
            !isset($_POST["eac_dashboard_message_toggle_submit"]) ||
            !isset($_GET["page"]) ||
            "otto-accounting" !== sanitize_key(wp_unslash($_GET["page"]))
        ) {
            return;
        }

        if (!current_user_can("eac_manage_options")) {
            return;
        }

        check_admin_referer("eac_toggle_dashboard_message");

        $is_enabled = isset($_POST["eac_dashboard_message_enabled"]) ? "yes" : "no";
        update_option("eac_dashboard_message_enabled", $is_enabled);
    }

    /**
     * Render dashboard intro/info message card.
     *
     * @return void
     */
    public static function render_info_message_card()
    {
        $is_enabled = "no" !== get_option("eac_dashboard_message_enabled", "yes");
        ?>
		<div class="eac-card" style="margin-top: 8px;">
			<?php if ($is_enabled): ?>
				<div class="eac-card__header">
					<h3 class="eac-card__title"><?php esc_html_e("About Otto Contracts", "otto-contracts"); ?></h3>
				</div>
			<?php endif; ?>
			<div class="eac-card__body">
				<?php if ($is_enabled): ?>
					<p style="margin: 0 0 8px;"><?php esc_html_e(
         "Otto Contracts helps you manage contracts and related business records in WordPress.",
         "otto-contracts"
     ); ?></p>
					<p style="margin: 0 0 8px;">
						<?php esc_html_e("Go to", "otto-contracts"); ?>
						<a href="https://www.softestate.net/otto-contracts/" target="_blank" rel="noopener noreferrer">https://www.softestate.net/otto-contracts/</a>
						<?php esc_html_e("for a showcase of advanced features.", "otto-contracts"); ?>
					</p>
					<p style="margin: 0 0 8px;">
						<?php
     $contribution_url = "https://www.softestate.net/contribution/";
     echo wp_kses(
         sprintf(
             /* translators: %1$s: contribution page URL (href), %2$s: same URL as visible link text */
             __(
                 'Thank you for your aid, <a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>, to this open source project.',
                 "otto-contracts",
             ),
             esc_url($contribution_url),
             esc_html($contribution_url),
         ),
         [
             "a" => [
                 "href" => true,
                 "target" => true,
                 "rel" => true,
             ],
         ],
     );
?>
					</p>
				<?php endif; ?>
				<form method="post" action="">
					<?php wp_nonce_field("eac_toggle_dashboard_message"); ?>
					<label for="eac-dashboard-message-enabled">
						<input type="checkbox" id="eac-dashboard-message-enabled" name="eac_dashboard_message_enabled" value="yes" <?php checked(
          $is_enabled,
          true
      ); ?> />
						<?php esc_html_e("Show dashboard intro message", "otto-contracts"); ?>
					</label>
					<p style="margin: 8px 0 0;">
						<button type="submit" class="button" name="eac_dashboard_message_toggle_submit" value="1"><?php esc_html_e(
          "Save",
          "otto-contracts"
      ); ?></button>
					</p>
				</form>
			</div>
		</div>
		<?php
    }

    
    public static function overview_widget()
    {
        if (!current_user_can("eac_read_reports")) {
            
            return;
        }
        $report = ReportsUtil::get_profits_report(wp_date("Y"), true);
        $profits = array_sum($report["profits"]);
        $payments = array_sum($report["payments"]);
        $delta =
            $profits > 0 && $payments > 0 ? ($profits / $payments) * 100 : 0;
        $stats = apply_filters("eac_dashboard_overview_stats", [
            [
                "label" => __("Incoming", "otto-contracts"),
                "value" => eac_format_amount(array_sum($report["payments"])),
            ],
            [
                "label" => __("Outgoing", "otto-contracts"),
                "value" => eac_format_amount(array_sum($report["expenses"])),
            ],
            [
                "label" => __("Profit", "otto-contracts"),
                "value" => eac_format_amount(array_sum($report["profits"])),
                "delta" => number_format($delta, 2),
            ],
        ]);
        $datasets = [
            "labels" => array_keys($report["payments"]),
            "type" => "line",
            "datasets" => [
                [
                    "backgroundColor" => "#3644ff",
                    "borderColor" => "#3644ff",
                    "data" => array_values($report["payments"]),
                    "fill" => false,
                    "label" => __("Sales", "otto-contracts"),
                    "type" => "line",
                ],
                [
                    "label" => __("Expenses", "otto-contracts"),
                    "backgroundColor" => "#f2385a",
                    "borderColor" => "#f2385a",
                    "type" => "line",
                    "fill" => false,
                    "data" => array_values($report["expenses"]),
                ],
                [
                    "label" => __("Profit/Loss", "otto-contracts"),
                    "backgroundColor" => "#00d48f",
                    "borderColor" => "#00d48f",
                    "type" => "line",
                    "fill" => false,
                    "data" => array_values($report["profits"]),
                ],
            ],
        ];
        ?>
		<div class="eac-card is--widget">
			<div class="eac-card__header">
				<?php esc_html_e("Overview", "otto-contracts"); ?>
			</div>
			<div class="eac-card__body">
				<canvas class="eac-chart" style="min-height: 300px;" data-datasets="<?php echo esc_attr(
        wp_json_encode($datasets),
    ); ?>" data-currency="<?php echo esc_attr(
    EAC()->currencies->get_symbol(eac_base_currency()),
); ?>"></canvas>
			</div>
		</div>
		<div class="eac-stats stats--3">
			<?php foreach ($stats as $stat): ?>
				<div class="eac-stat">
					<div class="eac-stat__label"><?php echo esc_html($stat["label"]); ?></div>
					<div class="eac-stat__value">
						<?php echo esc_html($stat["value"]); ?>
					</div>
					<?php if (isset($stat["meta"])): ?>
						<div class="eac-stat__meta">
							<span><?php echo wp_kses_post(
           implode(" </span><span> ", $stat["meta"]),
       ); ?></span>
						</div>
					<?php endif; ?>
					<?php if (isset($stat["delta"])): ?>
						<?php $delta_class = $stat["delta"] > 0 ? "is--positive" : "is--negative"; ?>
						<div class="eac-stat__delta <?php echo esc_attr(
          $delta_class,
      ); ?>" title="<?php esc_html_e(
    "Percentage of profit",
    "otto-contracts",
); ?>">
							<?php echo esc_html($stat["delta"]); ?>%
						</div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
    }

    
    public static function overview_stats($stats)
    {
        global $wpdb;

        $documents = $wpdb->get_row(
            "SELECT
				SUM(CASE WHEN d.type = 'invoice' THEN (d.total / d.exchange_rate) ELSE 0 END) -
				SUM(CASE WHEN d.type = 'invoice' THEN COALESCE(t.amount / t.exchange_rate, 0) ELSE 0 END) AS receivable,

				SUM(CASE WHEN d.type = 'bill' THEN (d.total / d.exchange_rate) ELSE 0 END) -
				SUM(CASE WHEN d.type = 'bill' THEN COALESCE(t.amount / t.exchange_rate, 0) ELSE 0 END) AS payable
			FROM
        		{$wpdb->prefix}otto_documents d
        	LEFT JOIN
        		 {$wpdb->prefix}otto_transactions t ON d.id = t.document_id
        	WHERE
        	    d.status IN ( 'received', 'sent', 'overdue', 'partial');",
        );

        $stats[] = [
            "label" => __("Receivable", "otto-contracts"),
            "value" => eac_format_amount($documents->receivable),
        ];

        $stats[] = [
            "label" => __("Payable", "otto-contracts"),
            "value" => eac_format_amount($documents->payable),
        ];

        $stats[] = [
            "label" => __("Upcoming", "otto-contracts"),
            "value" => eac_format_amount(
                $documents->receivable - $documents->payable,
            ),
        ];

        return $stats;
    }

    
    public static function recent_payments()
    {
        $payments = EAC()->payments->query([
            "limit" => 5,
            "orderby" => "payment_date",
            "order" => "DESC",
        ]); ?>
		<div class="eac-card is--widget">
			<div class="eac-card__header">
				<?php esc_html_e("Recent Payments", "otto-contracts"); ?>
				<?php if (!empty($payments)): ?>
					<a href="<?php echo esc_url(
         admin_url("admin.php?page=eac-sales&tab=payments"),
     ); ?>"><?php esc_html_e("View all", "otto-contracts"); ?></a>
				<?php endif; ?>
			</div>
			<?php if (!empty($payments)): ?>
			<table class="eac-table">
				<thead>
				<tr>
					<th><?php esc_html_e("Payment #", "otto-contracts"); ?></th>
					<th><?php esc_html_e("Date", "otto-contracts"); ?></th>
					<th class="is--last-item"><?php esc_html_e(
         "Amount",
         "otto-contracts",
     ); ?></th>
				</tr>
				</thead>
				<tbody>
					<?php foreach ($payments as $payment): ?>
						<tr>
							<td><a href="<?php echo esc_url(
           $payment->get_view_url(),
       ); ?>"><?php echo esc_html($payment->number); ?></a></td>
							<td><?php echo esc_html(
           wp_date(eac_date_format(), strtotime($payment->payment_date)),
       ); ?></td>
							<td class="is--last-item"><?php echo esc_html(
           $payment->formatted_amount,
       ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php else: ?>
				<div class="eac-card__body">
					<p class="empty"><?php esc_html_e(
         "No payments found.",
         "otto-contracts",
     ); ?></p>
				</div>
			<?php endif; ?>
		</div>
		<?php
    }

    
    public static function recent_expenses()
    {
        $expenses = EAC()->expenses->query([
            "limit" => 5,
            "orderby" => "payment_date",
            "order" => "DESC",
        ]); ?>
		<div class="eac-card is--widget">
			<div class="eac-card__header">
				<?php esc_html_e("Recent Expenses", "otto-contracts"); ?>
				<?php if (!empty($expenses)): ?>
					<a href="<?php echo esc_url(
         admin_url("admin.php?page=eac-purchases&tab=expenses"),
     ); ?>"><?php esc_html_e("View all", "otto-contracts"); ?></a>
				<?php endif; ?>
			</div>
			<?php if (!empty($expenses)): ?>
				<table class="eac-table">
					<thead>
					<tr>
						<th><?php esc_html_e("Expense #", "otto-contracts"); ?></th>
						<th><?php esc_html_e("Date", "otto-contracts"); ?></th>
						<th class="is--last-item"><?php esc_html_e(
          "Amount",
          "otto-contracts",
      ); ?></th>
					</tr>
					</thead>
					<tbody>
					<?php foreach ($expenses as $expense): ?>
						<tr>
							<td><a href="<?php echo esc_url(
           $expense->get_view_url(),
       ); ?>"><?php echo esc_html($expense->number); ?></a></td>
							<td><?php echo esc_html(
           wp_date(eac_date_format(), strtotime($expense->payment_date)),
       ); ?></td>
							<td class="is--last-item"><?php echo esc_html(
           $expense->formatted_amount,
       ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php else: ?>
				<div class="eac-card__body">
					<p class="empty"><?php esc_html_e(
         "No expenses found.",
         "otto-contracts",
     ); ?></p>
				</div>
			<?php endif; ?>
		</div>
		<?php
    }

    
    public static function recent_invoices()
    {
        $invoices = EAC()->invoices->query([
            "limit" => 5,
            "orderby" => "date",
            "order" => "DESC",
        ]); ?>
		<div class="eac-card is--widget">
			<div class="eac-card__header">
				<?php esc_html_e("Recent Contracts", "otto-contracts"); ?>
				<?php if (!empty($invoices)): ?>
					<a href="<?php echo esc_url(
         admin_url("admin.php?page=eac-sales&tab=invoices"),
     ); ?>"><?php esc_html_e("View all", "otto-contracts"); ?></a>
				<?php endif; ?>
			</div>
			<?php if (!empty($invoices)): ?>
			<table class="eac-table">
				<thead>
				<tr>
					<th><?php esc_html_e("Contract #", "otto-contracts"); ?></th>
					<th><?php esc_html_e("Date", "otto-contracts"); ?></th>
					<th class="is--last-item"><?php esc_html_e(
         "Amount",
         "otto-contracts",
     ); ?></th>
				</tr>
				</thead>
				<tbody>
					<?php foreach ($invoices as $invoice): ?>
						<tr>
							<td><a href="<?php echo esc_url(
           $invoice->get_view_url(),
       ); ?>"><?php echo esc_html($invoice->number); ?></a></td>
							<td><?php echo esc_html(
           wp_date(eac_date_format(), strtotime($invoice->issue_date)),
       ); ?></td>
							<td class="is--last-item"><?php echo esc_html(
           $invoice->formatted_total,
       ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php else: ?>
				<div class="eac-card__body">
					<p class="empty"><?php esc_html_e(
         "No invoices found.",
         "otto-contracts",
     ); ?></p>
				</div>
			<?php endif; ?>
		</div>
		<?php
    }

    
    public static function top_items()
    {
        global $wpdb;
        
        $item_ids = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT di.item_id, SUM(di.subtotal / d.exchange_rate) AS total_sales
				 FROM {$wpdb->prefix}otto_document_items AS di
         	     JOIN {$wpdb->prefix}otto_documents AS d ON di.document_id = d.id
                 WHERE d.type = %s AND d.status = %s
                 GROUP BY di.item_id
                 ORDER BY total_sales DESC
         		LIMIT 5",
                "invoice",
                "paid",
            ),
        );

        $items = [];
        foreach ($item_ids as $item_id) {
            $item = EAC()->items->get($item_id->item_id);
            if ($item) {
                $item->total_sales = $item_id->total_sales;
                $items[] = $item;
            }
        }
        ?>
		<div class="eac-card is--widget">
			<div class="eac-card__header">
				<?php esc_html_e("Top Items", "otto-contracts"); ?>
				<?php if (!empty($items)): ?>
					<a href="<?php echo esc_url(
         admin_url("admin.php?page=eac-items"),
     ); ?>"><?php esc_html_e("View all", "otto-contracts"); ?></a>
				<?php endif; ?>
			</div>
			<?php if (!empty($items)): ?>
			<table class="eac-table">
				<thead>
				<tr>
					<th><?php esc_html_e("Service", "otto-contracts"); ?></th>
					<th class="is--last-item"><?php esc_html_e(
         "Total Sales",
         "otto-contracts",
     ); ?></th>
				</tr>
				</thead>
				<tbody>
					<?php foreach ($items as $item): ?>
						<tr>
							<td>
								<a href="<?php echo esc_url($item->get_view_url()); ?>">
									<?php echo esc_html($item->name); ?>
								</a>
							</td>
							<td class="is--last-item"><?php echo esc_html(
           eac_format_amount($item->total_sales),
       ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php else: ?>
				<div class="eac-card__body">
					<p class="empty"><?php esc_html_e(
         "No data found.",
         "otto-contracts",
     ); ?></p>
				</div>
			<?php endif; ?>
		</div>
		<?php
    }

    
    public static function top_customers()
    {
        global $wpdb;
        
        $payments = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT contact_id, SUM(amount / exchange_rate) AS amount
				 FROM {$wpdb->prefix}otto_transactions
				 WHERE type = 'payment'
				 GROUP BY contact_id
				 ORDER BY amount DESC LIMIT %d",
                5,
            ),
        );

        $customers = [];
        foreach ($payments as $payment) {
            $customer = EAC()->customers->get($payment->contact_id);
            if ($customer) {
                $customer->amount = $payment->amount;
                $customers[] = $customer;
            }
        }
        ?>
		<div class="eac-card is--widget">
			<div class="eac-card__header">
				<?php esc_html_e("Top Customers", "otto-contracts"); ?>
				<?php if (!empty($customers)): ?>
					<a href="<?php echo esc_url(
         admin_url("admin.php?page=eac-sales&tab=customers"),
     ); ?>"><?php esc_html_e("View all", "otto-contracts"); ?></a>
				<?php endif; ?>
			</div>
			<?php if (!empty($customers)): ?>
			<table class="eac-table">
				<thead>
				<tr>
					<th><?php esc_html_e("Customer", "otto-contracts"); ?></th>
					<th class="is--last-item"><?php esc_html_e(
         "Amount",
         "otto-contracts",
     ); ?></th>
				</tr>
				</thead>
				<tbody>
					<?php foreach ($customers as $customer): ?>
						<tr>
							<td>
								<a href="<?php echo esc_url($customer->get_view_url()); ?>">
									<?php echo esc_html($customer->formatted_name); ?>
								</a>
							</td>
							<td class="is--last-item"><?php echo esc_html(
           eac_format_amount($customer->amount),
       ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php else: ?>
				<div class="eac-card__body">
					<p class="empty"><?php esc_html_e(
         "No data found.",
         "otto-contracts",
     ); ?></p>
				</div>
			<?php endif; ?>
		</div>
		<?php
    }

    
    public static function top_vendors()
    {
        global $wpdb;
        
        $expenses = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT contact_id, SUM(amount / exchange_rate) AS amount
				 FROM {$wpdb->prefix}otto_transactions
				 WHERE type = 'expense'
				 GROUP BY contact_id
				 ORDER BY amount DESC LIMIT %d",
                5,
            ),
        );

        $vendors = [];
        foreach ($expenses as $expense) {
            $vendor = EAC()->vendors->get($expense->contact_id);
            if ($vendor) {
                $vendor->amount = $expense->amount;
                $vendors[] = $vendor;
            }
        }
        ?>
		<div class="eac-card is--widget">
			<div class="eac-card__header">
				<?php esc_html_e("Top Vendors", "otto-contracts"); ?>
				<?php if (!empty($vendors)): ?>
					<a href="<?php echo esc_url(
         admin_url("admin.php?page=eac-purchases&tab=vendors"),
     ); ?>"><?php esc_html_e("View all", "otto-contracts"); ?></a>
				<?php endif; ?>
			</div>
			<?php if (!empty($vendors)): ?>
			<table class="eac-table">
				<thead>
				<tr>
					<th><?php esc_html_e("Vendor", "otto-contracts"); ?></th>
					<th class="is--last-item"><?php esc_html_e(
         "Amount",
         "otto-contracts",
     ); ?></th>
				</tr>
				</thead>
				<tbody>
					<?php foreach ($vendors as $vendor): ?>
						<tr>
							<td>
								<a href="<?php echo esc_url($vendor->get_view_url()); ?>">
									<?php echo esc_html($vendor->formatted_name); ?>
								</a>
							</td>
							<td class="is--last-item"><?php echo esc_html(
           eac_format_amount($vendor->amount),
       ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php else: ?>
				<div class="eac-card__body">
					<p class="empty"><?php esc_html_e(
         "No data found.",
         "otto-contracts",
     ); ?></p>
				</div>
			<?php endif; ?>
		</div>
		<?php
    }
}
