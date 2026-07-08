<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

add_action('after_setup_theme', static function (): void {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);
});

add_action('wp_enqueue_scripts', static function (): void {
    $theme = wp_get_theme();
    $version = $theme->get('Version') ?: '1.0.0';
    $theme_uri = get_template_directory_uri();
    $current_site = function_exists('ontario_current_site') ? ontario_current_site() : [];

    wp_enqueue_style(
        'ontario-google-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap',
        [],
        null
    );

    wp_enqueue_style(
        'choices',
        'https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css',
        [],
        null
    );

    wp_enqueue_style(
        'ontario-theme',
        $theme_uri . '/assets/css/styles.css',
        ['ontario-google-fonts', 'choices'],
        $version
    );

    wp_enqueue_script(
        'choices',
        'https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js',
        [],
        null,
        true
    );

    wp_enqueue_script(
        'ontario-theme',
        $theme_uri . '/assets/js/main.js',
        ['choices'],
        $version,
        true
    );

    if (function_exists('wp_script_add_data')) {
        wp_script_add_data('choices', 'defer', true);
        wp_script_add_data('ontario-theme', 'defer', true);
    }

    wp_localize_script('ontario-theme', 'ontarioSiteManager', [
        'restEndpoint' => esc_url_raw(home_url('/wp-json/ontario-site-manager/v1/lead')),
        'brandName' => function_exists('ontario_site_brand_name') ? ontario_site_brand_name() : get_bloginfo('name'),
        'siteId' => (int) ($current_site['id'] ?? 0),
        'siteHost' => (string) ($current_site['resolved_host'] ?? ''),
        'isPreview' => ! empty($current_site['is_preview']),
    ]);
});
