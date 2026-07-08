<?php
/**
 * Plugin Name: Ontario Bootstrap
 * Description: Sets the Ontario theme automatically on a fresh install.
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

const ONTARIO_BOOTSTRAP_VERSION = '1.1.0';

function ontario_bootstrap_legal_pages(): void
{
    $pages = [
        'termsandconditions' => [
            'post_title' => 'Terms & Conditions',
            'post_content' => <<<'HTML'
<p><strong>Effective Date:</strong> 01.01.2026</p>
<p>Welcome to <strong>{company_name}</strong> ("we," "our," or "us").</p>
<p>These Terms and Conditions ("Terms") govern your use of our website, {domain}, and any services provided by us. By accessing or using our site and services, you agree to be bound by these Terms.</p>
<p>If you do not agree with any part of these Terms, you must not use our website or services.</p>
<h3>1. Our Services</h3>
<p>{company_name} provides assistance and guidance to individuals and organizations who have been victims of online scams and frauds. Our services may include:</p>
<ul>
  <li>Scam consultation and case evaluation</li>
  <li>Guidance on recovery actions</li>
  <li>Communication support with relevant authorities or financial institutions</li>
  <li>Referral to legal or investigative partners (when applicable)</li>
</ul>
<h3>2. No Guarantee of Fund Recovery</h3>
<p><strong>IMPORTANT NOTICE:</strong></p>
<p>While we strive to assist victims to the best of our ability, we do not and cannot guarantee a full or partial recovery of lost funds. Many scam-related losses are difficult or impossible to recover due to jurisdictional limitations, anonymity of perpetrators, and other legal or technical constraints.</p>
<p>You acknowledge and accept that using our services does not ensure successful recovery.</p>
<h3>3. User Responsibilities</h3>
<p>By using our services, you agree to:</p>
<ul>
  <li>Provide accurate, complete, and honest information</li>
  <li>Not misuse or abuse our services</li>
  <li>Cooperate fully with our team or any third parties involved in your case</li>
  <li>Abide by all applicable laws and regulations</li>
</ul>
<h3>4. Eligibility</h3>
<p>To use our services, you must:</p>
<ul>
  <li>Be at least 18 years old or have parental/guardian consent</li>
  <li>Be legally competent to enter into a binding agreement</li>
</ul>
<h3>5. Payment and Fees</h3>
<p>Some of our services may be free, while others may require payment. You will be informed of any costs before service delivery. All payments are final unless otherwise agreed in writing.</p>
<h3>6. Intellectual Property</h3>
<p>All content on our website, including text, graphics, logos, and other materials, is the property of {company_name} or its content providers and is protected by intellectual property laws. You may not reproduce, distribute, or use any content without written permission.</p>
<h3>7. Limitation of Liability</h3>
<p>To the fullest extent permitted by law, {company_name} is not liable for any direct, indirect, incidental, or consequential damages resulting from:</p>
<ul>
  <li>Use or misuse of our website or services</li>
  <li>Failure to recover funds or achieve desired outcomes</li>
  <li>Delays, errors, or interruptions in service</li>
</ul>
<p>Our liability, if any, shall be limited to the amount paid for our services.</p>
<h3>8. Third-Party Services and Referrals</h3>
<p>We may refer you to trusted third parties such as legal experts or investigators. We do not control these third parties and are not responsible for their actions, advice, or results.</p>
<h3>9. Changes to Terms</h3>
<p>We may update these Terms at any time. The revised version will be posted on this page with an updated effective date. Continued use of our services means you accept the updated Terms.</p>
<h3>10. Termination</h3>
<p>We reserve the right to terminate or suspend access to our website or services at any time, with or without notice, for any reason including violation of these Terms.</p>
<h3>11. Contact Us</h3>
<p>For questions about these Terms, please contact:</p>
<p>{company_name}<br />Website: {domain}<br />Email: {email}<br />Phone: {phone}<br />Address: {address}</p>
HTML,
        ],
        'privacypolicy' => [
            'post_title' => 'Privacy Policy',
            'post_content' => <<<'HTML'
<p><strong>Effective Date:</strong> 01.01.2026</p>
<p>Welcome to <strong>{company_name}</strong> ("we," "our," or "us"). Your privacy is important to us. This Privacy Policy describes how we collect, use, disclose, and protect your personal information when you use our website, {domain}, and our scam recovery services.</p>
<h3>1. Information We Collect.</h3>
<p>We may collect and process the following types of personal data:</p>
<p><strong>a. Information You Provide to Us:</strong></p>
<ul>
  <li>Full name</li>
  <li>Email address</li>
  <li>Phone number</li>
  <li>Details about your scam incident (e.g., dates, amounts lost, parties involved)</li>
  <li>Documents or evidence you choose to upload</li>
</ul>
<p><strong>b. Information We Collect Automatically:</strong></p>
<ul>
  <li>IP address</li>
  <li>Browser type and version</li>
  <li>Operating system</li>
  <li>Pages visited and time spent on the site</li>
  <li>Cookies and similar tracking technologies</li>
</ul>
<h3>2. How We Use Your Information.</h3>
<p>We use your information for the following purposes:</p>
<ul>
  <li>To provide scam recovery advice and services</li>
  <li>To contact you regarding your case</li>
  <li>To improve our services and website</li>
  <li>To comply with legal obligations</li>
  <li>For internal record keeping</li>
</ul>
<h3>3. Legal Basis for Processing.</h3>
<p>We process your personal data based on:</p>
<ul>
  <li>Consent - When you provide us with personal data voluntarily</li>
  <li>Contract - To fulfill our agreement to assist with your case</li>
  <li>Legal obligation - When required to comply with laws</li>
  <li>Legitimate interest - To ensure service quality and security</li>
</ul>
<h3>4. Sharing Your Information.</h3>
<p>We do not sell or rent your data. We may share your information with:</p>
<ul>
  <li>Law enforcement or regulatory bodies upon request or legal obligation</li>
  <li>Service providers who assist in our operations (e.g., hosting, communication tools)</li>
  <li>Legal professionals or partner investigators involved in your case, with your consent</li>
</ul>
<p>All third parties are required to protect your data and use it only for the agreed purpose.</p>
<h3>5. Data Retention.</h3>
<p>We retain your personal information only as long as necessary:</p>
<ul>
  <li>To fulfill the purposes outlined in this policy</li>
  <li>To comply with legal obligations</li>
  <li>To resolve disputes and enforce agreements</li>
</ul>
<h3>6. Your Rights.</h3>
<p>Under GDPR and other applicable laws, you have the right to:</p>
<ul>
  <li>Access your personal data</li>
  <li>Request correction or deletion</li>
  <li>Withdraw consent at any time</li>
  <li>Object to or restrict processing</li>
  <li>Data portability (in some cases)</li>
  <li>File a complaint with a data protection authority</li>
</ul>
<p>To exercise these rights, contact us at: {email}</p>
<h3>7. Security of Your Data.</h3>
<p>We take reasonable administrative, technical, and physical measures to safeguard your information against loss, theft, and unauthorized use or access.</p>
<h3>8. Cookies</h3>
<p>Our website uses cookies to improve user experience and analyze website usage. You can control cookie preferences through your browser settings.</p>
<h3>9. International Data Transfers.</h3>
<p>If you are located outside of Canada, your information may be transferred to and processed in Canada or other countries with different data protection laws.</p>
<h3>10. Third-Party Links.</h3>
<p>Our website may contain links to third-party sites. We are not responsible for the privacy practices of these websites.</p>
<h3>11. Changes to This Policy.</h3>
<p>We may update this Privacy Policy from time to time. Changes will be posted on this page with an updated effective date.</p>
<h3>12. Contact Us.</h3>
<p>If you have any questions or concerns about this Privacy Policy, please contact:</p>
<p>{company_name}<br />Website: {domain}<br />{address}<br />{phone}<br />{email}</p>
HTML,
        ],
    ];

    foreach ($pages as $slug => $page) {
        $existing = get_page_by_path($slug, OBJECT, 'page');

        $postarr = [
            'post_title' => $page['post_title'],
            'post_name' => $slug,
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_content' => $page['post_content'],
        ];

        if ($existing instanceof WP_Post) {
            $existing_content = (string) $existing->post_content;
            $is_legacy_template = str_contains($existing_content, 'Ontario Refunds')
                || str_contains($existing_content, 'ontariorefunds.info')
                || str_contains($existing_content, 'support@ontariorefunds.info');

            if (! $is_legacy_template) {
                continue;
            }

            $postarr['ID'] = $existing->ID;
            wp_update_post($postarr);
            continue;
        }

        wp_insert_post($postarr);
    }
}

function ontario_bootstrap_hidden_admin_pages(): array
{
    return [
        'index.php',
        'edit-comments.php',
        'tools.php',
        'themes.php',
    ];
}

function ontario_bootstrap_preview_url(int $site_id): string
{
    $args = [
        'ontario_preview_site' => $site_id,
    ];

    if (class_exists('OSM_Current_Site')) {
        $args['ontario_preview_token'] = OSM_Current_Site::preview_token($site_id);
    }

    return add_query_arg($args, home_url('/'));
}

add_action('init', static function (): void {
    if (get_option('ontario_theme_bootstrapped')) {
        return;
    }

    $theme = wp_get_theme('Ontario');

    if (! $theme->exists()) {
        return;
    }

    switch_theme('Ontario');
    update_option('ontario_theme_bootstrapped', 1, true);
}, 20);

add_action('init', static function (): void {
    $installed_version = get_option('ontario_bootstrap_version');

    if ($installed_version === ONTARIO_BOOTSTRAP_VERSION) {
        return;
    }

    if (get_option('permalink_structure') !== '/%postname%/') {
        update_option('permalink_structure', '/%postname%/');
    }

    ontario_bootstrap_legal_pages();
    flush_rewrite_rules(false);
    update_option('ontario_bootstrap_version', ONTARIO_BOOTSTRAP_VERSION, true);
}, 25);

add_action('admin_menu', static function (): void {
    remove_menu_page('index.php');
    remove_menu_page('edit.php');
    remove_menu_page('edit-comments.php');
    remove_menu_page('tools.php');
    remove_menu_page('themes.php');
    remove_submenu_page('edit.php?post_type=ontario_site', 'post-new.php?post_type=ontario_site');
}, 999);

add_action('wp_before_admin_bar_render', static function (): void {
    global $wp_admin_bar;

    if (! $wp_admin_bar instanceof WP_Admin_Bar) {
        return;
    }

    $wp_admin_bar->remove_node('dashboard');
    $wp_admin_bar->remove_node('comments');
    $wp_admin_bar->remove_node('new-post');
    $wp_admin_bar->remove_node('wp-logo');
    $wp_admin_bar->remove_node('customize');
    $wp_admin_bar->remove_node('new-content');
    $wp_admin_bar->remove_node('themes');
    $wp_admin_bar->remove_node('plugins');
}, 999);

add_action('admin_bar_menu', static function (WP_Admin_Bar $wp_admin_bar): void {
    if (is_admin() || ! current_user_can('manage_options')) {
        return;
    }

    $sites_post_type = class_exists('OSM_Sites') ? OSM_Sites::post_type() : 'ontario_site';
    $current_site = function_exists('ontario_current_site') ? ontario_current_site() : [];
    $current_site_id = (int) ($current_site['id'] ?? 0);
    $current_label = trim((string) ($current_site['company_name'] ?? ''));

    if ($current_label === '') {
        $current_label = trim((string) ($current_site['post_title'] ?? 'Ontario Site'));
    }

    $wp_admin_bar->add_node([
        'id' => 'site-name',
        'title' => 'Dashboard',
        'href' => admin_url('edit.php?post_type=' . $sites_post_type),
    ]);

    if ($current_site_id > 0) {
        $wp_admin_bar->add_node([
            'id' => 'ontario-edit-current-site',
            'title' => '<span class="ab-icon dashicons dashicons-edit"></span><span class="ab-label">Edit This Site</span>',
            'href' => admin_url('post.php?post=' . $current_site_id . '&action=edit'),
        ]);
    }

    $wp_admin_bar->add_node([
        'id' => 'ontario-current-site',
        'title' => '<span class="ab-icon dashicons dashicons-admin-site-alt3"></span><span class="ab-label">' . esc_html($current_label) . '</span>',
        'href' => $current_site_id > 0 ? ontario_bootstrap_preview_url($current_site_id) : home_url('/'),
    ]);

    $posts = get_posts([
        'post_type' => $sites_post_type,
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
    ]);

    foreach ($posts as $post) {
        $label = get_post_meta($post->ID, '_osm_company_name', true);

        if (! is_string($label) || trim($label) === '') {
            $label = $post->post_title;
        }

        $title = ((int) $post->ID === $current_site_id ? '&#10003; ' : '') . esc_html($label);

        $wp_admin_bar->add_node([
            'id' => 'ontario-preview-site-' . (int) $post->ID,
            'parent' => 'ontario-current-site',
            'title' => $title,
            'href' => ontario_bootstrap_preview_url((int) $post->ID),
        ]);
    }
}, 90);

add_action('wp_head', static function (): void {
    if (! is_user_logged_in() || ! is_admin_bar_showing()) {
        return;
    }

    echo '<style>
      #wpadminbar #wp-admin-bar-ontario-edit-current-site > .ab-item,
      #wpadminbar #wp-admin-bar-ontario-current-site > .ab-item {
        display:flex;
        align-items:center;
        gap:6px;
      }
      #wpadminbar #wp-admin-bar-ontario-edit-current-site .ab-icon,
      #wpadminbar #wp-admin-bar-ontario-current-site .ab-icon {
        position:static;
        top:auto;
        float:none;
        width:auto;
        height:auto;
        margin:0;
        padding:0;
        line-height:1;
      }
      #wpadminbar #wp-admin-bar-ontario-edit-current-site .ab-label,
      #wpadminbar #wp-admin-bar-ontario-current-site .ab-label {
        display:inline-block;
      }
    </style>';
}, 100);

add_action('wp_dashboard_setup', static function (): void {
    remove_meta_box('dashboard_site_health', 'dashboard', 'normal');
    remove_meta_box('dashboard_right_now', 'dashboard', 'normal');
    remove_meta_box('dashboard_activity', 'dashboard', 'normal');
    remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
    remove_meta_box('dashboard_primary', 'dashboard', 'side');
    remove_meta_box('dashboard_recent_drafts', 'dashboard', 'side');
}, 999);

add_action('admin_init', static function (): void {
    global $pagenow;
    $sites_post_type = class_exists('OSM_Sites') ? OSM_Sites::post_type() : 'ontario_site';

    if (! is_admin() || ! is_string($pagenow)) {
        return;
    }

    if ($pagenow === 'edit.php') {
        $post_type = isset($_GET['post_type']) ? sanitize_key(wp_unslash($_GET['post_type'])) : 'post';

        if ($post_type === 'post') {
            wp_safe_redirect(admin_url('edit.php?post_type=' . $sites_post_type));
            exit;
        }

        return;
    }

    if (in_array($pagenow, ontario_bootstrap_hidden_admin_pages(), true)) {
        wp_safe_redirect(admin_url('edit.php?post_type=' . $sites_post_type));
        exit;
    }
}, 1);
