<?php

namespace Otto\Utilities;

defined("ABSPATH") || exit();


class ReportsUtil
{
    
    public static function get_dates_filter_options()
    {
        $options = [
            "today" => __("Today", "otto-contracts"),
            "yesterday" => __("Yesterday", "otto-contracts"),
            "this_week" => __("This Week", "otto-contracts"),
            "last_week" => __("Last Week", "otto-contracts"),
            "last_30_days" => __("Last 30 Days", "otto-contracts"),
            "this_month" => __("This Month", "otto-contracts"),
            "last_month" => __("Last Month", "otto-contracts"),
            "this_quarter" => __("This Quarter", "otto-contracts"),
            "last_quarter" => __("Last Quarter", "otto-contracts"),
            "this_year" => __("This Year", "otto-contracts"),
            "last_year" => __("Last Year", "otto-contracts"),
            "custom" => __("Custom", "otto-contracts"),
        ];

        return apply_filters("eac_dates_filter_options", $options);
    }

    
    public static function parse_date_range_filter($date_filter = "")
    {
        switch ($date_filter) {
            case "yesterday":
                $range["start_date"] = wp_date("Y-m-d", strtotime("yesterday"));
                $range["end_date"] = wp_date("Y-m-d", strtotime("yesterday"));
                break;
            case "this_week":
                $range["start_date"] = wp_date("Y-m-d", strtotime("this week"));
                $range["end_date"] = wp_date("Y-m-d");
                break;
            case "last_week":
                $range["start_date"] = wp_date("Y-m-d", strtotime("last week"));
                $range["end_date"] = wp_date("Y-m-d", strtotime("last week"));
                break;
            case "last_30_days":
                $range["start_date"] = wp_date("Y-m-d", strtotime("-30 days"));
                $range["end_date"] = wp_date("Y-m-d");
                break;
            case "last_month":
                $range["start_date"] = wp_date(
                    "Y-m-01",
                    strtotime("last month"),
                );
                $range["end_date"] = wp_date("Y-m-t", strtotime("last month"));
                break;
            case "this_quarter":
                $range["start_date"] = wp_date(
                    "Y-m-01",
                    strtotime("first day of this quarter"),
                );
                $range["end_date"] = wp_date("Y-m-d");
                break;
            case "last_quarter":
                $range["start_date"] = wp_date(
                    "Y-m-01",
                    strtotime("first day of last quarter"),
                );
                $range["end_date"] = wp_date(
                    "Y-m-t",
                    strtotime("last day of last quarter"),
                );
                break;

            case "this_year":
                $range["start_date"] = wp_date("Y-01-01");
                $range["end_date"] = wp_date("Y-m-d");
                break;
            case "last_year":
                $range["start_date"] = wp_date(
                    "Y-01-01",
                    strtotime("last year"),
                );
                $range["end_date"] = wp_date("Y-12-31", strtotime("last year"));
                break;

            case "this_month":
            default:
                $range["start_date"] = wp_date("Y-m-01");
                $range["end_date"] = wp_date("Y-m-d");
                break;
        }

        return $range;
    }

    
    public static function get_year_start_date($year = "")
    {
        if (empty($year)) {
            $year = gmdate("Y");
        }

        $year_start = get_option("eac_year_start_date", "01-01");
        $dates = explode("-", $year_start);
        $month = !empty($dates[0]) ? $dates[0] : "01";
        $day = !empty($dates[1]) ? $dates[1] : "01";
        $year = empty($year) ? (int) wp_date("Y") : absint($year);

        return gmdate(
            "Y-m-d 00:00:00",
            mktime(0, 0, 0, absint($month), absint($day), $year),
        );
    }

    
    public static function get_year_end_date($year = "")
    {
        if (empty($year)) {
            $year = wp_date("Y");
        }

        $start_date = self::get_year_start_date($year);
        

        return gmdate(
            "Y-m-d 23:59:59",
            strtotime($start_date . " +1 year -1 day"),
        );
    }

    
    public static function get_months_in_range(
        $start_date,
        $end_date,
        $format = "F,y"
    ) {
        
        $months = [];
        $start = new \DateTime($start_date);
        $end = new \DateTime($end_date);
        while ($start <= $end) {
            $months[] = gmdate("M, y", strtotime($start->format("Y-m-01")));
            $start->modify("first day of next month");
        }

        return $months;
    }

    
    public static function get_dates_range($start_date, $end_date)
    {
        $dates = [];
        $start = new \DateTime($start_date);
        $end = new \DateTime($end_date);
        $interval = \DateInterval::createFromDateString("1 day");
        $period = new \DatePeriod($start, $interval, $end);
        foreach ($period as $date) {
            $dates[] = $date->format("Y-m-d");
        }

        return $dates;
    }

    
    public static function get_comparison_dates($start_date, $end_date)
    {
        
        $gap = count(self::get_dates_range($start_date, $end_date));
        
        if ($gap <= 7) {
            $start = gmdate("Y-m-d", strtotime($start_date . " -7 days"));
            $end = gmdate("Y-m-d", strtotime($end_date . " -7 days"));
        } elseif ($gap <= 30) {
            $start = gmdate("Y-m-d", strtotime($start_date . " -1 month"));
            $end = gmdate("Y-m-d", strtotime($end_date . " -1 month"));
        } elseif ($gap <= 90) {
            $start = gmdate("Y-m-d", strtotime($start_date . " -3 months"));
            $end = gmdate("Y-m-d", strtotime($end_date . " -3 months"));
        } elseif ($gap <= 180) {
            $start = gmdate("Y-m-d", strtotime($start_date . " -6 months"));
            $end = gmdate("Y-m-d", strtotime($end_date . " -6 months"));
        } else {
            $start = gmdate("Y-m-d", strtotime($start_date . " -1 year"));
            $end = gmdate("Y-m-d", strtotime($end_date . " -1 year"));
        }

        return [
            "start" => $start,
            "end" => $end,
        ];
    }

    
    public static function get_random_color($key = null)
    {
        static $picked = [];
        
        $colors = apply_filters("eac_report_colors", [
            "#3366cc",
            "#dc3912",
            "#ff9900",
            "#109618",
            "#990099",
            "#0099c6",
            "#dd4477",
            "#66aa00",
            "#b82e2e",
            "#316395",
            "#994499",
            "#22aa99",
            "#aaaa11",
            "#6633cc",
            "#e67300",
            "#8b0707",
            "#651067",
            "#329262",
            "#5574a6",
            "#3b3eac",
            "#b77322",
            "#16d620",
            "#b91383",
            "#f4359e",
            "#9c5935",
            "#a9c413",
            "#2a778d",
            "#668d1c",
            "#bea413",
            "#0c5922",
            "#743411",
        ]);

        if (!empty($key)) {
            if (!isset($picked[$key])) {
                $picked[$key] = $colors[array_rand($colors)];
            }

            return $picked[$key];
        }

        return $colors[array_rand($colors)];
    }

    
    public static function get_payments_report($year = null, $force = false)
    {
        global $wpdb;
        $reports = get_transient("eac_payments_report");
        $reports = !is_array($reports) ? [] : $reports;
        $year = empty($year) ? wp_date("Y") : $year;
        $start_date = self::get_year_start_date($year);
        $end_date = self::get_year_end_date($year);
        $date_format = "M, y";

        if ($force || empty($reports[$year])) {
            $date_column = self::get_localized_time_sql("t.payment_date");
            
            $transactions = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT (t.amount/t.exchange_rate) amount, MONTH($date_column) AS month, YEAR($date_column) AS year, t.category_id
					FROM {$wpdb->prefix}otto_transactions AS t
					LEFT JOIN {$wpdb->prefix}otto_transfers AS it ON t.id = it.payment_id OR t.id = it.expense_id
					WHERE t.type = 'payment'
					AND it.payment_id IS NULL
					AND it.expense_id IS NULL
					AND t.payment_date BETWEEN %s AND %s
					ORDER BY t.payment_date ASC",
                    get_gmt_from_date($start_date),
                    get_gmt_from_date($end_date),
                ),
            );
            
            $months = array_fill_keys(
                self::get_months_in_range($start_date, $end_date, $date_format),
                0,
            );
            $month_count = count($months);
            $date_count = count(self::get_dates_range($start_date, $end_date));
            $data = [
                "total_amount" => 0,
                "total_count" => 0,
                "daily_avg" => 0,
                "month_avg" => 0,
                "date_count" => $date_count,
                "months" => $months,
                "categories" => [],
            ];
            foreach ($transactions as $transaction) {
                $trans_year = $transaction->year;
                $month = $transaction->month;
                $category_id = $transaction->category_id;
                $amount = round($transaction->amount, 2);
                $month_year = \DateTime::createFromFormat(
                    "Y-m",
                    $trans_year . "-" . $month,
                )->format($date_format);

                
                $data["total_amount"] += round($amount, 2);
                ++$data["total_count"];

                
                if (!isset($data["months"][$month_year])) {
                    $data["months"] = $months;
                }
                $data["months"][$month_year] += round($amount, 2);

                
                if (!isset($data["categories"][$category_id])) {
                    $data["categories"][$category_id] = $months;
                }
                $data["categories"][$category_id][$month_year] += round(
                    $amount,
                    2,
                );
            }

            
            if ($date_count > 0 && $data["total_amount"] > 0) {
                $data["daily_avg"] = round(
                    $data["total_amount"] / $date_count,
                    2,
                );
            }
            
            if ($data["total_amount"] > 0 && $month_count > 0) {
                $data["month_avg"] = round(
                    $data["total_amount"] / $month_count,
                    2,
                );
            }

            $reports[$year] = apply_filters(
                "eac_payments_report",
                $data,
                $year,
            );
            
            set_transient("eac_payments_report", $reports, HOUR_IN_SECONDS);
        }

        return $reports[$year];
    }

    
    public static function get_expenses_report($year = null, $force = true)
    {
        global $wpdb;
        $reports = get_transient("eac_expenses_report");
        $reports = !is_array($reports) ? [] : $reports;
        $year = empty($year) ? wp_date("Y") : $year;
        $start_date = self::get_year_start_date($year);
        $end_date = self::get_year_end_date($year);
        $date_format = "M, y";

        if (
            $force ||
            !isset($reports[$year]) ||
            !array_key_exists("monthly_aid", $reports[$year])
        ) {
            $date_column = self::get_localized_time_sql("t.payment_date");
            
            $transactions = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT (t.amount/t.exchange_rate) amount, MONTH($date_column) AS month, YEAR($date_column) AS year, t.category_id
					FROM {$wpdb->prefix}otto_transactions AS t
					LEFT JOIN {$wpdb->prefix}otto_transfers AS it ON t.id = it.payment_id OR t.id = it.expense_id
					WHERE t.type = 'expense'
					AND it.payment_id IS NULL
					AND it.expense_id IS NULL
					AND t.payment_date BETWEEN %s AND %s
					ORDER BY t.payment_date ASC",
                    get_gmt_from_date($start_date),
                    get_gmt_from_date($end_date),
                ),
            );
            

            $months = array_fill_keys(
                self::get_months_in_range($start_date, $end_date, $date_format),
                0,
            );
            $month_count = count($months);
            $date_count = count(self::get_dates_range($start_date, $end_date));
            $data = [
                "total_amount" => 0,
                "total_count" => 0,
                "monthly_aid" => 0,
                "month_avg" => 0,
                "date_count" => $date_count,
                "months" => $months,
                "categories" => [],
            ];
            foreach ($transactions as $transaction) {
                $trans_year = $transaction->year;
                $month = $transaction->month;
                $category_id = $transaction->category_id;
                $amount = round($transaction->amount, 2);
                $month_year = \DateTime::createFromFormat(
                    "Y-m",
                    $trans_year . "-" . $month,
                )->format($date_format);

                
                $data["total_amount"] += round($amount, 2);
                ++$data["total_count"];

                
                if (!isset($data["months"][$month_year])) {
                    $data["months"] = $months;
                }
                $data["months"][$month_year] += round($amount, 2);

                
                if (!isset($data["categories"][$category_id])) {
                    $data["categories"][$category_id] = $months;
                }
                $data["categories"][$category_id][$month_year] += round(
                    $amount,
                    2,
                );
            }

            
            if ($data["total_amount"] > 0 && $month_count > 0) {
                $data["month_avg"] = round(
                    $data["total_amount"] / $month_count,
                    2,
                );
            }

            $sales_report = self::get_payments_report($year, $force);
            $sales_month_avg = !empty($sales_report["month_avg"])
                ? (float) $sales_report["month_avg"]
                : 0.0;
            $data["monthly_aid"] = round($sales_month_avg * 0.023, 2);

            $reports[$year] = apply_filters(
                "eac_expenses_report",
                $data,
                $year,
            );
            
            set_transient("eac_expenses_report", $reports, HOUR_IN_SECONDS);
        }

        return $reports[$year];
    }

    
    public static function get_profits_report($year = null, $force = true)
    {
        global $wpdb;
        $reports = get_transient("eac_profits_report");
        $reports = !is_array($reports) ? [] : $reports;
        $year = empty($year) ? wp_date("Y") : $year;
        $start_date = self::get_year_start_date($year);
        $end_date = self::get_year_end_date($year);
        $date_format = "M, y";
        if (
            $force ||
            !isset($reports[$year]) ||
            !array_key_exists("profit_aid", $reports[$year])
        ) {
            $date_column = self::get_localized_time_sql("t.payment_date");

            
            $transactions = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT (t.amount/t.exchange_rate) amount, MONTH($date_column) AS month, YEAR($date_column) AS year, t.category_id, t.type
					FROM {$wpdb->prefix}otto_transactions AS t
					LEFT JOIN {$wpdb->prefix}otto_transfers AS it ON t.id = it.payment_id OR t.id = it.expense_id
					WHERE it.payment_id IS NULL
					AND it.expense_id IS NULL
					AND t.payment_date BETWEEN %s AND %s
					ORDER BY t.payment_date ASC",
                    get_gmt_from_date($start_date),
                    get_gmt_from_date($end_date),
                ),
            );
            

            $months = array_fill_keys(
                self::get_months_in_range($start_date, $end_date, $date_format),
                0,
            );
            $month_count = count($months);
            $date_count = count(self::get_dates_range($start_date, $end_date));
            $data = [
                "total_profit" => 0,
                "total_count" => 0,
                "profit_aid" => 0,
                "month_avg" => 0,
                "date_count" => $date_count,
                "payments" => $months,
                "expenses" => $months,
                "profits" => $months,
            ];

            foreach ($transactions as $transaction) {
                $type = $transaction->type;
                $trans_year = $transaction->year;
                $month = $transaction->month;
                $amount = round($transaction->amount, 2);
                $month_year = \DateTime::createFromFormat(
                    "Y-m",
                    $trans_year . "-" . $month,
                )->format($date_format);

                
                ++$data["total_count"];

                
                if ("payment" === $type) {
                    $data["total_profit"] += round($amount, 2);
                    $data["payments"][$month_year] += round($amount, 2);
                    $data["profits"][$month_year] += round($amount, 2);
                } else {
                    $data["total_profit"] -= round($amount, 2);
                    $data["expenses"][$month_year] += round($amount, 2);
                    $data["profits"][$month_year] -= round($amount, 2);
                }
            }

            
            if ($month_count > 0 && $data["total_profit"] > 0) {
                $data["month_avg"] = round(
                    $data["total_profit"] / $month_count,
                    2,
                );
            }

            $expenses_report = self::get_expenses_report($year, $force);
            $monthly_aid = isset($expenses_report["monthly_aid"])
                ? (float) $expenses_report["monthly_aid"]
                : 0.0;
            $annual_aid = $monthly_aid * 12;
            $data["profit_aid"] = round(
                abs($annual_aid - (float) $data["total_profit"]),
                2,
            );

            $reports[$year] = apply_filters("eac_profits_report", $data, $year);
            
            set_transient("eac_profits_report", $reports, HOUR_IN_SECONDS);
        }

        return $reports[$year];
    }

    
    /**
     * Drop cached sales / expenses / profits report payloads so queries use current DB data.
     * Also removes legacy transient keys from older versions that used mismatched names.
     */
    public static function flush_report_caches() {
        foreach (
            array(
                'eac_payments_report',
                'eac_expenses_report',
                'eac_profits_report',
                'get_expenses_report',
                'get_profits_report',
            ) as $key
        ) {
            delete_transient( $key );
        }
    }

    
    public static function annualize_data(
        $data,
        $year = null,
        $date_format = "M, y"
    ) {
        $year = empty($year) ? wp_date("Y") : absint($year);
        $start_date = self::get_year_start_date($year);
        $end_date = self::get_year_end_date($year);
        $months = array_fill_keys(
            self::get_months_in_range($start_date, $end_date, $date_format),
            0,
        );
        foreach ($data as $datum) {
            $datum = get_object_vars($datum);
            $datum = wp_parse_args($datum, [
                "month" => 0,
                "year" => 0,
                "amount" => 0,
            ]);

            
            if (
                !$datum["month"] ||
                !$datum["year"] ||
                absint($datum["year"]) !== absint($year)
            ) {
                continue;
            }
            $month_year = gmdate(
                "M, y",
                mktime(0, 0, 0, $datum["month"], 1, $datum["year"]),
            );
            if (isset($months[$month_year])) {
                $months[$month_year] += round($datum["amount"], 2);
            } else {
                $months[$month_year] = round($datum["amount"], 2);
            }
        }

        return $months;
    }

    
    public static function get_localized_time_sql($column = "date_created")
    {
        $timezone_string = get_option("timezone_string");

        if ($timezone_string) {
            $datetime = new \DateTime(
                "now",
                new \DateTimeZone($timezone_string),
            );
            $offset_seconds = $datetime->getOffset();
            $hours =
                $offset_seconds >= 0
                    ? floor($offset_seconds / 3600)
                    : ceil($offset_seconds / 3600);
            $minutes = abs($offset_seconds % 3600) / 60;
        } else {
            $offset_raw = get_option("gmt_offset");
            $hours = $offset_raw >= 0 ? floor($offset_raw) : ceil($offset_raw);
            $minutes = abs($offset_raw - $hours) * 60;
        }

        $offset = sprintf("%+03d:%02d", $hours, $minutes);

        
        return "CONVERT_TZ({$column}, '+00:00', '{$offset}')";
    }
}
