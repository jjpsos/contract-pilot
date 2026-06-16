<?php

namespace Jjpsos\ContractPilot\Admin;

use Jjpsos\ContractPilot\Utilities\NumberUtil;
use Jjpsos\ContractPilot\Utilities\ReportsUtil;

defined("ABSPATH") || exit();

class Dashboard
{
    public function __construct()
    {
        add_action("admin_init", [__CLASS__, "save_dashboard_message_toggle"]);
        add_action("contract_pilot_dashboard_overview_widgets", [
            __CLASS__,
            "overview_widget",
        ]);
        add_filter("contract_pilot_dashboard_overview_stats", [
            __CLASS__,
            "overview_stats",
        ]);
        add_action("contract_pilot_dashboard_widgets", [__CLASS__, "recent_payments"]);
        add_action("contract_pilot_dashboard_widgets", [__CLASS__, "recent_expenses"]);
        add_action("contract_pilot_dashboard_widgets", [__CLASS__, "recent_invoices"]);
        add_action("contract_pilot_dashboard_widgets", [__CLASS__, "top_items"]);
        add_action("contract_pilot_dashboard_widgets", [__CLASS__, "top_customers"]);
    }

    public static function render_page()
    {
        contract_pilot_render_admin_view(
            'screens/dashboard',
            ScreenViewData::dashboard(),
        );
    }

    /**
     * Save dashboard info message visibility.
     *
     * @return void
     */
    public static function save_dashboard_message_toggle()
    {
        if (!is_admin()) {
            return;
        }

        if (!current_user_can("contract_pilot_manage_options")) {
            return;
        }

        if (!isset($_POST["contract_pilot_dashboard_message_toggle_submit"])) {
            return;
        }

        check_admin_referer("contract_pilot_toggle_dashboard_message");

        $page = Request::get_key('page');
        if ("contract-pilot" !== $page) {
            return;
        }

        $is_enabled = isset($_POST["contract_pilot_dashboard_message_enabled"]) ? "yes" : "no";
        update_option("contract_pilot_dashboard_message_enabled", $is_enabled);
    }

    public static function overview_widget()
    {
        if (!current_user_can("contract_pilot_read_reports")) {
            return;
        }

        $report = ReportsUtil::get_profits_report(wp_date("Y"), true);
        $profits = array_sum($report["profits"]);
        $payments = array_sum($report["payments"]);
        $delta = $profits > 0 && $payments > 0 ? ($profits / $payments) * 100 : 0;
        $stats = apply_filters("contract_pilot_dashboard_overview_stats", [
            [
                "label" => __("Incoming", "contract-pilot"),
                "value" => contract_pilot_format_amount(array_sum($report["payments"])),
            ],
            [
                "label" => __("Outgoing", "contract-pilot"),
                "value" => contract_pilot_format_amount(array_sum($report["expenses"])),
            ],
            [
                "label" => __("Profit", "contract-pilot"),
                "value" => contract_pilot_format_amount(array_sum($report["profits"])),
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
                    "label" => __("Sales", "contract-pilot"),
                    "type" => "line",
                ],
                [
                    "label" => __("Expenses", "contract-pilot"),
                    "backgroundColor" => "#f2385a",
                    "borderColor" => "#f2385a",
                    "type" => "line",
                    "fill" => false,
                    "data" => array_values($report["expenses"]),
                ],
                [
                    "label" => __("Profit/Loss", "contract-pilot"),
                    "backgroundColor" => "#00d48f",
                    "borderColor" => "#00d48f",
                    "type" => "line",
                    "fill" => false,
                    "data" => array_values($report["profits"]),
                ],
            ],
        ];
        ?>
        <div class="contract-pilot-card is--widget">
            <div class="contract-pilot-card__header">
                <?php esc_html_e("Overview", "contract-pilot"); ?>
            </div>
            <div class="contract-pilot-card__body">
                <canvas class="contract-pilot-chart" style="min-height: 300px;" data-datasets="<?php echo esc_attr(wp_json_encode($datasets)); ?>" data-currency="<?php echo esc_attr(contract_pilot()->currencies->get_symbol(contract_pilot_base_currency())); ?>"></canvas>
            </div>
        </div>
        <div class="contract-pilot-stats stats--3">
            <?php foreach ($stats as $stat) : ?>
                <div class="contract-pilot-stat">
                    <div class="contract-pilot-stat__label"><?php echo esc_html($stat["label"]); ?></div>
                    <div class="contract-pilot-stat__value">
                        <?php echo esc_html($stat["value"]); ?>
                    </div>
                    <?php if (isset($stat["meta"])) : ?>
                        <div class="contract-pilot-stat__meta">
                            <span><?php echo wp_kses_post(implode(" </span><span> ", $stat["meta"])); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($stat["delta"])) : ?>
                        <?php $delta_class = $stat["delta"] > 0 ? "is--positive" : "is--negative"; ?>
                        <div class="contract-pilot-stat__delta <?php echo esc_attr($delta_class); ?>" title="<?php esc_html_e("Percentage of profit", "contract-pilot"); ?>">
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

        static $overview_documents = null;
        static $overview_loaded = false;

        if (!$overview_loaded) {
            $overview_loaded = true;
            // phpcs:ignore -- Direct aggregate query on plugin-owned reporting tables.
            $overview_documents = $wpdb->get_row(
                "SELECT
                SUM(CASE WHEN d.type = 'invoice' THEN (d.total / d.exchange_rate) ELSE 0 END) -
                SUM(CASE WHEN d.type = 'invoice' THEN COALESCE(t.amount / t.exchange_rate, 0) ELSE 0 END) AS receivable
            FROM
                {$wpdb->prefix}pilot_documents d
            LEFT JOIN
                {$wpdb->prefix}pilot_transactions t ON d.id = t.document_id
            WHERE
                d.status IN ( 'received', 'sent', 'overdue', 'partial');"
            );
        }

        $documents = $overview_documents;

        $stats[] = [
            "label" => __("Receivable", "contract-pilot"),
            "value" => contract_pilot_format_amount($documents->receivable),
        ];

        return $stats;
    }

    public static function recent_payments()
    {
        $payments = contract_pilot()->payments->query([
            "limit" => 5,
            "orderby" => "payment_date",
            "order" => "DESC",
        ]);
        ?>
        <div class="contract-pilot-card is--widget">
            <div class="contract-pilot-card__header">
                <?php esc_html_e("Recent Payments", "contract-pilot"); ?>
                <?php if (!empty($payments)) : ?>
                    <a href="<?php echo esc_url(admin_url("admin.php?page=contract-pilot-sales&tab=payments")); ?>"><?php esc_html_e("View all", "contract-pilot"); ?></a>
                <?php endif; ?>
            </div>
            <?php if (!empty($payments)) : ?>
                <table class="contract-pilot-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e("Payment #", "contract-pilot"); ?></th>
                            <th><?php esc_html_e("Date", "contract-pilot"); ?></th>
                            <th class="is--last-item"><?php esc_html_e("Amount", "contract-pilot"); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment) : ?>
                            <tr>
                                <td><a href="<?php echo esc_url($payment->get_view_url()); ?>"><?php echo esc_html($payment->number); ?></a></td>
                                <td><?php echo esc_html(wp_date(contract_pilot_date_format(), strtotime($payment->payment_date))); ?></td>
                                <td class="is--last-item"><?php echo esc_html($payment->formatted_amount); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <div class="contract-pilot-card__body">
                    <p class="empty"><?php esc_html_e("No payments found.", "contract-pilot"); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public static function recent_expenses()
    {
        $expenses = contract_pilot()->expenses->query([
            "limit" => 5,
            "orderby" => "payment_date",
            "order" => "DESC",
        ]);
        ?>
        <div class="contract-pilot-card is--widget">
            <div class="contract-pilot-card__header">
                <?php esc_html_e("Recent Expenses", "contract-pilot"); ?>
                <?php if (!empty($expenses)) : ?>
                    <a href="<?php echo esc_url(admin_url("admin.php?page=contract-pilot-purchases&tab=expenses")); ?>"><?php esc_html_e("View all", "contract-pilot"); ?></a>
                <?php endif; ?>
            </div>
            <?php if (!empty($expenses)) : ?>
                <table class="contract-pilot-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e("Expense #", "contract-pilot"); ?></th>
                            <th><?php esc_html_e("Date", "contract-pilot"); ?></th>
                            <th class="is--last-item"><?php esc_html_e("Amount", "contract-pilot"); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expenses as $expense) : ?>
                            <tr>
                                <td><a href="<?php echo esc_url($expense->get_view_url()); ?>"><?php echo esc_html($expense->number); ?></a></td>
                                <td><?php echo esc_html(wp_date(contract_pilot_date_format(), strtotime($expense->payment_date))); ?></td>
                                <td class="is--last-item"><?php echo esc_html($expense->formatted_amount); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <div class="contract-pilot-card__body">
                    <p class="empty"><?php esc_html_e("No expenses found.", "contract-pilot"); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public static function recent_invoices()
    {
        $invoices = contract_pilot()->invoices->query([
            "limit" => 5,
            "orderby" => "date",
            "order" => "DESC",
        ]);
        ?>
        <div class="contract-pilot-card is--widget">
            <div class="contract-pilot-card__header">
                <?php esc_html_e("Recent Contracts", "contract-pilot"); ?>
                <?php if (!empty($invoices)) : ?>
                    <a href="<?php echo esc_url(admin_url("admin.php?page=contract-pilot-sales&tab=invoices")); ?>"><?php esc_html_e("View all", "contract-pilot"); ?></a>
                <?php endif; ?>
            </div>
            <?php if (!empty($invoices)) : ?>
                <table class="contract-pilot-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e("Contract #", "contract-pilot"); ?></th>
                            <th><?php esc_html_e("Date", "contract-pilot"); ?></th>
                            <th class="is--last-item"><?php esc_html_e("Amount", "contract-pilot"); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invoices as $invoice) : ?>
                            <tr>
                                <td><a href="<?php echo esc_url($invoice->get_view_url()); ?>"><?php echo esc_html($invoice->number); ?></a></td>
                                <td><?php echo esc_html(wp_date(contract_pilot_date_format(), strtotime($invoice->issue_date))); ?></td>
                                <td class="is--last-item"><?php echo esc_html($invoice->formatted_total); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <div class="contract-pilot-card__body">
                    <p class="empty"><?php esc_html_e("No invoices found.", "contract-pilot"); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public static function top_items()
    {
        global $wpdb;

        // phpcs:ignore -- Direct aggregate query on plugin-owned reporting tables.
        $item_ids = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT di.item_id, SUM(di.subtotal / d.exchange_rate) AS total_sales
                 FROM {$wpdb->prefix}pilot_document_items AS di
                 JOIN {$wpdb->prefix}pilot_documents AS d ON di.document_id = d.id
                 WHERE d.type = %s AND d.status = %s
                 GROUP BY di.item_id
                 ORDER BY total_sales DESC
                 LIMIT 5",
                "invoice",
                "paid"
            )
        );

        $items = [];
        foreach ($item_ids as $item_id) {
            $item = contract_pilot()->items->get($item_id->item_id);
            if ($item) {
                $item->total_sales = $item_id->total_sales;
                $items[] = $item;
            }
        }
        ?>
        <div class="contract-pilot-card is--widget">
            <div class="contract-pilot-card__header">
                <?php esc_html_e("Top Items", "contract-pilot"); ?>
                <?php if (!empty($items)) : ?>
                    <a href="<?php echo esc_url(admin_url("admin.php?page=contract-pilot-items")); ?>"><?php esc_html_e("View all", "contract-pilot"); ?></a>
                <?php endif; ?>
            </div>
            <?php if (!empty($items)) : ?>
                <table class="contract-pilot-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e("Service", "contract-pilot"); ?></th>
                            <th class="is--last-item"><?php esc_html_e("Total Sales", "contract-pilot"); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item) : ?>
                            <tr>
                                <td>
                                    <a href="<?php echo esc_url($item->get_view_url()); ?>">
                                        <?php echo esc_html($item->name); ?>
                                    </a>
                                </td>
                                <td class="is--last-item"><?php echo esc_html(contract_pilot_format_amount($item->total_sales)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <div class="contract-pilot-card__body">
                    <p class="empty"><?php esc_html_e("No data found.", "contract-pilot"); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public static function top_customers()
    {
        global $wpdb;

        // phpcs:ignore -- Direct aggregate query on plugin-owned reporting tables.
        $payments = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT contact_id, SUM(amount / exchange_rate) AS amount
                 FROM {$wpdb->prefix}pilot_transactions
                 WHERE type = 'payment'
                 GROUP BY contact_id
                 ORDER BY amount DESC LIMIT %d",
                5
            )
        );

        $customers = [];
        foreach ($payments as $payment) {
            $customer = contract_pilot()->customers->get($payment->contact_id);
            if ($customer) {
                $customer->amount = $payment->amount;
                $customers[] = $customer;
            }
        }
        ?>
        <div class="contract-pilot-card is--widget">
            <div class="contract-pilot-card__header">
                <?php esc_html_e("Top Customers", "contract-pilot"); ?>
                <?php if (!empty($customers)) : ?>
                    <a href="<?php echo esc_url(admin_url("admin.php?page=contract-pilot-sales&tab=customers")); ?>"><?php esc_html_e("View all", "contract-pilot"); ?></a>
                <?php endif; ?>
            </div>
            <?php if (!empty($customers)) : ?>
                <table class="contract-pilot-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e("Customer", "contract-pilot"); ?></th>
                            <th class="is--last-item"><?php esc_html_e("Amount", "contract-pilot"); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $customer) : ?>
                            <tr>
                                <td>
                                    <a href="<?php echo esc_url($customer->get_view_url()); ?>">
                                        <?php echo esc_html($customer->formatted_name); ?>
                                    </a>
                                </td>
                                <td class="is--last-item"><?php echo esc_html(contract_pilot_format_amount($customer->amount)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <div class="contract-pilot-card__body">
                    <p class="empty"><?php esc_html_e("No data found.", "contract-pilot"); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

}
