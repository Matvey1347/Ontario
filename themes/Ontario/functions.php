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

if (! function_exists('ontario_site_display_mode')) {
    function ontario_site_display_mode(): string
    {
        $mode = sanitize_key(ontario_site_field('display_mode', 'full'));

        return in_array($mode, ['full', 'simple', 'choice'], true) ? $mode : 'full';
    }
}

if (! function_exists('ontario_display_preference')) {
    function ontario_display_preference(): string
    {
        $current_site = function_exists('ontario_current_site') ? ontario_current_site() : [];
        $cookie_name = 'ontario_display_preference';

        if (! empty($current_site['is_preview']) && ! empty($current_site['id'])) {
            $cookie_name .= '_preview_' . absint((int) $current_site['id']);
        }

        $value = isset($_COOKIE[$cookie_name])
            ? sanitize_key(wp_unslash((string) $_COOKIE[$cookie_name]))
            : '';

        return in_array($value, ['full', 'simple'], true) ? $value : '';
    }
}

if (! function_exists('ontario_effective_display_mode')) {
    function ontario_effective_display_mode(): string
    {
        $site_mode = ontario_site_display_mode();

        if ($site_mode !== 'choice') {
            return $site_mode;
        }

        $preference = ontario_display_preference();

        return $preference !== '' ? $preference : 'choice';
    }
}

if (! function_exists('ontario_should_show_display_choice_modal')) {
    function ontario_should_show_display_choice_modal(): bool
    {
        return ontario_site_display_mode() === 'choice' && ontario_display_preference() === '';
    }
}

if (! function_exists('ontario_site_display_choice_title')) {
    function ontario_site_display_choice_title(): string
    {
        return ontario_site_field('display_choice_title', 'Choose how you would like to view this website');
    }
}

if (! function_exists('ontario_site_display_choice_description')) {
    function ontario_site_display_choice_description(): string
    {
        return ontario_site_field('display_choice_description', 'You can continue with the full interactive design or switch to a simpler version with larger text and a calmer layout.');
    }
}

if (! function_exists('ontario_site_display_choice_simple_label')) {
    function ontario_site_display_choice_simple_label(): string
    {
        return ontario_site_field('display_choice_simple_label', 'Use simple design');
    }
}

if (! function_exists('ontario_site_display_choice_full_label')) {
    function ontario_site_display_choice_full_label(): string
    {
        return ontario_site_field('display_choice_full_label', 'Continue with full design');
    }
}

if (! function_exists('ontario_simple_image_uri')) {
    function ontario_simple_image_uri(string $name): string
    {
        $theme_uri = get_template_directory_uri();
        $map = [
            'hero' => '/assets/images/old-1.png',
            'scanner' => '/assets/images/old-2.png',
            'report' => '/assets/images/old-1.png',
            'process' => '/assets/images/old-3.png',
        ];

        return isset($map[$name]) ? $theme_uri . $map[$name] : '';
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

add_filter('body_class', static function (array $classes): array {
    $effective_mode = ontario_effective_display_mode();
    $site_mode = ontario_site_display_mode();

    $classes[] = 'ontario-display-' . $effective_mode;
    $classes[] = 'ontario-display-setting-' . $site_mode;

    if ($site_mode === 'choice' && $effective_mode === 'choice') {
        $classes[] = 'ontario-display-choice-pending';
    }

    return $classes;
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
    $theme_dir = get_template_directory();
    $current_site = function_exists('ontario_current_site') ? ontario_current_site() : [];
    $site_display_mode = ontario_site_display_mode();
    $effective_display_mode = ontario_effective_display_mode();
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
        file_exists($theme_dir . '/assets/css/styles.css') ? (string) filemtime($theme_dir . '/assets/css/styles.css') : $version
    );

    if ($site_display_mode !== 'full') {
        wp_enqueue_style(
            'ontario-theme-simple',
            $theme_uri . '/assets/css/simple.css',
            ['ontario-theme'],
            file_exists($theme_dir . '/assets/css/simple.css') ? (string) filemtime($theme_dir . '/assets/css/simple.css') : $version
        );
    }

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
        file_exists($theme_dir . '/assets/js/main.js') ? (string) filemtime($theme_dir . '/assets/js/main.js') : $version,
        true
    );

    if ($site_display_mode === 'choice') {
        wp_enqueue_script(
            'ontario-display-mode',
            $theme_uri . '/assets/js/display-mode.js',
            [],
            file_exists($theme_dir . '/assets/js/display-mode.js') ? (string) filemtime($theme_dir . '/assets/js/display-mode.js') : $version,
            false
        );

        if (function_exists('wp_script_add_data')) {
            wp_script_add_data('ontario-display-mode', 'defer', true);
        }
    }

    if (function_exists('wp_script_add_data')) {
        wp_script_add_data('choices', 'defer', true);
        wp_script_add_data('ontario-theme', 'defer', true);
    }

    if ($site_display_mode === 'choice') {
        $display_preference_key = 'ontario_display_preference';
        $display_preference_cookie = 'ontario_display_preference';

        if (! empty($current_site['is_preview']) && ! empty($current_site['id'])) {
            $suffix = '_preview_' . absint((int) $current_site['id']);
            $display_preference_key .= $suffix;
            $display_preference_cookie .= $suffix;
        }

        $early_display_script = <<<'JS'
(() => {
  const key = __DISPLAY_PREFERENCE_KEY__;
  const cookieName = __DISPLAY_PREFERENCE_COOKIE__;
  const root = document.documentElement;
  const cookiePattern = new RegExp('(?:^|;\\s*)' + cookieName.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '=(simple|full)(?:;|$)');
  let preference = '';

  const cookieMatch = document.cookie.match(cookiePattern);
  preference = cookieMatch ? cookieMatch[1] : '';

  if (!preference) {
    try {
      const stored = window.localStorage.getItem(key);
      if (stored === 'simple' || stored === 'full') {
        preference = stored;
        document.cookie = `${cookieName}=${stored}; path=/; max-age=15552000; SameSite=Lax`;
      }
    } catch (error) {}
  }

  root.classList.remove('ontario-display-full', 'ontario-display-simple', 'ontario-display-choice');
  root.classList.add(preference === 'simple' ? 'ontario-display-simple' : preference === 'full' ? 'ontario-display-full' : 'ontario-display-choice');
})();
JS;
        $early_display_script = str_replace(
            ['__DISPLAY_PREFERENCE_KEY__', '__DISPLAY_PREFERENCE_COOKIE__'],
            [wp_json_encode($display_preference_key), wp_json_encode($display_preference_cookie)],
            $early_display_script
        );
        wp_add_inline_script('ontario-display-mode', $early_display_script, 'before');
    }

    wp_localize_script('ontario-theme', 'ontarioSiteManager', [
        'restEndpoint' => esc_url_raw($rest_endpoint),
        'successUrl' => esc_url_raw(ontario_success_page_url()),
        'brandName' => function_exists('ontario_site_brand_name') ? ontario_site_brand_name() : get_bloginfo('name'),
        'siteId' => (int) ($current_site['id'] ?? 0),
        'siteHost' => (string) ($current_site['resolved_host'] ?? ''),
        'isPreview' => ! empty($current_site['is_preview']),
        'siteDisplayMode' => $site_display_mode,
        'effectiveDisplayMode' => $effective_display_mode,
        'showWelcomeModal' => $effective_display_mode === 'full',
        'showDisplayChoiceModal' => ontario_should_show_display_choice_modal(),
    ]);

    if ($site_display_mode === 'choice') {
        wp_localize_script('ontario-display-mode', 'ontarioDisplayMode', [
            'siteMode' => $site_display_mode,
            'effectiveMode' => $effective_display_mode,
            'showChoiceModal' => ontario_should_show_display_choice_modal(),
            'isPreview' => ! empty($current_site['is_preview']),
            'siteId' => (int) ($current_site['id'] ?? 0),
            'storageKey' => $display_preference_key,
            'cookieName' => $display_preference_cookie,
            'title' => ontario_site_display_choice_title(),
            'description' => ontario_site_display_choice_description(),
            'simpleLabel' => ontario_site_display_choice_simple_label(),
            'fullLabel' => ontario_site_display_choice_full_label(),
        ]);
    }
});
