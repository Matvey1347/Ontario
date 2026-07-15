<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class OSM_Clickcease
{
    private const BLOCKED_CODES = [3, 9, 17, 16, 7, 6, 11, 10, 25, 18, 2];
    private const MAX_WHITELIST_IPS = 5;
    private const RTI_SERVER_EUROPE = 'https://rti-eu-west-1.cheqzone.com/v1/realtime-interception';
    private const RTI_LOGGER = 'https://rtilogger.production.cheq-platform.com/';
    private const BOTZAPPING = 'https://botzapping.eu.cheq-platform.com';
    private const CHEQ_TAG = 'obs.sornavellon.com';
    private const CLICKCEASE = 'https://www.clickcease.com';
    private const CLICKCEASE_MONITORING = 'https://monitor.clickcease.com/stats';
    private const CLICKCEASE_BOTZAPPING = 'https://api.clickcease.com/dashboard/api/BotZappingDomain';

    private OSM_Current_Site $current_site;
    private OSM_Sites $sites;
    private OSM_Logger $logger;

    public function __construct(OSM_Current_Site $current_site, OSM_Sites $sites, OSM_Logger $logger)
    {
        $this->current_site = $current_site;
        $this->sites = $sites;
        $this->logger = $logger;

        add_action('save_post_' . OSM_Sites::post_type(), [$this, 'handle_site_save'], 20, 2);
        add_action('send_headers', [$this, 'server_validation'], -999);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets'], -999);
        add_action('wp_body_open', [$this, 'render_noscript_tags'], -999);
        add_action('wp_ajax_validate_clickcease_response', [$this, 'validate_clickcease_response'], -999);
        add_action('wp_ajax_nopriv_validate_clickcease_response', [$this, 'validate_clickcease_response'], -999);
    }

    public function handle_site_save(int $post_id, WP_Post $post): void
    {
        if ($post->post_type !== OSM_Sites::post_type()) {
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

        $site = $this->sites->get_site($post_id);
        $api_key = trim((string) ($site['clickcease_api_key'] ?? ''));
        $domain_key = trim((string) ($site['clickcease_domain_key'] ?? ''));
        $secret_key = trim((string) ($site['clickcease_secret_key'] ?? ''));
        $install = (string) ($site['clickcease_install_click_fraud'] ?? '0') === '1';
        $whitelist = $this->normalize_whitelist((string) ($site['clickcease_whitelist'] ?? ''));

        update_post_meta($post_id, '_osm_clickcease_whitelist', implode("\n", $whitelist));
        update_post_meta($post_id, '_osm_clickcease_invalid_secret', '0');
        update_post_meta($post_id, '_osm_clickcease_bot_zapping_authenticated', '0');
        update_post_meta($post_id, '_osm_clickcease_client_id', '');
        update_post_meta($post_id, '_osm_clickcease_monitoring', '0');

        if (! $install) {
            return;
        }

        if ($api_key === '' || $domain_key === '' || $secret_key === '') {
            return;
        }

        $this->validate_domain_key($domain_key);
        $client_id = $this->auth_with_botzapping($api_key, $domain_key, $secret_key, 'site_save');

        if ($client_id === '') {
            update_post_meta($post_id, '_osm_clickcease_invalid_secret', '1');
            return;
        }

        update_post_meta($post_id, '_osm_clickcease_bot_zapping_authenticated', '1');
        update_post_meta($post_id, '_osm_clickcease_client_id', $client_id);
        update_post_meta($post_id, '_osm_clickcease_invalid_secret', '0');
        update_post_meta($post_id, '_osm_clickcease_monitoring', $this->is_monitoring_with_botzapping($api_key, $domain_key, $secret_key) ? '1' : '0');
    }

    public function server_validation(): void
    {
        $site = $this->current_site->get_site();

        if (! $this->should_run_frontend($site)) {
            return;
        }

        if (isset($_GET['clickcease']) && sanitize_text_field(wp_unslash($_GET['clickcease'])) === 'valid') {
            return;
        }

        $client_ip = $this->get_user_ip();
        $whitelist = $this->normalize_whitelist((string) ($site['clickcease_whitelist'] ?? ''));

        if ($client_ip === '' || in_array($client_ip, $whitelist, true)) {
            return;
        }

        $api_key = (string) ($site['clickcease_api_key'] ?? '');
        $domain_key = (string) ($site['clickcease_domain_key'] ?? '');
        $secret_key = (string) ($site['clickcease_secret_key'] ?? '');
        $authenticated = (string) ($site['clickcease_bot_zapping_authenticated'] ?? '0') === '1';
        $invalid_secret = (string) ($site['clickcease_invalid_secret'] ?? '0') === '1';
        $is_monitoring = (string) ($site['clickcease_monitoring'] ?? '0') === '1';

        if ($api_key !== '' && $domain_key !== '' && $authenticated && $secret_key !== '' && ! $invalid_secret) {
            $validation = $this->auth_with_rti($site, $api_key, $this->current_request_url(), 'page_load', $domain_key);

            $forced = isset($_GET['clickcease']) ? sanitize_text_field(wp_unslash($_GET['clickcease'])) : '';

            if (! $is_monitoring && (! $validation['is_valid'] || in_array($forced, ['block', 'clearhtml'], true))) {
                status_header(403);
                exit;
            }

            return;
        }

        $this->logger->log('ClickCease keys incomplete', [
            'site_id' => (int) ($site['id'] ?? 0),
            'domain' => (string) ($site['primary_domain'] ?? ''),
        ]);
    }

    public function enqueue_frontend_assets(): void
    {
        $site = $this->current_site->get_site();

        if (! $this->should_run_frontend($site)) {
            return;
        }

        $client_ip = $this->get_user_ip();
        $whitelist = $this->normalize_whitelist((string) ($site['clickcease_whitelist'] ?? ''));

        if ((string) ($site['clickcease_install_click_fraud'] ?? '0') === '1') {
            wp_register_script('osm-clickcease-frontend', OSM_PLUGIN_URL . 'assets/js/osm-clickcease-frontend.js', [], OSM_PLUGIN_VERSION, true);
            wp_localize_script('osm-clickcease-frontend', 'osmClickcease', [
                'nonce' => wp_create_nonce('cc_ajax_nonce'),
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'ajaxAction' => 'validate_clickcease_response',
            ]);
            wp_enqueue_script('osm-clickcease-frontend');

            add_action('wp_head', static function (): void {
                echo "<script async src='" . esc_url(self::CLICKCEASE . "/monitor/stat.js") . "'></script>\n";
            }, 999);
        }

        if ($client_ip === '' || in_array($client_ip, $whitelist, true)) {
            return;
        }

        $api_key = (string) ($site['clickcease_domain_key'] ?? '');
        $authenticated = (string) ($site['clickcease_bot_zapping_authenticated'] ?? '0') === '1';

        if (! $authenticated || $api_key === '') {
            return;
        }

        $domain = $this->get_active_domain($site, $api_key);

        add_action('wp_head', static function () use ($domain, $api_key): void {
            echo "<script async src='" . esc_url($domain . '/i/' . $api_key . ".js") . "' class='ct_clicktrue'></script>\n";
        }, 999);
    }

    public function render_noscript_tags(): void
    {
        $site = $this->current_site->get_site();

        if (! $this->should_run_frontend($site)) {
            return;
        }

        $client_ip = $this->get_user_ip();
        $whitelist = $this->normalize_whitelist((string) ($site['clickcease_whitelist'] ?? ''));

        if ((string) ($site['clickcease_install_click_fraud'] ?? '0') === '1') {
            echo '<noscript><a href="' . esc_url(self::CLICKCEASE) . '" rel="nofollow"><img src="' . esc_url(self::CLICKCEASE_MONITORING . '/stats.aspx') . '" alt="Clickcease" /></a></noscript>' . "\n";
        }

        if ($client_ip === '' || in_array($client_ip, $whitelist, true)) {
            return;
        }

        $api_key = (string) ($site['clickcease_domain_key'] ?? '');
        $authenticated = (string) ($site['clickcease_bot_zapping_authenticated'] ?? '0') === '1';

        if (! $authenticated || $api_key === '') {
            return;
        }

        $domain = $this->get_active_domain($site, $api_key);
        echo '<noscript><iframe src="' . esc_url($domain . '/ns/' . $api_key . '.html?ch=""') . '" width="0" height="0" style="display:none"></iframe></noscript>' . "\n";
    }

    public function validate_clickcease_response(): void
    {
        if (! check_ajax_referer('cc_ajax_nonce', 'security', false)) {
            wp_send_json(['status' => 400, 'message' => 'Request could not be validated'], 400);
        }

        if (isset($_GET['clickcease']) && sanitize_text_field(wp_unslash($_GET['clickcease'])) === 'valid') {
            wp_send_json(['status' => 200, 'message' => ['action' => '']], 200);
        }

        if (! isset($_POST['cheq_hash']) || trim((string) wp_unslash($_POST['cheq_hash'])) === '') {
            wp_send_json(['status' => 400, 'error' => 'No hash'], 400);
        }

        $site = $this->current_site->get_site();
        $secret_key = (string) ($site['clickcease_secret_key'] ?? '');
        $message = str_replace(' ', '+', (string) wp_unslash($_POST['cheq_hash']));
        $iv = substr($message, 0, 16);
        $encrypted_message = substr($message, 16);
        $decrypted = openssl_decrypt($encrypted_message, 'AES-192-CTR', $secret_key, 0, $iv);
        $output = explode(':', (string) $decrypted);

        if (count($output) > 1 && ! is_numeric($output[0])) {
            $this->update_site_flag((int) ($site['id'] ?? 0), 'invalid_secret', '1');
        } else {
            $this->update_site_flag((int) ($site['id'] ?? 0), 'invalid_secret', '0');
        }

        $required_action = '';
        $is_monitoring = (string) ($site['clickcease_monitoring'] ?? '0') === '1';

        if (count($output) >= 4 && ! $is_monitoring) {
            if ((int) $output[2] === 7 || (int) $output[2] === 16) {
                $required_action = 'clearhtml';
            } elseif (in_array((int) $output[2], self::BLOCKED_CODES, true) && ! empty($output[1])) {
                $required_action = 'blockuser';
            }
        }

        wp_send_json(['status' => 200, 'message' => ['action' => $required_action]], 200);
    }

    private function should_run_frontend(array $site): bool
    {
        if (is_admin() || wp_doing_cron() || $this->current_site->is_preview()) {
            return false;
        }

        return ! current_user_can('manage_options') && ! empty($site);
    }

    private function validate_domain_key(string $domain_key): bool
    {
        $response = wp_remote_get('https://' . self::CHEQ_TAG . '/i/' . $domain_key . '.js');
        return wp_remote_retrieve_response_code($response) === 200;
    }

    private function auth_with_botzapping(string $api_key, string $tag_hash, string $secret, string $action): string
    {
        $request = [
            'method' => 'POST',
            'timeout' => 10,
            'redirection' => 5,
            'headers' => [
                'Content-Type' => 'application/json',
                'Origin' => 'plugin',
                'Authorization' => $api_key,
            ],
            'body' => wp_json_encode([
                'tagHash' => $tag_hash,
                'apiKey' => $api_key,
                'secretKey' => $secret,
                'newVersion' => true,
            ]),
        ];

        $response = wp_remote_post(self::BOTZAPPING . '/authorize/plugin', $request);

        if (is_wp_error($response)) {
            $this->logger->log('ClickCease auth error', [
                'action' => $action,
                'message' => $response->get_error_message(),
            ]);
            return '';
        }

        return wp_remote_retrieve_response_code($response) === 200 ? trim((string) wp_remote_retrieve_body($response)) : '';
    }

    private function is_monitoring_with_botzapping(string $api_key, string $tag_hash, string $secret): bool
    {
        $response = wp_remote_get(self::BOTZAPPING . '/plugin/monitoring?' . http_build_query([
            'tagHash' => $tag_hash,
            'apiKey' => $api_key,
            'secretKey' => $secret,
            'newVersion' => true,
        ]), [
            'timeout' => 10,
            'headers' => [
                'Content-Type' => 'application/json',
                'Origin' => 'plugin',
                'Authorization' => $api_key,
            ],
        ]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return false;
        }

        $decoded = json_decode((string) wp_remote_retrieve_body($response), true);
        return ! empty($decoded['isMonitoring']);
    }

    private function auth_with_rti(array $site, string $api_key, string $request_url, string $event_type, string $tag_hash): array
    {
        $request = [
            'method' => 'POST',
            'timeout' => 2,
            'redirection' => 10,
            'httpversion' => '1.1',
            'body' => $this->build_rti_request_params($api_key, $request_url, $event_type, $tag_hash),
        ];

        $response = wp_remote_post(self::RTI_SERVER_EUROPE, $request);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return ['is_valid' => true, 'output' => []];
        }

        $decoded = json_decode((string) wp_remote_retrieve_body($response));

        if (! is_object($decoded)) {
            return ['is_valid' => true, 'output' => []];
        }

        if (isset($decoded->setCookie) && is_string($decoded->setCookie)) {
            $cookie_values = explode(';', $decoded->setCookie);
            $cookie_name_value = explode('=', $cookie_values[0] ?? '', 2);
            if (count($cookie_name_value) > 1 && count($cookie_values) > 3) {
                setcookie($cookie_name_value[0] . '_en', $cookie_name_value[1], strtotime(explode('=', $cookie_values[1])[1] ?? ''), explode('=', $cookie_values[3])[1] ?? '/');
                setrawcookie($cookie_name_value[0], $cookie_name_value[1], strtotime(explode('=', $cookie_values[1])[1] ?? ''), explode('=', $cookie_values[3])[1] ?? '/');
            }
        }

        if (! isset($decoded->version) || ! is_numeric($decoded->version)) {
            $this->update_site_flag((int) ($site['id'] ?? 0), 'invalid_secret', '1');
        } else {
            $this->update_site_flag((int) ($site['id'] ?? 0), 'invalid_secret', '0');
        }

        if (isset($decoded->threatTypeCode) && in_array((int) $decoded->threatTypeCode, self::BLOCKED_CODES, true) && ! empty($decoded->isInvalid)) {
            return ['is_valid' => false, 'output' => $decoded];
        }

        return ['is_valid' => true, 'output' => $decoded];
    }

    private function build_rti_request_params(string $api_key, string $request_url, string $event_type, string $tag_hash): array
    {
        $domain = $this->current_site->get_domain();
        $request_params = [
            'ApiKey' => $api_key,
            'ClientIP' => $this->get_user_ip(),
            'RequestURL' => $request_url,
            'ResourceType' => 'text/html',
            'Method' => 'GET',
            'Host' => strtok($domain, '/'),
            'UserAgent' => $this->server('HTTP_USER_AGENT'),
            'Accept' => $this->server('HTTP_ACCEPT'),
            'AcceptLanguage' => $this->server('HTTP_ACCEPT_LANGUAGE'),
            'AcceptEncoding' => $this->server('HTTP_ACCEPT_ENCODING'),
            'HeaderNames' => 'Host,User-Agent,Accept,Accept-Langauge,Accept-Encoding',
            'EventType' => $event_type,
            'TagHash' => $tag_hash,
        ];

        if ($event_type === 'page_load') {
            $request_params['HeaderNames'] = 'Host,User-Agent,Accept,Accept-Langauge,Accept-Encoding,Cookie';
            $request_params['CheqCookie'] = isset($_COOKIE['_cheq_rti_en']) ? sanitize_text_field(wp_unslash($_COOKIE['_cheq_rti_en'])) : '';
            $request_params['Referer'] = $this->server('HTTP_REFERER');
            $request_params['Connection'] = $this->server('HTTP_CONNECTION');
        }

        return $request_params;
    }

    private function get_active_domain(array $site, string $api_key): string
    {
        $site_id = (int) ($site['id'] ?? 0);
        $cached = (string) ($site['clickcease_active_domain_value'] ?? '');
        $checked_at = (string) ($site['clickcease_active_domain_checked_at'] ?? '');

        if ($cached !== '' && $checked_at !== '' && strtotime($checked_at . ' + 1 hour') > time()) {
            return 'https://' . $cached;
        }

        $response = wp_remote_get(self::BOTZAPPING . '/plugin/active-domain', [
            'timeout' => 10,
            'headers' => [
                'Content-Type' => 'application/json',
                'Origin' => 'plugin',
                'Authorization' => $api_key,
            ],
        ]);

        $domain = $cached !== '' ? $cached : self::CHEQ_TAG;

        if (! is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $body = trim((string) wp_remote_retrieve_body($response));
            if ($body !== '') {
                $domain = $body;
                update_post_meta($site_id, '_osm_clickcease_active_domain_value', $domain);
                update_post_meta($site_id, '_osm_clickcease_active_domain_checked_at', current_time('mysql'));
            }
        }

        return 'https://' . $domain;
    }

    private function normalize_whitelist(string $raw): array
    {
        $pieces = preg_split('/[\r\n,]+/', $raw) ?: [];
        $ips = [];

        foreach ($pieces as $piece) {
            $ip = trim((string) $piece);

            if ($ip === '' || ! filter_var($ip, FILTER_VALIDATE_IP)) {
                continue;
            }

            $ips[] = $ip;
        }

        return array_slice(array_values(array_unique($ips)), 0, self::MAX_WHITELIST_IPS);
    }

    private function get_user_ip(): string
    {
        $ip = '';

        if ($this->server('HTTP_CLIENT_IP') !== '') {
            $ip = $this->server('HTTP_CLIENT_IP');
        } elseif ($this->server('HTTP_X_FORWARDED_FOR') !== '') {
            $ip = $this->server('HTTP_X_FORWARDED_FOR');
        } else {
            $ip = $this->server('REMOTE_ADDR');
        }

        $exploded = explode(',', apply_filters('wpb_get_ip', $ip));
        $candidate = trim((string) ($exploded[0] ?? ''));

        return (string) preg_replace('/((?::))(?:[0-9]+)$/', '', $candidate);
    }

    private function current_request_url(): string
    {
        $scheme = is_ssl() ? 'https' : 'http';
        $host = $this->current_site->get_domain();
        $uri = $this->server('REQUEST_URI');

        return $scheme . '://' . $host . ($uri !== '' ? $uri : '/');
    }

    private function server(string $key): string
    {
        return isset($_SERVER[$key]) ? (string) $_SERVER[$key] : '';
    }

    private function update_site_flag(int $site_id, string $suffix, string $value): void
    {
        if ($site_id <= 0) {
            return;
        }

        update_post_meta($site_id, '_osm_clickcease_' . $suffix, $value);
    }
}
