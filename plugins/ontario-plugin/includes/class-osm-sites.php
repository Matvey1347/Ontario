<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class OSM_Sites
{
    private const POST_TYPE = 'ontario_site';
    private const SEED_OPTION = 'osm_seeded_default_site';

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
            'zoho_accounts_url' => 'https://accounts.zoho.eu',
            'zoho_api_domain' => 'https://www.zohoapis.eu',
            'default_lead_status' => 'Contact in Future',
            'notification_emails' => 'support@ontariorefunds.info',
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

        echo '<div class="osm-tabs">';
        echo '<div class="notice notice-error osm-validation-notice" hidden><p><strong>Please fill in these required fields:</strong></p><ul class="osm-validation-list"></ul></div>';
        echo '<p class="description" style="margin:0 0 14px;">Fields marked with <span class="osm-required">*</span> are required.</p>';
        echo '<div class="osm-tab-nav" role="tablist" aria-label="Site settings tabs">';
        echo '<button type="button" class="button osm-tab-button is-active" data-osm-tab="domain" role="tab" aria-selected="true">Domain</button>';
        echo '<button type="button" class="button osm-tab-button" data-osm-tab="brand" role="tab" aria-selected="false">Brand</button>';
        echo '<button type="button" class="button osm-tab-button" data-osm-tab="meta" role="tab" aria-selected="false">Meta</button>';
        echo '<button type="button" class="button osm-tab-button" data-osm-tab="pixel" role="tab" aria-selected="false">Pixel</button>';
        echo '<button type="button" class="button osm-tab-button" data-osm-tab="crm" role="tab" aria-selected="false">CRM</button>';
        echo '<button type="button" class="button osm-tab-button" data-osm-tab="notifications" role="tab" aria-selected="false">Notifications</button>';
        echo '</div>';

        echo '<input type="hidden" name="osm_active_tab" value="domain" />';

        echo '<div class="osm-tab-panel is-active" data-osm-panel="domain" role="tabpanel">';
        echo '<table class="form-table"><tbody>';
        $this->render_checkbox_row('is_active', 'Active', $meta['is_active']);
        $this->render_checkbox_row('is_default', 'Default fallback site', $meta['is_default']);
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
        echo '</tbody></table>';
        echo '</div>';

        echo '<div class="osm-tab-panel" data-osm-panel="meta" role="tabpanel" hidden>';
        echo '<table class="form-table"><tbody>';
        $this->render_text_row('meta_title', 'Meta title', $meta['meta_title'], '', true);
        $this->render_textarea_row('meta_description', 'Meta description', $meta['meta_description']);
        echo '</tbody></table>';
        echo '</div>';

        echo '<div class="osm-tab-panel" data-osm-panel="pixel" role="tabpanel" hidden>';
        echo '<table class="form-table"><tbody>';
        $this->render_text_row('tracking_pixel', 'Meta / Pixel ID', $meta['tracking_pixel']);
        $this->render_textarea_row('tracking_head_code', 'Custom head tracking code', $meta['tracking_head_code']);
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
        $this->render_tabs_assets();
        echo '</div>';
    }

    public function render_preview_metabox(WP_Post $post): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $preview_url = wp_nonce_url(
            add_query_arg('ontario_preview_site', (string) $post->ID, home_url('/')),
            'ontario_preview_site_' . $post->ID
        );

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
            'tracking_pixel' => $meta['tracking_pixel'],
            'tracking_head_code' => $meta['tracking_head_code'],
            'zoho_enabled' => $meta['zoho_enabled'],
            'zoho_accounts_url' => $meta['zoho_accounts_url'],
            'zoho_api_domain' => $meta['zoho_api_domain'],
            'zoho_client_id' => $meta['zoho_client_id'],
            'zoho_client_secret' => $this->crypto->decrypt($meta['zoho_client_secret']),
            'zoho_refresh_token' => $this->crypto->decrypt($meta['zoho_refresh_token']),
            'default_lead_status' => $meta['default_lead_status'],
            'notification_emails' => $meta['notification_emails'],
        ];
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
                if (preview) preview.innerHTML = attachment.url ? `<img src="${attachment.url}" style="max-width:140px;height:auto;" alt="" />` : '';
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
            'logo_id' => ['type' => 'text', 'sanitize' => 'absint'],
            'favicon_id' => ['type' => 'text', 'sanitize' => 'absint'],
            'public_email' => ['type' => 'text', 'sanitize' => 'sanitize_email'],
            'phone_number' => ['type' => 'text', 'sanitize' => 'sanitize_text_field'],
            'address' => ['type' => 'textarea', 'sanitize' => [$this, 'sanitize_textarea']],
            'working_hours' => ['type' => 'text', 'sanitize' => 'sanitize_text_field'],
            'meta_description' => ['type' => 'textarea', 'sanitize' => [$this, 'sanitize_textarea']],
            'tracking_pixel' => ['type' => 'text', 'sanitize' => 'sanitize_text_field'],
            'tracking_head_code' => ['type' => 'textarea', 'sanitize' => [$this, 'sanitize_tracking_code']],
            'zoho_enabled' => ['type' => 'checkbox', 'sanitize' => 'sanitize_text_field'],
            'zoho_accounts_url' => ['type' => 'text', 'sanitize' => 'esc_url_raw'],
            'zoho_api_domain' => ['type' => 'text', 'sanitize' => 'esc_url_raw'],
            'zoho_client_id' => ['type' => 'text', 'sanitize' => 'sanitize_text_field'],
            'zoho_client_secret' => ['type' => 'secret', 'sanitize' => 'sanitize_text_field'],
            'zoho_refresh_token' => ['type' => 'secret', 'sanitize' => 'sanitize_text_field'],
            'default_lead_status' => ['type' => 'text', 'sanitize' => 'sanitize_text_field'],
            'notification_emails' => ['type' => 'textarea', 'sanitize' => [$this, 'sanitize_textarea']],
        ];
    }

    private function get_meta_values(int $post_id): array
    {
        $values = [];

        foreach (array_keys($this->fields()) as $key) {
            $values[$key] = (string) get_post_meta($post_id, '_osm_' . $key, true);
        }

        if ($values['meta_title'] === '') {
            $legacy_title = (string) get_post_meta($post_id, '_osm_site_title', true);
            $values['meta_title'] = $legacy_title !== '' ? $legacy_title : $this->default_meta_title(get_post($post_id));
        }

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

    private function render_text_row(string $key, string $label, string $value, string $placeholder = '', bool $required = false): void
    {
        echo '<tr><th scope="row"><label for="osm-' . esc_attr($key) . '">' . esc_html($label) . $this->render_required_mark($required) . '</label></th><td>';
        echo '<input class="regular-text" type="text" id="osm-' . esc_attr($key) . '" name="osm[' . esc_attr($key) . ']" value="' . esc_attr($value) . '" placeholder="' . esc_attr($placeholder) . '" data-osm-label="' . esc_attr($label) . '" ' . ($required ? 'required data-osm-required="1"' : '') . ' />';
        echo '</td></tr>';
    }

    private function render_textarea_row(string $key, string $label, string $value, string $help = '', bool $required = false): void
    {
        echo '<tr><th scope="row"><label for="osm-' . esc_attr($key) . '">' . esc_html($label) . $this->render_required_mark($required) . '</label></th><td>';
        echo '<textarea class="large-text" rows="4" id="osm-' . esc_attr($key) . '" name="osm[' . esc_attr($key) . ']" data-osm-label="' . esc_attr($label) . '" ' . ($required ? 'required data-osm-required="1"' : '') . '>' . esc_textarea($value) . '</textarea>';
        if ($help !== '') {
            echo '<p class="description">' . esc_html($help) . '</p>';
        }
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

    private function render_media_row(string $key, string $label, int $attachment_id): void
    {
        $preview_id = 'osm-preview-' . $key;
        $input_id = 'osm-' . $key;
        $clear_id = 'osm-clear-' . $key;
        $image = $attachment_id ? wp_get_attachment_image($attachment_id, 'medium', false, ['style' => 'max-width:140px;height:auto;']) : '';
        $clear_style = $attachment_id > 0 ? '' : 'display:none;';

        echo '<tr><th scope="row">' . esc_html($label) . '</th><td>';
        echo '<input type="hidden" id="' . esc_attr($input_id) . '" name="osm[' . esc_attr($key) . ']" value="' . esc_attr((string) $attachment_id) . '" />';
        echo '<div id="' . esc_attr($preview_id) . '" style="margin-bottom:10px;">' . $image . '</div>';
        echo '<button type="button" class="button osm-media-button" data-target="' . esc_attr($input_id) . '" data-preview="' . esc_attr($preview_id) . '" data-title="' . esc_attr($label) . '">Select media</button> ';
        echo '<button type="button" id="' . esc_attr($clear_id) . '" class="button-link-delete osm-media-clear" data-target="' . esc_attr($input_id) . '" data-preview="' . esc_attr($preview_id) . '" style="' . esc_attr($clear_style) . '">Clear</button>';
        echo '</td></tr>';
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

    private function render_tabs_assets(): void
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
        </style>
        <script>
        (() => {
          document.querySelectorAll('.osm-tabs').forEach((tabsRoot) => {
            if (tabsRoot.dataset.osmReady === '1') return;
            tabsRoot.dataset.osmReady = '1';

            const tabButtons = tabsRoot.querySelectorAll('.osm-tab-button');
            const tabPanels = tabsRoot.querySelectorAll('.osm-tab-panel');
            const tabInput = tabsRoot.querySelector('input[name="osm_active_tab"]');
            const validationNotice = tabsRoot.querySelector('.osm-validation-notice');
            const validationList = tabsRoot.querySelector('.osm-validation-list');
            const storageKey = <?php echo wp_json_encode($storage_key); ?>;
            const postForm = document.getElementById('post');
            const titleField = document.getElementById('title');

            tabsRoot.activateTab = (target) => {
              activateTab(target);
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

              try {
                window.localStorage.setItem(storageKey, target);
              } catch (error) {}
            };

            tabButtons.forEach((button) => {
              button.addEventListener('click', () => {
                activateTab(button.dataset.osmTab);
              });
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

            try {
              savedTab = window.localStorage.getItem(storageKey) || '';
            } catch (error) {}

            if (savedTab && tabsRoot.querySelector(`[data-osm-tab="${savedTab}"]`)) {
              activateTab(savedTab);
            } else {
              activateTab('domain');
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
