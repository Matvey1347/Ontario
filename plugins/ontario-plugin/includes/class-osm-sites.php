<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class OSM_Sites
{
    private const POST_TYPE = 'ontario_site';
    private const SEED_OPTION = 'osm_seeded_default_site';
    private const SETTINGS_OPTION = 'osm_global_settings';

    private OSM_Crypto $crypto;
    private OSM_Logger $logger;

    public function __construct(OSM_Crypto $crypto, OSM_Logger $logger)
    {
        $this->crypto = $crypto;
        $this->logger = $logger;

        add_action('init', [$this, 'register_post_type']);
        add_action('add_meta_boxes', [$this, 'register_meta_boxes']);
        add_action('save_post_' . self::POST_TYPE, [$this, 'save_post'], 10, 2);
        add_filter('manage_' . self::POST_TYPE . '_posts_columns', [$this, 'filter_columns']);
        add_action('manage_' . self::POST_TYPE . '_posts_custom_column', [$this, 'render_column'], 10, 2);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('admin_head', [$this, 'render_admin_list_styles']);
        add_action('admin_footer-post.php', [$this, 'render_media_script']);
        add_action('admin_footer-post-new.php', [$this, 'render_media_script']);
        add_action('init', [$this, 'ensure_seed_site'], 30);
        add_action('init', [$this, 'normalize_default_sites'], 35);
    }

    public static function post_type(): string
    {
        return self::POST_TYPE;
    }

    public function register_post_type(): void
    {
        $capabilities = [
            'edit_post' => 'manage_options',
            'read_post' => 'manage_options',
            'delete_post' => 'manage_options',
            'edit_posts' => 'manage_options',
            'edit_others_posts' => 'manage_options',
            'publish_posts' => 'manage_options',
            'read_private_posts' => 'manage_options',
            'delete_posts' => 'manage_options',
            'delete_private_posts' => 'manage_options',
            'delete_published_posts' => 'manage_options',
            'delete_others_posts' => 'manage_options',
            'edit_private_posts' => 'manage_options',
            'edit_published_posts' => 'manage_options',
            'create_posts' => 'manage_options',
        ];

        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name' => 'Sites',
                'singular_name' => 'Site',
                'add_new_item' => 'Add Site',
                'edit_item' => 'Edit Site',
                'menu_name' => 'Sites',
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'supports' => ['title'],
            'menu_icon' => 'dashicons-admin-site-alt3',
            'capabilities' => $capabilities,
            'map_meta_cap' => false,
        ]);
    }

    public function ensure_seed_site(): void
    {
        $existing = get_posts([
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
        ]);

        if (! empty($existing)) {
            update_option(self::SEED_OPTION, 1, true);
            return;
        }

        $host = wp_parse_url(home_url('/'), PHP_URL_HOST);

        $post_id = wp_insert_post([
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'post_title' => 'Ontario Refunds',
        ]);

        if (! $post_id || is_wp_error($post_id)) {
            return;
        }

        $defaults = [
            'is_active' => '1',
            'is_default' => '1',
            'primary_domain' => is_string($host) ? $host : 'ontariorefunds.info',
            'domain_aliases' => "localhost\n127.0.0.1",
            'meta_title' => 'Ontario Refunds',
            'company_name' => 'Ontario Refunds',
            'public_email' => 'support@ontariorefunds.info',
            'phone_number' => '+1 647 478 0877',
            'address' => '10-40 WEST WILMOT ST., RICHMOND HILL ON L4B 1H8',
            'working_hours' => '11AM-7PM ET',
            'meta_description' => 'Ontario Refunds helps victims of online financial fraud trace digital assets, prepare evidence-based reports, and understand practical next steps.',
            'display_mode' => 'full',
            'display_choice_title' => 'Choose how you would like to view this website',
            'display_choice_description' => 'You can continue with the full interactive design or switch to a simpler version with larger text and a calmer layout.',
            'display_choice_simple_label' => 'Use simple design',
            'display_choice_full_label' => 'Continue with full design',
            'zoho_accounts_url' => 'https://accounts.zoho.eu',
            'zoho_api_domain' => 'https://www.zohoapis.eu',
            'default_lead_status' => 'Contact in Future',
            'notification_emails' => 'support@ontariorefunds.info',
            'language_configuration' => 'inherit',
            'default_language' => '',
            'phone_country_selector_mode' => 'inherit',
        ];

        foreach ($defaults as $key => $value) {
            update_post_meta($post_id, '_osm_' . $key, $value);
        }

        update_option(self::SEED_OPTION, 1, true);
    }

    public function normalize_default_sites(): void
    {
        $posts = get_posts([
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'ASC',
            'fields' => 'ids',
        ]);

        if ($posts === []) {
            return;
        }

        $default_ids = [];

        foreach ($posts as $post_id) {
            if (get_post_meta((int) $post_id, '_osm_is_default', true) === '1') {
                $default_ids[] = (int) $post_id;
            }
        }

        if (count($default_ids) === 1) {
            return;
        }

        $keeper_id = $default_ids[0] ?? (int) $posts[0];

        foreach ($posts as $post_id) {
            update_post_meta((int) $post_id, '_osm_is_default', ((int) $post_id === $keeper_id) ? '1' : '0');
        }

        $this->logger->log('Normalized default sites', [
            'keeper_id' => $keeper_id,
            'default_ids_before' => $default_ids,
        ]);
    }

    public function register_meta_boxes(): void
    {
        add_meta_box('osm-site-settings', 'Site Settings', [$this, 'render_settings_metabox'], self::POST_TYPE, 'normal', 'high');
        $post = get_post();

        if ($post instanceof WP_Post && $post->post_type === self::POST_TYPE && $post->post_status !== 'auto-draft') {
            add_meta_box('osm-site-preview', 'Preview', [$this, 'render_preview_metabox'], self::POST_TYPE, 'side', 'high');
        }
    }

    public function render_settings_metabox(WP_Post $post): void
    {
        wp_nonce_field('osm_save_site', 'osm_site_nonce');
        $meta = $this->get_meta_values($post->ID);
        $storage_key = 'osm-active-tab-' . $post->ID;
        $show_default_toggle = $this->should_show_default_toggle($post->ID);

        echo '<div class="osm-tabs" data-osm-storage-key="' . esc_attr($storage_key) . '">';
        echo '<div class="notice notice-error osm-validation-notice" hidden><p><strong>Please fill in these required fields:</strong></p><ul class="osm-validation-list"></ul></div>';
        echo '<p class="description" style="margin:0 0 14px;">Fields marked with <span class="osm-required">*</span> are required.</p>';
        echo '<div class="osm-tab-nav" role="tablist" aria-label="Site settings tabs">';
        echo '<button type="button" class="button osm-tab-button is-active" data-osm-tab="domain" role="tab" aria-selected="true">Domain</button>';
        echo '<button type="button" class="button osm-tab-button" data-osm-tab="brand" role="tab" aria-selected="false">Brand</button>';
        echo '<button type="button" class="button osm-tab-button" data-osm-tab="meta" role="tab" aria-selected="false">Meta</button>';
        echo '<button type="button" class="button osm-tab-button" data-osm-tab="translations" role="tab" aria-selected="false">Translations</button>';
        echo '<button type="button" class="button osm-tab-button" data-osm-tab="content" role="tab" aria-selected="false">Content</button>';
        echo '<button type="button" class="button osm-tab-button" data-osm-tab="pixel" role="tab" aria-selected="false">Tracking</button>';
        echo '<button type="button" class="button osm-tab-button" data-osm-tab="crm" role="tab" aria-selected="false">CRM</button>';
        echo '<button type="button" class="button osm-tab-button" data-osm-tab="notifications" role="tab" aria-selected="false">Notifications</button>';
        echo '</div>';

        echo '<input type="hidden" name="osm_active_tab" value="domain" />';

        echo '<div class="osm-tab-panel is-active" data-osm-panel="domain" role="tabpanel">';
        echo '<table class="form-table"><tbody>';
        $this->render_checkbox_row('is_active', 'Active', $meta['is_active']);
        if ($show_default_toggle) {
            $this->render_checkbox_row('is_default', 'Default fallback site', $meta['is_default']);
        }
        $this->render_text_row('primary_domain', 'Primary domain', $meta['primary_domain'], 'test.com', true);
        $this->render_textarea_row('domain_aliases', 'Domain aliases', $meta['domain_aliases'], 'One domain per line');
        echo '</tbody></table>';
        echo '</div>';

        echo '<div class="osm-tab-panel" data-osm-panel="brand" role="tabpanel" hidden>';
        echo '<table class="form-table"><tbody>';
        $this->render_text_row('company_name', 'Brand / company name', $meta['company_name'], '', true);
        $this->render_media_row('logo_id', 'Logo image', (int) $meta['logo_id']);
        $this->render_media_row('favicon_id', 'Favicon / icon', (int) $meta['favicon_id']);
        $this->render_text_row('public_email', 'Public email', $meta['public_email']);
        $this->render_text_row('phone_number', 'Phone number', $meta['phone_number']);
        $this->render_textarea_row('address', 'Full address', $meta['address']);
        $this->render_text_row('working_hours', 'Working hours', $meta['working_hours']);
        $this->render_select_row('display_mode', 'Website display mode', $meta['display_mode'], [
            'full' => 'Full interactive design',
            'simple' => 'Always use simple design',
            'choice' => 'Let visitor choose',
        ]);
        $this->render_text_row('display_choice_title', 'Display choice modal title', $meta['display_choice_title']);
        $this->render_textarea_row('display_choice_description', 'Display choice modal description', $meta['display_choice_description']);
        $this->render_text_row('display_choice_simple_label', 'Simple design button label', $meta['display_choice_simple_label']);
        $this->render_text_row('display_choice_full_label', 'Full design button label', $meta['display_choice_full_label']);
        echo '</tbody></table>';
        echo '</div>';

        echo '<div class="osm-tab-panel" data-osm-panel="meta" role="tabpanel" hidden>';
        echo '<table class="form-table"><tbody>';
        $this->render_text_row('meta_title', 'Meta title', $meta['meta_title'], '', true);
        $this->render_textarea_row('meta_description', 'Meta description', $meta['meta_description']);
        echo '</tbody></table>';
        echo '</div>';

        echo '<div class="osm-tab-panel" data-osm-panel="translations" role="tabpanel" hidden>';
        $this->render_translations_panel($meta);
        echo '</div>';

        echo '<div class="osm-tab-panel" data-osm-panel="content" role="tabpanel" hidden>';
        $this->render_content_panel($meta);
        echo '</div>';

        echo '<div class="osm-tab-panel" data-osm-panel="pixel" role="tabpanel" hidden>';
        echo '<table class="form-table"><tbody>';
        $this->render_textarea_row('tracking_head_open_code', 'Head open tracking code', $meta['tracking_head_open_code'], 'Rendered immediately after the opening <head> tag. Use this for Google tag installs that require top-of-head placement.');
        $this->render_textarea_row('tracking_header_code', 'Header tracking code', $meta['tracking_header_code'], 'Rendered inside <head>. Paste full pixel / tag manager / analytics code here.');
        $this->render_textarea_row('tracking_body_code', 'Body tracking code', $meta['tracking_body_code'], 'Rendered right after <body> opens. Useful for noscript pixels or body snippets.');
        $this->render_textarea_row('tracking_success_code', 'Success page tracking code', $meta['tracking_success_code'], 'Rendered only on the /success/ page after a successful form submission.');
        echo '</tbody></table>';
        echo '</div>';

        echo '<div class="osm-tab-panel" data-osm-panel="crm" role="tabpanel" hidden>';
        echo '<table class="form-table"><tbody>';
        $this->render_checkbox_row('zoho_enabled', 'Zoho enabled', $meta['zoho_enabled']);
        $this->render_text_row('zoho_accounts_url', 'Zoho accounts URL', $meta['zoho_accounts_url'], 'https://accounts.zoho.eu');
        $this->render_text_row('zoho_api_domain', 'Zoho API domain', $meta['zoho_api_domain'], 'https://www.zohoapis.eu');
        $this->render_text_row('zoho_client_id', 'Zoho client ID', $meta['zoho_client_id']);
        $this->render_secret_row('zoho_client_secret', 'Zoho client secret', $meta['zoho_client_secret']);
        $this->render_secret_row('zoho_refresh_token', 'Zoho refresh token', $meta['zoho_refresh_token']);
        $this->render_text_row(
            'zoho_owner_id',
            'Zoho lead owner ID',
            $meta['zoho_owner_id'],
            '937731000000123456',
            false,
            [
                'inputmode' => 'numeric',
                'pattern' => '[0-9]+',
                'autocomplete' => 'off',
                'help' => 'Optional. Enter the Zoho CRM user ID. New leads created from this site will be assigned to this user as Owner. Leave empty to keep the current Zoho owner assignment behavior.',
                'help_secondary' => 'The value must be the numeric Zoho CRM user ID, not the user\'s name or email.',
            ]
        );
        $this->render_text_row('default_lead_status', 'Default lead status', $meta['default_lead_status'], 'Contact in Future');
        echo '</tbody></table>';
        echo '</div>';

        echo '<div class="osm-tab-panel" data-osm-panel="notifications" role="tabpanel" hidden>';
        echo '<table class="form-table"><tbody>';
        $this->render_textarea_row(
            'notification_emails',
            'Lead notification email(s)',
            $meta['notification_emails'],
            'Emails that should receive user leads. Use commas or one email per line.'
        );
        echo '</tbody></table>';
        echo '</div>';
        $this->render_tabs_assets($storage_key);
        echo '</div>';
    }

    public function render_preview_metabox(WP_Post $post): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $preview_url = add_query_arg([
            'ontario_preview_site' => (string) $post->ID,
            'ontario_preview_token' => OSM_Current_Site::preview_token((int) $post->ID),
        ], home_url('/'));

        echo '<p>Open this site profile on the current WordPress domain without changing DNS.</p>';
        echo '<p><a class="button button-primary" href="' . esc_url($preview_url) . '" target="_blank" rel="noopener">Open Preview</a></p>';
    }

    public function save_post(int $post_id, WP_Post $post): void
    {
        if ($post->post_type !== self::POST_TYPE) {
            return;
        }

        if (! isset($_POST['osm_site_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['osm_site_nonce'])), 'osm_save_site')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (! current_user_can('manage_options')) {
            return;
        }

        $fields = $this->fields();

        foreach ($fields as $key => $config) {
            $meta_key = '_osm_' . $key;
            $raw = $_POST['osm'][$key] ?? null;

            if ($config['type'] === 'checkbox') {
                update_post_meta($post_id, $meta_key, $raw ? '1' : '0');
                continue;
            }

             if ($config['type'] === 'multi_checkbox') {
                $values = $config['sanitize'](is_array($raw) ? $raw : []);
                update_post_meta($post_id, $meta_key, $values);
                continue;
            }

            if ($config['type'] === 'secret') {
                $raw_value = is_string($raw) ? trim(wp_unslash($raw)) : '';

                if ($raw_value === '') {
                    continue;
                }

                update_post_meta($post_id, $meta_key, $this->crypto->encrypt($raw_value));
                continue;
            }

            $value = is_string($raw) ? wp_unslash($raw) : '';
            $sanitized = $config['sanitize']($value);

            if ($key === 'meta_title' && $sanitized === '') {
                $sanitized = $this->default_meta_title($post);
            }

            update_post_meta($post_id, $meta_key, $sanitized);
        }

        if (class_exists('OSM_Plugin')) {
            $translations = OSM_Plugin::instance()->translations();
            $submitted_overrides = isset($_POST['osm_content_overrides']) && is_array($_POST['osm_content_overrides']) ? wp_unslash($_POST['osm_content_overrides']) : [];
            $submitted_state = isset($_POST['osm_content_state']) && is_array($_POST['osm_content_state']) ? wp_unslash($_POST['osm_content_state']) : [];
            $content_overrides = $this->build_content_overrides_from_request($translations, $submitted_overrides, $submitted_state);

            if ($content_overrides === []) {
                delete_post_meta($post_id, '_osm_content_overrides');
            } else {
                update_post_meta($post_id, '_osm_content_overrides', $content_overrides);
            }
        }

        if (get_post_meta($post_id, '_osm_is_default', true) === '1') {
            $others = get_posts([
                'post_type' => self::POST_TYPE,
                'post_status' => 'any',
                'posts_per_page' => -1,
                'post__not_in' => [$post_id],
                'fields' => 'ids',
            ]);

            foreach ($others as $other_id) {
                update_post_meta((int) $other_id, '_osm_is_default', '0');
            }
        }
    }

    public function filter_columns(array $columns): array
    {
        $columns['osm_domain'] = 'Domain';
        $columns['osm_active'] = 'Active';
        $columns['osm_default'] = 'Default';

        return $columns;
    }

    public function render_column(string $column, int $post_id): void
    {
        if ($column === 'osm_domain') {
            echo esc_html((string) get_post_meta($post_id, '_osm_primary_domain', true));
            return;
        }

        if ($column === 'osm_active') {
            echo $this->render_boolean_icon(get_post_meta($post_id, '_osm_is_active', true) === '1');
            return;
        }

        if ($column === 'osm_default') {
            echo $this->render_boolean_icon(get_post_meta($post_id, '_osm_is_default', true) === '1');
        }
    }

    public function get_active_sites(): array
    {
        $posts = get_posts([
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'menu_order title',
            'order' => 'ASC',
        ]);

        $sites = [];

        foreach ($posts as $post) {
            $site = $this->get_site($post->ID);

            if ($site['is_active'] !== '1') {
                continue;
            }

            $sites[] = $site;
        }

        return $sites;
    }

    public function get_site(int $post_id): array
    {
        $post = get_post($post_id);

        if (! $post instanceof WP_Post || $post->post_type !== self::POST_TYPE) {
            return [];
        }

        $meta = $this->get_meta_values($post_id);
        $settings = $this->get_global_settings();

        return [
            'id' => $post_id,
            'post_title' => $post->post_title,
            'is_active' => $meta['is_active'],
            'is_default' => $meta['is_default'],
            'primary_domain' => $this->normalize_host($meta['primary_domain']),
            'domain_aliases' => $this->parse_aliases($meta['domain_aliases']),
            'meta_title' => $meta['meta_title'],
            'company_name' => $meta['company_name'],
            'logo_id' => (int) $meta['logo_id'],
            'favicon_id' => (int) $meta['favicon_id'],
            'public_email' => $meta['public_email'],
            'phone_number' => $meta['phone_number'],
            'phone_href' => $this->normalize_phone_href($meta['phone_number']),
            'address' => $meta['address'],
            'working_hours' => $meta['working_hours'],
            'meta_description' => $meta['meta_description'],
            'display_mode' => $meta['display_mode'] !== '' ? $meta['display_mode'] : 'full',
            'display_choice_title' => $meta['display_choice_title'] !== '' ? $meta['display_choice_title'] : 'Choose how you would like to view this website',
            'display_choice_description' => $meta['display_choice_description'] !== '' ? $meta['display_choice_description'] : 'You can continue with the full interactive design or switch to a simpler version with larger text and a calmer layout.',
            'display_choice_simple_label' => $meta['display_choice_simple_label'] !== '' ? $meta['display_choice_simple_label'] : 'Use simple design',
            'display_choice_full_label' => $meta['display_choice_full_label'] !== '' ? $meta['display_choice_full_label'] : 'Continue with full design',
            'tracking_head_open_code' => $meta['tracking_head_open_code'],
            'tracking_header_code' => $meta['tracking_header_code'],
            'tracking_body_code' => $meta['tracking_body_code'],
            'tracking_success_code' => $meta['tracking_success_code'],
            'zoho_enabled' => $meta['zoho_enabled'],
            'zoho_accounts_url' => $meta['zoho_accounts_url'],
            'zoho_api_domain' => $meta['zoho_api_domain'],
            'zoho_client_id' => $meta['zoho_client_id'],
            'zoho_client_secret' => $this->crypto->decrypt($meta['zoho_client_secret']),
            'zoho_refresh_token' => $this->crypto->decrypt($meta['zoho_refresh_token']),
            'zoho_owner_id' => $meta['zoho_owner_id'],
            'default_lead_status' => $meta['default_lead_status'] !== '' ? $meta['default_lead_status'] : $settings['default_lead_status'],
            'notification_emails' => $meta['notification_emails'] !== '' ? $meta['notification_emails'] : $settings['notification_emails'],
            'language_configuration' => $meta['language_configuration'],
            'enabled_languages_raw' => $meta['enabled_languages'],
            'default_language_raw' => $meta['default_language'],
            'phone_country_selector_mode' => $meta['phone_country_selector_mode'],
            'content_overrides' => $meta['content_overrides'],
        ];
    }

    public function get_global_settings(): array
    {
        $stored = get_option(self::SETTINGS_OPTION, []);
        $stored = is_array($stored) ? $stored : [];

        $notification_emails = isset($stored['notification_emails'])
            ? $this->sanitize_textarea((string) $stored['notification_emails'])
            : '';
        $default_lead_status = isset($stored['default_lead_status'])
            ? sanitize_text_field((string) $stored['default_lead_status'])
            : 'Contact in Future';

        return [
            'notification_emails' => $notification_emails,
            'default_lead_status' => $default_lead_status,
            'content_overrides' => class_exists('OSM_Plugin')
                ? OSM_Plugin::instance()->translations()->sanitize_content_overrides((array) ($stored['content_overrides'] ?? []))
                : [],
        ];
    }

    public function save_global_settings(array $settings): void
    {
        update_option(self::SETTINGS_OPTION, [
            'notification_emails' => $this->sanitize_textarea((string) ($settings['notification_emails'] ?? '')),
            'default_lead_status' => sanitize_text_field((string) ($settings['default_lead_status'] ?? 'Contact in Future')),
            'content_overrides' => class_exists('OSM_Plugin')
                ? OSM_Plugin::instance()->translations()->sanitize_content_overrides((array) ($settings['content_overrides'] ?? []))
                : [],
        ], false);
    }

    public function field_registry(): array
    {
        return $this->fields();
    }

    public function secret_field_keys(): array
    {
        $keys = [];

        foreach ($this->fields() as $key => $config) {
            if (($config['type'] ?? '') === 'secret') {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    public function attachment_field_keys(): array
    {
        $keys = [];

        foreach ($this->fields() as $key => $config) {
            if (($config['reference'] ?? '') === 'attachment') {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    public function sanitize_registry_value(string $key, mixed $value): mixed
    {
        $fields = $this->fields();
        $config = $fields[$key] ?? null;

        if (! is_array($config)) {
            return is_scalar($value) ? sanitize_text_field((string) $value) : $value;
        }

        if (($config['type'] ?? '') === 'checkbox') {
            return ! empty($value) ? '1' : '0';
        }

        if (($config['type'] ?? '') === 'multi_checkbox') {
            return ($config['sanitize'])(is_array($value) ? $value : []);
        }

        if (($config['type'] ?? '') === 'secret') {
            return is_string($value) ? trim($value) : '';
        }

        $string_value = is_scalar($value) ? (string) $value : '';

        return ($config['sanitize'])($string_value);
    }

    public function encrypt_secret_value(string $key, string $plaintext): string
    {
        if (! in_array($key, $this->secret_field_keys(), true)) {
            return $plaintext;
        }

        return $this->crypto->encrypt($plaintext);
    }

    public function get_global_content_overrides(): array
    {
        $settings = $this->get_global_settings();

        return is_array($settings['content_overrides'] ?? null) ? $settings['content_overrides'] : [];
    }

    public function get_default_site(): array
    {
        foreach ($this->get_active_sites() as $site) {
            if (($site['is_default'] ?? '0') === '1') {
                return $site;
            }
        }

        $sites = $this->get_active_sites();

        return $sites[0] ?? [];
    }

    public function find_site_by_host(string $host): array
    {
        $normalized_host = $this->normalize_host($host);

        foreach ($this->get_active_sites() as $site) {
            if (($site['primary_domain'] ?? '') === $normalized_host) {
                return $site;
            }

            if (in_array($normalized_host, $site['domain_aliases'] ?? [], true)) {
                return $site;
            }
        }

        return [];
    }

    public function render_media_script(): void
    {
        $screen = get_current_screen();

        if (! $screen || $screen->post_type !== self::POST_TYPE) {
            return;
        }

        ?>
        <script>
        (() => {
          const buttons = document.querySelectorAll('.osm-media-button');
          if (!buttons.length || typeof wp === 'undefined' || !wp.media) return;

          buttons.forEach((button) => {
            button.addEventListener('click', (event) => {
              event.preventDefault();
              const target = document.getElementById(button.dataset.target);
              const preview = document.getElementById(button.dataset.preview);
              const clearButton = target ? document.querySelector(`.osm-media-clear[data-target="${target.id}"]`) : null;
              const frame = wp.media({
                title: button.dataset.title || 'Select media',
                button: { text: 'Use this media' },
                multiple: false
              });

              frame.on('select', () => {
                const attachment = frame.state().get('selection').first().toJSON();
                if (target) target.value = attachment.id || '';
                if (preview) preview.innerHTML = attachment.url ? `<div class="osm-media-preview-shell"><img src="${attachment.url}" style="max-width:140px;height:auto;" alt="" /></div>` : '';
                if (clearButton) clearButton.style.display = attachment.url ? '' : 'none';
              });

              frame.open();
            });
          });

          document.querySelectorAll('.osm-media-clear').forEach((button) => {
            button.addEventListener('click', (event) => {
              event.preventDefault();
              const target = document.getElementById(button.dataset.target);
              const preview = document.getElementById(button.dataset.preview);
              if (target) target.value = '';
              if (preview) preview.innerHTML = '';
              button.style.display = 'none';
            });
          });

        })();
        </script>
        <style>
          .osm-media-preview-shell {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 160px;
            min-height: 88px;
            padding: 16px;
            border-radius: 16px;
            background: linear-gradient(180deg, #17324d, #0f2438);
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.05);
          }

          .osm-media-preview-shell img {
            display: block;
            max-width: 100%;
            height: auto;
          }

          .osm-check {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 18px;
            height: 18px;
            border: 1px solid #8c8f94;
            border-radius: 4px;
            background: #fff;
            color: #1d2327;
            font-size: 13px;
            font-weight: 700;
            line-height: 1;
          }

          .osm-check.is-checked {
            border-color: #2271b1;
            background: #2271b1;
            color: #fff;
          }
        </style>
        <?php
    }

    public function enqueue_admin_assets(string $hook_suffix): void
    {
        $screen = get_current_screen();

        if (! $screen || $screen->post_type !== self::POST_TYPE) {
            return;
        }

        wp_enqueue_media();
    }

    public function render_admin_list_styles(): void
    {
        $screen = get_current_screen();

        if (! $screen || $screen->post_type !== self::POST_TYPE) {
            return;
        }

        echo '<style>
          .column-osm_active,
          .column-osm_default {
            width:120px;
          }
          .osm-status {
            display:inline-flex;
            align-items:center;
            justify-content:center;
            width:26px;
            height:26px;
            border-radius:999px;
            border:0;
            font-size:16px;
            font-weight:700;
            line-height:1;
            padding:0;
            font-family:Arial, sans-serif;
            vertical-align:middle;
          }
          .osm-status span {
            display:block;
            line-height:1;
            transform:translateY(-1px);
          }
          .osm-status-success {
            background:#dcfce7;
            color:#15803d;
          }
          .osm-status-neutral {
            background:#e5e7eb;
            color:#4b5563;
          }
        </style>';
    }

    private function fields(): array
    {
        return [
            'is_active' => ['type' => 'checkbox', 'sanitize' => 'sanitize_text_field'],
            'is_default' => ['type' => 'checkbox', 'sanitize' => 'sanitize_text_field'],
            'primary_domain' => ['type' => 'text', 'sanitize' => [$this, 'sanitize_domain']],
            'domain_aliases' => ['type' => 'textarea', 'sanitize' => [$this, 'sanitize_textarea']],
            'meta_title' => ['type' => 'text', 'sanitize' => 'sanitize_text_field'],
            'company_name' => ['type' => 'text', 'sanitize' => 'sanitize_text_field'],
            'logo_id' => ['type' => 'text', 'sanitize' => 'absint', 'reference' => 'attachment'],
            'favicon_id' => ['type' => 'text', 'sanitize' => 'absint', 'reference' => 'attachment'],
            'public_email' => ['type' => 'text', 'sanitize' => 'sanitize_email'],
            'phone_number' => ['type' => 'text', 'sanitize' => 'sanitize_text_field'],
            'address' => ['type' => 'textarea', 'sanitize' => [$this, 'sanitize_textarea']],
            'working_hours' => ['type' => 'text', 'sanitize' => 'sanitize_text_field'],
            'meta_description' => ['type' => 'textarea', 'sanitize' => [$this, 'sanitize_textarea']],
            'display_mode' => ['type' => 'text', 'sanitize' => [$this, 'sanitize_display_mode']],
            'display_choice_title' => ['type' => 'text', 'sanitize' => 'sanitize_text_field'],
            'display_choice_description' => ['type' => 'textarea', 'sanitize' => 'sanitize_textarea_field'],
            'display_choice_simple_label' => ['type' => 'text', 'sanitize' => 'sanitize_text_field'],
            'display_choice_full_label' => ['type' => 'text', 'sanitize' => 'sanitize_text_field'],
            'tracking_head_open_code' => ['type' => 'textarea', 'sanitize' => [$this, 'sanitize_tracking_code']],
            'tracking_header_code' => ['type' => 'textarea', 'sanitize' => [$this, 'sanitize_tracking_code']],
            'tracking_body_code' => ['type' => 'textarea', 'sanitize' => [$this, 'sanitize_tracking_code']],
            'tracking_success_code' => ['type' => 'textarea', 'sanitize' => [$this, 'sanitize_tracking_code']],
            'zoho_enabled' => ['type' => 'checkbox', 'sanitize' => 'sanitize_text_field'],
            'zoho_accounts_url' => ['type' => 'text', 'sanitize' => 'esc_url_raw'],
            'zoho_api_domain' => ['type' => 'text', 'sanitize' => 'esc_url_raw'],
            'zoho_client_id' => ['type' => 'text', 'sanitize' => 'sanitize_text_field'],
            'zoho_client_secret' => ['type' => 'secret', 'sanitize' => 'sanitize_text_field'],
            'zoho_refresh_token' => ['type' => 'secret', 'sanitize' => 'sanitize_text_field'],
            'zoho_owner_id' => ['type' => 'text', 'sanitize' => 'sanitize_text_field'],
            'default_lead_status' => ['type' => 'text', 'sanitize' => 'sanitize_text_field'],
            'notification_emails' => ['type' => 'textarea', 'sanitize' => [$this, 'sanitize_textarea']],
            'language_configuration' => ['type' => 'text', 'sanitize' => [$this, 'sanitize_language_configuration']],
            'enabled_languages' => ['type' => 'multi_checkbox', 'sanitize' => [$this, 'sanitize_enabled_languages']],
            'default_language' => ['type' => 'text', 'sanitize' => [$this, 'sanitize_language_code']],
            'phone_country_selector_mode' => ['type' => 'text', 'sanitize' => [$this, 'sanitize_phone_country_selector_mode']],
        ];
    }

    private function get_meta_values(int $post_id): array
    {
        $values = [];

        foreach (array_keys($this->fields()) as $key) {
            $field = $this->fields()[$key];
            $stored = get_post_meta($post_id, '_osm_' . $key, true);
            $values[$key] = $field['type'] === 'multi_checkbox'
                ? (is_array($stored) ? array_values($stored) : [])
                : (string) $stored;
        }

        if ($values['meta_title'] === '') {
            $legacy_title = (string) get_post_meta($post_id, '_osm_site_title', true);
            $values['meta_title'] = $legacy_title !== '' ? $legacy_title : $this->default_meta_title(get_post($post_id));
        }

        if ($values['tracking_header_code'] === '') {
            $values['tracking_header_code'] = (string) get_post_meta($post_id, '_osm_tracking_head_code', true);
        }

        $values['display_mode'] = $this->sanitize_display_mode($values['display_mode']);

        if ($values['display_choice_title'] === '') {
            $values['display_choice_title'] = 'Choose how you would like to view this website';
        }

        if ($values['display_choice_description'] === '') {
            $values['display_choice_description'] = 'You can continue with the full interactive design or switch to a simpler version with larger text and a calmer layout.';
        }

        if ($values['display_choice_simple_label'] === '') {
            $values['display_choice_simple_label'] = 'Use simple design';
        }

        if ($values['display_choice_full_label'] === '') {
            $values['display_choice_full_label'] = 'Continue with full design';
        }

        if (! is_array($values['enabled_languages'])) {
            $values['enabled_languages'] = [];
        }

        if ($values['language_configuration'] === '') {
            $values['language_configuration'] = 'inherit';
        }

        if ($values['phone_country_selector_mode'] === '') {
            $values['phone_country_selector_mode'] = 'inherit';
        }

        $content_overrides = get_post_meta($post_id, '_osm_content_overrides', true);
        $values['content_overrides'] = class_exists('OSM_Plugin')
            ? OSM_Plugin::instance()->translations()->sanitize_content_overrides(is_array($content_overrides) ? $content_overrides : [])
            : [];

        return $values;
    }

    private function default_meta_title(WP_Post|false|null $post): string
    {
        if (! $post instanceof WP_Post) {
            return '';
        }

        $title = trim($post->post_title);

        if ($title === '' || strtolower($title) === 'auto draft') {
            return '';
        }

        return $title;
    }

    private function render_text_row(string $key, string $label, string $value, string $placeholder = '', bool $required = false, array $options = []): void
    {
        $attributes = [];

        foreach (['inputmode', 'pattern', 'autocomplete'] as $attribute) {
            if (! empty($options[$attribute]) && is_string($options[$attribute])) {
                $attributes[] = $attribute . '="' . esc_attr($options[$attribute]) . '"';
            }
        }

        echo '<tr data-osm-field-row="' . esc_attr($key) . '"><th scope="row"><label for="osm-' . esc_attr($key) . '">' . esc_html($label) . $this->render_required_mark($required) . '</label></th><td>';
        echo '<input class="regular-text" type="text" id="osm-' . esc_attr($key) . '" name="osm[' . esc_attr($key) . ']" value="' . esc_attr($value) . '" placeholder="' . esc_attr($placeholder) . '" data-osm-label="' . esc_attr($label) . '" ' . ($required ? 'required data-osm-required="1" ' : '') . implode(' ', $attributes) . ' />';
        if (! empty($options['help']) && is_string($options['help'])) {
            echo '<p class="description">' . esc_html($options['help']) . '</p>';
        }
        if (! empty($options['help_secondary']) && is_string($options['help_secondary'])) {
            echo '<p class="description">' . esc_html($options['help_secondary']) . '</p>';
        }
        echo '</td></tr>';
    }

    private function render_textarea_row(string $key, string $label, string $value, string $help = '', bool $required = false): void
    {
        echo '<tr data-osm-field-row="' . esc_attr($key) . '"><th scope="row"><label for="osm-' . esc_attr($key) . '">' . esc_html($label) . $this->render_required_mark($required) . '</label></th><td>';
        echo '<textarea class="large-text" rows="4" id="osm-' . esc_attr($key) . '" name="osm[' . esc_attr($key) . ']" data-osm-label="' . esc_attr($label) . '" ' . ($required ? 'required data-osm-required="1"' : '') . '>' . esc_textarea($value) . '</textarea>';
        if ($help !== '') {
            echo '<p class="description">' . esc_html($help) . '</p>';
        }
        echo '</td></tr>';
    }

    private function render_select_row(string $key, string $label, string $value, array $options): void
    {
        echo '<tr data-osm-field-row="' . esc_attr($key) . '"><th scope="row"><label for="osm-' . esc_attr($key) . '">' . esc_html($label) . '</label></th><td>';
        echo '<select id="osm-' . esc_attr($key) . '" name="osm[' . esc_attr($key) . ']" data-osm-label="' . esc_attr($label) . '">';

        foreach ($options as $option_value => $option_label) {
            echo '<option value="' . esc_attr($option_value) . '"' . selected($value, $option_value, false) . '>' . esc_html($option_label) . '</option>';
        }

        echo '</select>';
        echo '</td></tr>';
    }

    private function render_checkbox_row(string $key, string $label, string $value): void
    {
        echo '<tr><th scope="row">' . esc_html($label) . '</th><td>';
        echo '<label><input type="checkbox" name="osm[' . esc_attr($key) . ']" value="1" ' . checked($value, '1', false) . ' /> ' . esc_html($label) . '</label>';
        echo '</td></tr>';
    }

    private function render_secret_row(string $key, string $label, string $stored_value): void
    {
        $masked = $this->crypto->mask($stored_value);
        $plain = $this->crypto->decrypt($stored_value);

        echo '<tr><th scope="row"><label for="osm-' . esc_attr($key) . '">' . esc_html($label) . '</label></th><td>';
        echo '<div class="osm-secret-field">';
        echo '<input class="regular-text osm-secret-input" type="password" id="osm-' . esc_attr($key) . '" name="osm[' . esc_attr($key) . ']" value="" placeholder="' . esc_attr($masked) . '" autocomplete="new-password" data-secret-value="' . esc_attr($plain) . '" />';
        echo '<button type="button" class="button osm-secret-toggle" data-target="osm-' . esc_attr($key) . '" aria-label="Show secret" aria-pressed="false">Show</button>';
        echo '</div>';
        echo '<p class="description">Leave empty to keep the current value.</p>';
        echo '</td></tr>';
    }

    private function render_translations_panel(array $meta): void
    {
        $languages = function_exists('ontario_available_languages') ? ontario_available_languages() : [];
        $global_settings = class_exists('OSM_Plugin')
            ? OSM_Plugin::instance()->translations()->global_settings()
            : [
                'enabled_languages' => ['en'],
                'default_language' => 'en',
            ];
        $selected_languages = $meta['enabled_languages'] !== []
            ? $meta['enabled_languages']
            : (array) ($global_settings['enabled_languages'] ?? ['en']);
        $selected_default_language = (string) ($meta['default_language'] !== ''
            ? $meta['default_language']
            : ($global_settings['default_language'] ?? 'en'));

        echo '<div class="osm-translation-section">';
        echo '<h3 style="margin-top:0;">Language settings</h3>';
        echo '<table class="form-table"><tbody>';
        echo '<tr data-osm-translation-custom-row="enabled_languages"><th scope="row">Enabled languages for this site</th><td><div class="osm-language-grid osm-language-grid--stacked">';

        foreach ($languages as $language) {
            $checked = in_array($language['code'], $selected_languages, true);
            echo '<label class="osm-language-card">';
            echo '<input type="checkbox" name="osm[enabled_languages][]" value="' . esc_attr((string) $language['code']) . '" ' . checked($checked, true, false) . ' />';
            echo '<span class="osm-language-card__flag">' . esc_html((string) $language['flag']) . '</span>';
            echo '<span><strong>' . esc_html((string) $language['native_name']) . '</strong></span>';
            echo '</label>';
        }

        echo '</div><p class="description">If no site-specific language values are saved yet, these fields start from the global settings. Any saved site value takes priority for this site.</p></td></tr>';
        echo '<tr data-osm-field-row="default_language"><th scope="row"><label for="osm-default_language">Default language for this site</label></th><td>';
        echo '<select id="osm-default_language" name="osm[default_language]" class="osm-language-select">';
        foreach ($languages as $language) {
            echo '<option value="' . esc_attr((string) $language['code']) . '"' . selected($selected_default_language, (string) $language['code'], false) . '>' . esc_html((string) $language['flag'] . ' ' . $language['native_name']) . '</option>';
        }
        echo '</select>';
        echo '</td></tr>';
        echo '<tr><th scope="row"><label for="osm-phone_country_selector_mode">Phone country selection</label></th><td>';
        echo '<select id="osm-phone_country_selector_mode" name="osm[phone_country_selector_mode]">';
        echo '<option value="inherit"' . selected((string) $meta['phone_country_selector_mode'], 'inherit', false) . '>Inherit global setting</option>';
        echo '<option value="enabled"' . selected((string) $meta['phone_country_selector_mode'], 'enabled', false) . '>Enable for this site</option>';
        echo '<option value="disabled"' . selected((string) $meta['phone_country_selector_mode'], 'disabled', false) . '>Disable for this site</option>';
        echo '</select>';
        echo '<p class="description">Overrides the global phone country selector setting for this site only.</p>';
        echo '</td></tr>';
        echo '</tbody></table>';
        echo '</div>';
    }

    private function build_content_overrides_from_request(OSM_Translations $translations, array $submitted_overrides, array $submitted_state): array
    {
        $sanitized_input = $translations->sanitize_content_overrides(is_array($submitted_overrides) ? $submitted_overrides : []);
        $result = [];

        foreach ($translations->available_languages() as $language) {
            $code = (string) $language['code'];

            foreach ($translations->editable_content_keys() as $key) {
                $value = $sanitized_input[$code][$key] ?? '';
                $state = is_array($submitted_state[$code][$key] ?? null) ? $submitted_state[$code][$key] : [];
                $has_override = ! empty($state['has_override']);
                $effective = $translations->sanitize_content_text((string) ($state['effective'] ?? ''));
                $reset = ! empty($state['reset']);

                if ($reset || $value === '') {
                    continue;
                }

                if (! $has_override && $value === $effective) {
                    continue;
                }

                $result[$code][$key] = $value;
            }
        }

        return $translations->sanitize_content_overrides($result);
    }

    private function content_source_label(string $source): string
    {
        return match ($source) {
            'site_override' => 'Site override',
            'global_override' => 'Global override',
            'english_translation_fallback' => 'English translation fallback',
            'translation_file' => 'Translation file',
            default => 'Inherited value',
        };
    }

    private function render_content_panel(array $meta): void
    {
        if (! class_exists('OSM_Plugin')) {
            echo '<p>Content settings are unavailable.</p>';
            return;
        }

        $translations = OSM_Plugin::instance()->translations();
        $groups = $translations->content_groups();
        $available_languages = $translations->available_languages();
        $global_settings = $translations->global_settings();
        $global_enabled = (array) ($global_settings['enabled_languages'] ?? ['en']);
        $selected_languages = $meta['enabled_languages'] !== [] ? $meta['enabled_languages'] : $global_enabled;
        $effective_languages = $selected_languages !== [] ? $selected_languages : ['en'];
        $site = [
            'enabled_languages_raw' => $meta['enabled_languages'],
            'default_language_raw' => $meta['default_language'],
            'phone_country_selector_mode' => $meta['phone_country_selector_mode'],
            'content_overrides' => $meta['content_overrides'],
        ];
        $site = array_merge($meta, $site);
        $effective_settings = $translations->effective_settings($site);
        $effective_languages = (array) ($effective_settings['enabled_languages'] ?? ['en']);
        $content_languages = [];

        foreach ($effective_languages as $code) {
            if (isset($available_languages[$code])) {
                $content_languages[] = $available_languages[$code];
            }
        }

        $show_language_tabs = count($effective_languages) > 1;

        echo '<div class="osm-content-editor" data-osm-content-editor data-global-enabled="' . esc_attr(wp_json_encode(array_values($global_enabled))) . '">';
        echo '<p class="description" style="margin-top:0;">These fields show the effective text currently visible on this site for each enabled language. Edit a field to create a site override, or reset it to inherit the current global or translation-file value.</p>';

        if ($show_language_tabs) {
            echo '<div class="osm-language-tab-nav" role="tablist" aria-label="Content language tabs">';

            $first = true;
            foreach ($content_languages as $language) {
                $code = (string) $language['code'];
                echo '<button type="button" class="button osm-language-tab' . ($first ? ' is-active' : '') . '" data-osm-content-lang-tab="' . esc_attr($code) . '">' . esc_html((string) $language['flag'] . ' ' . $language['native_name']) . '</button>';
                if ($first) {
                    $first = false;
                }
            }

            echo '</div>';
        }

        $activated = false;

        foreach ($content_languages as $language) {
            $code = (string) $language['code'];
            $active = ! $activated;
            if ($active) {
                $activated = true;
            }

            echo '<div class="osm-language-tab-panel' . (($active || ! $show_language_tabs) ? ' is-active' : '') . '" data-osm-content-lang-panel="' . esc_attr($code) . '"' . ($active || ! $show_language_tabs ? '' : ' hidden') . '>';

            foreach ($groups as $group) {
                echo '<section class="osm-content-group">';
                echo '<h3>' . esc_html((string) $group['label']) . '</h3>';

                foreach ((array) $group['keys'] as $key) {
                    $resolved = $translations->resolve_site_content_value($site, (string) $key, $code);
                    $site_override_exists = ! empty($meta['content_overrides'][$code][$key]);
                    $reset_value = $translations->resolve_global_content_value((string) $key, $code)['value'];
                    $source_label = $this->content_source_label((string) $resolved['source']);
                    $field_id = 'osm-content-' . md5($code . '|' . $key);

                    echo '<div class="osm-content-field">';
                    echo '<div class="osm-content-field__head">';
                    echo '<label for="' . esc_attr($field_id) . '"><strong>' . esc_html((string) $key) . '</strong></label>';
                    echo '<span class="osm-content-source osm-content-source--' . esc_attr((string) $resolved['source']) . '" data-osm-content-source-label>' . esc_html($source_label) . '</span>';
                    echo '</div>';
                    echo '<textarea class="large-text" rows="3" id="' . esc_attr($field_id) . '" name="osm_content_overrides[' . esc_attr($code) . '][' . esc_attr((string) $key) . ']" data-osm-content-input data-reset-value="' . esc_attr($reset_value) . '" data-reset-source="' . esc_attr($this->content_source_label($translations->resolve_global_content_value((string) $key, $code)['source'])) . '">' . esc_textarea((string) $resolved['value']) . '</textarea>';
                    echo '<input type="hidden" name="osm_content_state[' . esc_attr($code) . '][' . esc_attr((string) $key) . '][has_override]" value="' . ($site_override_exists ? '1' : '0') . '" />';
                    echo '<input type="hidden" name="osm_content_state[' . esc_attr($code) . '][' . esc_attr((string) $key) . '][effective]" value="' . esc_attr((string) $resolved['value']) . '" />';
                    echo '<input type="hidden" name="osm_content_state[' . esc_attr($code) . '][' . esc_attr((string) $key) . '][reset]" value="0" data-osm-content-reset-flag />';
                    echo '<div class="osm-content-field__actions">';
                    echo '<button type="button" class="button-link" data-osm-reset-content>Use inherited value</button>';
                    echo '</div>';
                    echo '</div>';
                }

                echo '</section>';
            }

            echo '</div>';
        }

        echo '</div>';
    }

    private function render_media_row(string $key, string $label, int $attachment_id): void
    {
        $preview_id = 'osm-preview-' . $key;
        $input_id = 'osm-' . $key;
        $clear_id = 'osm-clear-' . $key;
        $image = $this->media_preview_markup($key, $attachment_id);
        $clear_style = $attachment_id > 0 ? '' : 'display:none;';
        $description = $attachment_id > 0 ? '' : $this->media_fallback_description($key);

        echo '<tr><th scope="row">' . esc_html($label) . '</th><td>';
        echo '<input type="hidden" id="' . esc_attr($input_id) . '" name="osm[' . esc_attr($key) . ']" value="' . esc_attr((string) $attachment_id) . '" />';
        echo '<div id="' . esc_attr($preview_id) . '" style="margin-bottom:10px;">' . $image . '</div>';
        echo '<button type="button" class="button osm-media-button" data-target="' . esc_attr($input_id) . '" data-preview="' . esc_attr($preview_id) . '" data-title="' . esc_attr($label) . '">Select media</button> ';
        echo '<button type="button" id="' . esc_attr($clear_id) . '" class="button-link-delete osm-media-clear" data-target="' . esc_attr($input_id) . '" data-preview="' . esc_attr($preview_id) . '" style="' . esc_attr($clear_style) . '">Clear</button>';
        if ($description !== '') {
            echo '<p class="description">' . esc_html($description) . '</p>';
        }
        echo '</td></tr>';
    }

    private function media_preview_markup(string $key, int $attachment_id): string
    {
        if ($attachment_id > 0) {
            $image = (string) wp_get_attachment_image($attachment_id, 'medium', false, ['style' => 'max-width:140px;height:auto;']);

            return $image !== '' ? '<div class="osm-media-preview-shell">' . $image . '</div>' : '';
        }

        $fallback_url = $this->default_media_preview_url($key);

        if ($fallback_url === '') {
            return '';
        }

        return '<div class="osm-media-preview-shell"><img src="' . esc_url($fallback_url) . '" style="max-width:140px;height:auto;" alt="" /></div>';
    }

    private function default_media_preview_url(string $key): string
    {
        if ($key === 'favicon_id') {
            return content_url('themes/Ontario/assets/images/fav.png');
        }

        if ($key !== 'logo_id') {
            return '';
        }

        $default_site = $this->get_default_site();
        $default_logo_id = (int) ($default_site['logo_id'] ?? 0);

        if ($default_logo_id > 0) {
            $url = wp_get_attachment_image_url($default_logo_id, 'medium');

            if (is_string($url) && $url !== '') {
                return $url;
            }
        }

        return 'https://ontariorefunds.info/wp-content/uploads/2026/02/Ontario-Refunds-logo-color.png';
    }

    private function media_fallback_description(string $key): string
    {
        $fallback_url = $this->default_media_preview_url($key);

        if ($fallback_url === '') {
            return '';
        }

        return 'If empty, the default image currently shown on the site will be used.';
    }

    public function sanitize_domain(string $value): string
    {
        return $this->normalize_host($value);
    }

    public function sanitize_textarea(string $value): string
    {
        return trim(wp_kses_post($value));
    }

    public function sanitize_tracking_code(string $value): string
    {
        return trim((string) wp_unslash($value));
    }

    public function sanitize_display_mode(string $value): string
    {
        $value = sanitize_key($value);

        if (! in_array($value, ['full', 'simple', 'choice'], true)) {
            return 'full';
        }

        return $value;
    }

    public function sanitize_language_configuration(string $value): string
    {
        $value = sanitize_key($value);

        return $value === 'custom' ? 'custom' : 'inherit';
    }

    public function sanitize_enabled_languages(array $values): array
    {
        $available = function_exists('ontario_available_languages')
            ? array_column(ontario_available_languages(), 'code')
            : ['en'];
        $sanitized = [];

        foreach ($values as $value) {
            $code = sanitize_key((string) $value);

            if ($code !== '' && in_array($code, $available, true)) {
                $sanitized[] = $code;
            }
        }

        return array_values(array_unique($sanitized));
    }

    public function sanitize_language_code(string $value): string
    {
        $available = function_exists('ontario_available_languages')
            ? array_column(ontario_available_languages(), 'code')
            : ['en'];
        $value = sanitize_key($value);

        return in_array($value, $available, true) ? $value : '';
    }

    public function sanitize_phone_country_selector_mode(string $value): string
    {
        $value = sanitize_key($value);

        return in_array($value, ['inherit', 'enabled', 'disabled'], true) ? $value : 'inherit';
    }

    public function normalize_host(string $host): string
    {
        $host = strtolower(trim($host));
        $host = preg_replace('#^https?://#', '', $host) ?: $host;
        $host = preg_replace('#/.*$#', '', $host) ?: $host;
        $host = preg_replace('/:\d+$/', '', $host) ?: $host;
        $host = preg_replace('/^www\./', '', $host) ?: $host;

        return $host;
    }

    private function parse_aliases(string $raw): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        $aliases = [];

        foreach ($lines as $line) {
            $line = $this->normalize_host($line);

            if ($line !== '') {
                $aliases[] = $line;
            }
        }

        return array_values(array_unique($aliases));
    }

    private function render_boolean_icon(bool $value): string
    {
        $class = $value ? 'osm-status osm-status-success' : 'osm-status osm-status-neutral';
        $symbol = $value ? '&#10003;' : '-';
        $label = $value ? 'Yes' : 'No';

        return '<span class="' . esc_attr($class) . '" aria-label="' . esc_attr($label) . '" title="' . esc_attr($label) . '"><span aria-hidden="true">' . $symbol . '</span></span>';
    }

    private function render_required_mark(bool $required): string
    {
        if (! $required) {
            return '';
        }

        return ' <span class="osm-required" aria-hidden="true">*</span>';
    }

    private function should_show_default_toggle(int $post_id): bool
    {
        if (get_post_meta($post_id, '_osm_is_default', true) === '1') {
            return true;
        }

        $default_site_id = $this->current_default_site_id();

        return $default_site_id === 0;
    }

    private function current_default_site_id(): int
    {
        $posts = get_posts([
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'meta_key' => '_osm_is_default',
            'meta_value' => '1',
        ]);

        return isset($posts[0]) ? (int) $posts[0] : 0;
    }

    private function normalize_phone_href(string $phone_number): string
    {
        $digits = preg_replace('/\D+/', '', $phone_number) ?: '';

        if ($digits === '') {
            return '';
        }

        if ($digits[0] !== '1' && strlen($digits) === 10) {
            $digits = '1' . $digits;
        }

        return '+' . $digits;
    }

    private function render_tabs_assets(string $storage_key): void
    {
        ?>
        <style>
          .osm-tabs {
            margin-top: 8px;
          }

          .osm-tab-nav {
            display: flex;
            gap: 8px;
            margin-bottom: 18px;
            padding-bottom: 12px;
            border-bottom: 1px solid #dcdcde;
          }

          .osm-tab-button {
            min-width: 120px;
            background: #fff;
            color: #2271b1;
          }

          .osm-tab-button.is-active,
          .osm-tab-button.is-active:hover,
          .osm-tab-button.is-active:focus {
            background: #2271b1;
            border-color: #2271b1;
            color: #fff !important;
            box-shadow: none;
          }

          .osm-required {
            color: #d63638;
            font-weight: 700;
            display: inline-block;
          }

          .osm-validation-notice {
            margin: 0 0 16px;
          }

          .osm-validation-list {
            margin: 8px 0 0 18px;
            list-style: disc;
          }

          .osm-validation-list button {
            padding: 0;
            border: 0;
            background: transparent;
            color: #2271b1;
            text-decoration: underline;
            cursor: pointer;
          }

          .osm-secret-field {
            display:flex;
            align-items:center;
            gap:8px;
            max-width: 34rem;
          }

          .osm-secret-field .osm-secret-input {
            flex:1 1 auto;
          }

          .osm-secret-field .osm-secret-toggle {
            flex:0 0 auto;
            min-width:72px;
          }

          .osm-field-error {
            border-color: #d63638 !important;
            box-shadow: 0 0 0 1px #d63638 !important;
          }

          .osm-tab-panel[hidden] {
            display: none !important;
          }

          .osm-language-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px;
          }

          .osm-language-grid--stacked {
            grid-template-columns: minmax(260px, 360px);
          }

          .osm-language-card {
            display: grid;
            grid-template-columns: 28px 32px 1fr;
            column-gap: 10px;
            align-items: center;
            padding: 12px 16px;
            border: 1px solid #dcdcde;
            border-radius: 12px;
            background: #f8f9fa;
          }

          .osm-language-card input {
            margin: 0;
            justify-self: center;
          }

          .osm-language-card__flag {
            font-size: 20px;
            line-height: 1;
          }

          .osm-language-select {
            min-width: 240px;
            font-size: 16px;
          }

          .osm-content-editor {
            display: grid;
            gap: 18px;
          }

          .osm-language-tab-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
          }

          .osm-language-tab.is-active,
          .osm-language-tab.is-active:hover,
          .osm-language-tab.is-active:focus {
            background: #2271b1;
            border-color: #2271b1;
            color: #fff !important;
          }

          .osm-language-tab-panel[hidden] {
            display: none !important;
          }

          .osm-language-tab-panel {
            display: none;
          }

          .osm-language-tab-panel.is-active {
            display: grid;
            gap: 18px;
          }

          .osm-content-group {
            background: #fff;
            border: 1px solid #dcdcde;
            border-radius: 16px;
            padding: 18px;
          }

          .osm-content-group h3 {
            margin: 0 0 16px;
          }

          .osm-content-field + .osm-content-field {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #e5e7eb;
          }

          .osm-content-field__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 8px;
          }

          .osm-content-source {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 26px;
            padding: 0 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            background: #eef2ff;
            color: #1d4ed8;
          }

          .osm-content-source--site_override { background:#dcfce7; color:#166534; }
          .osm-content-source--global_override { background:#dbeafe; color:#1d4ed8; }
          .osm-content-source--translation_file { background:#f3f4f6; color:#374151; }
          .osm-content-source--english_translation_fallback { background:#fef3c7; color:#92400e; }

          .osm-content-field__actions {
            margin-top: 6px;
          }
        </style>
        <script>
        (() => {
          document.querySelectorAll('.osm-tabs').forEach((tabsRoot) => {
            if (tabsRoot.dataset.osmReady === '1') return;
            tabsRoot.dataset.osmReady = '1';

            const tabButtons = Array.from(tabsRoot.querySelectorAll('.osm-tab-button'));
            const tabPanels = Array.from(tabsRoot.querySelectorAll('.osm-tab-panel'));
            const tabInput = tabsRoot.querySelector('input[name="osm_active_tab"]');
            const validationNotice = tabsRoot.querySelector('.osm-validation-notice');
            const validationList = tabsRoot.querySelector('.osm-validation-list');
            const storageKey = tabsRoot.dataset.osmStorageKey || '';
            const postForm = document.getElementById('post');
            const titleField = document.getElementById('title');
            const metaTitleField = tabsRoot.querySelector('#osm-meta_title');
            const displayModeField = tabsRoot.querySelector('#osm-display_mode');
            const displayChoiceRows = [
              'display_choice_title',
              'display_choice_description',
              'display_choice_simple_label',
              'display_choice_full_label'
            ].map((key) => tabsRoot.querySelector(`[data-osm-field-row="${key}"]`)).filter(Boolean);
            const contentEditor = tabsRoot.querySelector('[data-osm-content-editor]');
            const languageCheckboxes = Array.from(tabsRoot.querySelectorAll('input[name="osm[enabled_languages][]"]'));

            const syncDisplayModeFields = () => {
              if (!displayModeField || !displayChoiceRows.length) {
                return;
              }

              const showChoiceFields = displayModeField.value === 'choice';

              displayChoiceRows.forEach((row) => {
                row.hidden = !showChoiceFields;
              });
            };

            const syncMetaTitleFromTitle = () => {
              if (!titleField || !metaTitleField) {
                return;
              }

              if (metaTitleField.dataset.osmManual === '1') {
                return;
              }

              metaTitleField.value = String(titleField.value || '').trim();
            };

            const refreshMetaTitleManualState = () => {
              if (!metaTitleField) {
                return;
              }

              metaTitleField.dataset.osmManual = String(metaTitleField.value || '').trim() ? '1' : '0';
            };

            const activateTab = (target) => {
              tabButtons.forEach((item) => {
                const active = item.dataset.osmTab === target;
                item.classList.toggle('is-active', active);
                item.setAttribute('aria-selected', active ? 'true' : 'false');
              });

              tabPanels.forEach((panel) => {
                const active = panel.dataset.osmPanel === target;
                panel.classList.toggle('is-active', active);
                panel.hidden = !active;
              });

              if (tabInput) {
                tabInput.value = target;
              }

              if (!storageKey) {
                return;
              }

              try {
                window.localStorage.setItem(storageKey, target);
              } catch (error) {}
            };

            tabsRoot.activateTab = activateTab;

            const updateContentLanguagePanels = () => {
              if (!contentEditor) {
                return;
              }

              let activeCodes = languageCheckboxes.filter((input) => input.checked).map((input) => input.value);

              if (!activeCodes.length) {
                try {
                  activeCodes = JSON.parse(contentEditor.dataset.globalEnabled || '[]');
                } catch (error) {
                  activeCodes = [];
                }
              }

              if (!activeCodes.length) {
                activeCodes = ['en'];
              }

              const tabs = Array.from(contentEditor.querySelectorAll('[data-osm-content-lang-tab]'));
              const panels = Array.from(contentEditor.querySelectorAll('[data-osm-content-lang-panel]'));
              const currentActiveTab = contentEditor.querySelector('[data-osm-content-lang-tab].is-active');
              let nextActiveCode = currentActiveTab && !currentActiveTab.hidden ? currentActiveTab.dataset.osmContentLangTab : '';

              tabs.forEach((tab) => {
                const visible = activeCodes.includes(tab.dataset.osmContentLangTab);
                tab.hidden = !visible;
                tab.classList.toggle('is-active', false);
              });

              panels.forEach((panel) => {
                const visible = activeCodes.includes(panel.dataset.osmContentLangPanel);
                panel.hidden = !visible;
                panel.classList.toggle('is-active', false);
              });

              if (!nextActiveCode || !activeCodes.includes(nextActiveCode)) {
                nextActiveCode = activeCodes[0] || '';
              }

              if (!nextActiveCode) {
                return;
              }

              const nextTab = contentEditor.querySelector(`[data-osm-content-lang-tab="${nextActiveCode}"]`);
              const nextPanel = contentEditor.querySelector(`[data-osm-content-lang-panel="${nextActiveCode}"]`);

              nextTab?.classList.add('is-active');
              nextPanel?.classList.add('is-active');
            };

            const activateContentLanguage = (code) => {
              if (!contentEditor) {
                return;
              }

              contentEditor.querySelectorAll('[data-osm-content-lang-tab]').forEach((tab) => {
                tab.classList.toggle('is-active', tab.dataset.osmContentLangTab === code);
              });

              contentEditor.querySelectorAll('[data-osm-content-lang-panel]').forEach((panel) => {
                const isActive = panel.dataset.osmContentLangPanel === code;
                panel.classList.toggle('is-active', isActive);
                panel.hidden = !isActive;
              });
            };

            if (!tabButtons.length || !tabPanels.length) {
              return;
            }

            tabButtons.forEach((button) => {
              button.addEventListener('click', () => {
                activateTab(button.dataset.osmTab);
              });
            });

            if (contentEditor) {
              Array.from(contentEditor.querySelectorAll('[data-osm-content-lang-tab]')).forEach((button) => {
                button.addEventListener('click', () => activateContentLanguage(button.dataset.osmContentLangTab));
              });

              languageCheckboxes.forEach((checkbox) => {
                checkbox.addEventListener('change', updateContentLanguagePanels);
              });

              Array.from(contentEditor.querySelectorAll('[data-osm-reset-content]')).forEach((button) => {
                button.addEventListener('click', () => {
                  const field = button.closest('.osm-content-field');

                  if (!field) {
                    return;
                  }

                  const input = field.querySelector('[data-osm-content-input]');
                  const resetFlag = field.querySelector('[data-osm-content-reset-flag]');
                  const sourceLabel = field.querySelector('[data-osm-content-source-label]');

                  if (!input || !resetFlag) {
                    return;
                  }

                  input.value = input.dataset.resetValue || '';
                  resetFlag.value = '1';

                  if (sourceLabel) {
                    sourceLabel.textContent = input.dataset.resetSource || 'Inherited value';
                  }
                });
              });

              Array.from(contentEditor.querySelectorAll('[data-osm-content-input]')).forEach((input) => {
                input.addEventListener('input', () => {
                  const field = input.closest('.osm-content-field');
                  const resetFlag = field ? field.querySelector('[data-osm-content-reset-flag]') : null;

                  if (resetFlag) {
                    resetFlag.value = '0';
                  }
                });
              });
            }

            displayModeField?.addEventListener('change', syncDisplayModeFields);
            titleField?.addEventListener('input', syncMetaTitleFromTitle);
            metaTitleField?.addEventListener('input', () => {
              refreshMetaTitleManualState();
              syncMetaTitleFromTitle();
            });

            tabsRoot.querySelectorAll('.osm-secret-toggle').forEach((button) => {
              button.addEventListener('click', () => {
                const input = tabsRoot.querySelector('#' + button.dataset.target);

                if (!input) {
                  return;
                }

                const revealing = input.type === 'password';

                if (revealing) {
                  if (!input.value && input.dataset.secretValue) {
                    input.value = input.dataset.secretValue;
                  }

                  input.type = 'text';
                  button.textContent = 'Hide';
                  button.setAttribute('aria-label', 'Hide secret');
                  button.setAttribute('aria-pressed', 'true');
                } else {
                  input.type = 'password';
                  button.textContent = 'Show';
                  button.setAttribute('aria-label', 'Show secret');
                  button.setAttribute('aria-pressed', 'false');
                }
              });
            });

            let savedTab = '';

            if (storageKey) {
              try {
                savedTab = window.localStorage.getItem(storageKey) || '';
              } catch (error) {}
            }

            if (savedTab && tabsRoot.querySelector(`[data-osm-tab="${savedTab}"]`)) {
              activateTab(savedTab);
            } else {
              activateTab('domain');
            }

            syncDisplayModeFields();
            refreshMetaTitleManualState();
            syncMetaTitleFromTitle();
            if (contentEditor) {
              updateContentLanguagePanels();
            }

            const requiredFields = Array.from(tabsRoot.querySelectorAll('[data-osm-required="1"]'));

            const clearErrors = () => {
              if (validationNotice) {
                validationNotice.hidden = true;
              }

              if (validationList) {
                validationList.innerHTML = '';
              }

              requiredFields.forEach((field) => {
                field.classList.remove('osm-field-error');
              });

              if (titleField) {
                titleField.classList.remove('osm-field-error');
              }
            };

            const getFieldTab = (field) => {
              const panel = field.closest('.osm-tab-panel');
              return panel ? panel.dataset.osmPanel || 'domain' : 'domain';
            };

            const isEmpty = (field) => {
              return !String(field.value || '').trim();
            };

            const buildErrors = () => {
              const errors = [];

              if (titleField && !String(titleField.value || '').trim()) {
                errors.push({
                  label: 'Title',
                  field: titleField,
                  tab: null
                });
              }

              requiredFields.forEach((field) => {
                if (isEmpty(field)) {
                  errors.push({
                    label: field.dataset.osmLabel || 'Required field',
                    field,
                    tab: getFieldTab(field)
                  });
                }
              });

              return errors;
            };

            const focusError = (error) => {
              if (error.tab) {
                activateTab(error.tab);
              }

              window.setTimeout(() => {
                error.field.focus();
                if (typeof error.field.scrollIntoView === 'function') {
                  error.field.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
              }, 50);
            };

            const renderErrors = (errors) => {
              clearErrors();

              if (!errors.length || !validationNotice || !validationList) {
                return;
              }

              errors.forEach((error) => {
                error.field.classList.add('osm-field-error');
                const item = document.createElement('li');
                const button = document.createElement('button');
                button.type = 'button';
                button.textContent = error.label;
                button.addEventListener('click', () => focusError(error));
                item.appendChild(button);
                validationList.appendChild(item);
              });

              validationNotice.hidden = false;
              validationNotice.scrollIntoView({ behavior: 'smooth', block: 'start' });
            };

            [...requiredFields, titleField].filter(Boolean).forEach((field) => {
              field.addEventListener('input', () => {
                field.classList.remove('osm-field-error');
              });
            });

            if (postForm) {
              postForm.addEventListener('submit', (event) => {
                const errors = buildErrors();

                if (!errors.length) {
                  clearErrors();
                  return;
                }

                event.preventDefault();
                renderErrors(errors);
                focusError(errors[0]);
              });
            }
          });
        })();
        </script>
        <?php
    }
}
