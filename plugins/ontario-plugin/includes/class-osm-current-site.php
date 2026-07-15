<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class OSM_Current_Site
{
    private OSM_Sites $sites;
    private OSM_Logger $logger;
    private ?array $site = null;

    public function __construct(OSM_Sites $sites, OSM_Logger $logger)
    {
        $this->sites = $sites;
        $this->logger = $logger;

        add_filter('home_url', [$this, 'filter_frontend_url'], 10, 4);
        add_filter('get_site_icon_url', [$this, 'filter_site_icon_url'], 10, 3);
        add_filter('pre_get_document_title', [$this, 'filter_document_title']);
        add_filter('the_content', [$this, 'filter_content_tokens'], 20);
        add_action('wp_head', [$this, 'render_head_assets'], 1);
        add_action('wp_body_open', [$this, 'render_body_assets'], 1);
    }

    public function get_site(): array
    {
        if ($this->site !== null) {
            return $this->site;
        }

        $preview_site = $this->resolve_preview_site();

        if ($preview_site !== []) {
            $this->site = $this->with_localization_settings($preview_site);
            return $this->site;
        }

        $host = $this->get_request_host();
        $raw_host = $this->get_request_host_raw();

        if ($host !== '') {
            $matched = $this->sites->find_site_by_host($host);

            if ($matched !== []) {
                $matched['resolved_host'] = $raw_host !== '' ? $raw_host : $host;
                $matched['resolved_host_normalized'] = $host;
                $this->site = $this->with_localization_settings($matched);
                return $this->site;
            }
        }

        $fallback = $this->sites->get_default_site();

        if ($fallback !== []) {
            $fallback['resolved_host'] = $raw_host !== '' ? $raw_host : ($host !== '' ? $host : ($fallback['primary_domain'] ?? ''));
            $fallback['resolved_host_normalized'] = $host;
            $this->site = $this->with_localization_settings($fallback);
            return $this->site;
        }

        $this->site = $this->with_localization_settings($this->defaults());

        return $this->site;
    }

    public function get_field(string $key, string $default = ''): string
    {
        $site = $this->get_site();

        if (array_key_exists($key, $site) && is_scalar($site[$key])) {
            return trim((string) $site[$key]);
        }

        return $default;
    }

    public function get_logo_url(): string
    {
        $site = $this->get_site();
        $logo_id = (int) ($site['logo_id'] ?? 0);

        if ($logo_id > 0) {
            $url = wp_get_attachment_image_url($logo_id, 'full');

            if (is_string($url) && $url !== '') {
                return $url;
            }
        }

        if (! empty($site['logo_url'])) {
            return (string) $site['logo_url'];
        }

        $default_site = $this->sites->get_default_site();
        $default_logo_id = (int) ($default_site['logo_id'] ?? 0);

        if ($default_logo_id > 0) {
            $url = wp_get_attachment_image_url($default_logo_id, 'full');

            if (is_string($url) && $url !== '') {
                return $url;
            }
        }

        if (! empty($default_site['logo_url'])) {
            return (string) $default_site['logo_url'];
        }

        $defaults = $this->defaults();

        return (string) ($defaults['logo_url'] ?? '');
    }

    public function get_brand_name(): string
    {
        return $this->get_field('company_name', 'Ontario Refunds');
    }

    public function get_domain(): string
    {
        $site = $this->get_site();

        return (string) ($site['resolved_host'] ?? $site['primary_domain'] ?? wp_parse_url(home_url('/'), PHP_URL_HOST));
    }

    public function is_preview(): bool
    {
        return ! empty($this->get_site()['is_preview']);
    }

    public static function preview_token(int $site_id): string
    {
        return hash_hmac('sha256', 'ontario_preview_site|' . $site_id, wp_salt('auth'));
    }

    public function replace_tokens(string $content): string
    {
        $replacements = [
            '{company_name}' => $this->get_brand_name(),
            '{domain}' => $this->get_domain(),
            '{email}' => $this->get_field('public_email'),
            '{phone}' => $this->get_field('phone_number'),
            '{address}' => $this->get_field('address'),
        ];

        return strtr($content, $replacements);
    }

    public function get_tracking_code(string $location = 'head'): string
    {
        if ($this->is_preview()) {
            return '';
        }

        $site = $this->get_site();
        $field = match ($location) {
            'head_open' => 'tracking_head_open_code',
            'body' => 'tracking_body_code',
            'success' => 'tracking_success_code',
            default => 'tracking_header_code',
        };

        return trim((string) ($site[$field] ?? ''));
    }

    public function render_tracking_code(string $location = 'head'): string
    {
        $code = $this->get_tracking_code($location);

        return $code !== '' ? $code . "\n" : '';
    }

    public function filter_document_title(string $title): string
    {
        $site_title = $this->get_field('meta_title', get_bloginfo('name'));

        if (is_front_page() || is_home()) {
            return $site_title;
        }

        if (is_singular()) {
            $page_title = single_post_title('', false);
            return $page_title !== '' ? $page_title . ' - ' . $site_title : $site_title;
        }

        return $site_title;
    }

    public function filter_content_tokens(string $content): string
    {
        if (is_admin()) {
            return $content;
        }

        return $this->replace_tokens($content);
    }

    public function render_head_assets(): void
    {
        $favicon_url = $this->get_favicon_url();

        if ($favicon_url !== '') {
            echo '<link rel="icon" href="' . esc_url($favicon_url) . '" />' . "\n";
            echo '<link rel="shortcut icon" href="' . esc_url($favicon_url) . '" />' . "\n";
            echo '<link rel="apple-touch-icon" href="' . esc_url($favicon_url) . '" />' . "\n";
        }

        $tracking = $this->render_tracking_code('head');

        if ($tracking !== '') {
            echo $tracking;
        }
    }

    public function render_body_assets(): void
    {
        $tracking = $this->render_tracking_code('body');

        if ($tracking !== '') {
            echo $tracking;
        }
    }

    public function filter_site_icon_url(string $url, int $size, int $blog_id): string
    {
        $favicon_url = $this->get_favicon_url();

        return $favicon_url !== '' ? $favicon_url : $url;
    }

    public function filter_frontend_url(string $url, string $path, ?string $orig_scheme, ?int $blog_id): string
    {
        if (is_admin() || wp_doing_cron()) {
            return $url;
        }

        $host = $this->get_request_host_raw();

        if ($host === '') {
            return $url;
        }

        $parts = wp_parse_url($url);

        if (! is_array($parts) || empty($parts['host'])) {
            return $url;
        }

        $scheme = $this->get_request_scheme();
        $rebuilt = $scheme . '://' . $host;

        if (! empty($parts['path'])) {
            $rebuilt .= $parts['path'];
        }

        if (! empty($parts['query'])) {
            $rebuilt .= '?' . $parts['query'];
        }

        if (! empty($parts['fragment'])) {
            $rebuilt .= '#' . $parts['fragment'];
        }

        return $rebuilt;
    }

    private function resolve_preview_site(): array
    {
        $preview_id = isset($_GET['ontario_preview_site']) ? absint($_GET['ontario_preview_site']) : 0;
        $preview_token = isset($_GET['ontario_preview_token']) ? sanitize_text_field(wp_unslash($_GET['ontario_preview_token'])) : '';
        $legacy_nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';

        if ($preview_id < 1) {
            return [];
        }

        $token_valid = $preview_token !== '' && hash_equals(self::preview_token($preview_id), $preview_token);
        $legacy_nonce_valid = $legacy_nonce !== '' && wp_verify_nonce($legacy_nonce, 'ontario_preview_site_' . $preview_id);

        if (! current_user_can('manage_options') && ! $token_valid && ! $legacy_nonce_valid) {
            return [];
        }

        $site = $this->sites->get_site($preview_id);

        if ($site === []) {
            return [];
        }

        $site['is_preview'] = true;
        $site['resolved_host'] = $this->get_request_host_raw();
        $site['resolved_host_normalized'] = $this->get_request_host();

        return $site;
    }

    private function get_request_host(): string
    {
        $host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';

        return $this->sites->normalize_host($host);
    }

    private function get_favicon_url(): string
    {
        $site = $this->get_site();
        $favicon_id = (int) ($site['favicon_id'] ?? 0);

        if ($favicon_id > 0) {
            $favicon = wp_get_attachment_image_url($favicon_id, 'full');

            if (is_string($favicon) && $favicon !== '') {
                return $favicon;
            }
        }

        if (! empty($site['favicon_url'])) {
            return (string) $site['favicon_url'];
        }

        $default_site = $this->sites->get_default_site();
        $default_favicon_id = (int) ($default_site['favicon_id'] ?? 0);

        if ($default_favicon_id > 0) {
            $favicon = wp_get_attachment_image_url($default_favicon_id, 'full');

            if (is_string($favicon) && $favicon !== '') {
                return $favicon;
            }
        }

        if (! empty($default_site['favicon_url'])) {
            return (string) $default_site['favicon_url'];
        }

        $defaults = $this->defaults();

        return (string) ($defaults['favicon_url'] ?? '');
    }

    private function get_request_host_raw(): string
    {
        $host = isset($_SERVER['HTTP_HOST']) ? strtolower(trim(sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])))) : '';
        $host = preg_replace('#^https?://#', '', $host) ?: $host;
        $host = preg_replace('#/.*$#', '', $host) ?: $host;

        return $host;
    }

    private function get_request_scheme(): string
    {
        if (is_ssl()) {
            return 'https';
        }

        return 'http';
    }

    private function defaults(): array
    {
        return [
            'id' => 0,
            'resolved_host' => $this->get_request_host_raw(),
            'resolved_host_normalized' => $this->get_request_host(),
            'primary_domain' => 'ontariorefunds.info',
            'meta_title' => 'Ontario Refunds',
            'company_name' => 'Ontario Refunds',
            'logo_url' => 'https://ontariorefunds.info/wp-content/uploads/2026/02/Ontario-Refunds-logo-color.png',
            'favicon_url' => content_url('themes/Ontario/assets/images/fav.png'),
            'public_email' => 'support@ontariorefunds.info',
            'phone_number' => '+1 647 478 0877',
            'phone_href' => '+16474780877',
            'address' => '10-40 WEST WILMOT ST., RICHMOND HILL ON L4B 1H8',
            'working_hours' => '11AM-7PM ET',
            'meta_description' => 'Ontario Refunds helps victims of online financial fraud trace digital assets, prepare evidence-based reports, and understand practical next steps.',
            'notification_emails' => 'support@ontariorefunds.info',
            'tracking_head_open_code' => '',
            'tracking_header_code' => '',
            'tracking_body_code' => '',
            'tracking_success_code' => '',
            'clickcease_install_click_fraud' => '0',
            'clickcease_api_key' => '',
            'clickcease_domain_key' => '',
            'clickcease_secret_key' => '',
            'clickcease_whitelist' => '',
            'clickcease_bot_zapping_authenticated' => '0',
            'clickcease_client_id' => '',
            'clickcease_monitoring' => '0',
            'clickcease_invalid_secret' => '0',
            'clickcease_active_domain_value' => '',
            'clickcease_active_domain_checked_at' => '',
            'language_configuration' => 'inherit',
            'enabled_languages_raw' => [],
            'default_language_raw' => '',
            'phone_country_selector_mode' => 'inherit',
            'enabled_languages' => ['en'],
            'default_language' => 'en',
            'phone_country_selector_enabled' => false,
        ];
    }

    private function with_localization_settings(array $site): array
    {
        if (! class_exists('OSM_Plugin')) {
            return $site;
        }

        $plugin = OSM_Plugin::instance();

        if (! method_exists($plugin, 'translations')) {
            return $site;
        }

        $effective = $plugin->translations()->effective_settings($site);

        return array_merge($site, $effective);
    }
}
