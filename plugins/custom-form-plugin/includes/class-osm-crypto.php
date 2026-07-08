<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class OSM_Crypto
{
    private OSM_Logger $logger;

    public function __construct(OSM_Logger $logger)
    {
        $this->logger = $logger;
    }

    public function encrypt(string $plaintext): string
    {
        $plaintext = trim($plaintext);

        if ($plaintext === '') {
            return '';
        }

        if (! self::is_available()) {
            $this->logger->log('OpenSSL unavailable, storing secret in fallback mode');
            return base64_encode(wp_json_encode([
                'v' => 1,
                'fallback' => true,
                'value' => $plaintext,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $this->get_key(), OPENSSL_RAW_DATA, $iv, $tag);

        if ($ciphertext === false) {
            $this->logger->log('OpenSSL encrypt failed, storing secret in fallback mode');
            return base64_encode(wp_json_encode([
                'v' => 1,
                'fallback' => true,
                'value' => $plaintext,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        return base64_encode(wp_json_encode([
            'v' => 1,
            'alg' => 'aes-256-gcm',
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag),
            'value' => base64_encode($ciphertext),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public function decrypt(string $payload): string
    {
        $payload = trim($payload);

        if ($payload === '') {
            return '';
        }

        $decoded = json_decode(base64_decode($payload, true) ?: '', true);

        if (! is_array($decoded)) {
            return '';
        }

        if (! empty($decoded['fallback']) && isset($decoded['value']) && is_string($decoded['value'])) {
            return $decoded['value'];
        }

        if (! self::is_available()) {
            return '';
        }

        $iv = base64_decode((string) ($decoded['iv'] ?? ''), true);
        $tag = base64_decode((string) ($decoded['tag'] ?? ''), true);
        $value = base64_decode((string) ($decoded['value'] ?? ''), true);

        if ($iv === false || $tag === false || $value === false) {
            return '';
        }

        $plaintext = openssl_decrypt($value, 'aes-256-gcm', $this->get_key(), OPENSSL_RAW_DATA, $iv, $tag);

        return is_string($plaintext) ? $plaintext : '';
    }

    public function mask(string $encrypted_value): string
    {
        $plain = $this->decrypt($encrypted_value);

        if ($plain === '') {
            return '';
        }

        $visible = substr($plain, -4);

        return '••••••••' . $visible;
    }

    public static function is_available(): bool
    {
        return function_exists('openssl_encrypt') && function_exists('openssl_decrypt');
    }

    private function get_key(): string
    {
        return hash('sha256', AUTH_KEY . SECURE_AUTH_KEY . LOGGED_IN_KEY . NONCE_KEY, true);
    }
}
