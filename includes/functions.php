<?php


use Otto\Utilities\I18nUtil;

defined("ABSPATH") || exit();

require_once __DIR__ . "/Functions/formatters.php";
require_once __DIR__ . "/Functions/misc.php";
require_once __DIR__ . "/Functions/templates.php";


function eac_base_currency()
{
    $currency = get_option("eac_base_currency", "USD");

    return apply_filters("eac_base_currency", strtoupper($currency));
}


function eac_get_currencies()
{
    $currencies = apply_filters(
        "eac_currencies",
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

    
    $base = eac_base_currency();
    $currencies[$base]["rate"] = 1;
    $currencies[$base]["position"] = get_option(
        "eac_currency_position",
        "before",
    );
    $currencies[$base]["thousand"] = stripslashes(
        get_option("eac_thousand_separator", ","),
    );
    $currencies[$base]["decimal"] = stripslashes(
        get_option("eac_decimal_separator", "."),
    );
    $currencies[$base]["precision"] = absint(
        get_option("eac_currency_precision", 2),
    );

    
    uasort($currencies, function ($a, $b) {
        if (eac_base_currency() === $a["code"]) {
            return -1;
        }
        if (eac_base_currency() === $b["code"]) {
            return 1;
        }

        return strcasecmp($a["formatted_name"], $b["formatted_name"]);
    });

    return $currencies;
}


function eac_format_amount($amount, $currency = null)
{
    $currencies = eac_get_currencies();
    if (!is_numeric($amount)) {
        $amount = eac_sanitize_amount($amount, $currency);
    }
    $data = array_key_exists($currency, $currencies)
        ? $currencies[$currency]
        : $currencies[eac_base_currency()];
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


function eac_sanitize_amount($amount, $currency = null)
{
    if (!is_numeric($amount)) {
        $amount = sanitize_text_field($amount);
        $currencies = eac_get_currencies();
        $data = array_key_exists($currency, $currencies)
            ? $currencies[$currency]
            : $currencies[eac_base_currency()];

        
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


function eac_convert_currency($amount, $from = 1, $to = 1)
{
    $currencies = eac_get_currencies();
    if (!is_numeric($amount)) {
        $amount = eac_sanitize_amount($amount);
    }

    
    if (
        !is_numeric($from) &&
        strlen($from) === 3 &&
        array_key_exists($from, $currencies)
    ) {
        $from = EAC()->currencies->get_rate($from);
    }
    if (
        !is_numeric($to) &&
        strlen($to) === 3 &&
        array_key_exists($to, $currencies)
    ) {
        $to = EAC()->currencies->get_rate($to);
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


function eac_sanitize_number($number, $decimals = 2)
{
    
    $number = preg_replace('/\.(?![^.]+$)|[^0-9.-]/', "", eac_clean($number));

    if ($decimals) {
        $number = (float) preg_replace("/[^0-9.-]/", "", $number);
        
        if (is_numeric($decimals)) {
            $number = number_format(floatval($number), $decimals, ".", "");
        }

        return $number;
    }

    return (int) preg_replace("/[^0-9]/", "", $number);
}


function eac_round_number($val, $decimals = 6, $mode = PHP_ROUND_HALF_UP)
{
    $val = eac_sanitize_number($val, $decimals);

    return round($val, $decimals, $mode);
}


function eac_get_payment_methods()
{
    return apply_filters("eac_payment_methods", [
        "cash" => esc_html__("Cash", "otto-contracts"),
        "check" => esc_html__("Cheque", "otto-contracts"),
        "credit" => esc_html__("Credit Card", "otto-contracts"),
        "debit" => esc_html__("Debit Card", "otto-contracts"),
        "bank" => esc_html__("Bank Transfer", "otto-contracts"),
        "paypal" => esc_html__("PayPal", "otto-contracts"),
        "other" => esc_html__("Other", "otto-contracts"),
    ]);
}


function eac_format_datetime(
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
function eac_invoice_status_label_map()
{
    return [
        "draft" => "Contract/Draft",
        "sent" => "Contract/Sent",
        "accept" => "Accept/Bill",
        "partial" => "Partial/Bill",
        "paid" => "Paid/Bill",
        "overdue" => "Overdue/Bill",
        "otto" => "Otto/Bill",
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
function eac_invoice_status_label_for_status($status)
{
    if (!is_string($status) || "" === $status) {
        return "";
    }
    $status = sanitize_key($status);
    $map = eac_invoice_status_label_map();

    return isset($map[$status]) ? $map[$status] : "";
}

/**
 * Status label for contract admin headings (View … / Edit …).
 *
 * @param object $invoice Model with `status` and optional `status_label`.
 * @return string
 */
function eac_invoice_heading_status_label($invoice)
{
    if (!is_object($invoice) || !isset($invoice->status)) {
        return "";
    }
    $label = isset($invoice->status_label)
        ? trim((string) $invoice->status_label)
        : "";
    if ("" === $label) {
        $label = eac_invoice_status_label_for_status((string) $invoice->status);
    }

    return $label;
}
