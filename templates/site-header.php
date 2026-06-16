<?php



defined("ABSPATH") || exit(); ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <link rel="profile" href="https://gmpg.org/xfn/11"/>
    <meta name="robots" content="noindex,nofollow">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title><?php echo esc_html(
        apply_filters(
            "contract_pilot_page_title",
            esc_html__("Contract Pilot", "contract-pilot"),
        ),
    ); ?></title>
    <meta charset="<?php bloginfo("charset"); ?>">
    <?php wp_head(); ?>
</head>

<body class="wp-core-ui contract-pilot">
<div class="contract-pilot-page-content">
