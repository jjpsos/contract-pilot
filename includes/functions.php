<?php


use Jjpsos\ContractPilot\Utilities\I18nUtil;

defined("ABSPATH") || exit();

require_once __DIR__ . "/Functions/formatters.php";
require_once __DIR__ . "/Functions/misc.php";
require_once __DIR__ . "/Functions/templates.php";


function contract_pilot_base_currency()
{
    $allowed_currencies = contract_pilot_allowed_base_currencies();
    $currency = strtoupper((string) get_option("contract_pilot_base_currency", "USD"));

    if (!array_key_exists($currency, $allowed_currencies)) {
        $currency = "USD";
    }

    return apply_filters("contract_pilot_base_currency", $currency);
}

/**
 * Allowed global base currencies.
 *
 * @return array<string, string>
 */
function contract_pilot_allowed_base_currencies()
{
    return [
        "USD" => __("US Dollar (USD)", "contract-pilot"),
        "CAD" => __("Canadian Dollar (CAD)", "contract-pilot"),
    ];
}

/**
 * Set every banking account row to the given currency (must be allowed).
 *
 * @param string $currency Currency code, e.g. USD or CAD.
 * @return void
 */
function contract_pilot_sync_all_banking_account_currencies($currency)
{
    global $wpdb;

    $currency = strtoupper(sanitize_text_field((string) $currency));
    if (!array_key_exists($currency, contract_pilot_allowed_base_currencies())) {
        return;
    }

    $table = $wpdb->prefix . "pilot_accounts";
    // phpcs:ignore -- Bulk update across plugin-owned accounts table.
    $wpdb->query(
        $wpdb->prepare(
            "UPDATE `" . esc_sql($table) . "` SET currency = %s",
            $currency,
        ),
    );
}

/**
 * After the base currency option changes, update all account rows to match.
 *
 * @param string $option Option name.
 * @param mixed  $old_value Previous value.
 * @param mixed  $value New value.
 * @return void
 */
function contract_pilot_on_updated_base_currency_option($option, $old_value, $value)
{
    if ("contract_pilot_base_currency" !== $option) {
        return;
    }

    $new = strtoupper(trim((string) $value));
    $old = strtoupper(trim((string) $old_value));

    if ("" === $new || $new === $old) {
        return;
    }

    if (!array_key_exists($new, contract_pilot_allowed_base_currencies())) {
        return;
    }

    contract_pilot_sync_all_banking_account_currencies($new);
}

add_action("updated_option", "contract_pilot_on_updated_base_currency_option", 10, 3);

function contract_pilot_get_currencies()
{
    $currencies = apply_filters(
        "contract_pilot_currencies",
        array_map(function ($currency) {
            $currency["formatted_name"] = esc_html(
                sprintf(
                    "%s (%s) - (%s)",
                    $currency["name"],
                    $currency["symbol"],
                    $currency["code"],
                ),
            );

            return $currency;
        }, I18nUtil::get_currencies()),
    );


    $base = contract_pilot_base_currency();
    $currencies[$base]["rate"] = 1;
    $currencies[$base]["position"] = get_option(
        "contract_pilot_currency_position",
        "before",
    );
    $currencies[$base]["thousand"] = stripslashes(
        get_option("contract_pilot_thousand_separator", ","),
    );
    $currencies[$base]["decimal"] = stripslashes(
        get_option("contract_pilot_decimal_separator", "."),
    );
    $currencies[$base]["precision"] = absint(
        get_option("contract_pilot_currency_precision", 2),
    );


    uasort($currencies, function ($a, $b) {
        if (contract_pilot_base_currency() === $a["code"]) {
            return -1;
        }
        if (contract_pilot_base_currency() === $b["code"]) {
            return 1;
        }

        return strcasecmp($a["formatted_name"], $b["formatted_name"]);
    });

    return $currencies;
}

/**
 * Minimal currency config for the admin money.js module (USD and CAD only).
 *
 * @return array{baseCurrency: string, currencies: array<string, array<string, mixed>>}
 */
function contract_pilot_get_money_js_config()
{
    $all = I18nUtil::get_currencies();
    $allowed = array_keys(contract_pilot_allowed_base_currencies());
    $base = contract_pilot_base_currency();
    $currencies = [];

    foreach ($allowed as $code) {
        if (!isset($all[$code])) {
            continue;
        }

        $currencies[$code] = [
            "symbol" => $all[$code]["symbol"],
            "precision" => $all[$code]["precision"],
            "position" => $all[$code]["position"],
            "decimal" => $all[$code]["decimal"],
            "thousand" => $all[$code]["thousand"],
        ];
    }

    if (isset($currencies[$base])) {
        $currencies[$base]["position"] = get_option(
            "contract_pilot_currency_position",
            "before",
        );
        $currencies[$base]["thousand"] = stripslashes(
            get_option("contract_pilot_thousand_separator", ","),
        );
        $currencies[$base]["decimal"] = stripslashes(
            get_option("contract_pilot_decimal_separator", "."),
        );
        $currencies[$base]["precision"] = absint(
            get_option("contract_pilot_currency_precision", 2),
        );
    }

    return [
        "baseCurrency" => $base,
        "currencies" => $currencies,
    ];
}


function contract_pilot_format_amount($amount, $currency = null)
{
    $currencies = contract_pilot_get_currencies();
    if (!is_numeric($amount)) {
        $amount = contract_pilot_sanitize_amount($amount, $currency);
    }
    $data = array_key_exists($currency, $currencies)
        ? $currencies[$currency]
        : $currencies[contract_pilot_base_currency()];
    $negative = $amount < 0;
    $prefix = "before" === $data["position"] ? $data["symbol"] : "";
    $suffix = "after" === $data["position"] ? $data["symbol"] : "";

    $amount = $negative ? -$amount : $amount;
    $amount = number_format(
        $amount,
        $data["precision"],
        $data["decimal"],
        $data["thousand"],
    );

    return $negative
        ? sprintf("-%s%s%s", $prefix, $amount, $suffix)
        : sprintf("%s%s%s", $prefix, $amount, $suffix);
}


function contract_pilot_sanitize_amount($amount, $currency = null)
{
    if (!is_numeric($amount)) {
        $amount = sanitize_text_field($amount);
        $currencies = contract_pilot_get_currencies();
        $data = array_key_exists($currency, $currencies)
            ? $currencies[$currency]
            : $currencies[contract_pilot_base_currency()];


        $amount = str_replace($data["symbol"], "", $amount);

        $amount = preg_replace(
            "/[^0-9" . $data["thousand"] . "" . $data["decimal"] . "\-\+]/",
            "",
            $amount,
        );

        $amount = str_replace(
            [$data["thousand"], $data["decimal"]],
            ["", "."],
            $amount,
        );


        if (preg_match('/^([\-\+])?\d+$/', $amount)) {
            $amount = (int) $amount;
        } elseif (preg_match('/^([\-\+])?\d+\.\d+$/', $amount)) {
            $amount = (float) $amount;
        } else {
            $amount = 0;
        }
    }

    return $amount;
}


function contract_pilot_convert_currency($amount, $from = 1, $to = 1)
{
    $currencies = contract_pilot_get_currencies();
    if (!is_numeric($amount)) {
        $amount = contract_pilot_sanitize_amount($amount);
    }


    if (
        !is_numeric($from) &&
        strlen($from) === 3 &&
        array_key_exists($from, $currencies)
    ) {
        $from = contract_pilot()->currencies->get_rate($from);
    }
    if (
        !is_numeric($to) &&
        strlen($to) === 3 &&
        array_key_exists($to, $currencies)
    ) {
        $to = contract_pilot()->currencies->get_rate($to);
    }

    if (!is_numeric($from) || $from <= 0 || !is_numeric($to) || $to <= 0) {
        return 0;
    }


    if ($from === $to) {
        return $amount;
    }


    if (1 !== $from) {
        $amount = $amount / $from;
    }


    if (1 !== $to) {
        $amount = $amount * $to;
    }

    return $amount;
}


function contract_pilot_sanitize_number($number, $decimals = 2)
{

    $number = preg_replace('/\.(?![^.]+$)|[^0-9.-]/', "", contract_pilot_clean($number));

    if ($decimals) {
        $number = (float) preg_replace("/[^0-9.-]/", "", $number);

        if (is_numeric($decimals)) {
            $number = number_format(floatval($number), $decimals, ".", "");
        }

        return $number;
    }

    return (int) preg_replace("/[^0-9]/", "", $number);
}


function contract_pilot_round_number($val, $decimals = 6, $mode = PHP_ROUND_HALF_UP)
{
    $val = contract_pilot_sanitize_number($val, $decimals);

    return round($val, $decimals, $mode);
}


function contract_pilot_get_payment_methods()
{
    return apply_filters("contract_pilot_payment_methods", [
        "cash" => esc_html__("Cash", "contract-pilot"),
        "check" => esc_html__("Cheque", "contract-pilot"),
        "credit" => esc_html__("Credit Card", "contract-pilot"),
        "debit" => esc_html__("Debit Card", "contract-pilot"),
        "bank" => esc_html__("Bank Transfer", "contract-pilot"),
        "paypal" => esc_html__("PayPal", "contract-pilot"),
        "other" => esc_html__("Other", "contract-pilot"),
    ]);
}


function contract_pilot_format_datetime(
    $date = "now",
    $format = "Y-m-d H:i:s",
    $timezone = null
) {
    if ("now" === $date) {
        $date = current_time("mysql", true);
    } elseif (empty($date)) {
        return "";
    }

    $datetime = date_create($date, new DateTimeZone("UTC"));

    if (false === $datetime) {
        return false;
    }

    return wp_date($format, $datetime->getTimestamp(), $timezone);
}

/**
 * Map of invoice status slug to stored/display status label (contracts/bills).
 *
 * @return array<string, string>
 */
function contract_pilot_invoice_status_label_map()
{
    return [
        "draft" => "Contract/Draft",
        "sent" => "Contract/Sent",
        "accept" => "Accept/Bill",
        "partial" => "Partial/Bill",
        "paid" => "Paid/Bill",
        "overdue" => "Overdue/Bill",
        "otto" => "Auto/Bill",
        "cancelled" => "Cancelled/Bill",
        "canceled" => "Cancelled/Bill",
    ];
}

/**
 * Display label for a contract/invoice document status slug.
 *
 * @param string $status Raw status slug.
 * @return string Label, or empty string if unknown.
 */
function contract_pilot_invoice_status_label_for_status($status)
{
    if (!is_string($status) || "" === $status) {
        return "";
    }
    $status = sanitize_key($status);
    $map = contract_pilot_invoice_status_label_map();

    return isset($map[$status]) ? $map[$status] : "";
}

/**
 * Status label for contract admin headings (View … / Edit …).
 *
 * @param object $invoice Model with `status` and optional `status_label`.
 * @return string
 */
function contract_pilot_invoice_heading_status_label($invoice)
{
    if (!is_object($invoice) || !isset($invoice->status)) {
        return "";
    }
    $label = isset($invoice->status_label)
        ? trim((string) $invoice->status_label)
        : "";
    if ("" === $label) {
        $label = contract_pilot_invoice_status_label_for_status((string) $invoice->status);
    }

    return $label;
}
