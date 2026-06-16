<?php

use Jjpsos\ContractPilot\Utilities\I18nUtil;

defined("ABSPATH") || exit();


function contract_pilot_clean($value)
{
    if (is_array($value)) {
        return array_map("contract_pilot_clean", $value);
    } else {
        return is_scalar($value) ? sanitize_text_field($value) : $value;
    }
}


function contract_pilot_tooltip($tip, $allow_html = false)
{
    if ($allow_html) {
        $tip = contract_pilot_sanitize_tooltip($tip);
    } else {
        $tip = esc_attr($tip);
    }

    return '<span class="contract-pilot-tooltip" title="' .
        wp_kses_post($tip) .
        '">[?]</span>';
}


function contract_pilot_sanitize_tooltip($text)
{
    return htmlspecialchars(
        wp_kses(html_entity_decode($text), [
            "br" => [],
            "em" => [],
            "strong" => [],
            "small" => [],
            "span" => [],
            "ul" => [],
            "li" => [],
            "ol" => [],
            "p" => [],
        ]),
    );
}


function contract_pilot_get_formatted_address($fields = [], $separator = "<br/>")
{
    $defaults = [
        "name" => "",
        "company" => "",
        "address" => "",
        "city" => "",
        "state" => "",
        "postcode" => "",
        "country" => "",
    ];
    $format = apply_filters(
        "contract_pilot_address_format",
        "<strong>{name}</strong>\n{company}\n{address}\n{city} {state} {postcode}\n{country}",
    );
    $fields = wp_parse_args($fields, $defaults);
    $countries = I18nUtil::get_countries();
    $fields["country"] = isset($countries[$fields["country"]])
        ? $countries[$fields["country"]]
        : $fields["country"];
    $replacers = array_map("esc_html", [
        "{name}" => $fields["name"],
        "{company}" => $fields["company"],
        "{address}" => $fields["address"],
        "{city}" => $fields["city"],
        "{state}" => $fields["state"],
        "{postcode}" => $fields["postcode"],
        "{country}" => $fields["country"],
    ]);
    $formatted_address = str_replace(
        array_keys($replacers),
        $replacers,
        $format,
    );

    $formatted_address = preg_replace("/  +/", " ", trim($formatted_address));
    $formatted_address = preg_replace('/\n\n+/', "\n", $formatted_address);

    $address_lines = array_map(
        "trim",
        array_filter(explode("\n", $formatted_address)),
    );

    $extra = array_diff_key($fields, $defaults);
    foreach ($extra as $key => $value) {
        if (!empty($value)) {
            switch ($key) {
                case "tax_number":
                    $address_lines[] =
                        __("Tax #", "contract-pilot") . $value;
                    break;
                default:
                    $address_lines[] = $value;
            }
        }
    }


    $address_lines = array_map("trim", $address_lines);

    $address_lines = array_filter($address_lines);

    return implode($separator, $address_lines);
}


function contract_pilot_date_format()
{
    $date_format = get_option("date_format");
    if (empty($date_format)) {
        /* translators: Default PHP date format when the site date format option is empty. See https://www.php.net/manual/en/datetime.format.php */
        $date_format = __('F j, Y', 'contract-pilot');
    }

    return apply_filters("contract_pilot_date_format", $date_format);
}


function contract_pilot_time_format()
{
    $time_format = get_option("time_format");
    if (empty($time_format)) {
        /* translators: Default PHP time format when the site time format option is empty. See https://www.php.net/manual/en/datetime.format.php */
        $time_format = __('g:i a', 'contract-pilot');
    }

    return apply_filters("contract_pilot_time_format", $time_format);
}


function contract_pilot_date_time_format()
{
    return contract_pilot_date_format() . " " . contract_pilot_time_format();
}
