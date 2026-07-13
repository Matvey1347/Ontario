<?php
/**
 * Plugin Name: Ontario Plugin
 * Description: Multi-domain site profiles, dynamic branding, lead capture, CRM integration, logs.
 * Version: 2.0.1
 * Author: Codex
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

define('OSM_PLUGIN_VERSION', '2.0.1');
define('OSM_PLUGIN_FILE', __FILE__);
define('OSM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('OSM_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once OSM_PLUGIN_PATH . 'includes/class-osm-logger.php';
require_once OSM_PLUGIN_PATH . 'includes/class-osm-crypto.php';
require_once OSM_PLUGIN_PATH . 'includes/class-osm-sites.php';
require_once OSM_PLUGIN_PATH . 'includes/class-osm-current-site.php';
require_once OSM_PLUGIN_PATH . 'includes/class-osm-translations.php';
require_once OSM_PLUGIN_PATH . 'includes/class-osm-leads.php';
require_once OSM_PLUGIN_PATH . 'includes/class-osm-zoho-crm.php';
require_once OSM_PLUGIN_PATH . 'includes/class-osm-rest-forms.php';
require_once OSM_PLUGIN_PATH . 'includes/class-osm-admin.php';
require_once OSM_PLUGIN_PATH . 'includes/class-osm-plugin.php';

register_activation_hook(OSM_PLUGIN_FILE, ['OSM_Plugin', 'activate']);

add_action('plugins_loaded', static function (): void {
    OSM_Plugin::instance();
});

if (! function_exists('ontario_current_site')) {
    function ontario_current_site(): array
    {
        return OSM_Plugin::instance()->current_site()->get_site();
    }
}

if (! function_exists('ontario_site_field')) {
    function ontario_site_field(string $key, string $default = ''): string
    {
        return OSM_Plugin::instance()->current_site()->get_field($key, $default);
    }
}

if (! function_exists('ontario_site_logo_url')) {
    function ontario_site_logo_url(): string
    {
        return OSM_Plugin::instance()->current_site()->get_logo_url();
    }
}

if (! function_exists('ontario_site_brand_name')) {
    function ontario_site_brand_name(): string
    {
        return OSM_Plugin::instance()->current_site()->get_brand_name();
    }
}

if (! function_exists('ontario_replace_site_tokens')) {
    function ontario_replace_site_tokens(string $content): string
    {
        return OSM_Plugin::instance()->current_site()->replace_tokens($content);
    }
}

if (! function_exists('ontario_render_tracking_code')) {
    function ontario_render_tracking_code(string $location = 'head'): string
    {
        return OSM_Plugin::instance()->current_site()->render_tracking_code($location);
    }
}

if (! function_exists('ontario_render_success_tracking_code')) {
    function ontario_render_success_tracking_code(): string
    {
        return OSM_Plugin::instance()->current_site()->render_tracking_code('success');
    }
}

if (! function_exists('ontario_current_language')) {
    function ontario_current_language(): string
    {
        return OSM_Plugin::instance()->translations()->current_language();
    }
}

if (! function_exists('ontario_available_languages')) {
    function ontario_available_languages(): array
    {
        return array_values(OSM_Plugin::instance()->translations()->available_languages());
    }
}

if (! function_exists('ontario_enabled_languages')) {
    function ontario_enabled_languages(): array
    {
        return OSM_Plugin::instance()->translations()->enabled_languages();
    }
}

if (! function_exists('ontario_language_switch_url')) {
    function ontario_language_switch_url(string $language): string
    {
        return OSM_Plugin::instance()->translations()->language_switch_url($language);
    }
}

if (! function_exists('ontario_phone_country_selector_enabled')) {
    function ontario_phone_country_selector_enabled(): bool
    {
        return OSM_Plugin::instance()->translations()->phone_country_selector_enabled();
    }
}

if (! function_exists('ontario_phone_countries')) {
    function ontario_phone_countries(): array
    {
        return OSM_Plugin::instance()->translations()->phone_countries();
    }
}

if (! function_exists('ontario_t')) {
    function ontario_t(string $key, array $replacements = [], string $fallback = ''): string
    {
        return OSM_Plugin::instance()->translations()->translate($key, $replacements, $fallback);
    }
}
