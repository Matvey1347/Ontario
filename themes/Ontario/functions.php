<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

if (! function_exists('ontario_current_site')) {
    function ontario_current_site(): array
    {
        return [];
    }
}

if (! function_exists('ontario_site_field')) {
    function ontario_site_field(string $key, string $default = ''): string
    {
        return $default;
    }
}

if (! function_exists('ontario_site_logo_url')) {
    function ontario_site_logo_url(): string
    {
        return '';
    }
}

if (! function_exists('ontario_site_brand_name')) {
    function ontario_site_brand_name(): string
    {
        $name = get_bloginfo('name');

        return is_string($name) && $name !== '' ? $name : 'Ontario Refunds';
    }
}

if (! function_exists('ontario_replace_site_tokens')) {
    function ontario_replace_site_tokens(string $content): string
    {
        return $content;
    }
}

if (! function_exists('ontario_render_tracking_code')) {
    function ontario_render_tracking_code(string $location = 'head'): string
    {
        return '';
    }
}

if (! function_exists('ontario_render_success_tracking_code')) {
    function ontario_render_success_tracking_code(): string
    {
        return '';
    }
}

if (! function_exists('ontario_success_page_url')) {
    function ontario_success_page_url(): string
    {
        return home_url('/success/');
    }
}

add_action('admin_init', static function (): void {
    if (! is_admin() || wp_doing_ajax()) {
        return;
    }

    if (post_type_exists('ontario_site')) {
        return;
    }

    $requested_post_type = isset($_GET['post_type']) ? sanitize_key((string) $_GET['post_type']) : '';

    if ($requested_post_type === '' && isset($_GET['post'])) {
        $requested_post_type = get_post_type((int) $_GET['post']) ?: '';
    }

    if ($requested_post_type !== 'ontario_site') {
        return;
    }

    wp_safe_redirect(add_query_arg('ontario_plugin_required', '1', admin_url('plugins.php')));
    exit;
});

add_action('admin_notices', static function (): void {
    if (! is_admin() || ! current_user_can('activate_plugins')) {
        return;
    }

    if (! isset($_GET['ontario_plugin_required']) || $_GET['ontario_plugin_required'] !== '1') {
        return;
    }

    echo '<div class="notice notice-warning is-dismissible"><p>Ontario Plugin must be activated to manage Ontario Sites.</p></div>';
});

add_action('after_setup_theme', static function (): void {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);
});

add_action('template_redirect', static function (): void {
    if (is_admin()) {
        return;
    }

    $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash((string) $_SERVER['REQUEST_URI']) : '';
    $request_path = trim((string) wp_parse_url($request_uri, PHP_URL_PATH), '/');
    $home_path = trim((string) wp_parse_url(home_url('/'), PHP_URL_PATH), '/');

    if ($home_path !== '' && str_starts_with($request_path, $home_path . '/')) {
        $request_path = substr($request_path, strlen($home_path) + 1);
    } elseif ($request_path === $home_path) {
        $request_path = '';
    }

    if ($request_path !== 'success') {
        return;
    }

    global $wp_query;

    if ($wp_query instanceof WP_Query) {
        $wp_query->is_404 = false;
    }

    status_header(200);
    nocache_headers();
    include get_template_directory() . '/page-success.php';
    exit;
});

add_action('wp_enqueue_scripts', static function (): void {
    $theme = wp_get_theme();
    $version = $theme->get('Version') ?: '1.0.0';
    $theme_uri = get_template_directory_uri();
    $current_site = function_exists('ontario_current_site') ? ontario_current_site() : [];
    $rest_endpoint = home_url('/wp-json/ontario-site-manager/v1/lead');

    if (! empty($current_site['is_preview']) && ! empty($current_site['id'])) {
        $rest_endpoint = add_query_arg([
            'ontario_preview_site' => (int) $current_site['id'],
            'ontario_preview_token' => OSM_Current_Site::preview_token((int) $current_site['id']),
        ], $rest_endpoint);
    }

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
        'restEndpoint' => esc_url_raw($rest_endpoint),
        'successUrl' => esc_url_raw(ontario_success_page_url()),
        'brandName' => function_exists('ontario_site_brand_name') ? ontario_site_brand_name() : get_bloginfo('name'),
        'siteId' => (int) ($current_site['id'] ?? 0),
        'siteHost' => (string) ($current_site['resolved_host'] ?? ''),
        'isPreview' => ! empty($current_site['is_preview']),
    ]);
});
