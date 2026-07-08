<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class OSM_Admin
{
    private OSM_Sites $sites;
    private OSM_Current_Site $current_site;
    private OSM_Leads $leads;
    private OSM_Logger $logger;

    public function __construct(OSM_Sites $sites, OSM_Current_Site $current_site, OSM_Leads $leads, OSM_Logger $logger)
    {
        $this->sites = $sites;
        $this->current_site = $current_site;
        $this->leads = $leads;
        $this->logger = $logger;

        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_notices', [$this, 'render_notices']);
    }

    public function register_menu(): void
    {
        add_submenu_page(
            'edit.php?post_type=' . OSM_Sites::post_type(),
            'Leads',
            'Leads',
            'manage_options',
            'ontario-site-leads',
            [$this, 'render_leads_page']
        );

        add_submenu_page(
            'edit.php?post_type=' . OSM_Sites::post_type(),
            'Logs',
            'Logs',
            'manage_options',
            'ontario-site-logs',
            [$this, 'render_logs_page']
        );
    }

    public function render_leads_page(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $selected_site_id = isset($_GET['site_id']) ? absint($_GET['site_id']) : 0;
        $items = $this->leads->recent(100, ['site_id' => $selected_site_id]);
        $sites = get_posts([
            'post_type' => OSM_Sites::post_type(),
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        echo '<div class="wrap"><h1>Leads</h1>';
        echo '<form id="osm-leads-filter-form" method="get" class="osm-leads-filter-form">';
        echo '<input type="hidden" name="post_type" value="' . esc_attr(OSM_Sites::post_type()) . '" />';
        echo '<input type="hidden" name="page" value="ontario-site-leads" />';
        echo '<label for="osm-site-filter" class="screen-reader-text">Filter by site</label>';
        echo '<select id="osm-site-filter" name="site_id">';
        echo '<option value="0">All sites</option>';
        foreach ($sites as $site) {
            $label = get_post_meta($site->ID, '_osm_company_name', true);
            if (! is_string($label) || $label === '') {
                $label = $site->post_title;
            }
            echo '<option value="' . esc_attr((string) $site->ID) . '" ' . selected($selected_site_id, (int) $site->ID, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '</form>';

        $this->render_leads_styles();
        echo '<div id="osm-leads-loader" class="osm-leads-loader-panel" hidden aria-live="polite"><span class="osm-leads-loader">Loading...</span></div>';
        echo '<div id="osm-leads-table-wrap">';
        echo '<table class="widefat fixed striped osm-leads-table"><thead><tr>';
        echo '<th>ID</th><th>Site</th><th>Form</th><th>Name</th><th>Email</th><th>Phone</th><th>Created</th><th>Email</th><th>CRM</th><th>Payload</th>';
        echo '</tr></thead><tbody>';

        if ($items === []) {
            echo '<tr><td colspan="10" class="osm-empty-state">No leads captured yet.</td></tr>';
        } else {
            foreach ($items as $item) {
                $payload = json_decode((string) $item['payload'], true);
                $payload_json = is_array($payload)
                    ? wp_json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    : (string) $item['payload'];
                $payload_plain = $this->format_payload_text(is_array($payload) ? $payload : ['payload' => (string) $item['payload']]);

                echo '<tr>';
                echo '<td>' . esc_html((string) $item['id']) . '</td>';
                echo '<td><strong>' . esc_html((string) ($item['site_name'] ?: $item['site_domain'])) . '</strong><br><small>' . esc_html((string) $item['site_domain']) . '</small></td>';
                echo '<td>' . esc_html((string) $item['form_key']) . '</td>';
                echo '<td>' . esc_html((string) $item['lead_name']) . '</td>';
                echo '<td>' . esc_html((string) $item['lead_email']) . '</td>';
                echo '<td>' . esc_html((string) $item['lead_phone']) . '</td>';
                echo '<td>' . esc_html((string) $item['created_at']) . '</td>';
                echo '<td>' . $this->render_status_badge('email', (string) $item['email_status'], (string) ($item['email_message'] ?? ''), '') . '</td>';
                echo '<td>' . $this->render_status_badge('crm', (string) $item['crm_status'], (string) ($item['crm_message'] ?? ''), (string) ($item['crm_reference'] ?? '')) . '</td>';
                echo '<td>' . $this->render_payload_toggle((int) $item['id'], $payload_json, $payload_plain) . '</td>';
                echo '</tr>';
            }
        }

        echo '</tbody></table>';
        echo '</div>';
        echo '<div id="osm-toast-stack" class="osm-toast-stack" aria-live="polite" aria-atomic="false"></div>';
        echo '<div id="osm-payload-modal" class="osm-payload-modal" hidden>';
        echo '<div class="osm-payload-modal__backdrop" data-osm-modal-close></div>';
        echo '<div class="osm-payload-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="osm-payload-modal-title">';
        echo '<div class="osm-payload-modal__header"><h2 id="osm-payload-modal-title">Payload</h2><button type="button" class="osm-payload-modal__close" data-osm-modal-close aria-label="Close modal">&times;</button></div>';
        echo '<div class="osm-payload-modal__actions"><button type="button" class="button button-primary" id="osm-copy-json">Copy JSON</button><button type="button" class="button" id="osm-copy-text">Copy text</button></div>';
        echo '<pre id="osm-payload-modal-content" class="osm-payload-modal__content"></pre>';
        echo '</div>';
        echo '</div>';
        $this->render_leads_script();
        echo '</div>';
    }

    public function render_notices(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        if (! OSM_Crypto::is_available()) {
            echo '<div class="notice notice-warning"><p>Ontario Plugin: OpenSSL is unavailable. CRM secrets are stored in fallback mode.</p></div>';
        }
    }

    public function render_logs_page(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        if (
            isset($_POST['osm_clear_logs'])
            && check_admin_referer('osm_clear_logs_action', 'osm_clear_logs_nonce')
        ) {
            $this->logger->clear();
            wp_safe_redirect(add_query_arg([
                'post_type' => OSM_Sites::post_type(),
                'page' => 'ontario-site-logs',
                'logs_cleared' => '1',
            ], admin_url('edit.php')));
            exit;
        }

        $selected_site_id = isset($_GET['site_id']) ? absint($_GET['site_id']) : 0;
        $selected_level = isset($_GET['log_level']) ? sanitize_key((string) $_GET['log_level']) : 'all';
        $selected_level = in_array($selected_level, ['all', 'success', 'info', 'error'], true) ? $selected_level : 'all';
        $entries = $this->logger->read_entries([
            'site_id' => $selected_site_id,
            'level' => $selected_level,
        ]);
        $storage_path = $this->logger->storage_path();
        $sites = get_posts([
            'post_type' => OSM_Sites::post_type(),
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        echo '<div class="wrap"><h1>Logs</h1>';

        if (isset($_GET['logs_cleared']) && $_GET['logs_cleared'] === '1') {
            echo '<div class="notice notice-success is-dismissible"><p>Logs cleared.</p></div>';
        }

        $this->render_logs_styles();
        echo '<div class="osm-logs-toolbar">';
        echo '<div class="osm-logs-meta"><strong>Current file:</strong> <code>' . esc_html($storage_path) . '</code><br /><strong>Entries today:</strong> ' . esc_html((string) count($entries)) . '</div>';
        echo '<form id="osm-logs-filter-form" method="get" class="osm-logs-filter-form">';
        echo '<input type="hidden" name="post_type" value="' . esc_attr(OSM_Sites::post_type()) . '" />';
        echo '<input type="hidden" name="page" value="ontario-site-logs" />';
        echo '<label for="osm-logs-site-filter" class="screen-reader-text">Filter logs by site</label>';
        echo '<select id="osm-logs-site-filter" name="site_id">';
        echo '<option value="0">All sites</option>';
        foreach ($sites as $site) {
            $label = get_post_meta($site->ID, '_osm_company_name', true);
            if (! is_string($label) || $label === '') {
                $label = $site->post_title;
            }
            echo '<option value="' . esc_attr((string) $site->ID) . '" ' . selected($selected_site_id, (int) $site->ID, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<label for="osm-logs-level-filter" class="screen-reader-text">Filter logs by level</label>';
        echo '<select id="osm-logs-level-filter" name="log_level">';
        echo '<option value="all"' . selected($selected_level, 'all', false) . '>All levels</option>';
        echo '<option value="success"' . selected($selected_level, 'success', false) . '>Success only</option>';
        echo '<option value="info"' . selected($selected_level, 'info', false) . '>Info only</option>';
        echo '<option value="error"' . selected($selected_level, 'error', false) . '>Errors only</option>';
        echo '</select>';
        echo '</form>';
        echo '<form method="post">';
        wp_nonce_field('osm_clear_logs_action', 'osm_clear_logs_nonce');
        echo '<input type="hidden" name="osm_clear_logs" value="1" />';
        submit_button('Clear logs', 'delete', 'submit', false, ['onclick' => "return confirm('Clear all current logs?');"]);
        echo '</form>';
        echo '</div>';

        echo '<div id="osm-logs-loader" class="osm-leads-loader-panel" hidden aria-live="polite"><span class="osm-leads-loader">Loading...</span></div>';
        echo '<div id="osm-logs-list-wrap">';

        if ($entries === []) {
            echo '<div class="osm-logs-empty">No logs match the current filters.</div></div>';
            echo '<div id="osm-toast-stack" class="osm-toast-stack" aria-live="polite" aria-atomic="false"></div>';
            $this->render_logs_script();
            echo '</div>';
            return;
        }

        echo '<div class="osm-logs-list">';

        foreach ($entries as $entry) {
            $message_class = $this->log_level_to_class((string) ($entry['level'] ?? 'info'));
            echo '<article class="osm-log-entry ' . esc_attr($message_class) . '">';
            echo '<div class="osm-log-entry__head">';
            echo '<div>';
            echo '<div class="osm-log-entry__time">' . esc_html((string) $entry['timestamp']) . '</div>';
            echo '<div class="osm-log-entry__message">' . esc_html((string) $entry['message']) . '</div>';
            echo '<div class="osm-log-entry__badges">';
            echo $this->render_log_badge(
                ucfirst((string) ($entry['level'] ?? 'info')),
                'osm-log-badge--' . (string) ($entry['level'] ?? 'info'),
                'Log level: ' . ucfirst((string) ($entry['level'] ?? 'info'))
            );

            $site_label = trim((string) ($entry['site_name'] ?? ''));
            if ($site_label === '') {
                $site_label = trim((string) ($entry['site_domain'] ?? ''));
            }

            if ($site_label !== '') {
                $tooltip = trim((string) ($entry['site_domain'] ?? '')) ?: $site_label;
                echo $this->render_log_badge($site_label, 'osm-log-badge--site', $tooltip);
            }
            echo '</div>';
            echo '</div>';
            echo '<button type="button" class="button osm-log-copy" data-copy="' . esc_attr((string) $entry['raw']) . '">Copy raw</button>';
            echo '</div>';

            if ($entry['context'] !== null && $entry['context'] !== '') {
                echo '<details class="osm-log-entry__details"><summary>Details</summary><pre>' . esc_html($this->format_log_context($entry['context'])) . '</pre></details>';
            }

            echo '</article>';
        }

        echo '</div>';
        echo '</div>';
        echo '<div id="osm-toast-stack" class="osm-toast-stack" aria-live="polite" aria-atomic="false"></div>';
        $this->render_logs_script();
        echo '</div>';
    }

    private function render_status_badge(string $type, string $status, string $message, string $reference): string
    {
        $status = trim($status);
        $reference = trim($reference);
        $message = trim($message);

        if (in_array($status, ['email_sent', 'crm_sent'], true)) {
            $tooltip = $reference !== '' ? 'Lead ID: ' . $reference : ($message !== '' ? $message : 'Success');
            return '<span class="osm-status-wrap"><span class="osm-status osm-status-success" data-tooltip="' . esc_attr($tooltip) . '" aria-label="' . esc_attr($tooltip) . '">&#10003;</span></span>';
        }

        if (in_array($status, ['email_failed', 'crm_failed', 'email_not_configured', 'email_smtp_not_configured', 'email_from_invalid'], true)) {
            $copy_value = $message !== '' ? $message : 'Unknown error';
            return '<span class="osm-status-wrap"><button type="button" class="osm-status osm-status-failed osm-copyable-error" data-tooltip="' . esc_attr($copy_value) . '" aria-label="' . esc_attr($copy_value) . '" data-copy="' . esc_attr($copy_value) . '"><span aria-hidden="true">&#10005;</span></button></span>';
        }

        $tooltip = $message !== '' ? $message : $status;

        return '<span class="osm-status-wrap"><span class="osm-status osm-status-neutral" data-tooltip="' . esc_attr($tooltip) . '" aria-label="' . esc_attr($tooltip) . '">-</span></span>';
    }

    private function render_payload_toggle(int $lead_id, string $payload_json, string $payload_plain): string
    {
        return sprintf(
            '<button type="button" class="button button-secondary osm-payload-toggle" data-lead-id="%1$d" data-payload-json="%2$s" data-payload-text="%3$s">View payload</button>',
            $lead_id,
            esc_attr($payload_json),
            esc_attr($payload_plain)
        );
    }

    private function format_payload_text(array $payload): string
    {
        $labels = [
            'fullName' => 'name',
            'name' => 'name',
            'phone' => 'tel',
            'email' => 'email',
            'message' => 'message',
            'lossAmount' => 'lossAmount',
            'transferMethod' => 'transferMethod',
            'caseDetails' => 'caseDetails',
            'lossConfirm' => 'lossConfirm',
        ];
        $lines = [];

        foreach ($payload as $key => $value) {
            $label = $labels[(string) $key] ?? (string) $key;
            $string_value = is_array($value)
                ? wp_json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : (string) $value;
            $lines[] = $label . ': ' . $string_value;
        }

        return implode("\n", $lines);
    }

    private function log_level_to_class(string $level): string
    {
        if ($level === 'success') {
            return 'is-success';
        }

        if ($level === 'error') {
            return 'is-error';
        }

        return 'is-neutral';
    }

    private function render_log_badge(string $label, string $class_name, string $tooltip): string
    {
        return sprintf(
            '<span class="osm-log-badge-wrap"><span class="osm-log-badge %1$s" data-tooltip="%2$s" aria-label="%2$s">%3$s</span></span>',
            esc_attr($class_name),
            esc_attr($tooltip),
            esc_html($label)
        );
    }

    private function format_log_context(array|string $context): string
    {
        if (is_array($context)) {
            return (string) wp_json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return $context;
    }

    private function render_leads_styles(): void
    {
        echo '<style>
          .osm-leads-table td { vertical-align: top; }
          .osm-leads-filter-form {
            margin:16px 0 18px;
            display:block;
            width:100%;
            max-width:100%;
          }
          .osm-leads-filter-form select {
            display:block;
            width:100%;
            max-width:100%;
          }
          .osm-empty-state {
            padding:24px 16px;
            text-align:center;
            color:#50575e;
            font-weight:600;
          }
          .osm-status {
            display:inline-flex;
            align-items:center;
            justify-content:center;
            width:26px;
            height:26px;
            border-radius:999px;
            border:0;
            font-size:16px;
            font-weight:700;
            line-height:1;
            cursor:default;
            text-decoration:none;
            padding:0;
            appearance:none;
            -webkit-appearance:none;
            font-family:Arial, sans-serif;
            vertical-align:middle;
          }
          .osm-status-wrap {
            position:relative;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            cursor:pointer;
          }
          .osm-status span {
            display:block;
            line-height:1;
            transform:translateY(-1px);
          }
          .osm-status[data-tooltip]::after {
            content:attr(data-tooltip);
            position:absolute;
            left:50%;
            bottom:calc(100% + 10px);
            transform:translateX(-50%);
            min-width:160px;
            max-width:320px;
            padding:8px 10px;
            border-radius:8px;
            background:#1f2937;
            color:#fff;
            box-shadow:0 12px 32px rgba(0,0,0,.22);
            font-size:12px;
            font-weight:600;
            line-height:1.45;
            text-align:left;
            white-space:normal;
            opacity:0;
            visibility:hidden;
            pointer-events:none;
            z-index:20;
          }
          .osm-status[data-tooltip]::before {
            content:"";
            position:absolute;
            left:50%;
            bottom:calc(100% + 4px);
            transform:translateX(-50%);
            border:6px solid transparent;
            border-top-color:#1f2937;
            opacity:0;
            visibility:hidden;
            pointer-events:none;
            z-index:20;
          }
          .osm-status-wrap:hover .osm-status[data-tooltip]::after,
          .osm-status-wrap:hover .osm-status[data-tooltip]::before,
          .osm-status:focus[data-tooltip]::after,
          .osm-status:focus[data-tooltip]::before {
            opacity:1;
            visibility:visible;
          }
          .osm-status-success {
            background:#dcfce7;
            color:#15803d;
            cursor:pointer;
          }
          .osm-status-failed {
            background:#fee2e2;
            color:#dc2626;
            cursor:pointer;
          }
          .osm-status-neutral {
            background:#e5e7eb;
            color:#4b5563;
            cursor:pointer;
          }
          .osm-status-failed:hover,
          .osm-status-failed:focus {
            color:#b91c1c;
            outline:none;
            box-shadow:0 0 0 2px rgba(220,38,38,.15);
          }
          .osm-leads-loader-panel[hidden],
          .osm-payload-modal[hidden] {
            display:none !important;
          }
          .osm-leads-loader-panel {
            min-height:220px;
            display:flex;
            align-items:center;
            justify-content:center;
          }
          .osm-leads-loader {
            display:inline-flex;
            align-items:center;
            color:#2271b1;
            font-weight:600;
            font-size:16px;
          }
          .osm-leads-loader::before {
            content:"";
            width:16px;
            height:16px;
            margin-right:8px;
            border:2px solid rgba(34,113,177,.2);
            border-top-color:#2271b1;
            border-radius:50%;
            animation: osmSpin .8s linear infinite;
          }
          .osm-payload-modal {
            position:fixed;
            inset:0;
            z-index:99998;
          }
          .osm-payload-modal__backdrop {
            position:absolute;
            inset:0;
            background:rgba(15, 23, 42, .58);
          }
          .osm-payload-modal__dialog {
            position:relative;
            z-index:1;
            width:min(760px, calc(100vw - 32px));
            max-height:calc(100vh - 48px);
            margin:24px auto;
            background:#fff;
            border-radius:16px;
            box-shadow:0 24px 80px rgba(15, 23, 42, .28);
            overflow:hidden;
          }
          .osm-payload-modal__header {
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:16px;
            padding:20px 24px;
            border-bottom:1px solid #dcdcde;
          }
          .osm-payload-modal__header h2 {
            margin:0;
            font-size:24px;
            line-height:1.2;
          }
          .osm-payload-modal__close {
            display:inline-flex;
            align-items:center;
            justify-content:center;
            width:36px;
            height:36px;
            padding:0;
            border:0;
            border-radius:999px;
            background:#f3f4f6;
            color:#111827;
            font-size:26px;
            line-height:1;
            cursor:pointer;
          }
          .osm-payload-modal__actions {
            display:flex;
            gap:10px;
            padding:16px 24px 0;
          }
          .osm-payload-modal__content {
            margin:0;
            padding:20px 24px 24px;
            max-height:calc(100vh - 220px);
            overflow:auto;
            white-space:pre-wrap;
            word-break:break-word;
            font-size:13px;
            line-height:1.6;
            font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            background:#fff;
          }
          @keyframes osmSpin {
            to { transform: rotate(360deg); }
          }
        </style>';
        $this->render_toast_styles();
    }

    private function render_leads_script(): void
    {
        ?>
        <script>
        (() => {
          const toastStack = document.getElementById('osm-toast-stack');
          const filter = document.getElementById('osm-site-filter');
          const filterForm = document.getElementById('osm-leads-filter-form');
          const loader = document.getElementById('osm-leads-loader');
          const tableWrap = document.getElementById('osm-leads-table-wrap');
          const payloadModal = document.getElementById('osm-payload-modal');
          const payloadModalContent = document.getElementById('osm-payload-modal-content');
          const copyJsonButton = document.getElementById('osm-copy-json');
          const copyTextButton = document.getElementById('osm-copy-text');
          let currentPayloadJson = '';
          let currentPayloadText = '';

          function showToast(message, type = 'success') {
            if (!toastStack) return;

            const toast = document.createElement('div');
            toast.className = 'osm-copy-toast' + (type === 'error' ? ' is-error' : '');
            toast.innerHTML = '<span class="osm-copy-toast__text"></span><button type="button" class="osm-copy-toast__close" aria-label="Close alert">&times;</button>';
            toast.querySelector('.osm-copy-toast__text').textContent = message;

            const removeToast = () => {
              if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
              }
            };

            toast.querySelector('.osm-copy-toast__close').addEventListener('click', removeToast);
            toastStack.appendChild(toast);
            window.setTimeout(removeToast, 2600);
          }

          function setLoadingState(isLoading) {
            if (loader) {
              loader.hidden = !isLoading;
            }

            if (tableWrap) {
              tableWrap.hidden = isLoading;
            }
          }

          async function copyText(text) {
            if (navigator.clipboard && window.isSecureContext) {
              await navigator.clipboard.writeText(text);
              return;
            }

            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.setAttribute('readonly', '');
            textarea.style.position = 'absolute';
            textarea.style.left = '-9999px';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            textarea.remove();
          }

          function openPayloadModal(payloadJson, payloadText) {
            if (!payloadModal || !payloadModalContent) return;

            currentPayloadJson = payloadJson || '';
            currentPayloadText = payloadText || '';
            payloadModalContent.textContent = currentPayloadJson;
            payloadModal.hidden = false;
            document.body.style.overflow = 'hidden';
          }

          function closePayloadModal() {
            if (!payloadModal) return;
            payloadModal.hidden = true;
            document.body.style.overflow = '';
          }

          document.querySelectorAll('.osm-copyable-error').forEach((button) => {
            button.addEventListener('click', async () => {
              const value = button.dataset.copy || '';

              if (!value) return;

              try {
                await copyText(value);
                showToast('Copied to clipboard');
              } catch (error) {
                showToast('Unable to copy', 'error');
              }
            });
          });

          document.querySelectorAll('.osm-payload-toggle').forEach((button) => {
            button.addEventListener('click', () => {
              openPayloadModal(button.dataset.payloadJson || '', button.dataset.payloadText || '');
            });
          });

          document.querySelectorAll('[data-osm-modal-close]').forEach((element) => {
            element.addEventListener('click', closePayloadModal);
          });

          document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
              closePayloadModal();
            }
          });

          if (copyJsonButton) {
            copyJsonButton.addEventListener('click', async () => {
              try {
                await copyText(currentPayloadJson);
                showToast('JSON copied to clipboard');
              } catch (error) {
                showToast('Unable to copy JSON', 'error');
              }
            });
          }

          if (copyTextButton) {
            copyTextButton.addEventListener('click', async () => {
              try {
                await copyText(currentPayloadText);
                showToast('Text copied to clipboard');
              } catch (error) {
                showToast('Unable to copy text', 'error');
              }
            });
          }

          setLoadingState(false);
          window.addEventListener('pageshow', () => setLoadingState(false));

          if (filter && filterForm) {
            filter.addEventListener('change', () => {
              setLoadingState(true);
              filterForm.submit();
            });
          }
        })();
        </script>
        <?php
    }

    private function render_logs_styles(): void
    {
        echo '<style>
          .osm-logs-toolbar {
            display:flex;
            align-items:flex-start;
            justify-content:space-between;
            gap:16px;
            margin:18px 0 20px;
            padding:18px 20px;
            border:1px solid #dcdcde;
            border-radius:14px;
            background:#fff;
          }
          .osm-logs-filter-form {
            display:flex;
            flex:1 1 auto;
            flex-wrap:wrap;
            gap:12px;
            align-items:center;
            justify-content:flex-end;
          }
          .osm-logs-filter-form select {
            min-width:180px;
          }
          .osm-logs-meta {
            color:#1d2327;
            line-height:1.6;
          }
          .osm-logs-empty {
            padding:28px 20px;
            border:1px solid #dcdcde;
            border-radius:14px;
            background:#fff;
            color:#50575e;
            font-weight:600;
            text-align:center;
          }
          .osm-leads-loader-panel[hidden] {
            display:none !important;
          }
          .osm-leads-loader-panel {
            min-height:220px;
            display:flex;
            align-items:center;
            justify-content:center;
          }
          .osm-leads-loader {
            display:inline-flex;
            align-items:center;
            color:#2271b1;
            font-weight:600;
            font-size:16px;
          }
          .osm-leads-loader::before {
            content:"";
            width:16px;
            height:16px;
            margin-right:8px;
            border:2px solid rgba(34,113,177,.2);
            border-top-color:#2271b1;
            border-radius:50%;
            animation: osmSpin .8s linear infinite;
          }
          .osm-logs-list {
            display:grid;
            gap:14px;
          }
          .osm-log-entry {
            padding:18px 20px;
            border:1px solid #dcdcde;
            border-left-width:4px;
            border-radius:14px;
            background:#fff;
            box-shadow:0 6px 18px rgba(15, 23, 42, .04);
          }
          .osm-log-entry.is-error { border-left-color:#dc2626; }
          .osm-log-entry.is-warning { border-left-color:#d97706; }
          .osm-log-entry.is-success { border-left-color:#16a34a; }
          .osm-log-entry.is-neutral { border-left-color:#64748b; }
          .osm-log-entry__head {
            display:flex;
            align-items:flex-start;
            justify-content:space-between;
            gap:16px;
          }
          .osm-log-entry__time {
            color:#64748b;
            font-size:12px;
            font-weight:700;
            letter-spacing:.02em;
            text-transform:uppercase;
          }
          .osm-log-entry__message {
            margin-top:6px;
            color:#111827;
            font-size:16px;
            font-weight:700;
            line-height:1.45;
          }
          .osm-log-entry__badges {
            display:flex;
            flex-wrap:wrap;
            gap:8px;
            margin-top:12px;
          }
          .osm-log-badge-wrap {
            position:relative;
            display:inline-flex;
          }
          .osm-log-badge {
            display:inline-flex;
            align-items:center;
            min-height:28px;
            padding:4px 10px;
            border-radius:999px;
            font-size:12px;
            font-weight:700;
            line-height:1.2;
          }
          .osm-log-badge--success {
            background:#dcfce7;
            color:#15803d;
          }
          .osm-log-badge--info,
          .osm-log-badge--site {
            background:#e0f2fe;
            color:#0369a1;
          }
          .osm-log-badge--error {
            background:#fee2e2;
            color:#dc2626;
          }
          .osm-log-badge[data-tooltip]::after {
            content:attr(data-tooltip);
            position:absolute;
            left:50%;
            bottom:calc(100% + 10px);
            transform:translateX(-50%);
            min-width:160px;
            max-width:320px;
            padding:8px 10px;
            border-radius:8px;
            background:#1f2937;
            color:#fff;
            box-shadow:0 12px 32px rgba(0,0,0,.22);
            font-size:12px;
            font-weight:600;
            line-height:1.45;
            text-align:left;
            white-space:normal;
            opacity:0;
            visibility:hidden;
            pointer-events:none;
            z-index:20;
          }
          .osm-log-badge[data-tooltip]::before {
            content:"";
            position:absolute;
            left:50%;
            bottom:calc(100% + 4px);
            transform:translateX(-50%);
            border:6px solid transparent;
            border-top-color:#1f2937;
            opacity:0;
            visibility:hidden;
            pointer-events:none;
            z-index:20;
          }
          .osm-log-badge-wrap:hover .osm-log-badge[data-tooltip]::after,
          .osm-log-badge-wrap:hover .osm-log-badge[data-tooltip]::before,
          .osm-log-badge:focus[data-tooltip]::after,
          .osm-log-badge:focus[data-tooltip]::before {
            opacity:1;
            visibility:visible;
          }
          .osm-log-entry__details {
            margin-top:14px;
          }
          .osm-log-entry__details summary {
            cursor:pointer;
            color:#2271b1;
            font-weight:600;
          }
          .osm-log-entry__details pre {
            margin:12px 0 0;
            padding:14px 16px;
            overflow:auto;
            border-radius:12px;
            background:#0f172a;
            color:#e2e8f0;
            font-size:12px;
            line-height:1.6;
          }
          @media (max-width: 782px) {
            .osm-logs-toolbar,
            .osm-log-entry__head {
              display:block;
            }
            .osm-logs-filter-form {
              justify-content:stretch;
              margin-top:12px;
            }
            .osm-logs-filter-form select {
              width:100%;
            }
            .osm-log-copy {
              margin-top:12px !important;
            }
          }
          @keyframes osmSpin {
            to { transform: rotate(360deg); }
          }
        </style>';
        $this->render_toast_styles();
    }

    private function render_toast_styles(): void
    {
        echo '<style>
          .osm-toast-stack {
            position:fixed;
            right:24px;
            bottom:24px;
            z-index:99999;
            display:flex;
            flex-direction:column;
            align-items:flex-end;
            gap:10px;
            pointer-events:none;
          }
          .osm-copy-toast {
            display:flex;
            align-items:center;
            gap:12px;
            padding:12px 16px;
            border-radius:10px;
            background:#16a34a;
            color:#fff;
            box-shadow:0 12px 30px rgba(0,0,0,.18);
            font-weight:600;
            pointer-events:auto;
            min-width:260px;
            max-width:min(420px, calc(100vw - 48px));
          }
          .osm-copy-toast__text {
            flex:1 1 auto;
          }
          .osm-copy-toast__close {
            flex:0 0 auto;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            width:24px;
            height:24px;
            padding:0;
            border:0;
            border-radius:999px;
            background:rgba(255,255,255,.16);
            color:#fff;
            font-size:16px;
            line-height:1;
            cursor:pointer;
          }
          .osm-copy-toast.is-error {
            background:#dc2626;
          }
        </style>';
    }

    private function render_logs_script(): void
    {
        ?>
        <script>
        (() => {
          const toastStack = document.getElementById('osm-toast-stack');
          const filterForm = document.getElementById('osm-logs-filter-form');
          const loader = document.getElementById('osm-logs-loader');
          const listWrap = document.getElementById('osm-logs-list-wrap');
          const siteFilter = document.getElementById('osm-logs-site-filter');
          const levelFilter = document.getElementById('osm-logs-level-filter');

          function showToast(message, type = 'success') {
            if (!toastStack) return;

            const toast = document.createElement('div');
            toast.className = 'osm-copy-toast' + (type === 'error' ? ' is-error' : '');
            toast.innerHTML = '<span class="osm-copy-toast__text"></span><button type="button" class="osm-copy-toast__close" aria-label="Close alert">&times;</button>';
            toast.querySelector('.osm-copy-toast__text').textContent = message;

            const removeToast = () => {
              if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
              }
            };

            toast.querySelector('.osm-copy-toast__close').addEventListener('click', removeToast);
            toastStack.appendChild(toast);
            window.setTimeout(removeToast, 2600);
          }

          function setLoadingState(isLoading) {
            if (loader) {
              loader.hidden = !isLoading;
            }

            if (listWrap) {
              listWrap.hidden = isLoading;
            }
          }

          async function copyText(text) {
            if (navigator.clipboard && window.isSecureContext) {
              await navigator.clipboard.writeText(text);
              return;
            }

            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.setAttribute('readonly', '');
            textarea.style.position = 'absolute';
            textarea.style.left = '-9999px';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            textarea.remove();
          }

          document.querySelectorAll('.osm-log-copy').forEach((button) => {
            button.addEventListener('click', async () => {
              try {
                await copyText(button.dataset.copy || '');
                showToast('Log line copied');
              } catch (error) {
                showToast('Unable to copy log line', 'error');
              }
            });
          });

          setLoadingState(false);
          window.addEventListener('pageshow', () => setLoadingState(false));

          [siteFilter, levelFilter].forEach((element) => {
            if (!element || !filterForm) return;

            element.addEventListener('change', () => {
              setLoadingState(true);
              filterForm.submit();
            });
          });
        })();
        </script>
        <?php
    }
}
