<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class OSM_Logger
{
    private const REDACT_KEYS = [
        'client_secret',
        'refresh_token',
        'access_token',
        'authorization',
        'password',
        'secret',
    ];

    public function log(string $message, array|string|null $context = null): void
    {
        $log_dir = OSM_PLUGIN_PATH . 'logs/';

        if (! file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }

        $line = '[' . gmdate('Y-m-d H:i:s') . '] ' . $message;

        if ($context !== null) {
            if (is_array($context)) {
                $line .= ' | ' . wp_json_encode($this->sanitize_context($context), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } else {
                $line .= ' | ' . $context;
            }
        }

        $line .= PHP_EOL;

        file_put_contents($log_dir . 'osm-' . gmdate('Y-m-d') . '.log', $line, FILE_APPEND);
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
}
