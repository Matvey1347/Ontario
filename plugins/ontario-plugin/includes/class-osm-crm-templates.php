<?php
declare(strict_types=1);

if (! defined('ABSPATH')) { exit; }

final class OSM_CRM_Templates
{
    private OSM_Crypto $crypto;

    public function __construct(OSM_Crypto $crypto)
    {
        $this->crypto = $crypto;
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_init', [$this, 'handle_admin_post']);
    }

    public static function activate(): void
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $table = self::table_name();
        $charset = $wpdb->get_charset_collate();
        dbDelta("CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(190) NOT NULL,
            accounts_url VARCHAR(255) NOT NULL DEFAULT '',
            api_domain VARCHAR(255) NOT NULL DEFAULT '',
            client_id TEXT NOT NULL,
            client_secret LONGTEXT NOT NULL,
            refresh_token LONGTEXT NOT NULL,
            owner_id VARCHAR(100) NOT NULL DEFAULT '',
            default_lead_status VARCHAR(100) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY name (name)
        ) {$charset};");
    }

    public static function table_name(): string
    {
        global $wpdb;
        return $wpdb->prefix . 'osm_crm_templates';
    }

    public function all(): array
    {
        global $wpdb;
        return $wpdb->get_results('SELECT id, name, accounts_url, api_domain, client_id, client_secret, refresh_token, owner_id, default_lead_status FROM ' . self::table_name() . ' ORDER BY name ASC', ARRAY_A) ?: [];
    }

    public function get(int $id): array
    {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . self::table_name() . ' WHERE id = %d', $id), ARRAY_A);
        if (! is_array($row)) { return []; }
        $row['client_secret'] = $this->crypto->decrypt((string) $row['client_secret']);
        $row['refresh_token'] = $this->crypto->decrypt((string) $row['refresh_token']);
        return $row;
    }

    public function register_menu(): void
    {
        add_submenu_page('edit.php?post_type=' . OSM_Sites::post_type(), 'CRM Templates', 'CRM Templates', 'manage_options', 'ontario-crm-templates', [$this, 'render_page']);
    }

    public function handle_admin_post(): void
    {
        if (! is_admin() || ! current_user_can('manage_options') || empty($_POST['osm_crm_template_action'])) { return; }
        check_admin_referer('osm_crm_templates');
        global $wpdb;
        $action = sanitize_key(wp_unslash((string) $_POST['osm_crm_template_action']));
        $id = absint($_POST['template_id'] ?? 0);
        if ($action === 'delete' && $id) {
            $wpdb->delete(self::table_name(), ['id' => $id], ['%d']);
        } elseif ($action === 'save') {
            $previous = $id ? $this->get($id) : [];
            $secret = trim((string) wp_unslash($_POST['client_secret'] ?? ''));
            $refresh = trim((string) wp_unslash($_POST['refresh_token'] ?? ''));
            $data = [
                'name' => sanitize_text_field(wp_unslash((string) ($_POST['name'] ?? ''))),
                'accounts_url' => esc_url_raw(wp_unslash((string) ($_POST['accounts_url'] ?? ''))),
                'api_domain' => esc_url_raw(wp_unslash((string) ($_POST['api_domain'] ?? ''))),
                'client_id' => sanitize_text_field(wp_unslash((string) ($_POST['client_id'] ?? ''))),
                'client_secret' => $this->crypto->encrypt($secret !== '' ? $secret : (string) ($previous['client_secret'] ?? '')),
                'refresh_token' => $this->crypto->encrypt($refresh !== '' ? $refresh : (string) ($previous['refresh_token'] ?? '')),
                'owner_id' => sanitize_text_field(wp_unslash((string) ($_POST['owner_id'] ?? ''))),
                'default_lead_status' => sanitize_text_field(wp_unslash((string) ($_POST['default_lead_status'] ?? ''))),
                'updated_at' => current_time('mysql'),
            ];
            if ($data['name'] !== '') {
                if ($id) { $wpdb->update(self::table_name(), $data, ['id' => $id]); }
                else { $data['created_at'] = current_time('mysql'); $wpdb->insert(self::table_name(), $data); }
            }
        }
        wp_safe_redirect(admin_url('edit.php?post_type=' . OSM_Sites::post_type() . '&page=ontario-crm-templates&saved=1'));
        exit;
    }

    public function render_page(): void
    {
        if (! current_user_can('manage_options')) { return; }
        $editing = isset($_GET['edit']) ? $this->get(absint($_GET['edit'])) : [];
        echo '<div class="wrap"><h1>CRM Templates</h1><p>Reusable Zoho connection settings. Credentials are encrypted at rest.</p>';
        if (isset($_GET['saved'])) { echo '<div class="notice notice-success is-dismissible"><p>Template saved.</p></div>'; }
        echo '<h2>' . ($editing ? 'Edit template' : 'Add template') . '</h2><form method="post">';
        wp_nonce_field('osm_crm_templates');
        echo '<input type="hidden" name="osm_crm_template_action" value="save"><input type="hidden" name="template_id" value="' . esc_attr((string) ($editing['id'] ?? 0)) . '"><table class="form-table"><tbody>';
        $fields = ['name' => 'Template name', 'accounts_url' => 'Zoho accounts URL', 'api_domain' => 'Zoho API domain', 'client_id' => 'Zoho client ID', 'client_secret' => 'Zoho client secret', 'refresh_token' => 'Zoho refresh token', 'owner_id' => 'Zoho lead owner ID', 'default_lead_status' => 'Default lead status'];
        foreach ($fields as $key => $label) {
            $secret = in_array($key, ['client_secret', 'refresh_token'], true);
            $value = $secret ? '' : (string) ($editing[$key] ?? ($key === 'accounts_url' ? 'https://accounts.zoho.eu' : ($key === 'api_domain' ? 'https://www.zohoapis.eu' : '')));
            echo '<tr><th><label for="crm-template-' . esc_attr($key) . '">' . esc_html($label) . '</label></th><td><input class="regular-text" id="crm-template-' . esc_attr($key) . '" name="' . esc_attr($key) . '" type="' . ($secret ? 'password' : 'text') . '" value="' . esc_attr($value) . '" ' . ($secret && $editing ? 'placeholder="Leave empty to keep current value"' : '') . '></td></tr>';
        }
        echo '</tbody></table><p><button class="button button-primary">Save template</button></p></form><hr><h2>Saved templates</h2><table class="widefat striped"><thead><tr><th>Name</th><th>Zoho region</th><th>Client ID</th><th>Actions</th></tr></thead><tbody>';
        foreach ($this->all() as $row) {
            echo '<tr><td>' . esc_html($row['name']) . '</td><td>' . esc_html($row['accounts_url']) . '</td><td>' . esc_html($row['client_id']) . '</td><td><a href="' . esc_url(add_query_arg('edit', (int) $row['id'])) . '">Edit</a> · <form method="post" style="display:inline">';
            wp_nonce_field('osm_crm_templates');
            echo '<input type="hidden" name="osm_crm_template_action" value="delete"><input type="hidden" name="template_id" value="' . esc_attr((string) $row['id']) . '"><button class="button-link-delete" onclick="return confirm(\'Delete this template?\')">Delete</button></form></td></tr>';
        }
        echo '</tbody></table></div>';
    }
}
