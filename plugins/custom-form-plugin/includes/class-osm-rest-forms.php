<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class OSM_Rest_Forms
{
    private OSM_Current_Site $current_site;
    private OSM_Leads $leads;
    private OSM_Zoho_CRM $zoho;
    private OSM_Logger $logger;

    public function __construct(OSM_Current_Site $current_site, OSM_Leads $leads, OSM_Zoho_CRM $zoho, OSM_Logger $logger)
    {
        $this->current_site = $current_site;
        $this->leads = $leads;
        $this->zoho = $zoho;
        $this->logger = $logger;

        add_action('rest_api_init', [$this, 'register_routes']);
        add_action('wpcf7_before_send_mail', [$this, 'capture_cf7_submission'], 10, 1);
    }

    public function register_routes(): void
    {
        register_rest_route('ontario-site-manager/v1', '/lead', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'handle_lead'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function handle_lead(WP_REST_Request $request): WP_REST_Response
    {
        $params = $request->get_params();
        $form_key = $this->detect_form_key($params);

        if ($form_key === '') {
            return new WP_REST_Response(['success' => false, 'message' => 'Unknown form'], 400);
        }

        if (! empty($params['website'])) {
            return new WP_REST_Response(['success' => true, 'message' => 'Submitted'], 200);
        }

        $payload = $this->sanitize_payload($form_key, $params);
        $validation = $this->validate_payload($form_key, $payload);

        if ($validation !== true) {
            return new WP_REST_Response(['success' => false, 'message' => $validation], 422);
        }

        $site = $this->current_site->get_site();
        $lead_id = $this->leads->create($site, $form_key, $payload);

        if ($lead_id < 1) {
            return new WP_REST_Response(['success' => false, 'message' => 'Failed to save lead'], 500);
        }

        $email_result = $this->send_notification_email($site, $form_key, $payload);
        $crm_result = $this->zoho->create_lead($site, $form_key, $payload);

        $this->leads->update_delivery($lead_id, [
            'status' => 'saved',
            'email_status' => (string) ($email_result['status'] ?? 'email_failed'),
            'email_message' => (string) ($email_result['message'] ?? ''),
            'crm_status' => ! empty($crm_result['success']) ? 'crm_sent' : 'crm_failed',
            'crm_message' => (string) ($crm_result['message'] ?? ''),
            'crm_reference' => (string) ($crm_result['reference'] ?? ''),
        ]);

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Lead submitted successfully',
            'leadId' => $lead_id,
            'email' => [
                'success' => ! empty($email_result['success']),
                'message' => (string) ($email_result['message'] ?? ''),
                'recipients' => $email_result['recipients'] ?? [],
            ],
            'crm' => [
                'success' => ! empty($crm_result['success']),
                'message' => (string) ($crm_result['message'] ?? ''),
                'reference' => (string) ($crm_result['reference'] ?? ''),
            ],
        ], 200);
    }

    public function capture_cf7_submission($contact_form): void
    {
        if (! class_exists('WPCF7_Submission')) {
            return;
        }

        $submission = WPCF7_Submission::get_instance();

        if (! $submission) {
            return;
        }

        $posted = $submission->get_posted_data();

        if (! is_array($posted) || $posted === []) {
            return;
        }

        $payload = [];

        foreach ($posted as $key => $value) {
            $payload[sanitize_key((string) $key)] = is_array($value)
                ? array_map('sanitize_text_field', $value)
                : sanitize_textarea_field((string) $value);
        }

        $form_id = is_object($contact_form) && method_exists($contact_form, 'id') ? (string) $contact_form->id() : 'cf7';
        $site = $this->current_site->get_site();
        $lead_id = $this->leads->create($site, 'cf7_' . $form_id, $payload);

        if ($lead_id < 1) {
            return;
        }

        $email_result = $this->send_notification_email($site, 'cf7_' . $form_id, $payload);
        $crm_result = $this->zoho->create_lead($site, 'cf7_' . $form_id, $payload);

        $this->leads->update_delivery($lead_id, [
            'status' => 'saved',
            'email_status' => (string) ($email_result['status'] ?? 'email_failed'),
            'email_message' => (string) ($email_result['message'] ?? ''),
            'crm_status' => ! empty($crm_result['success']) ? 'crm_sent' : 'crm_failed',
            'crm_message' => (string) ($crm_result['message'] ?? ''),
            'crm_reference' => (string) ($crm_result['reference'] ?? ''),
        ]);
    }

    private function detect_form_key(array $params): string
    {
        $form_key = isset($params['formType']) ? sanitize_key((string) $params['formType']) : '';

        if (in_array($form_key, ['caseForm', 'quickForm'], true)) {
            return $form_key;
        }

        if (isset($params['lossAmount'], $params['transferMethod'], $params['fullName'])) {
            return 'caseForm';
        }

        if (isset($params['name'], $params['message'], $params['lossConfirm'])) {
            return 'quickForm';
        }

        return '';
    }

    private function sanitize_payload(string $form_key, array $params): array
    {
        $fields = [
            'caseForm' => ['lossAmount', 'transferMethod', 'caseDetails', 'fullName', 'email', 'phone'],
            'quickForm' => ['name', 'phone', 'email', 'message', 'lossConfirm'],
        ];

        $payload = [];

        foreach ($fields[$form_key] as $field) {
            $value = $params[$field] ?? '';

            if ($field === 'email') {
                $payload[$field] = sanitize_email((string) $value);
                continue;
            }

            $payload[$field] = sanitize_textarea_field((string) $value);
        }

        return $payload;
    }

    private function validate_payload(string $form_key, array $payload): true|string
    {
        $required = [
            'caseForm' => ['lossAmount', 'transferMethod', 'fullName', 'email', 'phone'],
            'quickForm' => ['name', 'phone', 'email', 'message', 'lossConfirm'],
        ];

        foreach ($required[$form_key] as $field) {
            if (empty($payload[$field])) {
                return 'Please complete all required fields.';
            }
        }

        if (! is_email($payload['email'] ?? '')) {
            return 'Enter a valid email address.';
        }

        return true;
    }

    private function send_notification_email(array $site, string $form_key, array $payload): array
    {
        $recipients_raw = (string) ($site['notification_emails'] ?? '');
        $recipients = preg_split('/[\s,;]+/', $recipients_raw) ?: [];
        $recipients = array_values(array_filter(array_map('sanitize_email', $recipients), 'is_email'));

        if ($recipients === []) {
            return [
                'success' => false,
                'status' => 'email_not_configured',
                'message' => 'No notification email recipients configured',
                'recipients' => [],
            ];
        }

        $subject = sprintf('%s lead: %s', (string) ($site['company_name'] ?? 'Ontario Site'), $form_key);
        $lines = [];

        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $value = implode(', ', array_map('strval', $value));
            }

            $lines[] = $key . ': ' . (string) $value;
        }

        $lines[] = 'Domain: ' . (string) ($site['resolved_host'] ?? $site['primary_domain'] ?? '');
        $message = implode("\n", $lines);

        $sent = wp_mail($recipients, $subject, $message);

        if ($sent) {
            return [
                'success' => true,
                'status' => 'email_sent',
                'message' => 'Notification email sent successfully',
                'recipients' => $recipients,
            ];
        }

        $this->logger->log('Notification email failed', [
            'form_key' => $form_key,
            'recipients' => $recipients,
            'site_id' => $site['id'] ?? 0,
        ]);

        return [
            'success' => false,
            'status' => 'email_failed',
            'message' => 'Notification email sending failed',
            'recipients' => $recipients,
        ];
    }
}
