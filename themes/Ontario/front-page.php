<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

$theme_uri = function_exists('ontario_theme_asset_uri') ? ontario_theme_asset_uri() : get_template_directory_uri();
$brand_name = function_exists('ontario_site_brand_name') ? ontario_site_brand_name() : get_bloginfo('name');
$phone_number = function_exists('ontario_site_field') ? ontario_site_field('phone_number') : '';
$working_hours = function_exists('ontario_site_field') ? ontario_site_field('working_hours') : '';
$address = function_exists('ontario_site_field') ? ontario_site_field('address') : '';
$display_mode = function_exists('ontario_effective_display_mode') ? ontario_effective_display_mode() : 'full';

if (function_exists('ontario_uses_newsite_design') && ontario_uses_newsite_design()) {
    $front_page_host = strtolower((string) (wp_parse_url(home_url('/'), PHP_URL_HOST) ?: ''));
    if ($front_page_host === 'torontorefund.com' || $front_page_host === 'www.torontorefund.com') {
        $torontorefund_css = get_template_directory() . '/assets/css/torontorefund-newsite.css';
        wp_enqueue_style(
            'ontario-torontorefund-newsite',
            get_template_directory_uri() . '/assets/css/torontorefund-newsite.css',
            ['ontario-newsite'],
            is_readable($torontorefund_css) ? (string) filemtime($torontorefund_css) : null
        );
        add_filter('body_class', static function (array $classes): array {
            $classes[] = 'ontario-torontorefund';
            return $classes;
        });
    }
    include get_template_directory() . '/page-newsite.php';
    return;
}

get_header();

if ($display_mode === 'simple') {
    include locate_template('template-parts/front-page/simple.php');
} else {
    include locate_template('template-parts/front-page/full.php');
}

include locate_template('template-parts/front-page/modals.php');

get_footer();
