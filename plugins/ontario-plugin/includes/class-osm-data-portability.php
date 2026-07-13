<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class OSM_Data_Portability
{
    private const JOB_OPTION_PREFIX = 'osm_data_job_';
    private const TMP_NAMESPACE = 'ontario-plugin-data';
    private const MANIFEST_FILE = 'manifest.json';
    private const OPTIONS_FILE = 'options.json';
    private const SITES_FILE = 'sites.json';
    private const LOGS_FILE = 'logs/current.log';
    private const PACKAGE_VERSION = 1;
    private const EXPORTABLE_OPTION_PREFIX = 'osm_';
    private const EXCLUDED_OPTION_PREFIXES = [
        'osm_data_job_',
        'osm_data_lock_',
        'osm_data_token_',
        '_transient_osm_',
        '_transient_timeout_osm_',
        'osm_transient_',
        'osm_cache_',
    ];

    private OSM_Sites $sites;
    private OSM_Translations $translations;
    private OSM_Leads $leads;
    private OSM_Logger $logger;
    private OSM_Crypto $crypto;
    private OSM_Plugin $plugin;

    public function __construct(OSM_Sites $sites, OSM_Translations $translations, OSM_Leads $leads, OSM_Logger $logger, OSM_Crypto $crypto, OSM_Plugin $plugin)
    {
        $this->sites = $sites;
        $this->translations = $translations;
        $this->leads = $leads;
        $this->logger = $logger;
        $this->crypto = $crypto;
        $this->plugin = $plugin;

        add_action('admin_menu', [$this, 'register_menu']);
        add_action('wp_ajax_osm_data_stream_export', [$this, 'handle_stream_export']);
        add_action('wp_ajax_osm_data_stream_import', [$this, 'handle_stream_import']);
        add_action('wp_ajax_osm_data_start_export', [$this, 'handle_start_export']);
        add_action('wp_ajax_osm_data_start_import', [$this, 'handle_start_import']);
        add_action('wp_ajax_osm_data_run_job', [$this, 'handle_run_job']);
        add_action('wp_ajax_osm_data_download_export', [$this, 'handle_download_export']);
    }

    public function register_menu(): void
    {
        add_submenu_page(
            'edit.php?post_type=' . OSM_Sites::post_type(),
            'Data',
            'Data',
            'manage_options',
            'ontario-site-data',
            [$this, 'render_page']
        );
    }

    public function render_page(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $nonce = wp_create_nonce('osm_data_portability');

        echo '<div class="wrap"><h1>Data</h1>';
        echo '<p class="description">Export or import all Ontario Plugin data, including options, site profiles, media references, secrets, tables, leads, translations, content overrides, and logs.</p>';
        echo '<div class="osm-data-grid">';
        echo '<section class="osm-data-card">';
        echo '<h2>Export all data</h2>';
        echo '<p>Create a portable Ontario Plugin backup package for migration or restore.</p>';
        echo '<button type="button" class="button button-primary button-hero" id="osm-data-export-start">Export all data</button>';
        echo '<div class="osm-data-progress" id="osm-data-export-progress" hidden>';
        echo '<div class="osm-data-progress__bar"><span id="osm-data-export-bar"></span></div>';
        echo '<p id="osm-data-export-status">Preparing export…</p>';
        echo '</div>';
        echo '<p id="osm-data-export-download" hidden><a class="button button-secondary" id="osm-data-export-download-link" href="#">Download export package manually</a></p>';
        echo '</section>';

        echo '<section class="osm-data-card">';
        echo '<h2>Import all data</h2>';
        echo '<p>Restore an Ontario Plugin package on this WordPress installation.</p>';
        echo '<input type="file" id="osm-data-import-file" accept=".zip,application/zip" />';
        echo '<p><button type="button" class="button button-primary" id="osm-data-import-start">Import all data</button></p>';
        echo '<div class="osm-data-progress" id="osm-data-import-progress" hidden>';
        echo '<div class="osm-data-progress__bar"><span id="osm-data-import-bar"></span></div>';
        echo '<p id="osm-data-import-status">Preparing import…</p>';
        echo '</div>';
        echo '</section>';
        echo '</div>';
        echo '<div id="osm-toast-stack" class="osm-toast-stack" aria-live="polite" aria-atomic="false"></div>';
        $this->render_styles();
        $this->render_script($nonce);
        echo '</div>';
    }

    public function handle_start_export(): void
    {
        $this->assert_access();
        $this->assert_nonce();

        if (! class_exists('ZipArchive')) {
            wp_send_json_error(['message' => 'ZipArchive is not available on this server.'], 500);
        }

        $job = $this->create_job('export');
        $this->save_job($job);

        wp_send_json_success([
            'jobId' => $job['id'],
            'status' => $this->public_job_status($job),
        ]);
    }

    public function handle_stream_export(): void
    {
        $this->assert_access();
        $this->assert_nonce();

        if (! class_exists('ZipArchive')) {
            $this->stream_error_and_exit('ZipArchive is not available on this server.');
        }

        $job = $this->create_job('export');
        $this->stream_job($job);
    }

    public function handle_start_import(): void
    {
        $this->assert_access();
        $this->assert_nonce();

        if (! class_exists('ZipArchive')) {
            wp_send_json_error(['message' => 'ZipArchive is not available on this server.'], 500);
        }

        if (empty($_FILES['package']) || ! is_array($_FILES['package'])) {
            wp_send_json_error(['message' => 'Choose an export package to import.'], 400);
        }

        $file = $_FILES['package'];

        if (! empty($file['error'])) {
            wp_send_json_error(['message' => 'Upload failed.'], 400);
        }

        $job = $this->create_job('import');
        $import_zip = $job['work_dir'] . '/import.zip';

        if (! @move_uploaded_file((string) $file['tmp_name'], $import_zip)) {
            wp_send_json_error(['message' => 'Unable to store uploaded package.'], 500);
        }

        $job['state']['import_zip'] = $import_zip;
        $this->save_job($job);

        wp_send_json_success([
            'jobId' => $job['id'],
            'status' => $this->public_job_status($job),
        ]);
    }

    public function handle_stream_import(): void
    {
        $this->assert_access();
        $this->assert_nonce();

        if (! class_exists('ZipArchive')) {
            $this->stream_error_and_exit('ZipArchive is not available on this server.');
        }

        if (empty($_FILES['package']) || ! is_array($_FILES['package'])) {
            $this->stream_error_and_exit('Choose an export package to import.');
        }

        $file = $_FILES['package'];

        if (! empty($file['error'])) {
            $this->stream_error_and_exit('Upload failed.');
        }

        $job = $this->create_job('import');
        $import_zip = $job['work_dir'] . '/import.zip';

        if (! @move_uploaded_file((string) $file['tmp_name'], $import_zip)) {
            $this->stream_error_and_exit('Unable to store uploaded package.');
        }

        $job['state']['import_zip'] = $import_zip;
        $this->stream_job($job);
    }

    public function handle_run_job(): void
    {
        $this->assert_access();
        $this->assert_nonce();

        $job_id = $this->sanitize_job_id(isset($_POST['job_id']) ? wp_unslash((string) $_POST['job_id']) : '');
        $job = $this->load_job($job_id);

        if ($job === null) {
            wp_send_json_error(['message' => 'Job not found.'], 404);
        }

        try {
            $updated = ($job['type'] === 'import')
                ? $this->run_import_step($job)
                : $this->run_export_step($job);

            $this->save_job($updated);

            wp_send_json_success([
                'jobId' => $updated['id'],
                'status' => $this->public_job_status($updated),
            ]);
        } catch (Throwable $error) {
            $job['status'] = 'failed';
            $job['message'] = $error->getMessage();
            $this->save_job($job);

            wp_send_json_error([
                'jobId' => $job['id'],
                'status' => $this->public_job_status($job),
                'message' => $error->getMessage(),
            ], 500);
        }
    }

    public function handle_download_export(): void
    {
        $this->assert_access();

        $job_id = $this->sanitize_job_id(isset($_GET['job_id']) ? wp_unslash((string) $_GET['job_id']) : '');
        check_admin_referer('osm_data_download_' . $job_id);
        $job = $this->load_job($job_id);

        if ($job === null || $job['type'] !== 'export' || $job['status'] !== 'completed') {
            wp_die('Export package not available.');
        }

        $zip_path = (string) ($job['state']['zip_path'] ?? '');

        if ($zip_path === '' || ! is_file($zip_path)) {
            wp_die('Export file not found.');
        }

        nocache_headers();
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . basename($zip_path) . '"');
        header('Content-Length: ' . (string) filesize($zip_path));
        readfile($zip_path);
        exit;
    }

    private function stream_job(array $job): void
    {
        $this->start_stream_response();

        try {
            $this->save_job($job);
            $this->stream_payload([
                'success' => true,
                'status' => $this->public_job_status($job),
            ]);

            while (($job['status'] ?? 'running') === 'running') {
                $job = ($job['type'] === 'import')
                    ? $this->run_import_step($job)
                    : $this->run_export_step($job);

                $this->save_job($job);

                $this->stream_payload([
                    'success' => true,
                    'status' => $this->public_job_status($job),
                ]);

                if (($job['status'] ?? '') === 'completed') {
                    break;
                }
            }
        } catch (Throwable $error) {
            $job['status'] = 'failed';
            $job['message'] = $error->getMessage();
            $this->save_job($job);

            $this->stream_payload([
                'success' => false,
                'status' => $this->public_job_status($job),
                'message' => $error->getMessage(),
            ]);
        }

        exit;
    }

    private function run_export_step(array $job): array
    {
        $job['message'] = 'Running export…';

        return match ((string) $job['step']) {
            'prepare' => $this->export_prepare($job),
            'options' => $this->export_options($job),
            'sites' => $this->export_sites($job),
            'attachments' => $this->export_attachments($job),
            'tables' => $this->export_tables($job),
            'logs' => $this->export_logs($job),
            'finalize' => $this->export_finalize($job),
            default => throw new RuntimeException('Unknown export step.'),
        };
    }

    private function run_import_step(array $job): array
    {
        $job['message'] = 'Running import…';

        return match ((string) $job['step']) {
            'extract' => $this->import_extract($job),
            'prepare' => $this->import_prepare($job),
            'options' => $this->import_options($job),
            'attachments' => $this->import_attachments($job),
            'sites' => $this->import_sites($job),
            'tables' => $this->import_tables($job),
            'logs' => $this->import_logs($job),
            'finalize' => $this->import_finalize($job),
            default => throw new RuntimeException('Unknown import step.'),
        };
    }

    private function export_prepare(array $job): array
    {
        $dirs = [
            $job['work_dir'] . '/attachments',
            $job['work_dir'] . '/tables',
            $job['work_dir'] . '/logs',
        ];

        foreach ($dirs as $dir) {
            wp_mkdir_p($dir);
        }

        $job['state']['site_posts'] = $this->site_posts_for_export();
        $job['state']['site_index'] = 0;
        $job['state']['site_rows'] = [];
        $job['state']['attachment_ids'] = [];
        $job['state']['attachment_index'] = 0;
        $job['state']['tables'] = $this->discover_plugin_tables();
        $job['state']['table_index'] = 0;
        $job['state']['table_offset'] = 0;
        $job['state']['table_files'] = [];
        $job['state']['options'] = $this->discover_exportable_options();
        $job['step'] = 'options';
        $job['message'] = 'Prepared export data sources.';

        return $job;
    }

    private function export_options(array $job): array
    {
        $data = [];

        foreach ((array) ($job['state']['options'] ?? []) as $option_name) {
            $data[$option_name] = get_option((string) $option_name, null);
        }

        $this->write_json($job['work_dir'] . '/' . self::OPTIONS_FILE, $data);
        $job['step'] = 'sites';
        $job['message'] = 'Exported options.';

        return $job;
    }

    private function export_sites(array $job): array
    {
        $posts = (array) ($job['state']['site_posts'] ?? []);
        $index = (int) ($job['state']['site_index'] ?? 0);
        $rows = (array) ($job['state']['site_rows'] ?? []);
        $attachment_ids = array_map('intval', (array) ($job['state']['attachment_ids'] ?? []));

        $batch_limit = 10;
        $processed = 0;

        while (isset($posts[$index]) && $processed < $batch_limit) {
            $post = get_post((int) $posts[$index]);

            if (! $post instanceof WP_Post) {
                $index++;
                continue;
            }

            $meta = get_post_meta($post->ID);
            $export_meta = [];

            foreach ($meta as $meta_key => $values) {
                if (! str_starts_with((string) $meta_key, '_osm_')) {
                    continue;
                }

                $value = maybe_unserialize($values[0] ?? '');
                $field_key = substr((string) $meta_key, 5);

                if (in_array($field_key, $this->sites->secret_field_keys(), true)) {
                    $ciphertext = is_string($value) ? $value : '';
                    $plaintext = $this->crypto->decrypt($ciphertext);

                    if ($ciphertext !== '' && $plaintext === '') {
                        throw new RuntimeException('Unable to decrypt secret field ' . $meta_key . ' for site ID ' . $post->ID . '.');
                    }

                    $export_meta[$meta_key] = [
                        'encoding' => 'secret_plaintext',
                        'value' => $plaintext,
                    ];
                    continue;
                }

                if (in_array($field_key, $this->sites->attachment_field_keys(), true)) {
                    $attachment_id = absint($value);

                    if ($attachment_id > 0) {
                        $attachment_ids[] = $attachment_id;
                    }
                }

                $export_meta[$meta_key] = [
                    'encoding' => 'raw',
                    'value' => $value,
                ];
            }

            $rows[] = [
                'old_id' => (int) $post->ID,
                'post' => [
                    'post_title' => $post->post_title,
                    'post_name' => $post->post_name,
                    'post_status' => $post->post_status,
                    'post_date' => $post->post_date,
                    'post_date_gmt' => $post->post_date_gmt,
                    'post_modified' => $post->post_modified,
                    'post_modified_gmt' => $post->post_modified_gmt,
                    'post_content' => $post->post_content,
                    'post_excerpt' => $post->post_excerpt,
                    'menu_order' => (int) $post->menu_order,
                    'post_parent' => (int) $post->post_parent,
                    'comment_status' => $post->comment_status,
                    'ping_status' => $post->ping_status,
                ],
                'meta' => $export_meta,
            ];

            $index++;
            $processed++;
        }

        $job['state']['site_index'] = $index;
        $job['state']['site_rows'] = $rows;
        $job['state']['attachment_ids'] = array_values(array_unique(array_filter($attachment_ids)));

        if ($index >= count($posts)) {
            $this->write_json($job['work_dir'] . '/' . self::SITES_FILE, $rows);
            $job['step'] = 'attachments';
            $job['message'] = 'Exported site profiles.';
        } else {
            $job['message'] = 'Exporting site profiles (' . $index . '/' . count($posts) . ')…';
        }

        return $job;
    }

    private function export_attachments(array $job): array
    {
        $attachment_ids = array_values(array_map('intval', (array) ($job['state']['attachment_ids'] ?? [])));
        $index = (int) ($job['state']['attachment_index'] ?? 0);
        $manifest_rows = (array) ($job['state']['attachment_manifest'] ?? []);
        $batch_limit = 5;
        $processed = 0;

        while (isset($attachment_ids[$index]) && $processed < $batch_limit) {
            $attachment_id = $attachment_ids[$index];
            $attachment = get_post($attachment_id);

            if (! $attachment instanceof WP_Post || $attachment->post_type !== 'attachment') {
                $index++;
                continue;
            }

            $source_path = get_attached_file($attachment_id);

            if (! is_string($source_path) || $source_path === '' || ! is_file($source_path)) {
                $index++;
                continue;
            }

            $dir = $job['work_dir'] . '/attachments/' . $attachment_id;
            wp_mkdir_p($dir);
            $target_name = basename($source_path);
            $target_path = $dir . '/' . $target_name;

            if (! @copy($source_path, $target_path)) {
                throw new RuntimeException('Unable to copy attachment file for attachment ID ' . $attachment_id . '.');
            }

            $meta = [
                'old_id' => $attachment_id,
                'file' => 'attachments/' . $attachment_id . '/' . $target_name,
                'mime_type' => get_post_mime_type($attachment_id),
                'post_title' => $attachment->post_title,
                'post_excerpt' => $attachment->post_excerpt,
                'post_content' => $attachment->post_content,
                'alt' => (string) get_post_meta($attachment_id, '_wp_attachment_image_alt', true),
                'metadata' => wp_get_attachment_metadata($attachment_id),
            ];

            $this->write_json($dir . '/meta.json', $meta);
            $manifest_rows[] = $meta;
            $index++;
            $processed++;
        }

        $job['state']['attachment_index'] = $index;
        $job['state']['attachment_manifest'] = $manifest_rows;

        if ($index >= count($attachment_ids)) {
            $job['step'] = 'tables';
            $job['message'] = 'Exported attachments.';
        } else {
            $job['message'] = 'Exporting attachments (' . $index . '/' . count($attachment_ids) . ')…';
        }

        return $job;
    }

    private function export_tables(array $job): array
    {
        global $wpdb;

        $tables = (array) ($job['state']['tables'] ?? []);
        $table_index = (int) ($job['state']['table_index'] ?? 0);
        $offset = (int) ($job['state']['table_offset'] ?? 0);

        if (! isset($tables[$table_index])) {
            $job['step'] = 'logs';
            $job['message'] = 'Exported database tables.';
            return $job;
        }

        $table = (string) $tables[$table_index];
        $logical_name = $this->logical_table_name($table);
        $file_path = $job['work_dir'] . '/tables/' . $logical_name . '.jsonl';
        $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} ORDER BY 1 ASC LIMIT %d OFFSET %d", 200, $offset), ARRAY_A);

        if ($rows === []) {
            $job['state']['table_files'][$logical_name] = [
                'file' => 'tables/' . $logical_name . '.jsonl',
                'columns' => $this->table_columns($table),
            ];
            $job['state']['table_index'] = $table_index + 1;
            $job['state']['table_offset'] = 0;
            $job['message'] = 'Exported table ' . $logical_name . '.';
            return $job;
        }

        $handle = fopen($file_path, $offset === 0 ? 'wb' : 'ab');

        if (! is_resource($handle)) {
            throw new RuntimeException('Unable to open export file for table ' . $logical_name . '.');
        }

        foreach ($rows as $row) {
            fwrite($handle, wp_json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
        }

        fclose($handle);

        $job['state']['table_offset'] = $offset + count($rows);
        $job['message'] = 'Exporting table ' . $logical_name . ' (' . $job['state']['table_offset'] . ' rows)…';

        return $job;
    }

    private function export_logs(array $job): array
    {
        $source = $this->logger->storage_path();
        $target = $job['work_dir'] . '/' . self::LOGS_FILE;
        wp_mkdir_p(dirname($target));

        if (is_file($source)) {
            @copy($source, $target);
        } else {
            file_put_contents($target, '');
        }

        $job['step'] = 'finalize';
        $job['message'] = 'Exported logs.';

        return $job;
    }

    private function export_finalize(array $job): array
    {
        $manifest = [
            'package_version' => self::PACKAGE_VERSION,
            'plugin_version' => defined('OSM_PLUGIN_VERSION') ? OSM_PLUGIN_VERSION : '',
            'created_at' => gmdate('c'),
            'options_file' => self::OPTIONS_FILE,
            'sites_file' => self::SITES_FILE,
            'logs_file' => self::LOGS_FILE,
            'attachments' => array_values((array) ($job['state']['attachment_manifest'] ?? [])),
            'tables' => $job['state']['table_files'] ?? [],
        ];

        $this->write_json($job['work_dir'] . '/' . self::MANIFEST_FILE, $manifest);

        $zip_path = $job['base_dir'] . '/ontario-plugin-export-' . gmdate('Ymd-His') . '.zip';
        $zip = new ZipArchive();

        if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to create export archive.');
        }

        $this->add_directory_to_zip($zip, $job['work_dir'], '');
        $zip->close();

        $job['state']['zip_path'] = $zip_path;
        $job['status'] = 'completed';
        $job['message'] = 'Export completed.';

        return $job;
    }

    private function import_extract(array $job): array
    {
        $zip_path = (string) ($job['state']['import_zip'] ?? '');

        if ($zip_path === '' || ! is_file($zip_path)) {
            throw new RuntimeException('Import package file not found.');
        }

        $extract_dir = $job['work_dir'] . '/source';
        wp_mkdir_p($extract_dir);

        $zip = new ZipArchive();

        if ($zip->open($zip_path) !== true) {
            throw new RuntimeException('Unable to open import archive.');
        }

        $zip->extractTo($extract_dir);
        $zip->close();

        $job['state']['source_dir'] = $extract_dir;
        $job['step'] = 'prepare';
        $job['message'] = 'Package extracted.';

        return $job;
    }

    private function import_prepare(array $job): array
    {
        $source_dir = (string) ($job['state']['source_dir'] ?? '');
        $manifest = $this->read_json_file($source_dir . '/' . self::MANIFEST_FILE);

        if (! is_array($manifest)) {
            throw new RuntimeException('Import manifest is missing or invalid.');
        }

        foreach ($this->plugin->table_schema_callbacks() as $callback) {
            call_user_func($callback);
        }

        $job['state']['manifest'] = $manifest;
        $job['state']['site_mapping'] = [];
        $job['state']['attachment_mapping'] = [];
        $job['state']['attachment_index'] = 0;
        $job['state']['site_index'] = 0;
        $job['state']['table_index'] = 0;
        $job['state']['table_line'] = 0;
        $job['step'] = 'options';
        $job['message'] = 'Prepared import targets.';

        return $job;
    }

    private function import_options(array $job): array
    {
        $manifest = (array) ($job['state']['manifest'] ?? []);
        $source_dir = (string) ($job['state']['source_dir'] ?? '');
        $options = $this->read_json_file($source_dir . '/' . (string) ($manifest['options_file'] ?? self::OPTIONS_FILE));

        if (! is_array($options)) {
            throw new RuntimeException('Options payload is invalid.');
        }

        foreach ($this->discover_exportable_options() as $existing_option) {
            if (! array_key_exists($existing_option, $options)) {
                delete_option($existing_option);
            }
        }

        foreach ($options as $option_name => $value) {
            if (! is_string($option_name) || ! $this->is_exportable_option($option_name)) {
                continue;
            }

            update_option($option_name, $value, false);
        }

        $job['step'] = 'attachments';
        $job['message'] = 'Imported options.';

        return $job;
    }

    private function import_attachments(array $job): array
    {
        $manifest = (array) ($job['state']['manifest'] ?? []);
        $source_dir = (string) ($job['state']['source_dir'] ?? '');
        $attachments = array_values((array) ($manifest['attachments'] ?? []));
        $index = (int) ($job['state']['attachment_index'] ?? 0);
        $mapping = (array) ($job['state']['attachment_mapping'] ?? []);
        $batch_limit = 3;
        $processed = 0;

        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        while (isset($attachments[$index]) && $processed < $batch_limit) {
            $attachment = $attachments[$index];

            if (! is_array($attachment)) {
                $index++;
                continue;
            }

            $old_id = (int) ($attachment['old_id'] ?? 0);
            $file = $source_dir . '/' . (string) ($attachment['file'] ?? '');

            if ($old_id < 1 || ! is_file($file)) {
                $index++;
                continue;
            }

            $upload_bits = wp_upload_bits(basename($file), null, (string) file_get_contents($file));

            if (! empty($upload_bits['error'])) {
                throw new RuntimeException('Unable to restore attachment file for source attachment ID ' . $old_id . '.');
            }

            $attachment_post = [
                'post_mime_type' => (string) ($attachment['mime_type'] ?? 'application/octet-stream'),
                'post_title' => (string) ($attachment['post_title'] ?? ''),
                'post_content' => (string) ($attachment['post_content'] ?? ''),
                'post_excerpt' => (string) ($attachment['post_excerpt'] ?? ''),
                'post_status' => 'inherit',
            ];

            $new_id = wp_insert_attachment($attachment_post, $upload_bits['file']);

            if (is_wp_error($new_id) || $new_id < 1) {
                throw new RuntimeException('Unable to create attachment post for source attachment ID ' . $old_id . '.');
            }

            $metadata = wp_generate_attachment_metadata($new_id, $upload_bits['file']);
            if (is_array($metadata)) {
                wp_update_attachment_metadata($new_id, $metadata);
            }

            update_post_meta($new_id, '_wp_attachment_image_alt', (string) ($attachment['alt'] ?? ''));
            $mapping[$old_id] = (int) $new_id;
            $index++;
            $processed++;
        }

        $job['state']['attachment_index'] = $index;
        $job['state']['attachment_mapping'] = $mapping;

        if ($index >= count($attachments)) {
            $job['step'] = 'sites';
            $job['message'] = 'Imported attachments.';
        } else {
            $job['message'] = 'Importing attachments (' . $index . '/' . count($attachments) . ')…';
        }

        return $job;
    }

    private function import_sites(array $job): array
    {
        $manifest = (array) ($job['state']['manifest'] ?? []);
        $source_dir = (string) ($job['state']['source_dir'] ?? '');
        $sites = $this->read_json_file($source_dir . '/' . (string) ($manifest['sites_file'] ?? self::SITES_FILE));

        if (! is_array($sites)) {
            throw new RuntimeException('Sites payload is invalid.');
        }

        $index = (int) ($job['state']['site_index'] ?? 0);
        $mapping = (array) ($job['state']['site_mapping'] ?? []);
        $attachment_mapping = (array) ($job['state']['attachment_mapping'] ?? []);
        $batch_limit = 10;
        $processed = 0;

        if ($index === 0) {
            $existing_sites = get_posts([
                'post_type' => OSM_Sites::post_type(),
                'post_status' => 'any',
                'posts_per_page' => -1,
                'fields' => 'ids',
            ]);

            foreach ($existing_sites as $existing_id) {
                wp_delete_post((int) $existing_id, true);
            }
        }

        while (isset($sites[$index]) && $processed < $batch_limit) {
            $site = $sites[$index];

            if (! is_array($site)) {
                $index++;
                continue;
            }

            $post = (array) ($site['post'] ?? []);
            $postarr = [
                'post_type' => OSM_Sites::post_type(),
                'post_title' => (string) ($post['post_title'] ?? ''),
                'post_name' => (string) ($post['post_name'] ?? ''),
                'post_status' => (string) ($post['post_status'] ?? 'publish'),
                'post_date' => (string) ($post['post_date'] ?? current_time('mysql')),
                'post_date_gmt' => (string) ($post['post_date_gmt'] ?? current_time('mysql', true)),
                'post_modified' => (string) ($post['post_modified'] ?? current_time('mysql')),
                'post_modified_gmt' => (string) ($post['post_modified_gmt'] ?? current_time('mysql', true)),
                'post_content' => (string) ($post['post_content'] ?? ''),
                'post_excerpt' => (string) ($post['post_excerpt'] ?? ''),
                'menu_order' => (int) ($post['menu_order'] ?? 0),
                'post_parent' => 0,
                'comment_status' => (string) ($post['comment_status'] ?? 'closed'),
                'ping_status' => (string) ($post['ping_status'] ?? 'closed'),
            ];

            $new_id = wp_insert_post(wp_slash($postarr), true, false);

            if (is_wp_error($new_id) || $new_id < 1) {
                throw new RuntimeException('Unable to restore site profile ' . (string) ($post['post_title'] ?? ''));
            }

            foreach ((array) ($site['meta'] ?? []) as $meta_key => $meta_payload) {
                if (! is_string($meta_key) || ! str_starts_with($meta_key, '_osm_') || ! is_array($meta_payload)) {
                    continue;
                }

                $field_key = substr($meta_key, 5);
                $encoding = (string) ($meta_payload['encoding'] ?? 'raw');
                $value = $meta_payload['value'] ?? null;

                if ($encoding === 'secret_plaintext') {
                    $plaintext = is_string($value) ? $value : '';
                    update_post_meta($new_id, $meta_key, $this->sites->encrypt_secret_value($field_key, $plaintext));
                    continue;
                }

                if (in_array($field_key, $this->sites->attachment_field_keys(), true)) {
                    $old_attachment_id = absint($value);
                    $value = $attachment_mapping[$old_attachment_id] ?? 0;
                }

                if (isset($this->sites->field_registry()[$field_key])) {
                    $value = $this->sites->sanitize_registry_value($field_key, $value);
                }

                update_post_meta($new_id, $meta_key, $value);
            }

            $old_id = (int) ($site['old_id'] ?? 0);
            if ($old_id > 0) {
                $mapping[$old_id] = (int) $new_id;
            }

            $index++;
            $processed++;
        }

        $job['state']['site_index'] = $index;
        $job['state']['site_mapping'] = $mapping;

        if ($index >= count($sites)) {
            $job['step'] = 'tables';
            $job['message'] = 'Imported site profiles.';
        } else {
            $job['message'] = 'Importing site profiles (' . $index . '/' . count($sites) . ')…';
        }

        return $job;
    }

    private function import_tables(array $job): array
    {
        global $wpdb;

        $manifest = (array) ($job['state']['manifest'] ?? []);
        $source_dir = (string) ($job['state']['source_dir'] ?? '');
        $tables = array_keys((array) ($manifest['tables'] ?? []));
        $table_index = (int) ($job['state']['table_index'] ?? 0);
        $site_mapping = (array) ($job['state']['site_mapping'] ?? []);

        if (! isset($tables[$table_index])) {
            $job['step'] = 'logs';
            $job['message'] = 'Imported tables.';
            return $job;
        }

        $logical_name = (string) $tables[$table_index];
        $table_name = $wpdb->prefix . $logical_name;
        $allowed = array_keys($this->plugin->table_schema_callbacks());

        if (! in_array($logical_name, $allowed, true)) {
            $job['state']['table_index'] = $table_index + 1;
            $job['message'] = 'Skipped unsupported table ' . $logical_name . '.';
            return $job;
        }

        $source_file = $source_dir . '/' . (string) ($manifest['tables'][$logical_name]['file'] ?? '');

        if (! is_file($source_file)) {
            $job['state']['table_index'] = $table_index + 1;
            return $job;
        }

        $line = (int) ($job['state']['table_line'] ?? 0);
        $lines = file($source_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if (! is_array($lines)) {
            throw new RuntimeException('Unable to read table export for ' . $logical_name . '.');
        }

        if ($line === 0) {
            $wpdb->query("TRUNCATE TABLE {$table_name}");
        }

        $columns = $this->table_columns($table_name);
        $batch_limit = 200;
        $processed = 0;

        while (isset($lines[$line]) && $processed < $batch_limit) {
            $row = json_decode((string) $lines[$line], true);

            if (! is_array($row)) {
                $line++;
                continue;
            }

            if (isset($row['site_id'])) {
                $row['site_id'] = isset($site_mapping[(int) $row['site_id']]) ? (int) $site_mapping[(int) $row['site_id']] : 0;
            }

            $insert = [];
            foreach ($row as $column => $value) {
                if (in_array((string) $column, $columns, true)) {
                    $insert[(string) $column] = $value;
                }
            }

            if ($insert !== []) {
                $wpdb->insert($table_name, $insert);
            }

            $line++;
            $processed++;
        }

        $job['state']['table_line'] = $line;

        if ($line >= count($lines)) {
            $job['state']['table_index'] = $table_index + 1;
            $job['state']['table_line'] = 0;
            $job['message'] = 'Imported table ' . $logical_name . '.';
        } else {
            $job['message'] = 'Importing table ' . $logical_name . ' (' . $line . '/' . count($lines) . ')…';
        }

        return $job;
    }

    private function import_logs(array $job): array
    {
        $manifest = (array) ($job['state']['manifest'] ?? []);
        $source_dir = (string) ($job['state']['source_dir'] ?? '');
        $source_file = $source_dir . '/' . (string) ($manifest['logs_file'] ?? self::LOGS_FILE);
        $target = $this->logger->storage_path();

        if (is_file($source_file)) {
            file_put_contents($target, (string) file_get_contents($source_file));
        } else {
            file_put_contents($target, '');
        }

        $job['step'] = 'finalize';
        $job['message'] = 'Imported logs.';

        return $job;
    }

    private function import_finalize(array $job): array
    {
        $job['status'] = 'completed';
        $job['message'] = 'Import completed.';

        return $job;
    }

    private function discover_exportable_options(): array
    {
        global $wpdb;

        $like = $wpdb->esc_like(self::EXPORTABLE_OPTION_PREFIX) . '%';
        $rows = $wpdb->get_col($wpdb->prepare("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", $like));
        $options = [];

        foreach ((array) $rows as $option_name) {
            if (is_string($option_name) && $this->is_exportable_option($option_name)) {
                $options[] = $option_name;
            }
        }

        sort($options);

        return $options;
    }

    private function is_exportable_option(string $option_name): bool
    {
        if (! str_starts_with($option_name, self::EXPORTABLE_OPTION_PREFIX)) {
            return false;
        }

        foreach (self::EXCLUDED_OPTION_PREFIXES as $prefix) {
            if (str_starts_with($option_name, $prefix)) {
                return false;
            }
        }

        return true;
    }

    private function site_posts_for_export(): array
    {
        return get_posts([
            'post_type' => OSM_Sites::post_type(),
            'post_status' => ['publish', 'draft', 'private', 'pending', 'trash'],
            'posts_per_page' => -1,
            'orderby' => 'ID',
            'order' => 'ASC',
            'fields' => 'ids',
        ]);
    }

    private function discover_plugin_tables(): array
    {
        global $wpdb;

        $like = $wpdb->esc_like($wpdb->prefix . 'osm_') . '%';
        $rows = $wpdb->get_col($wpdb->prepare('SHOW TABLES LIKE %s', $like));
        $tables = [];

        foreach ((array) $rows as $table) {
            if (is_string($table) && str_starts_with($table, $wpdb->prefix . 'osm_')) {
                $tables[] = $table;
            }
        }

        sort($tables);

        return $tables;
    }

    private function logical_table_name(string $table_name): string
    {
        global $wpdb;

        return str_starts_with($table_name, $wpdb->prefix) ? substr($table_name, strlen($wpdb->prefix)) : $table_name;
    }

    private function table_columns(string $table_name): array
    {
        global $wpdb;

        $columns = $wpdb->get_col("DESCRIBE {$table_name}", 0);

        return array_values(array_filter(array_map('strval', (array) $columns)));
    }

    private function create_job(string $type): array
    {
        $id = 'job_' . strtolower(wp_generate_password(12, false, false));
        $base_dir = trailingslashit($this->data_root_dir()) . $id;
        $work_dir = $base_dir . '/work';
        wp_mkdir_p($work_dir);

        return [
            'id' => $id,
            'type' => $type,
            'status' => 'running',
            'step' => $type === 'import' ? 'extract' : 'prepare',
            'message' => $type === 'import' ? 'Preparing import…' : 'Preparing export…',
            'created_at' => gmdate('c'),
            'base_dir' => $base_dir,
            'work_dir' => $work_dir,
            'state' => [],
        ];
    }

    private function data_root_dir(): string
    {
        $upload = wp_upload_dir();
        $dir = trailingslashit((string) $upload['basedir']) . self::TMP_NAMESPACE;
        wp_mkdir_p($dir);
        $this->protect_directory($dir);

        return $dir;
    }

    private function protect_directory(string $dir): void
    {
        if (! file_exists($dir . '/index.php')) {
            file_put_contents($dir . '/index.php', "<?php\n");
        }

        if (! file_exists($dir . '/.htaccess')) {
            file_put_contents($dir . '/.htaccess', "Deny from all\n");
        }
    }

    private function save_job(array $job): void
    {
        update_option(self::JOB_OPTION_PREFIX . $job['id'], $job, false);
    }

    private function load_job(string $job_id): ?array
    {
        if ($job_id === '') {
            return null;
        }

        $job = get_option(self::JOB_OPTION_PREFIX . $job_id, null);

        return is_array($job) ? $job : null;
    }

    private function sanitize_job_id(string $job_id): string
    {
        $job_id = trim($job_id);

        if ($job_id === '') {
            return '';
        }

        return preg_match('/^job_[A-Za-z0-9]+$/', $job_id) === 1 ? $job_id : '';
    }

    private function public_job_status(array $job): array
    {
        $download_url = '';

        if (($job['type'] ?? '') === 'export' && ($job['status'] ?? '') === 'completed') {
            $download_url = add_query_arg([
                'action' => 'osm_data_download_export',
                'job_id' => (string) $job['id'],
                '_wpnonce' => wp_create_nonce('osm_data_download_' . $job['id']),
            ], admin_url('admin-ajax.php'));
        }

        return [
            'type' => (string) ($job['type'] ?? ''),
            'status' => (string) ($job['status'] ?? 'running'),
            'step' => (string) ($job['step'] ?? ''),
            'message' => (string) ($job['message'] ?? ''),
            'downloadUrl' => $download_url,
        ];
    }

    private function add_directory_to_zip(ZipArchive $zip, string $directory, string $prefix): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            $path = $file->getPathname();
            $relative = ltrim($prefix . str_replace($directory, '', $path), '/');

            if ($file->isDir()) {
                $zip->addEmptyDir($relative);
                continue;
            }

            $zip->addFile($path, $relative);
        }
    }

    private function write_json(string $path, mixed $data): void
    {
        $json = wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (! is_string($json)) {
            throw new RuntimeException('Unable to encode JSON export data.');
        }

        file_put_contents($path, $json);
    }

    private function read_json_file(string $path): mixed
    {
        if (! is_file($path)) {
            return null;
        }

        $contents = file_get_contents($path);

        if (! is_string($contents) || $contents === '') {
            return null;
        }

        return json_decode($contents, true);
    }

    private function start_stream_response(): void
    {
        if (function_exists('ignore_user_abort')) {
            ignore_user_abort(true);
        }

        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        while (ob_get_level() > 0) {
            @ob_end_flush();
        }

        nocache_headers();
        header('Content-Type: application/x-ndjson; charset=' . get_option('blog_charset'));
        header('X-Accel-Buffering: no');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
    }

    private function stream_payload(array $payload): void
    {
        echo wp_json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";

        if (function_exists('ob_flush')) {
            @ob_flush();
        }

        flush();
    }

    private function stream_error_and_exit(string $message): void
    {
        $this->start_stream_response();
        $this->stream_payload([
            'success' => false,
            'status' => [
                'status' => 'failed',
                'message' => $message,
            ],
            'message' => $message,
        ]);
        exit;
    }

    private function render_styles(): void
    {
        echo '<style>
          .osm-data-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:20px;max-width:1080px}
          .osm-data-card{background:#fff;border:1px solid #dcdcde;border-radius:16px;padding:24px}
          .osm-data-progress{margin-top:18px}
          .osm-data-progress__bar{height:12px;border-radius:999px;background:#e5e7eb;overflow:hidden}
          .osm-data-progress__bar span{display:block;height:100%;width:100%;background:linear-gradient(90deg,#2271b1,#36a2eb);animation:osmDataPulse 1.1s linear infinite}
          @keyframes osmDataPulse{0%{transform:translateX(-70%)}100%{transform:translateX(100%)}}
          .osm-toast-stack{position:fixed;right:24px;bottom:24px;z-index:100000;display:grid;gap:10px;pointer-events:none}
          .osm-copy-toast{display:flex;align-items:center;gap:12px;padding:12px 16px;border-radius:10px;background:#16a34a;color:#fff;box-shadow:0 12px 30px rgba(0,0,0,.18);font-weight:600;pointer-events:auto;min-width:260px;max-width:min(420px, calc(100vw - 48px))}
          .osm-copy-toast.is-error{background:#dc2626}
          .osm-copy-toast__text{flex:1 1 auto}
          .osm-copy-toast__close{flex:0 0 auto;display:inline-flex;align-items:center;justify-content:center;width:24px;height:24px;padding:0;border:0;border-radius:999px;background:rgba(255,255,255,.16);color:#fff;font-size:16px;line-height:1;cursor:pointer}
        </style>';
    }

    private function render_script(string $nonce): void
    {
        ?>
        <script>
        (() => {
          const exportButton = document.getElementById('osm-data-export-start');
          const importButton = document.getElementById('osm-data-import-start');
          const importFile = document.getElementById('osm-data-import-file');
          const exportProgress = document.getElementById('osm-data-export-progress');
          const importProgress = document.getElementById('osm-data-import-progress');
          const exportStatus = document.getElementById('osm-data-export-status');
          const importStatus = document.getElementById('osm-data-import-status');
          const downloadWrap = document.getElementById('osm-data-export-download');
          const downloadLink = document.getElementById('osm-data-export-download-link');
          const toastStack = document.getElementById('osm-toast-stack');
          const nonce = <?php echo wp_json_encode($nonce); ?>;

          function triggerDownload(url) {
            const link = document.createElement('a');
            link.href = url;
            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();
            link.remove();
          }

          function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = 'osm-copy-toast' + (type === 'error' ? ' is-error' : '');
            toast.innerHTML = '<span class="osm-copy-toast__text"></span><button type="button" class="osm-copy-toast__close" aria-label="Close alert">&times;</button>';
            toast.querySelector('.osm-copy-toast__text').textContent = message;
            const removeToast = () => toast.remove();
            toast.querySelector('.osm-copy-toast__close').addEventListener('click', removeToast);
            toastStack.appendChild(toast);
            window.setTimeout(removeToast, 3200);
          }

          async function streamRequest(action, formData, type) {
            const progress = type === 'export' ? exportProgress : importProgress;
            const status = type === 'export' ? exportStatus : importStatus;
            const decoder = new TextDecoder();
            let buffer = '';

            formData.append('action', action);
            formData.append('_ajax_nonce', nonce);

            progress.hidden = false;
            const response = await fetch(ajaxurl, {
              method: 'POST',
              credentials: 'same-origin',
              body: formData,
            });

            if (!response.ok || !response.body) {
              throw new Error('Unable to start request.');
            }

            const reader = response.body.getReader();

            while (true) {
              const chunk = await reader.read();

              if (chunk.done) {
                break;
              }

              buffer += decoder.decode(chunk.value, { stream: true });
              const lines = buffer.split('\n');
              buffer = lines.pop() || '';

              for (const rawLine of lines) {
                const line = rawLine.trim();

                if (!line) {
                  continue;
                }

                const data = JSON.parse(line);
                const nextStatus = data.status || {};
                status.textContent = nextStatus.message || 'Working…';

                if (!data.success) {
                  progress.hidden = true;
                  throw new Error(data.message || nextStatus.message || 'Job failed.');
                }

                if (nextStatus.status === 'completed') {
                  progress.hidden = true;

                  if (type === 'export' && nextStatus.downloadUrl) {
                    downloadWrap.hidden = false;
                    downloadLink.href = nextStatus.downloadUrl;
                    triggerDownload(nextStatus.downloadUrl);
                  }

                  showToast(type === 'export' ? 'Export completed.' : 'Import completed.');
                  return;
                }
              }
            }

            progress.hidden = true;
            throw new Error('The streamed request finished unexpectedly.');
          }

          exportButton?.addEventListener('click', async () => {
            exportButton.disabled = true;
            downloadWrap.hidden = true;
            exportProgress.hidden = true;
            try {
              exportStatus.textContent = 'Preparing export…';
              await streamRequest('osm_data_stream_export', new FormData(), 'export');
            } catch (error) {
              showToast(error instanceof Error ? error.message : 'Unable to export data.', 'error');
            } finally {
              exportButton.disabled = false;
            }
          });

          importButton?.addEventListener('click', async () => {
            if (!importFile || !importFile.files || !importFile.files[0]) {
              showToast('Choose an export package first.', 'error');
              return;
            }

            importButton.disabled = true;
            importProgress.hidden = true;
            try {
              const formData = new FormData();
              formData.append('package', importFile.files[0]);
              importStatus.textContent = 'Preparing import…';
              await streamRequest('osm_data_stream_import', formData, 'import');
            } catch (error) {
              showToast(error instanceof Error ? error.message : 'Unable to import data.', 'error');
            } finally {
              importButton.disabled = false;
            }
          });
        })();
        </script>
        <?php
    }

    private function assert_access(): void
    {
        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'You do not have permission to do that.'], 403);
        }
    }

    private function assert_nonce(): void
    {
        check_ajax_referer('osm_data_portability');
    }
}
