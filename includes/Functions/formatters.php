<?php

use Otto\Utilities\I18nUtil;

defined("ABSPATH") || exit();


function eac_clean($value)
{
    if (is_array($value)) {
        return array_map("eac_clean", $value);
    } else {
        return is_scalar($value) ? sanitize_text_field($value) : $value;
    }
}


function eac_tooltip($tip, $allow_html = false)
{
    if ($allow_html) {
        $tip = eac_sanitize_tooltip($tip);
    } else {
        $tip = esc_attr($tip);
    }

    return '<span class="eac-tooltip" title="' .
        wp_kses_post($tip) .
        '">[?]</span>';
}


function eac_sanitize_tooltip($text)
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


function eac_get_formatted_address($fields = [], $separator = "<br/>")
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
        "eac_address_format",
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
                        __("Tax #", "otto-contracts") . $value;
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


function eac_date_format()
{
    $date_format = get_option("date_format");
    if (empty($date_format)) {
        
        $date_format = "F j, Y";
    }

    return apply_filters("eac_date_format", $date_format);
}


function eac_time_format()
{
    $time_format = get_option("time_format");
    if (empty($time_format)) {
        
        $time_format = "g:i a";
    }

    return apply_filters("eac_time_format", $time_format);
}


function eac_date_time_format()
{
    return eac_date_format() . " " . eac_time_format();
}
