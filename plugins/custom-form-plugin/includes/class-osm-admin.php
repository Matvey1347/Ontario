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
        echo '<form id="osm-leads-filter-form" method="get" style="margin:16px 0 18px; display:flex; gap:10px; align-items:center;">';
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
        echo '<span id="osm-leads-loader" class="osm-leads-loader" hidden aria-live="polite">Loading...</span>';
        echo '</form>';

        $this->render_leads_styles();
        echo '<table class="widefat fixed striped osm-leads-table"><thead><tr>';
        echo '<th>ID</th><th>Site</th><th>Form</th><th>Name</th><th>Email</th><th>Phone</th><th>Created</th><th>Email</th><th>CRM</th><th>Payload</th>';
        echo '</tr></thead><tbody>';

        if ($items === []) {
            echo '<tr><td colspan="10" class="osm-empty-state">No leads captured yet.</td></tr>';
        } else {
            foreach ($items as $item) {
                $payload = json_decode((string) $item['payload'], true);
                $payload_text = is_array($payload)
                    ? esc_html(wp_json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
                    : esc_html((string) $item['payload']);

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
                echo '<td>' . $this->render_payload_toggle((int) $item['id'], $payload_text) . '</td>';
                echo '</tr>';
            }
        }

        echo '</tbody></table>';
        echo '<div id="osm-copy-toast" class="osm-copy-toast" hidden>Copied to clipboard</div>';
        $this->render_leads_script();
        echo '</div>';
    }

    public function render_notices(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        if (! OSM_Crypto::is_available()) {
            echo '<div class="notice notice-warning"><p>Site Manager: OpenSSL is unavailable. CRM secrets are stored in fallback mode.</p></div>';
        }
    }

    private function render_status_badge(string $type, string $status, string $message, string $reference): string
    {
        $status = trim($status);
        $reference = trim($reference);
        $message = trim($message);

        if (in_array($status, ['email_sent', 'crm_sent'], true)) {
            $tooltip = $reference !== '' ? 'Lead ID: ' . $reference : ($message !== '' ? $message : 'Success');
            return '<span class="osm-status osm-status-success" title="' . esc_attr($tooltip) . '" aria-label="' . esc_attr($tooltip) . '">&#10003;</span>';
        }

        if (in_array($status, ['email_failed', 'crm_failed'], true)) {
            $copy_value = $message !== '' ? $message : 'Unknown error';
            return '<button type="button" class="osm-status osm-status-failed osm-copyable-error" title="' . esc_attr($copy_value) . '" aria-label="' . esc_attr($copy_value) . '" data-copy="' . esc_attr($copy_value) . '"><span aria-hidden="true">&#10005;</span></button>';
        }

        $tooltip = $message !== '' ? $message : $status;

        return '<span class="osm-status osm-status-neutral" title="' . esc_attr($tooltip) . '" aria-label="' . esc_attr($tooltip) . '">-</span>';
    }

    private function render_payload_toggle(int $lead_id, string $payload_text): string
    {
        $target_id = 'osm-payload-' . $lead_id;

        return sprintf(
            '<button type="button" class="button button-secondary osm-payload-toggle" data-target="%1$s" aria-expanded="false">View payload</button><div id="%1$s" class="osm-payload-box" hidden><pre>%2$s</pre></div>',
            esc_attr($target_id),
            $payload_text
        );
    }

    private function render_leads_styles(): void
    {
        echo '<style>
          .osm-leads-table td { vertical-align: top; }
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
          .osm-status span {
            display:block;
            line-height:1;
            transform:translateY(-1px);
          }
          .osm-status-success {
            background:#dcfce7;
            color:#15803d;
          }
          .osm-status-failed {
            background:#fee2e2;
            color:#dc2626;
            cursor:pointer;
          }
          .osm-status-neutral {
            background:#e5e7eb;
            color:#4b5563;
          }
          .osm-status-failed:hover,
          .osm-status-failed:focus {
            color:#b91c1c;
            outline:none;
            box-shadow:0 0 0 2px rgba(220,38,38,.15);
          }
          .osm-leads-loader {
            display:inline-flex;
            align-items:center;
            color:#2271b1;
            font-weight:600;
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
          .osm-payload-box {
            margin-top:10px;
            max-width:420px;
            padding:10px 12px;
            border:1px solid #dcdcde;
            border-radius:8px;
            background:#f8f9fa;
          }
          .osm-payload-toggle[aria-expanded="true"] {
            background:#2271b1;
            border-color:#2271b1;
            color:#fff;
          }
          .osm-payload-box pre {
            margin:0;
            white-space:pre-wrap;
            word-break:break-word;
            font-size:12px;
            line-height:1.55;
          }
          .osm-copy-toast {
            position:fixed;
            right:24px;
            bottom:24px;
            z-index:99999;
            padding:12px 16px;
            border-radius:10px;
            background:#16a34a;
            color:#fff;
            box-shadow:0 12px 30px rgba(0,0,0,.18);
            font-weight:600;
          }
          .osm-copy-toast.is-error {
            background:#dc2626;
          }
          @keyframes osmSpin {
            to { transform: rotate(360deg); }
          }
        </style>';
    }

    private function render_leads_script(): void
    {
        ?>
        <script>
        (() => {
          const toast = document.getElementById('osm-copy-toast');
          const filter = document.getElementById('osm-site-filter');
          const filterForm = document.getElementById('osm-leads-filter-form');
          const loader = document.getElementById('osm-leads-loader');
          let toastTimer = null;

          function showToast(message, type = 'success') {
            if (!toast) return;
            toast.textContent = message;
            toast.classList.toggle('is-error', type === 'error');
            toast.hidden = false;
            window.clearTimeout(toastTimer);
            toastTimer = window.setTimeout(() => {
              toast.hidden = true;
              toast.classList.remove('is-error');
            }, 2200);
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

          document.querySelectorAll('.osm-copyable-error').forEach((button) => {
            button.addEventListener('click', async () => {
              const value = button.dataset.copy || '';

              if (!value) return;

              try {
                await copyText(value);
                showToast('Error copied to clipboard', 'error');
              } catch (error) {
                showToast('Unable to copy error', 'error');
              }
            });
          });

          document.querySelectorAll('.osm-payload-toggle').forEach((button) => {
            button.addEventListener('click', () => {
              const target = document.getElementById(button.dataset.target || '');
              if (!target) return;

              const expanded = button.getAttribute('aria-expanded') === 'true';
              button.setAttribute('aria-expanded', expanded ? 'false' : 'true');
              button.textContent = expanded ? 'View payload' : 'Hide payload';
              target.hidden = expanded;
            });
          });

          if (filter && filterForm) {
            filter.addEventListener('change', () => {
              if (loader) {
                loader.hidden = false;
              }
              filterForm.submit();
            });
          }
        })();
        </script>
        <?php
    }
}
