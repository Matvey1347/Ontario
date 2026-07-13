<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class OSM_Translations
{
    private const SETTINGS_OPTION = 'osm_localization_settings';
    private const QUERY_ARG = 'ontario_lang';
    private const CONTENT_GROUPS = [
        'full_hero' => [
            'label' => 'Full Hero',
            'keys' => [
                'hero.badge',
                'hero.title',
                'hero.copy',
                'hero.cta_primary',
                'hero.cta_secondary',
                'hero.trust.encrypted',
                'hero.trust.pipeda',
                'hero.trust.soc2',
            ],
        ],
        'simple_hero' => [
            'label' => 'Simple Hero',
            'keys' => [
                'simple.hero.badge',
                'simple.hero.title',
                'simple.hero.lead',
                'simple.hero.note',
                'simple.hero.primary',
                'simple.hero.secondary',
                'simple.hero.trust_private',
                'simple.hero.trust_encrypted',
            ],
        ],
        'footer' => [
            'label' => 'Footer',
            'keys' => [
                'footer.description',
                'footer.security',
                'footer.copyright',
                'footer.terms',
                'footer.privacy',
            ],
        ],
    ];

    private OSM_Sites $sites;
    private OSM_Current_Site $current_site;
    private ?array $available_languages = null;
    private array $translations = [];
    private ?array $phone_countries = null;
    private ?string $current_language = null;

    public function __construct(OSM_Sites $sites, OSM_Current_Site $current_site)
    {
        $this->sites = $sites;
        $this->current_site = $current_site;

        add_action('template_redirect', [$this, 'maybe_handle_language_switch'], 1);
        add_filter('language_attributes', [$this, 'filter_language_attributes'], 20, 2);
    }

    public function available_languages(): array
    {
        if ($this->available_languages !== null) {
            return $this->available_languages;
        }

        $languages = [];
        $files = glob(OSM_PLUGIN_PATH . 'translations/*.php') ?: [];

        foreach ($files as $file) {
            $code = basename($file, '.php');
            $data = require $file;

            if (! is_array($data)) {
                continue;
            }

            if (($data['code'] ?? '') !== $code) {
                continue;
            }

            if (
                ! isset($data['name'], $data['native_name'], $data['flag'], $data['strings'])
                || ! is_string($data['name'])
                || ! is_string($data['native_name'])
                || ! is_string($data['flag'])
                || ! is_array($data['strings'])
            ) {
                continue;
            }

            $languages[$code] = [
                'code' => $code,
                'name' => trim($data['name']),
                'native_name' => trim($data['native_name']),
                'flag' => trim($data['flag']),
                'strings' => $data['strings'],
            ];
        }

        ksort($languages);
        $this->available_languages = $languages;

        return $this->available_languages;
    }

    public function global_settings(): array
    {
        $stored = get_option(self::SETTINGS_OPTION, []);
        $stored = is_array($stored) ? $stored : [];

        return $this->sanitize_localization_settings($stored);
    }

    public function save_global_settings(array $settings): void
    {
        update_option(self::SETTINGS_OPTION, $this->sanitize_localization_settings($settings), false);
    }

    public function content_groups(): array
    {
        return self::CONTENT_GROUPS;
    }

    public function editable_content_keys(): array
    {
        $keys = [];

        foreach (self::CONTENT_GROUPS as $group) {
            foreach ($group['keys'] as $key) {
                $keys[] = $key;
            }
        }

        return array_values(array_unique($keys));
    }

    public function is_editable_content_key(string $key): bool
    {
        return in_array($key, $this->editable_content_keys(), true);
    }

    public function effective_settings(array $site): array
    {
        $global = $this->global_settings();
        $available = array_keys($this->available_languages());
        $raw_enabled = $site['enabled_languages_raw'] ?? [];
        $site_enabled = $this->sanitize_language_codes(is_array($raw_enabled) ? $raw_enabled : [], $available);
        $site_default = sanitize_key((string) ($site['default_language_raw'] ?? ''));

        $enabled_languages = $site_enabled !== [] ? $site_enabled : $global['enabled_languages'];
        if ($enabled_languages === []) {
            $enabled_languages = ['en'];
        }

        $default_language = $site_default !== '' ? $site_default : $global['default_language'];
        if (! in_array($default_language, $enabled_languages, true)) {
            if ($site_default === '') {
                $default_language = in_array('en', $enabled_languages, true) ? 'en' : $enabled_languages[0];
            } else {
                $default_language = in_array($global['default_language'], $enabled_languages, true)
                    ? $global['default_language']
                    : (in_array('en', $enabled_languages, true) ? 'en' : $enabled_languages[0]);
            }
        }

        $phone_mode = sanitize_key((string) ($site['phone_country_selector_mode'] ?? 'inherit'));
        $phone_enabled = match ($phone_mode) {
            'enabled' => true,
            'disabled' => false,
            default => $global['phone_country_selector_enabled'] === '1',
        };

        return [
            'enabled_languages' => $enabled_languages,
            'default_language' => $default_language,
            'phone_country_selector_enabled' => $phone_enabled,
            'phone_country_selector_mode' => in_array($phone_mode, ['inherit', 'enabled', 'disabled'], true) ? $phone_mode : 'inherit',
        ];
    }

    public function current_language(): string
    {
        if ($this->current_language !== null) {
            return $this->current_language;
        }

        $site = $this->current_site->get_site();
        $effective = $this->effective_settings($site);
        $enabled = $effective['enabled_languages'];
        $cookie_name = $this->language_cookie_name($site);

        $requested = isset($_GET[self::QUERY_ARG]) ? sanitize_key(wp_unslash((string) $_GET[self::QUERY_ARG])) : '';
        if (in_array($requested, $enabled, true)) {
            $this->current_language = $requested;
            return $this->current_language;
        }

        $cookie_value = isset($_COOKIE[$cookie_name]) ? sanitize_key(wp_unslash((string) $_COOKIE[$cookie_name])) : '';
        if (in_array($cookie_value, $enabled, true)) {
            $this->current_language = $cookie_value;
            return $this->current_language;
        }

        $browser = $this->detect_browser_language($enabled);
        if ($browser !== '') {
            $this->current_language = $browser;
            return $this->current_language;
        }

        $default = (string) $effective['default_language'];
        if (in_array($default, $enabled, true)) {
            $this->current_language = $default;
            return $this->current_language;
        }

        $this->current_language = 'en';

        return $this->current_language;
    }

    public function language_metadata(string $code): array
    {
        return $this->available_languages()[$code] ?? [];
    }

    public function enabled_languages(): array
    {
        $site = $this->current_site->get_site();
        $effective = $this->effective_settings($site);
        $languages = [];

        foreach ($effective['enabled_languages'] as $code) {
            $meta = $this->language_metadata($code);

            if ($meta !== []) {
                $languages[] = $meta;
            }
        }

        return $languages;
    }

    public function translate(string $key, array $replacements = [], string $fallback = ''): string
    {
        $language = $this->current_language();
        $resolved = $this->resolve_value($key, $language, $this->current_site->get_site(), $fallback);
        $value = $resolved['value'];

        if ($value === '') {
            return $key;
        }

        if ($replacements !== []) {
            $normalized = [];

            foreach ($replacements as $placeholder => $replacement) {
                if (! is_scalar($replacement)) {
                    continue;
                }

                $normalized['{' . sanitize_key((string) $placeholder) . '}'] = (string) $replacement;
            }

            $value = strtr($value, $normalized);
        }

        return $value;
    }

    public function language_switch_url(string $language): string
    {
        $site = $this->current_site->get_site();
        $effective = $this->effective_settings($site);

        if (! in_array($language, $effective['enabled_languages'], true)) {
            return $this->current_url_without_language();
        }

        return add_query_arg(self::QUERY_ARG, $language, $this->current_url_without_language());
    }

    public function phone_country_selector_enabled(): bool
    {
        $site = $this->current_site->get_site();
        $effective = $this->effective_settings($site);

        return (bool) $effective['phone_country_selector_enabled'];
    }

    public function phone_countries(): array
    {
        if ($this->phone_countries !== null) {
            return $this->phone_countries;
        }

        $countries = require OSM_PLUGIN_PATH . 'config/phone-countries.php';
        $normalized = [];

        foreach ($countries as $country) {
            if (! is_array($country)) {
                continue;
            }

            $iso2 = strtoupper((string) ($country['iso2'] ?? ''));
            $name = trim((string) ($country['name'] ?? ''));
            $dial_code = trim((string) ($country['dial_code'] ?? ''));

            if ($iso2 === '' || $name === '' || $dial_code === '' || ! preg_match('/^\+[0-9]+$/', $dial_code)) {
                continue;
            }

            $normalized[] = [
                'iso2' => $iso2,
                'name' => $name,
                'dial_code' => $dial_code,
                'flag' => $this->iso2_to_flag($iso2),
            ];
        }

        $this->phone_countries = $normalized;

        return $this->phone_countries;
    }

    public function maybe_handle_language_switch(): void
    {
        if (is_admin() || ! isset($_GET[self::QUERY_ARG])) {
            return;
        }

        $site = $this->current_site->get_site();
        $effective = $this->effective_settings($site);
        $language = sanitize_key(wp_unslash((string) $_GET[self::QUERY_ARG]));

        if (! in_array($language, $effective['enabled_languages'], true)) {
            return;
        }

        $this->set_language_cookie($site, $language);
        $target = remove_query_arg(self::QUERY_ARG, $this->current_full_url());
        wp_safe_redirect($target);
        exit;
    }

    public function filter_language_attributes(string $output, string $doctype): string
    {
        if (is_admin()) {
            return $output;
        }

        $language = $this->current_language();
        $locale = match ($language) {
            'fr' => 'fr-CA',
            'pl' => 'pl-PL',
            'ru' => 'ru-RU',
            default => 'en-CA',
        };

        if ($doctype === 'html') {
            return sprintf('lang="%s"', esc_attr($locale));
        }

        return sprintf('xml:lang="%s"', esc_attr($locale));
    }

    public function default_translation(string $key, string $fallback = ''): string
    {
        $value = $this->translation_value('en', $key);

        return $value !== '' ? $value : $fallback;
    }

    public function sanitize_content_text(string $value): string
    {
        return trim(wp_kses_post($value));
    }

    public function sanitize_content_overrides(array $overrides): array
    {
        $available_languages = array_keys($this->available_languages());
        $allowed_keys = $this->editable_content_keys();
        $sanitized = [];

        foreach ($overrides as $language => $values) {
            $language = sanitize_key((string) $language);

            if ($language === '' || ! in_array($language, $available_languages, true) || ! is_array($values)) {
                continue;
            }

            foreach ($values as $key => $value) {
                $key = (string) $key;

                if (! in_array($key, $allowed_keys, true)) {
                    continue;
                }

                $text = $this->sanitize_content_text(is_string($value) ? wp_unslash($value) : '');

                if ($text === '') {
                    continue;
                }

                $sanitized[$language][$key] = $text;
            }
        }

        ksort($sanitized);

        foreach ($sanitized as &$values) {
            ksort($values);
        }

        return $sanitized;
    }

    public function resolve_global_content_value(string $key, string $language, string $fallback = ''): array
    {
        return $this->resolve_value($key, $language, [], $fallback, false);
    }

    public function resolve_site_content_value(array $site, string $key, string $language, string $fallback = ''): array
    {
        return $this->resolve_value($key, $language, $site, $fallback, true);
    }

    private function translation_value(string $language, string $key): string
    {
        if (! isset($this->translations[$language])) {
            $available = $this->available_languages();
            $this->translations[$language] = $available[$language]['strings'] ?? [];
        }

        $value = $this->translations[$language][$key] ?? '';

        return is_string($value) ? $value : '';
    }

    private function resolve_value(string $key, string $language, array $site, string $fallback = '', bool $include_site_override = true): array
    {
        $site_overrides = $include_site_override ? $this->sanitize_content_overrides((array) ($site['content_overrides'] ?? [])) : [];
        $global_overrides = $this->sites->get_global_content_overrides();

        if ($this->is_editable_content_key($key)) {
            $site_value = $site_overrides[$language][$key] ?? '';

            if ($site_value !== '') {
                return ['value' => $site_value, 'source' => 'site_override'];
            }

            $global_value = $global_overrides[$language][$key] ?? '';

            if ($global_value !== '') {
                return ['value' => $global_value, 'source' => 'global_override'];
            }
        }

        $language_value = $this->translation_value($language, $key);

        if ($language_value !== '') {
            return ['value' => $language_value, 'source' => 'translation_file'];
        }

        if ($language !== 'en') {
            $english_value = $this->translation_value('en', $key);

            if ($english_value !== '') {
                return ['value' => $english_value, 'source' => 'english_translation_fallback'];
            }
        }

        if ($fallback !== '') {
            return ['value' => $fallback, 'source' => 'fallback'];
        }

        return ['value' => $key, 'source' => 'key'];
    }

    private function sanitize_localization_settings(array $settings): array
    {
        $available = array_keys($this->available_languages());
        $enabled = $this->sanitize_language_codes((array) ($settings['enabled_languages'] ?? []), $available);

        if ($enabled === []) {
            $enabled = ['en'];
        }

        $default = sanitize_key((string) ($settings['default_language'] ?? 'en'));
        if (! in_array($default, $enabled, true)) {
            $default = in_array('en', $enabled, true) ? 'en' : $enabled[0];
        }

        $phone_enabled = ! empty($settings['phone_country_selector_enabled']) ? '1' : '0';

        return [
            'enabled_languages' => $enabled,
            'default_language' => $default,
            'phone_country_selector_enabled' => $phone_enabled,
        ];
    }

    private function sanitize_language_codes(array $codes, array $allowlist): array
    {
        $sanitized = [];

        foreach ($codes as $code) {
            $normalized = sanitize_key((string) $code);

            if ($normalized !== '' && in_array($normalized, $allowlist, true)) {
                $sanitized[] = $normalized;
            }
        }

        return array_values(array_unique($sanitized));
    }

    private function detect_browser_language(array $enabled_languages): string
    {
        $header = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? wp_unslash((string) $_SERVER['HTTP_ACCEPT_LANGUAGE']) : '';

        if ($header === '') {
            return '';
        }

        $parts = explode(',', $header);
        $candidates = [];

        foreach ($parts as $part) {
            $segment = trim($part);
            if ($segment === '') {
                continue;
            }

            $pieces = explode(';', $segment);
            $language = strtolower(trim($pieces[0]));
            $code = substr($language, 0, 2);

            if ($code !== '' && in_array($code, $enabled_languages, true) && ! in_array($code, $candidates, true)) {
                $candidates[] = $code;
            }
        }

        return $candidates[0] ?? '';
    }

    private function language_cookie_name(array $site): string
    {
        $site_id = absint((int) ($site['id'] ?? 0));
        $cookie = 'ontario_language_' . $site_id;

        if (! empty($site['is_preview'])) {
            $cookie .= '_preview';
        }

        return $cookie;
    }

    private function set_language_cookie(array $site, string $language): void
    {
        $secure = is_ssl();
        $cookie_name = $this->language_cookie_name($site);
        $expires = time() + YEAR_IN_SECONDS;

        if (PHP_VERSION_ID >= 70300) {
            setcookie($cookie_name, $language, [
                'expires' => $expires,
                'path' => '/',
                'secure' => $secure,
                'httponly' => false,
                'samesite' => 'Lax',
            ]);
        } else {
            setcookie($cookie_name, $language, $expires, '/; samesite=Lax', '', $secure, false);
        }

        $_COOKIE[$cookie_name] = $language;
    }

    private function current_url_without_language(): string
    {
        return remove_query_arg(self::QUERY_ARG, $this->current_full_url());
    }

    private function current_full_url(): string
    {
        $scheme = is_ssl() ? 'https' : 'http';
        $host = isset($_SERVER['HTTP_HOST']) ? wp_unslash((string) $_SERVER['HTTP_HOST']) : wp_parse_url(home_url('/'), PHP_URL_HOST);
        $uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash((string) $_SERVER['REQUEST_URI']) : '/';

        return $scheme . '://' . $host . $uri;
    }

    private function iso2_to_flag(string $iso2): string
    {
        $iso2 = strtoupper($iso2);

        if (strlen($iso2) !== 2) {
            return '';
        }

        $offset = 127397;

        return mb_chr(ord($iso2[0]) + $offset, 'UTF-8') . mb_chr(ord($iso2[1]) + $offset, 'UTF-8');
    }
}
