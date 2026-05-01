<?php

namespace Otto\Admin;

use Otto\Models\Account;
use Otto\Utilities\ReportsUtil;

defined("ABSPATH") || exit();


class Accounts
{
    
    public function __construct()
    {
        add_filter("eac_banking_page_tabs", [__CLASS__, "register_tabs"]);
        add_action("admin_post_eac_edit_account", [__CLASS__, "handle_edit"]);
        add_action("eac_banking_page_accounts_loaded", [
            __CLASS__,
            "page_loaded",
        ]);
        add_action("eac_banking_page_accounts_content", [
            __CLASS__,
            "page_content",
        ]);
        add_action("eac_account_profile_section_overview", [
            __CLASS__,
            "overview_section",
        ]);
        add_action("eac_account_profile_section_payments", [
            __CLASS__,
            "payments_section",
        ]);
        add_action("eac_account_profile_section_expenses", [
            __CLASS__,
            "expenses_section",
        ]);
        add_action("eac_account_profile_section_notes", [
            __CLASS__,
            "account_notes",
        ]);
    }

    
    public static function register_tabs($tabs)
    {
        if (
            current_user_can("eac_read_accounts") ||
            current_user_can("eac_banking_tools_access") ||
            current_user_can("eac_manage_options")
        ) {
            
            $tabs["accounts"] = __("Account", "otto-contracts");
        }

        return $tabs;
    }

    
    public static function handle_edit()
    {
        check_admin_referer("eac_edit_account");
        if (!current_user_can("eac_edit_accounts")) {
            
            wp_die(
                esc_html__(
                    "You do not have permission to edit accounts.",
                    "otto-contracts",
                ),
            );
        }

        $referer = wp_get_referer();
        $data = [
            "id" => isset($_POST["id"]) ? absint(wp_unslash($_POST["id"])) : 0,
            "type" => isset($_POST["type"])
                ? sanitize_text_field(wp_unslash($_POST["type"]))
                : "",
            "name" => isset($_POST["name"])
                ? sanitize_text_field(wp_unslash($_POST["name"]))
                : "",
            "number" => isset($_POST["number"])
                ? sanitize_text_field(wp_unslash($_POST["number"]))
                : "",
            "currency" => isset($_POST["currency"])
                ? sanitize_text_field(wp_unslash($_POST["currency"]))
                : "",
        ];

        $account = EAC()->accounts->insert($data);
        if (is_wp_error($account)) {
            EAC()->flash->error($account->get_error_message());
        } else {
            EAC()->flash->success(
                __("Account saved successfully.", "otto-contracts"),
            );
            $referer = remove_query_arg(["action"], $referer);
            $referer = add_query_arg(
                [
                    "action" => "view",
                    "id" => $account->id,
                ],
                $referer,
            );
        }

        wp_safe_redirect($referer);
        exit();
    }

    
    public static function page_loaded($action)
    {
        global $eac_list_table;
        switch ($action) {
            case "add":
                if (!current_user_can("eac_edit_accounts")) {
                    
                    wp_die(
                        esc_html__(
                            "You do not have permission to add accounts.",
                            "otto-contracts",
                        ),
                    );
                }
                break;
            case "edit":
                $id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
                if (!EAC()->accounts->get($id)) {
                    wp_die(
                        esc_html__(
                            "You attempted to retrieve an account that does not exist. Perhaps it was deleted?",
                            "otto-contracts",
                        ),
                    );
                }
                if (
                    "edit" === $action &&
                    !current_user_can("eac_edit_accounts")
                ) {
                    
                    wp_die(
                        esc_html__(
                            "You do not have permission to edit accounts.",
                            "otto-contracts",
                        ),
                    );
                }
                break;

            default:
                $screen = get_current_screen();
                $eac_list_table = new ListTables\Accounts();
                $eac_list_table->prepare_items();
                $screen->add_option("per_page", [
                    "label" => __(
                        "Number of accounts per page:",
                        "otto-contracts",
                    ),
                    "default" => 20,
                    "option" => "eac_accounts_per_page",
                ]);
                break;
        }
    }

    
    public static function page_content($action)
    {
        switch ($action) {
            case "add":
            case "edit":
                include __DIR__ . "/views/account-edit.php";
                break;
            case "view":
                include __DIR__ . "/views/account-view.php";
                break;
            default:
                include __DIR__ . "/views/account-list.php";
                break;
        }
    }

    
    public static function overview_section($account)
    {
        global $wpdb;
        $start_date = ReportsUtil::get_year_start_date();
        $end_date = ReportsUtil::get_year_end_date();
        $date_column = ReportsUtil::get_localized_time_sql("t.payment_date");
        $transactions = $wpdb->get_results(
            
            $wpdb->prepare(
                "SELECT t.amount amount, MONTH({$date_column}) AS month, YEAR({$date_column}) AS year, t.type
					FROM {$wpdb->prefix}otto_transactions AS t
					LEFT JOIN {$wpdb->prefix}otto_transfers AS it ON t.id = it.payment_id OR t.id = it.expense_id
					WHERE it.payment_id IS NULL
					AND it.expense_id IS NULL
					AND t.account_id = %d
					AND t.payment_date BETWEEN %s AND %s
					ORDER BY t.payment_date ASC",
                $account->id,
                get_gmt_from_date($start_date),
                get_gmt_from_date($end_date),
            ),
            
        );
        $stats[] = [
            "label" => __("Incoming", "otto-contracts"),
            "value" => eac_format_amount(
                array_sum(
                    wp_list_pluck(
                        wp_list_filter($transactions, ["type" => "payment"]),
                        "amount",
                    ),
                ),
                $account->currency,
            ),
            "meta" => [
                eac_format_datetime(get_gmt_from_date($start_date), "Y"),
            ],
        ];
        $stats[] = [
            "label" => __("Outgoing", "otto-contracts"),
            "value" => eac_format_amount(
                array_sum(
                    wp_list_pluck(
                        wp_list_filter($transactions, ["type" => "expense"]),
                        "amount",
                    ),
                ),
                $account->currency,
            ),
            "meta" => [
                eac_format_datetime(get_gmt_from_date($start_date), "Y"),
            ],
        ];
        $stats[] = [
            "label" => __("Balance", "otto-contracts"),
            "value" => $account->formatted_balance,
        ];
        $stats = apply_filters("eac_account_overview_stats", $stats);

        $payments = ReportsUtil::annualize_data(
            wp_list_filter($transactions, ["type" => "payment"]),
        );
        $expenses = ReportsUtil::annualize_data(
            wp_list_filter($transactions, ["type" => "expense"]),
        );
        $chart = [
            "type" => "line",
            "labels" => array_keys($payments),
            "datasets" => [
                [
                    "label" => __("Incoming", "otto-contracts"),
                    "backgroundColor" => "#4CAF50",
                    "data" => array_values($payments),
                ],
                [
                    "label" => __("Outgoing", "otto-contracts"),
                    "backgroundColor" => "#F44336",
                    "data" => array_values($expenses),
                ],
            ],
        ];
        ?>
		<h2 class="has--border"><?php echo esc_html__(
      "Overview",
      "otto-contracts",
  ); ?></h2>
		<div class="eac-chart">
			<canvas class="eac-chart" id="eac-account-chart" style="height: 300px;margin-bottom: 20px;" data-datasets="<?php echo esc_attr(
       wp_json_encode($chart),
   ); ?>" data-currency="<?php echo esc_attr(EAC()->currencies->get_symbol($account->currency)); ?>"></canvas>
		</div>
		<div class="eac-stats stats--3">
			<?php foreach ($stats as $stat): ?>
				<div class="eac-stat">
					<div class="eac-stat__label"><?php echo esc_html($stat["label"]); ?></div>
					<div class="eac-stat__value">
						<?php echo esc_html($stat["value"]); ?>
						<?php if (isset($stat["delta"])): ?>
							<?php $delta_class = $stat["delta"] > 0 ? "is--positive" : "is--negative"; ?>
							<div class="eac-stat__delta <?php echo esc_attr($delta_class); ?>">
								<?php echo esc_html($stat["delta"]); ?>%
							</div>
						<?php endif; ?>
					</div>
					<?php if (isset($stat["meta"])): ?>
						<div class="eac-stat__meta">
							<span><?php echo wp_kses_post(
           implode(" </span><span> ", $stat["meta"]),
       ); ?></span>
						</div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
		<h2><?php echo esc_html__("Account Details", "otto-contracts"); ?></h2>
		<?php $attributes = [
      [
          "label" => __("Name", "otto-contracts"),
          "value" => $account->name,
      ],
      "number" => [
          "label" => __("Number", "otto-contracts"),
          "value" => $account->number,
      ],
      "currency" => [
          "label" => __("Currency", "otto-contracts"),
          "value" => $account->currency,
      ],
      "created" => [
          "label" => __("Created", "otto-contracts"),
          "value" => $account->date_created
              ? eac_format_datetime($account->date_created, eac_date_format())
              : "&mdash;",
      ],
      [
          "label" => __("Updated", "otto-contracts"),
          "value" => $account->date_updated
              ? eac_format_datetime($account->date_updated, eac_date_format())
              : "&mdash;",
      ],
  ]; ?>
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

    
    public static function payments_section($account)
    {
        $payments = EAC()->payments->query([
            "account_id" => $account->id,
            "orderby" => "date_created",
            "order" => "DESC",
            "limit" => 20,
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
		</table>
		<?php
    }

    
    public static function expenses_section($account)
    {
        $expenses = EAC()->expenses->query([
            "account_id" => $account->id,
            "orderby" => "date_created",
            "order" => "DESC",
            "limit" => 20,
        ]); ?>
		<h2 class="has--border"><?php esc_html_e(
      "Recent Expenses",
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
			<?php if ($expenses): ?>
				<?php foreach ($expenses as $expense): ?>
					<tr>
						<td>
							<a href="<?php echo esc_url($expense->get_view_url()); ?>">
								<?php echo esc_html($expense->number); ?>
							</a>
						</td>
						<td><?php echo esc_html(
          wp_date(eac_date_format(), strtotime($expense->payment_date)),
      ); ?></td>
						<td><?php echo esc_html(
          $expense->reference ? $expense->reference : "&mdash;",
      ); ?></td>
						<td><?php echo esc_html($expense->formatted_amount); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php else: ?>
				<tr>
					<td colspan="4"><?php esc_html_e(
         "No expenses found.",
         "otto-contracts",
     ); ?></td>
				</tr>
			<?php endif; ?>
		</table>
		<?php
    }

    
    public static function account_notes($account)
    {
        
        if (!$account->exists()) {
            return;
        }

        $notes = EAC()->notes->query([
            "parent_id" => $account->id,
            "parent_type" => "account",
            "orderby" => "date_created",
            "order" => "DESC",
            "limit" => 20,
        ]);
        ?>
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
       $account->id,
   ); ?>" data-parent_type="account" data-nonce="<?php echo esc_attr(
    wp_create_nonce("eac_add_note"),
); ?>">
				<?php esc_html_e("Add Note", "otto-contracts"); ?>
			</button>
		<?php endif; ?>

		<?php include __DIR__ . "/views/note-list.php"; ?>
		<?php
    }
}
