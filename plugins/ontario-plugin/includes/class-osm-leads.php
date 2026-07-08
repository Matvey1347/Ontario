<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class OSM_Leads
{
    private const TABLE_SUFFIX = 'osm_leads';

    private OSM_Logger $logger;

    public function __construct(OSM_Logger $logger)
    {
        $this->logger = $logger;
    }

    public static function activate(): void
    {
        global $wpdb;

        $table_name = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            site_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            site_name VARCHAR(255) NOT NULL DEFAULT '',
            site_domain VARCHAR(190) NOT NULL DEFAULT '',
            form_key VARCHAR(100) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            lead_name VARCHAR(255) NOT NULL DEFAULT '',
            lead_email VARCHAR(190) NOT NULL DEFAULT '',
            lead_phone VARCHAR(100) NOT NULL DEFAULT '',
            payload LONGTEXT NOT NULL,
            user_ip VARCHAR(100) NOT NULL DEFAULT '',
            user_agent TEXT NULL,
            referer TEXT NULL,
            source_url TEXT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'saved',
            email_status VARCHAR(50) NOT NULL DEFAULT 'pending',
            email_message TEXT NULL,
            crm_status VARCHAR(50) NOT NULL DEFAULT 'pending',
            crm_message TEXT NULL,
            crm_reference VARCHAR(190) NOT NULL DEFAULT '',
            PRIMARY KEY (id),
            KEY site_id (site_id),
            KEY form_key (form_key),
            KEY created_at (created_at),
            KEY status (status)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public static function table_name(): string
    {
        global $wpdb;

        return $wpdb->prefix . self::TABLE_SUFFIX;
    }

    public function create(array $site, string $form_key, array $payload): int
    {
        global $wpdb;

        $data = [
            'site_id' => (int) ($site['id'] ?? 0),
            'site_name' => (string) ($site['company_name'] ?? $site['post_title'] ?? ''),
            'site_domain' => (string) ($site['resolved_host'] ?? $site['primary_domain'] ?? ''),
            'form_key' => $form_key,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
            'lead_name' => $this->extract_name($payload),
            'lead_email' => $this->extract_email($payload),
            'lead_phone' => $this->extract_phone($payload),
            'payload' => wp_json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'user_ip' => isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '',
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_textarea_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '',
            'referer' => isset($_SERVER['HTTP_REFERER']) ? esc_url_raw(wp_unslash($_SERVER['HTTP_REFERER'])) : '',
            'source_url' => isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '',
            'status' => 'saved',
            'email_status' => 'pending',
            'crm_status' => 'pending',
        ];

        $result = $wpdb->insert(self::table_name(), $data);

        if ($result === false) {
            $this->logger->log('Lead insert failed', ['db_error' => $wpdb->last_error]);
            return 0;
        }

        return (int) $wpdb->insert_id;
    }

    public function update_delivery(int $lead_id, array $changes): void
    {
        global $wpdb;

        $allowed = ['status', 'email_status', 'email_message', 'crm_status', 'crm_message', 'crm_reference'];
        $data = ['updated_at' => current_time('mysql')];

        foreach ($allowed as $key) {
            if (array_key_exists($key, $changes)) {
                $data[$key] = $changes[$key];
            }
        }

        $wpdb->update(self::table_name(), $data, ['id' => $lead_id]);
    }

    public function recent(int $limit = 100, array $filters = []): array
    {
        global $wpdb;

        $limit = max(1, min(500, $limit));
        $table = self::table_name();
        $where = '1=1';

        if (! empty($filters['site_id'])) {
            $where .= $wpdb->prepare(' AND site_id = %d', (int) $filters['site_id']);
        }

        return $wpdb->get_results("SELECT * FROM {$table} WHERE {$where} ORDER BY id DESC LIMIT {$limit}", ARRAY_A) ?: [];
    }

    private function extract_name(array $payload): string
    {
        return trim((string) ($payload['fullName'] ?? $payload['name'] ?? ''));
    }

    private function extract_email(array $payload): string
    {
        return trim((string) ($payload['email'] ?? ''));
    }

    private function extract_phone(array $payload): string
    {
        return trim((string) ($payload['phone'] ?? ''));
    }
}
