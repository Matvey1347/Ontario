<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class OSM_Zoho_CRM
{
    private OSM_Logger $logger;

    public function __construct(OSM_Logger $logger)
    {
        $this->logger = $logger;
    }

    public function create_lead(array $site, string $form_key, array $payload): array
    {
        if (($site['zoho_enabled'] ?? '0') !== '1') {
            $this->logger->log('Zoho lead sync skipped', [
                'reason' => 'Zoho disabled',
                'form_key' => $form_key,
                'site_id' => $site['id'] ?? 0,
                'site_name' => $site['company_name'] ?? '',
                'site_domain' => $site['resolved_host'] ?? $site['primary_domain'] ?? '',
            ], $this->build_log_meta('info', $site));
            return ['success' => false, 'message' => 'Zoho disabled'];
        }

        if (empty($site['zoho_client_id']) || empty($site['zoho_client_secret']) || empty($site['zoho_refresh_token'])) {
            $this->logger->log('Zoho lead sync skipped', [
                'reason' => 'Zoho credentials incomplete',
                'form_key' => $form_key,
                'site_id' => $site['id'] ?? 0,
                'site_name' => $site['company_name'] ?? '',
                'site_domain' => $site['resolved_host'] ?? $site['primary_domain'] ?? '',
            ], $this->build_log_meta('info', $site));
            return ['success' => false, 'message' => 'Zoho credentials incomplete'];
        }

        $access_token = $this->refresh_access_token($site);

        if ($access_token === '') {
            return ['success' => false, 'message' => 'Failed to refresh access token'];
        }

        $endpoint = trailingslashit((string) $site['zoho_api_domain']) . 'crm/v8/Leads';
        $lead_data = $this->map_payload($site, $form_key, $payload);

        $response = wp_remote_post($endpoint, [
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'Zoho-oauthtoken ' . $access_token,
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode(['data' => [$lead_data]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        if (is_wp_error($response)) {
            $this->logger->log('Zoho create lead failed', [
                'error' => $response->get_error_message(),
                'form_key' => $form_key,
                'site_id' => $site['id'] ?? 0,
                'site_name' => $site['company_name'] ?? '',
                'site_domain' => $site['resolved_host'] ?? $site['primary_domain'] ?? '',
            ], $this->build_log_meta('error', $site));
            return ['success' => false, 'message' => $response->get_error_message()];
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);

        if ($code >= 200 && $code < 300 && isset($decoded['data'][0]['status']) && $decoded['data'][0]['status'] === 'success') {
            $reference = '';

            if (! empty($decoded['data'][0]['details']['id'])) {
                $reference = (string) $decoded['data'][0]['details']['id'];
            }

            $this->logger->log('Zoho lead created', [
                'form_key' => $form_key,
                'site_id' => $site['id'] ?? 0,
                'site_name' => $site['company_name'] ?? '',
                'site_domain' => $site['resolved_host'] ?? $site['primary_domain'] ?? '',
                'reference' => $reference,
            ], $this->build_log_meta('success', $site));

            return ['success' => true, 'message' => 'Lead created', 'data' => $decoded, 'reference' => $reference];
        }

        $this->logger->log('Zoho create lead non-success response', [
            'status_code' => $code,
            'body' => $body,
            'form_key' => $form_key,
            'site_id' => $site['id'] ?? 0,
            'site_name' => $site['company_name'] ?? '',
            'site_domain' => $site['resolved_host'] ?? $site['primary_domain'] ?? '',
        ], $this->build_log_meta('error', $site));
        return ['success' => false, 'message' => 'Zoho lead creation failed', 'data' => $decoded];
    }

    private function refresh_access_token(array $site): string
    {
        $endpoint = trailingslashit((string) $site['zoho_accounts_url']) . 'oauth/v2/token';

        $response = wp_remote_post($endpoint, [
            'timeout' => 30,
            'body' => [
                'refresh_token' => (string) $site['zoho_refresh_token'],
                'client_id' => (string) $site['zoho_client_id'],
                'client_secret' => (string) $site['zoho_client_secret'],
                'grant_type' => 'refresh_token',
            ],
        ]);

        if (is_wp_error($response)) {
            $this->logger->log('Zoho token refresh failed', [
                'error' => $response->get_error_message(),
                'site_id' => $site['id'] ?? 0,
                'site_name' => $site['company_name'] ?? '',
                'site_domain' => $site['resolved_host'] ?? $site['primary_domain'] ?? '',
            ], $this->build_log_meta('error', $site));
            return '';
        }

        $decoded = json_decode(wp_remote_retrieve_body($response), true);

        return is_array($decoded) && ! empty($decoded['access_token']) ? (string) $decoded['access_token'] : '';
    }

    private function map_payload(array $site, string $form_key, array $payload): array
    {
        $name = trim((string) ($payload['fullName'] ?? $payload['name'] ?? 'Website Lead'));
        $parts = preg_split('/\s+/', $name) ?: [];
        $first_name = count($parts) > 1 ? array_shift($parts) : '';
        $last_name = trim(implode(' ', $parts));
        if ($last_name === '') {
            $last_name = $name !== '' ? $name : 'Website Lead';
        }

        $description_lines = [
            'Form: ' . $form_key,
        ];

        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $value = implode(', ', array_map('strval', $value));
            }

            $description_lines[] = $key . ': ' . (string) $value;
        }

        $lead = [
            'First_Name' => $first_name,
            'Last_Name' => $last_name,
            'Email' => (string) ($payload['email'] ?? ''),
            'Phone' => (string) ($payload['phone'] ?? ''),
            'Lead_Source' => $this->sanitize_value((string) ($site['company_name'] ?? 'Website')),
            'Lead_Status' => (string) ($site['default_lead_status'] ?? 'Contact in Future'),
            'Description' => implode("\n", $description_lines),
        ];

        if (! empty($payload['lossAmount'])) {
            $lead['Amount_lost'] = $this->sanitize_value((string) $payload['lossAmount']);
        }

        if (! empty($payload['transferMethod'])) {
            $lead['Scam_type'] = $this->sanitize_value((string) $payload['transferMethod']);
        }

        return array_filter($lead, static fn($value) => $value !== '');
    }

    private function sanitize_value(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', $value) ?: '');
    }

    private function build_log_meta(string $level, array $site): array
    {
        return [
            'level' => $level,
            'site_id' => (int) ($site['id'] ?? 0),
            'site_name' => (string) ($site['company_name'] ?? ''),
            'site_domain' => (string) ($site['resolved_host'] ?? $site['primary_domain'] ?? ''),
        ];
    }
}
