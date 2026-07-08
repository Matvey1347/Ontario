<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class OSM_Logger
{
    private const META_KEY = '__osm';
    private const REDACT_KEYS = [
        'client_secret',
        'refresh_token',
        'access_token',
        'authorization',
        'password',
        'secret',
    ];
    private const STORAGE_OPTION = 'osm_logs_storage_day';

    public function log(string $message, array|string|null $context = null, array $meta = []): void
    {
        $path = $this->storage_path();
        $this->prepare_storage($path);
        $this->rotate_if_new_day($path);

        $line = '[' . gmdate('Y-m-d H:i:s') . '] ' . $message;
        $meta = $this->normalize_meta($meta);

        if (is_array($context)) {
            $payload = $this->sanitize_context($context);

            if ($meta !== []) {
                $payload[self::META_KEY] = $meta;
            }

            $line .= ' | ' . wp_json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } elseif ($context !== null) {
            $line .= ' | ' . $context;
        } elseif ($meta !== []) {
            $line .= ' | ' . wp_json_encode([self::META_KEY => $meta], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $line .= PHP_EOL;

        file_put_contents($path, $line, FILE_APPEND);
    }

    public function read_entries(array $filters = []): array
    {
        $path = $this->storage_path();
        $this->prepare_storage($path);
        $this->rotate_if_new_day($path);
        $contents = file_get_contents($path);

        if (! is_string($contents) || trim($contents) === '') {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', trim($contents)) ?: [];
        $entries = [];

        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }

            $timestamp = '';
            $message = $line;
            $context = null;

            if (preg_match('/^\[(.*?)\]\s*(.*)$/', $line, $matches) === 1) {
                $timestamp = (string) ($matches[1] ?? '');
                $message = (string) ($matches[2] ?? '');
            }

            $meta = [];

            if (str_contains($message, ' | ')) {
                [$message, $context_raw] = explode(' | ', $message, 2);
                $decoded = json_decode($context_raw, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $meta = $this->extract_meta($decoded, (string) $message, $context_raw);
                    unset($decoded[self::META_KEY]);
                    $context = $decoded === [] ? null : $decoded;
                } else {
                    $context = $context_raw;
                    $meta = $this->infer_meta((string) $message, $context_raw);
                }
            } else {
                $meta = $this->infer_meta((string) $message, null);
            }

            $entry = [
                'timestamp' => $timestamp,
                'message' => $message,
                'context' => $context,
                'raw' => $line,
                'level' => $meta['level'] ?? 'info',
                'site_id' => (int) ($meta['site_id'] ?? 0),
                'site_name' => (string) ($meta['site_name'] ?? ''),
                'site_domain' => (string) ($meta['site_domain'] ?? ''),
            ];

            if (! $this->matches_filters($entry, $filters)) {
                continue;
            }

            $entries[] = $entry;
        }

        return array_reverse($entries);
    }

    public function clear(): void
    {
        $path = $this->storage_path();
        $this->prepare_storage($path);
        file_put_contents($path, '');
        update_option(self::STORAGE_OPTION, gmdate('Y-m-d'), false);
    }

    public function storage_path(): string
    {
        return OSM_PLUGIN_PATH . 'logs';
    }

    private function prepare_storage(string $path): void
    {
        if (is_dir($path)) {
            $legacy_contents = '';
            $files = glob(trailingslashit($path) . '*') ?: [];
            sort($files);

            foreach ($files as $file) {
                if (! is_file($file)) {
                    continue;
                }

                $contents = file_get_contents($file);

                if (is_string($contents) && $contents !== '') {
                    $legacy_contents .= rtrim($contents) . PHP_EOL;
                }
            }

            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }

            @rmdir($path);

            if (! file_exists($path)) {
                file_put_contents($path, $legacy_contents);
            }
        }

        if (! file_exists($path)) {
            file_put_contents($path, '');
        }
    }

    private function rotate_if_new_day(string $path): void
    {
        $today = gmdate('Y-m-d');
        $stored_day = (string) get_option(self::STORAGE_OPTION, '');

        if ($stored_day === $today) {
            return;
        }

        file_put_contents($path, '');
        update_option(self::STORAGE_OPTION, $today, false);
    }

    private function sanitize_context(array $context): array
    {
        $sanitized = [];

        foreach ($context as $key => $value) {
            $string_key = strtolower((string) $key);

            if ($this->is_sensitive_key($string_key)) {
                $sanitized[$key] = '[redacted]';
                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitize_context($value);
                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }

    private function is_sensitive_key(string $key): bool
    {
        foreach (self::REDACT_KEYS as $needle) {
            if (str_contains($key, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function normalize_meta(array $meta): array
    {
        $normalized = [];
        $level = strtolower(trim((string) ($meta['level'] ?? '')));

        if (in_array($level, ['success', 'info', 'error'], true)) {
            $normalized['level'] = $level;
        }

        $site_id = isset($meta['site_id']) ? (int) $meta['site_id'] : 0;

        if ($site_id > 0) {
            $normalized['site_id'] = $site_id;
        }

        foreach (['site_name', 'site_domain'] as $key) {
            $value = trim((string) ($meta[$key] ?? ''));

            if ($value !== '') {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    private function extract_meta(array $decoded, string $message, ?string $raw_context): array
    {
        $meta = [];

        if (isset($decoded[self::META_KEY]) && is_array($decoded[self::META_KEY])) {
            $meta = $this->normalize_meta($decoded[self::META_KEY]);
        }

        if ($meta === []) {
            $meta = $this->infer_meta($message, $raw_context, $decoded);
        }

        return $meta;
    }

    private function infer_meta(string $message, ?string $raw_context = null, ?array $context = null): array
    {
        $meta = [
            'level' => $this->detect_level($message),
        ];

        if (is_array($context)) {
            $site_id = isset($context['site_id']) ? (int) $context['site_id'] : 0;

            if ($site_id > 0) {
                $meta['site_id'] = $site_id;
            }

            foreach (['site_name', 'site_domain'] as $key) {
                if (! empty($context[$key]) && is_scalar($context[$key])) {
                    $meta[$key] = trim((string) $context[$key]);
                }
            }
        }

        if ($raw_context !== null && ! isset($meta['site_domain']) && preg_match('/"site_domain":"([^"]+)"/', $raw_context, $matches) === 1) {
            $meta['site_domain'] = (string) ($matches[1] ?? '');
        }

        return $this->normalize_meta($meta);
    }

    private function detect_level(string $message): string
    {
        $normalized = strtolower($message);

        if (str_contains($normalized, 'failed') || str_contains($normalized, 'error') || str_contains($normalized, 'blocked')) {
            return 'error';
        }

        if (str_contains($normalized, 'success') || str_contains($normalized, 'sent') || str_contains($normalized, 'created')) {
            return 'success';
        }

        return 'info';
    }

    private function matches_filters(array $entry, array $filters): bool
    {
        $site_id = isset($filters['site_id']) ? (int) $filters['site_id'] : 0;
        $level = strtolower(trim((string) ($filters['level'] ?? 'all')));

        if ($site_id > 0 && (int) ($entry['site_id'] ?? 0) !== $site_id) {
            return false;
        }

        if ($level !== 'all' && in_array($level, ['success', 'info', 'error'], true) && (string) ($entry['level'] ?? 'info') !== $level) {
            return false;
        }

        return true;
    }
}
