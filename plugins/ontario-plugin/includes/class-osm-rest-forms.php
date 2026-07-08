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

        $this->logger->log('Lead captured', [
            'lead_id' => $lead_id,
            'form_key' => $form_key,
            'site_id' => $site['id'] ?? 0,
            'site_name' => $site['company_name'] ?? '',
            'site_domain' => $site['resolved_host'] ?? $site['primary_domain'] ?? '',
        ], $this->build_log_meta('info', $site));

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
                'status' => (string) ($email_result['status'] ?? ''),
                'message' => (string) ($email_result['message'] ?? ''),
                'recipients' => $email_result['recipients'] ?? [],
            ],
            'crm' => [
                'success' => ! empty($crm_result['success']),
                'status' => ! empty($crm_result['success']) ? 'crm_sent' : 'crm_failed',
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

        $this->logger->log('Lead captured', [
            'lead_id' => $lead_id,
            'form_key' => 'cf7_' . $form_id,
            'site_id' => $site['id'] ?? 0,
            'site_name' => $site['company_name'] ?? '',
            'site_domain' => $site['resolved_host'] ?? $site['primary_domain'] ?? '',
        ], $this->build_log_meta('info', $site));

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
            $this->logger->log('Notification email not configured', [
                'form_key' => $form_key,
                'site_id' => $site['id'] ?? 0,
                'site_name' => $site['company_name'] ?? '',
                'site_domain' => $site['resolved_host'] ?? $site['primary_domain'] ?? '',
                'reason' => 'No notification email recipients configured',
            ], $this->build_log_meta('info', $site));

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
        $smtp_state = $this->get_smtp_state();

        if (! empty($smtp_state['blocked'])) {
            $this->logger->log('Notification email blocked before send', [
                'form_key' => $form_key,
                'recipients' => $recipients,
                'site_id' => $site['id'] ?? 0,
                'site_name' => $site['company_name'] ?? '',
                'site_domain' => $site['resolved_host'] ?? $site['primary_domain'] ?? '',
                'subject' => $subject,
                'reason' => $smtp_state['message'] ?? '',
                'smtp_state' => $smtp_state,
            ], $this->build_log_meta('error', $site));

            return [
                'success' => false,
                'status' => (string) ($smtp_state['status'] ?? 'email_failed'),
                'message' => (string) ($smtp_state['message'] ?? 'Email sending is not configured'),
                'recipients' => $recipients,
            ];
        }

        $mail_error = [
            'message' => '',
            'messages' => [],
            'data' => null,
        ];
        $mail_failed_listener = static function ($error) use (&$mail_error): void {
            if (! $error instanceof WP_Error) {
                return;
            }

            $mail_error['message'] = $error->get_error_message();
            $mail_error['messages'] = $error->get_error_messages();
            $mail_error['data'] = $error->get_error_data();
        };

        add_action('wp_mail_failed', $mail_failed_listener, 10, 1);
        $sent = wp_mail($recipients, $subject, $message);
        remove_action('wp_mail_failed', $mail_failed_listener, 10);

        if ($sent) {
            $this->logger->log('Notification email sent', [
                'form_key' => $form_key,
                'recipients' => $recipients,
                'site_id' => $site['id'] ?? 0,
                'site_name' => $site['company_name'] ?? '',
                'site_domain' => $site['resolved_host'] ?? $site['primary_domain'] ?? '',
                'subject' => $subject,
            ], $this->build_log_meta('success', $site));

            return [
                'success' => true,
                'status' => 'email_sent',
                'message' => 'Notification email sent successfully',
                'recipients' => $recipients,
            ];
        }

        $diagnostic_message = $this->build_email_failure_message($mail_error, $recipients);
        $mail_config = [
            'mailer' => (string) apply_filters('wp_mail_content_type', 'text/plain'),
            'php_smtp' => (string) ini_get('SMTP'),
            'php_smtp_port' => (string) ini_get('smtp_port'),
            'sendmail_path' => (string) ini_get('sendmail_path'),
        ];

        $this->logger->log('Notification email failed', [
            'form_key' => $form_key,
            'recipients' => $recipients,
            'site_id' => $site['id'] ?? 0,
            'site_name' => $site['company_name'] ?? '',
            'site_domain' => $site['resolved_host'] ?? $site['primary_domain'] ?? '',
            'subject' => $subject,
            'reason' => $diagnostic_message,
            'wp_mail_error' => $mail_error['message'],
            'wp_mail_error_messages' => $mail_error['messages'],
            'wp_mail_error_data' => $mail_error['data'],
            'mail_config' => $mail_config,
            'smtp_state' => $smtp_state,
        ], $this->build_log_meta('error', $site));

        return [
            'success' => false,
            'status' => 'email_failed',
            'message' => $diagnostic_message,
            'recipients' => $recipients,
        ];
    }

    private function build_email_failure_message(array $mail_error, array $recipients): string
    {
        $recipients_text = implode(', ', $recipients);

        if (! empty($mail_error['message'])) {
            return sprintf(
                'Email delivery failed for %s. WordPress mail error: %s',
                $recipients_text,
                (string) $mail_error['message']
            );
        }

        $sendmail_path = trim((string) ini_get('sendmail_path'));
        $smtp_host = trim((string) ini_get('SMTP'));
        $smtp_port = trim((string) ini_get('smtp_port'));

        if ($sendmail_path === '' && $smtp_host === '') {
            return sprintf(
                'Email delivery failed for %s. WordPress wp_mail() returned false and no PHP mail transport is configured. Configure SMTP or sendmail for this environment.',
                $recipients_text
            );
        }

        $transport = $sendmail_path !== ''
            ? 'sendmail (' . $sendmail_path . ')'
            : 'SMTP (' . $smtp_host . ($smtp_port !== '' ? ':' . $smtp_port : '') . ')';

        return sprintf(
            'Email delivery failed for %s. WordPress wp_mail() returned false while using %s. Check the mail server configuration or SMTP credentials.',
            $recipients_text,
            $transport
        );
    }

    private function get_smtp_state(): array
    {
        $result = [
            'blocked' => false,
            'status' => '',
            'message' => '',
            'provider' => 'wordpress',
            'mailer' => '',
            'from_email' => '',
        ];

        if (! function_exists('wp_mail_smtp')) {
            return $result;
        }

        $options = get_option('wp_mail_smtp', []);

        if (! is_array($options)) {
            $options = [];
        }

        $mail = isset($options['mail']) && is_array($options['mail']) ? $options['mail'] : [];
        $smtp = isset($options['smtp']) && is_array($options['smtp']) ? $options['smtp'] : [];
        $mailer = (string) ($mail['mailer'] ?? '');
        $from_email = sanitize_email((string) ($mail['from_email'] ?? ''));

        $result['provider'] = 'wp_mail_smtp';
        $result['mailer'] = $mailer;
        $result['from_email'] = $from_email;

        if ($mailer === '' || $mailer === 'mail') {
            $result['blocked'] = true;
            $result['status'] = 'email_smtp_not_configured';
            $result['message'] = 'WP Mail SMTP is installed but not configured. Choose and configure a mailer in WP Mail SMTP settings.';
            return $result;
        }

        if ($from_email === '' || ! is_email($from_email)) {
            $result['blocked'] = true;
            $result['status'] = 'email_from_invalid';
            $result['message'] = 'WP Mail SMTP is missing a valid From email address. Configure a valid From Email in WP Mail SMTP settings.';
            return $result;
        }

        if ($mailer === 'smtp') {
            $host = trim((string) ($smtp['host'] ?? ''));
            $port = trim((string) ($smtp['port'] ?? ''));
            $auth = ! empty($smtp['auth']);
            $user = trim((string) ($smtp['user'] ?? ''));
            $pass = trim((string) ($smtp['pass'] ?? ''));

            if ($host === '' || $port === '') {
                $result['blocked'] = true;
                $result['status'] = 'email_smtp_not_configured';
                $result['message'] = 'WP Mail SMTP mailer is set to SMTP, but host or port is missing.';
                return $result;
            }

            if ($auth && ($user === '' || $pass === '')) {
                $result['blocked'] = true;
                $result['status'] = 'email_smtp_not_configured';
                $result['message'] = 'WP Mail SMTP SMTP authentication is enabled, but username or password is missing.';
                return $result;
            }
        }

        return $result;
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
