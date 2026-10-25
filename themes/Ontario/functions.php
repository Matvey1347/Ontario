<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

if (! function_exists('ontario_current_site')) {
    function ontario_current_site(): array
    {
        return [];
    }
}

if (! function_exists('ontario_site_field')) {
    function ontario_site_field(string $key, string $default = ''): string
    {
        return $default;
    }
}

if (! function_exists('ontario_site_logo_url')) {
    function ontario_site_logo_url(): string
    {
        return '';
    }
}

if (! function_exists('ontario_site_brand_name')) {
    function ontario_site_brand_name(): string
    {
        $name = get_bloginfo('name');

        return is_string($name) && $name !== '' ? $name : 'Ontario Refunds';
    }
}

if (! function_exists('ontario_replace_site_tokens')) {
    function ontario_replace_site_tokens(string $content): string
    {
        return $content;
    }
}

if (! function_exists('ontario_render_tracking_code')) {
    function ontario_render_tracking_code(string $location = 'head'): string
    {
        return '';
    }
}

if (! function_exists('ontario_render_success_tracking_code')) {
    function ontario_render_success_tracking_code(): string
    {
        return '';
    }
}

if (! function_exists('ontario_current_language')) {
    function ontario_current_language(): string
    {
        return 'en';
    }
}

if (! function_exists('ontario_available_languages')) {
    function ontario_available_languages(): array
    {
        return [
            [
                'code' => 'en',
                'name' => 'English',
                'native_name' => 'English',
                'flag' => '🇬🇧',
            ],
        ];
    }
}

if (! function_exists('ontario_enabled_languages')) {
    function ontario_enabled_languages(): array
    {
        return ontario_available_languages();
    }
}

if (! function_exists('ontario_language_switch_url')) {
    function ontario_language_switch_url(string $language): string
    {
        return add_query_arg('ontario_lang', sanitize_key($language), home_url(add_query_arg([])));
    }
}

if (! function_exists('ontario_phone_country_selector_enabled')) {
    function ontario_phone_country_selector_enabled(): bool
    {
        return false;
    }
}

if (! function_exists('ontario_phone_countries')) {
    function ontario_phone_countries(): array
    {
        return [
            ['iso2' => 'CA', 'name' => 'Canada', 'dial_code' => '+1', 'flag' => '🇨🇦'],
        ];
    }
}

if (! function_exists('ontario_t')) {
    function ontario_t(string $key, array $replacements = [], string $fallback = ''): string
    {
        $value = $fallback !== '' ? $fallback : $key;

        if ($replacements !== []) {
            $normalized = [];

            foreach ($replacements as $placeholder => $replacement) {
                if (is_scalar($replacement)) {
                    $normalized['{' . sanitize_key((string) $placeholder) . '}'] = (string) $replacement;
                }
            }

            $value = strtr($value, $normalized);
        }

        return $value;
    }
}

if (! function_exists('ontario_success_page_url')) {
    function ontario_success_page_url(): string
    {
        return home_url('/success/');
    }
}

if (! function_exists('ontario_uses_newsite_design')) {
    function ontario_uses_newsite_design(): bool
    {
        return is_front_page() && ontario_site_field('homepage_design', '') === 'newsite';
    }
}

if (! function_exists('ontario_site_display_mode')) {
    function ontario_site_display_mode(): string
    {
        $mode = sanitize_key(ontario_site_field('display_mode', 'full'));

        return in_array($mode, ['full', 'simple', 'choice'], true) ? $mode : 'full';
    }
}

if (! function_exists('ontario_display_preference')) {
    function ontario_display_preference(): string
    {
        $current_site = function_exists('ontario_current_site') ? ontario_current_site() : [];
        $cookie_name = 'ontario_display_preference';

        if (! empty($current_site['is_preview']) && ! empty($current_site['id'])) {
            $cookie_name .= '_preview_' . absint((int) $current_site['id']);
        }

        $value = isset($_COOKIE[$cookie_name])
            ? sanitize_key(wp_unslash((string) $_COOKIE[$cookie_name]))
            : '';

        return in_array($value, ['full', 'simple'], true) ? $value : '';
    }
}

if (! function_exists('ontario_effective_display_mode')) {
    function ontario_effective_display_mode(): string
    {
        $site_mode = ontario_site_display_mode();

        if ($site_mode !== 'choice') {
            return $site_mode;
        }

        $preference = ontario_display_preference();

        return $preference !== '' ? $preference : 'choice';
    }
}

if (! function_exists('ontario_should_show_display_choice_modal')) {
    function ontario_should_show_display_choice_modal(): bool
    {
        return ontario_site_display_mode() === 'choice' && ontario_display_preference() === '';
    }
}

if (! function_exists('ontario_site_display_choice_title')) {
    function ontario_site_display_choice_title(): string
    {
        $site = ontario_current_site();
        $custom = ontario_site_field('display_choice_title', '');

        if ($custom !== '' && ontario_current_language() === (string) ($site['default_language'] ?? 'en')) {
            return $custom;
        }

        return ontario_t('display.default_title', [], 'Choose how you would like to view this website');
    }
}

if (! function_exists('ontario_site_display_choice_description')) {
    function ontario_site_display_choice_description(): string
    {
        $site = ontario_current_site();
        $custom = ontario_site_field('display_choice_description', '');

        if ($custom !== '' && ontario_current_language() === (string) ($site['default_language'] ?? 'en')) {
            return $custom;
        }

        return ontario_t('display.default_description', [], 'You can continue with the full interactive design or switch to a simpler version with larger text and a calmer layout.');
    }
}

if (! function_exists('ontario_site_display_choice_simple_label')) {
    function ontario_site_display_choice_simple_label(): string
    {
        $site = ontario_current_site();
        $custom = ontario_site_field('display_choice_simple_label', '');

        if ($custom !== '' && ontario_current_language() === (string) ($site['default_language'] ?? 'en')) {
            return $custom;
        }

        return ontario_t('display.default_simple', [], 'Use simple design');
    }
}

if (! function_exists('ontario_site_display_choice_full_label')) {
    function ontario_site_display_choice_full_label(): string
    {
        $site = ontario_current_site();
        $custom = ontario_site_field('display_choice_full_label', '');

        if ($custom !== '' && ontario_current_language() === (string) ($site['default_language'] ?? 'en')) {
            return $custom;
        }

        return ontario_t('display.default_full', [], 'Continue with full design');
    }
}

if (! function_exists('ontario_simple_image_uri')) {
    function ontario_simple_image_uri(string $name): string
    {
        $theme_uri = ontario_theme_asset_uri();
        $map = [
            'hero' => '/assets/images/old-1.webp',
            'scanner' => '/assets/images/old-2.webp',
            'report' => '/assets/images/old-1.webp',
            'process' => '/assets/images/old-3.webp',
        ];

        return isset($map[$name]) ? $theme_uri . $map[$name] : '';
    }
}

if (! function_exists('ontario_theme_asset_uri')) {
    function ontario_theme_asset_uri(string $path = ''): string
    {
        $base = home_url('/wp-content/themes/' . get_template());

        return $path === '' ? $base : $base . '/' . ltrim($path, '/');
    }
}

if (! function_exists('ontario_legal_page_key')) {
    function ontario_legal_page_key(): string
    {
        if (! is_page()) {
            return '';
        }

        $post = get_queried_object();

        if (! $post instanceof WP_Post) {
            return '';
        }

        $slug = sanitize_title($post->post_name);
        $title = sanitize_title((string) $post->post_title);

        return match (true) {
            in_array($slug, ['termsandconditions', 'terms-and-conditions', 'termsconditions'], true),
            in_array($title, ['terms-and-conditions', 'terms-conditions'], true) => 'terms',
            in_array($slug, ['privacypolicy', 'privacy-policy'], true),
            $title === 'privacy-policy' => 'privacy',
            default => '',
        };
    }
}

if (! function_exists('ontario_legal_page_url')) {
    function ontario_legal_page_url(string $page_key): string
    {
        $slug = match ($page_key) {
            'terms' => 'termsandconditions',
            'privacy' => 'privacypolicy',
            default => '',
        };

        return $slug !== '' ? home_url('/' . $slug . '/') : home_url('/');
    }
}

if (! function_exists('ontario_legal_page_content')) {
    function ontario_legal_page_content(string $page_key): array
    {
        $site = ontario_current_site();
        $website = (string) ($site['resolved_host'] ?? wp_parse_url(home_url('/'), PHP_URL_HOST) ?? '');
        $brand_name = ontario_site_brand_name();
        $email = ontario_site_field('public_email');
        $phone = ontario_site_field('phone_number');
        $address = ontario_site_field('address');

        $replacements = [
            'brand_name' => $brand_name,
            'website' => $website,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'effective_date' => '01.01.2026',
        ];

        if ($page_key === 'terms') {
            return [
                'title' => ontario_t('legal.terms.title', [], 'Terms & Conditions'),
                'effective_date' => ontario_t('legal.effective_date', $replacements, 'Effective Date: 01.01.2026'),
                'intro' => [
                    ontario_t('legal.terms.intro_1', $replacements, 'Welcome to {brand_name} (“we,” “our,” or “us”).'),
                    ontario_t('legal.terms.intro_2', $replacements, 'These Terms and Conditions (“Terms”) govern your use of our website, {website}, and any services provided by us. By accessing or using our site and services, you agree to be bound by these Terms.'),
                    ontario_t('legal.terms.intro_3', $replacements, 'If you do not agree with any part of these Terms, you must not use our website or services.'),
                ],
                'sections' => [
                    [
                        'heading' => ontario_t('legal.terms.section_1.heading', [], '1. Our Services'),
                        'paragraphs' => [
                            ontario_t('legal.terms.section_1.intro', [], 'Ontario Refunds provides assistance and guidance to individuals and organizations who have been victims of online scams and frauds. Our services may include:'),
                        ],
                        'list' => [
                            ontario_t('legal.terms.section_1.item_1', [], 'Scam consultation and case evaluation'),
                            ontario_t('legal.terms.section_1.item_2', [], 'Guidance on recovery actions'),
                            ontario_t('legal.terms.section_1.item_3', [], 'Communication support with relevant authorities or financial institutions'),
                            ontario_t('legal.terms.section_1.item_4', [], 'Referral to legal or investigative partners (when applicable)'),
                        ],
                    ],
                    [
                        'heading' => ontario_t('legal.terms.section_2.heading', [], '2. No Guarantee of Fund Recovery'),
                        'paragraphs' => [
                            ontario_t('legal.terms.section_2.notice', [], 'IMPORTANT NOTICE:'),
                            ontario_t('legal.terms.section_2.p1', [], 'While we strive to assist victims to the best of our ability, we do not and cannot guarantee a full or partial recovery of lost funds. Many scam-related losses are difficult or impossible to recover due to jurisdictional limitations, anonymity of perpetrators, and other legal or technical constraints.'),
                            ontario_t('legal.terms.section_2.p2', [], 'You acknowledge and accept that using our services does not ensure successful recovery.'),
                        ],
                    ],
                    [
                        'heading' => ontario_t('legal.terms.section_3.heading', [], '3. User Responsibilities'),
                        'paragraphs' => [
                            ontario_t('legal.terms.section_3.intro', [], 'By using our services, you agree to:'),
                        ],
                        'list' => [
                            ontario_t('legal.terms.section_3.item_1', [], 'Provide accurate, complete, and honest information'),
                            ontario_t('legal.terms.section_3.item_2', [], 'Not misuse or abuse our services'),
                            ontario_t('legal.terms.section_3.item_3', [], 'Cooperate fully with our team or any third parties involved in your case'),
                            ontario_t('legal.terms.section_3.item_4', [], 'Abide by all applicable laws and regulations'),
                        ],
                    ],
                    [
                        'heading' => ontario_t('legal.terms.section_4.heading', [], '4. Eligibility'),
                        'paragraphs' => [
                            ontario_t('legal.terms.section_4.intro', [], 'To use our services, you must:'),
                        ],
                        'list' => [
                            ontario_t('legal.terms.section_4.item_1', [], 'Be at least 18 years old or have parental/guardian consent'),
                            ontario_t('legal.terms.section_4.item_2', [], 'Be legally competent to enter into a binding agreement'),
                        ],
                    ],
                    ['heading' => ontario_t('legal.terms.section_5.heading', [], '5. Payment and Fees'), 'paragraphs' => [ontario_t('legal.terms.section_5.p1', [], 'Some of our services may be free, while others may require payment. You will be informed of any costs before service delivery. All payments are final unless otherwise agreed in writing.')]],
                    ['heading' => ontario_t('legal.terms.section_6.heading', [], '6. Intellectual Property'), 'paragraphs' => [ontario_t('legal.terms.section_6.p1', $replacements, 'All content on our website, including text, graphics, logos, and other materials, is the property of {brand_name} or its content providers and is protected by intellectual property laws. You may not reproduce, distribute, or use any content without written permission.')]],
                    ['heading' => ontario_t('legal.terms.section_7.heading', [], '7. Limitation of Liability'), 'paragraphs' => [ontario_t('legal.terms.section_7.intro', $replacements, 'To the fullest extent permitted by law, {brand_name} is not liable for any direct, indirect, incidental, or consequential damages resulting from:' )], 'list' => [ontario_t('legal.terms.section_7.item_1', [], 'Use or misuse of our website or services'), ontario_t('legal.terms.section_7.item_2', [], 'Failure to recover funds or achieve desired outcomes'), ontario_t('legal.terms.section_7.item_3', [], 'Delays, errors, or interruptions in service')], 'closing' => [ontario_t('legal.terms.section_7.p2', [], 'Our liability, if any, shall be limited to the amount paid for our services.')]],
                    ['heading' => ontario_t('legal.terms.section_8.heading', [], '8. Third-Party Services and Referrals'), 'paragraphs' => [ontario_t('legal.terms.section_8.p1', [], 'We may refer you to trusted third parties such as legal experts or investigators. We do not control these third parties and are not responsible for their actions, advice, or results.')]],
                    ['heading' => ontario_t('legal.terms.section_9.heading', [], '9. Changes to Terms'), 'paragraphs' => [ontario_t('legal.terms.section_9.p1', [], 'We may update these Terms at any time. The revised version will be posted on this page with an updated effective date. Continued use of our services means you accept the updated Terms.')]],
                    ['heading' => ontario_t('legal.terms.section_10.heading', [], '10. Termination'), 'paragraphs' => [ontario_t('legal.terms.section_10.p1', [], 'We reserve the right to terminate or suspend access to our website or services at any time, with or without notice, for any reason including violation of these Terms.')]],
                    ['heading' => ontario_t('legal.terms.section_11.heading', [], '11. Contact Us'), 'paragraphs' => [ontario_t('legal.terms.section_11.p1', [], 'For questions about these Terms, please contact:')], 'contact_block' => true],
                ],
            ];
        }

        return [
            'title' => ontario_t('legal.privacy.title', [], 'Privacy Policy'),
            'effective_date' => ontario_t('legal.effective_date', $replacements, 'Effective Date: 01.01.2026'),
            'intro' => [
                ontario_t('legal.privacy.intro_1', $replacements, 'Welcome to {brand_name} (“we,” “our,” or “us”). Your privacy is important to us. This Privacy Policy describes how we collect, use, disclose, and protect your personal information when you use our website, {website}, and our scam recovery services.'),
            ],
            'sections' => [
                ['heading' => ontario_t('legal.privacy.section_1.heading', [], '1. Information We Collect.'), 'paragraphs' => [ontario_t('legal.privacy.section_1.intro', [], 'We may collect and process the following types of personal data:'), ontario_t('legal.privacy.section_1.sub_a', [], 'a. Information You Provide to Us:')], 'list' => [ontario_t('legal.privacy.section_1.item_1', [], 'Full name'), ontario_t('legal.privacy.section_1.item_2', [], 'Email address'), ontario_t('legal.privacy.section_1.item_3', [], 'Phone number'), ontario_t('legal.privacy.section_1.item_4', [], 'Details about your scam incident (e.g., dates, amounts lost, parties involved)'), ontario_t('legal.privacy.section_1.item_5', [], 'Documents or evidence you choose to upload')], 'closing' => [ontario_t('legal.privacy.section_1.sub_b', [], 'b. Information We Collect Automatically:'), ontario_t('legal.privacy.section_1.item_auto_1', [], 'IP address'), ontario_t('legal.privacy.section_1.item_auto_2', [], 'Browser type and version'), ontario_t('legal.privacy.section_1.item_auto_3', [], 'Operating system'), ontario_t('legal.privacy.section_1.item_auto_4', [], 'Pages visited and time spent on the site'), ontario_t('legal.privacy.section_1.item_auto_5', [], 'Cookies and similar tracking technologies')]],
                ['heading' => ontario_t('legal.privacy.section_2.heading', [], '2. How We Use Your Information.'), 'paragraphs' => [ontario_t('legal.privacy.section_2.intro', [], 'We use your information for the following purposes:')], 'list' => [ontario_t('legal.privacy.section_2.item_1', [], 'To provide scam recovery advice and services'), ontario_t('legal.privacy.section_2.item_2', [], 'To contact you regarding your case'), ontario_t('legal.privacy.section_2.item_3', [], 'To improve our services and website'), ontario_t('legal.privacy.section_2.item_4', [], 'To comply with legal obligations'), ontario_t('legal.privacy.section_2.item_5', [], 'For internal record keeping')]],
                ['heading' => ontario_t('legal.privacy.section_3.heading', [], '3. Legal Basis for Processing.'), 'paragraphs' => [ontario_t('legal.privacy.section_3.intro', [], 'We process your personal data based on:')], 'list' => [ontario_t('legal.privacy.section_3.item_1', [], 'Consent – When you provide us with personal data voluntarily'), ontario_t('legal.privacy.section_3.item_2', [], 'Contract – To fulfill our agreement to assist with your case'), ontario_t('legal.privacy.section_3.item_3', [], 'Legal obligation – When required to comply with laws'), ontario_t('legal.privacy.section_3.item_4', [], 'Legitimate interest – To ensure service quality and security')]],
                ['heading' => ontario_t('legal.privacy.section_4.heading', [], '4. Sharing Your Information.'), 'paragraphs' => [ontario_t('legal.privacy.section_4.intro', [], 'We do not sell or rent your data. We may share your information with:')], 'list' => [ontario_t('legal.privacy.section_4.item_1', [], 'Law enforcement or regulatory bodies upon request or legal obligation'), ontario_t('legal.privacy.section_4.item_2', [], 'Service providers who assist in our operations (e.g., hosting, communication tools)'), ontario_t('legal.privacy.section_4.item_3', [], 'Legal professionals or partner investigators involved in your case, with your consent')], 'closing' => [ontario_t('legal.privacy.section_4.p2', [], 'All third parties are required to protect your data and use it only for the agreed purpose.')]],
                ['heading' => ontario_t('legal.privacy.section_5.heading', [], '5. Data Retention.'), 'paragraphs' => [ontario_t('legal.privacy.section_5.intro', [], 'We retain your personal information only as long as necessary:')], 'list' => [ontario_t('legal.privacy.section_5.item_1', [], 'To fulfill the purposes outlined in this policy'), ontario_t('legal.privacy.section_5.item_2', [], 'To comply with legal obligations'), ontario_t('legal.privacy.section_5.item_3', [], 'To resolve disputes and enforce agreements')]],
                ['heading' => ontario_t('legal.privacy.section_6.heading', [], '6. Your Rights.'), 'paragraphs' => [ontario_t('legal.privacy.section_6.intro', [], 'Under GDPR and other applicable laws, you have the right to:')], 'list' => [ontario_t('legal.privacy.section_6.item_1', [], 'Access your personal data'), ontario_t('legal.privacy.section_6.item_2', [], 'Request correction or deletion'), ontario_t('legal.privacy.section_6.item_3', [], 'Withdraw consent at any time'), ontario_t('legal.privacy.section_6.item_4', [], 'Object to or restrict processing'), ontario_t('legal.privacy.section_6.item_5', [], 'Data portability (in some cases)'), ontario_t('legal.privacy.section_6.item_6', [], 'File a complaint with a data protection authority')], 'closing' => [ontario_t('legal.privacy.section_6.p2', $replacements, 'To exercise these rights, contact us at: {email}')]],
                ['heading' => ontario_t('legal.privacy.section_7.heading', [], '7. Security of Your Data.'), 'paragraphs' => [ontario_t('legal.privacy.section_7.p1', [], 'We take reasonable administrative, technical, and physical measures to safeguard your information against loss, theft, and unauthorized use or access.')]],
                ['heading' => ontario_t('legal.privacy.section_8.heading', [], '8. Cookies'), 'paragraphs' => [ontario_t('legal.privacy.section_8.p1', [], 'Our website uses cookies to improve user experience and analyze website usage. You can control cookie preferences through your browser settings.')]],
                ['heading' => ontario_t('legal.privacy.section_9.heading', [], '9. International Data Transfers.'), 'paragraphs' => [ontario_t('legal.privacy.section_9.p1', [], 'If you are located outside of Canada, your information may be transferred to and processed in Canada or other countries with different data protection laws.')]],
                ['heading' => ontario_t('legal.privacy.section_10.heading', [], '10. Third-Party Links.'), 'paragraphs' => [ontario_t('legal.privacy.section_10.p1', [], 'Our website may contain links to third-party sites. We are not responsible for the privacy practices of these websites.')]],
                ['heading' => ontario_t('legal.privacy.section_11.heading', [], '11. Changes to This Policy.'), 'paragraphs' => [ontario_t('legal.privacy.section_11.p1', [], 'We may update this Privacy Policy from time to time. Changes will be posted on this page with an updated effective date.')]],
                ['heading' => ontario_t('legal.privacy.section_12.heading', [], '12. Contact Us.'), 'paragraphs' => [ontario_t('legal.privacy.section_12.p1', [], 'If you have any questions or concerns about this Privacy Policy, please contact:')], 'contact_block' => true],
            ],
        ];
    }
}

add_action('admin_init', static function (): void {
    if (! is_admin() || wp_doing_ajax()) {
        return;
    }

    if (post_type_exists('ontario_site')) {
        return;
    }

    $requested_post_type = isset($_GET['post_type']) ? sanitize_key((string) $_GET['post_type']) : '';

    if ($requested_post_type === '' && isset($_GET['post'])) {
        $requested_post_type = get_post_type((int) $_GET['post']) ?: '';
    }

    if ($requested_post_type !== 'ontario_site') {
        return;
    }

    wp_safe_redirect(add_query_arg('ontario_plugin_required', '1', admin_url('plugins.php')));
    exit;
});

add_action('admin_notices', static function (): void {
    if (! is_admin() || ! current_user_can('activate_plugins')) {
        return;
    }

    if (! isset($_GET['ontario_plugin_required']) || $_GET['ontario_plugin_required'] !== '1') {
        return;
    }

    echo '<div class="notice notice-warning is-dismissible"><p>Ontario Plugin must be activated to manage Ontario Sites.</p></div>';
});

add_action('after_setup_theme', static function (): void {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);
});

add_filter('body_class', static function (array $classes): array {
    $effective_mode = ontario_effective_display_mode();
    $site_mode = ontario_site_display_mode();

    $classes[] = 'ontario-display-' . $effective_mode;
    $classes[] = 'ontario-display-setting-' . $site_mode;

    if ($site_mode === 'choice' && $effective_mode === 'choice') {
        $classes[] = 'ontario-display-choice-pending';
    }

    if (ontario_uses_newsite_design()) {
        $classes[] = 'ontario-newsite';
    }

    return $classes;
});

add_action('template_redirect', static function (): void {
    if (is_admin()) {
        return;
    }

    $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash((string) $_SERVER['REQUEST_URI']) : '';
    $request_path = trim((string) wp_parse_url($request_uri, PHP_URL_PATH), '/');
    $home_path = trim((string) wp_parse_url(home_url('/'), PHP_URL_PATH), '/');

    if ($home_path !== '' && str_starts_with($request_path, $home_path . '/')) {
        $request_path = substr($request_path, strlen($home_path) + 1);
    } elseif ($request_path === $home_path) {
        $request_path = '';
    }

    if ($request_path !== 'success') {
        return;
    }

    global $wp_query;

    if ($wp_query instanceof WP_Query) {
        $wp_query->is_404 = false;
    }

    status_header(200);

    // The thank-you page is intentionally private, but the public campaign
    // landing page must remain eligible for the host/CDN cache. Its lead form
    // sends attribution and GCLID directly to the REST endpoint, so caching
    // this document does not remove attribution from a submitted lead.
    if ($request_path === 'success') {
        nocache_headers();
    }
    include get_template_directory() . '/page-success.php';
    exit;
});

add_filter('document_title_parts', static function (array $parts): array {
    if (ontario_uses_newsite_design()) {
        $parts['title'] = ontario_t('newsite.meta_title', ['brand_name' => ontario_site_brand_name()], 'Online Scam Case Assessment | {brand_name}');
    }

    return $parts;
});

add_action('wp_head', static function (): void {
    if (ontario_uses_newsite_design()) {
        $brand_name = ontario_site_brand_name();
        $title = ontario_t('newsite.meta_title', ['brand_name' => $brand_name], 'Online Scam Case Assessment | {brand_name}');
        $description = ontario_t('newsite.meta_description', ['brand_name' => $brand_name], 'Request a professional assessment for an online scam or cybercrime case.');
        $canonical_url = home_url('/');
    } else {
        return;
    }

    echo '<link rel="canonical" href="' . esc_url($canonical_url) . '" />' . "\n";
    echo '<meta property="og:type" content="website" />' . "\n";
    echo '<meta property="og:title" content="' . esc_attr($title) . '" />' . "\n";
    echo '<meta property="og:description" content="' . esc_attr($description) . '" />' . "\n";
    echo '<meta property="og:url" content="' . esc_url($canonical_url) . '" />' . "\n";
}, 1);

add_action('wp_enqueue_scripts', static function (): void {
    $theme = wp_get_theme();
    $version = $theme->get('Version') ?: '1.0.0';
    $theme_uri = ontario_theme_asset_uri();
    $theme_dir = get_template_directory();
    $is_front_page = is_front_page();
    $is_newsite_page = ontario_uses_newsite_design();
    $script_host = strtolower((string) (wp_parse_url(home_url('/'), PHP_URL_HOST) ?: ''));
    $is_refundassist_site = $script_host === 'refundassist.info' || $script_host === 'www.refundassist.info';
    $current_site = function_exists('ontario_current_site') ? ontario_current_site() : [];
    $site_display_mode = ontario_site_display_mode();
    $effective_display_mode = ontario_effective_display_mode();
    $rest_endpoint = home_url('/wp-json/ontario-site-manager/v1/lead');

    if (! empty($current_site['is_preview']) && ! empty($current_site['id'])) {
        $rest_endpoint = add_query_arg([
            'ontario_preview_site' => (int) $current_site['id'],
            'ontario_preview_token' => OSM_Current_Site::preview_token((int) $current_site['id']),
        ], $rest_endpoint);
    }

    if ($is_front_page || $is_newsite_page) {
        $theme_css_path = $theme_dir . '/assets/css/styles.css';
        if ($is_newsite_page) {
            $newsite_css_path = $theme_dir . '/assets/css/newsite.css';

            // This landing page is deliberately cacheable.  Keeping ~66 KiB of
            // CSS inline made every dynamic HTML response much larger and
            // prevented the browser/CDN from reusing the styles on later
            // visits.  The versioned files have immutable cache headers, and
            // stylesheet links remain render-blocking so there is no FOUC.
            wp_enqueue_style(
                'ontario-theme',
                $theme_uri . '/assets/css/styles.css',
                [],
                is_readable($theme_css_path) ? (string) filemtime($theme_css_path) : $version
            );

            wp_enqueue_style(
                'ontario-newsite',
                $theme_uri . '/assets/css/newsite.css',
                ['ontario-theme'],
                is_readable($newsite_css_path) ? (string) filemtime($newsite_css_path) : $version
            );
        } else {
            wp_register_style('ontario-theme', false, [], $version);
            wp_enqueue_style('ontario-theme');

            if (is_readable($theme_css_path)) {
                wp_add_inline_style('ontario-theme', (string) file_get_contents($theme_css_path));
            }
        }

        if ($site_display_mode !== 'full') {
            $simple_css_path = $theme_dir . '/assets/css/simple.css';
            wp_register_style('ontario-theme-simple', false, ['ontario-theme'], $version);
            wp_enqueue_style('ontario-theme-simple');

            if (is_readable($simple_css_path)) {
                wp_add_inline_style('ontario-theme-simple', (string) file_get_contents($simple_css_path));
            }
        }
    } else {
        wp_enqueue_style(
            'ontario-theme',
            $theme_uri . '/assets/css/styles.css',
            [],
            file_exists($theme_dir . '/assets/css/styles.css') ? (string) filemtime($theme_dir . '/assets/css/styles.css') : $version
        );

        if ($site_display_mode !== 'full') {
            wp_enqueue_style(
                'ontario-theme-simple',
                $theme_uri . '/assets/css/simple.css',
                ['ontario-theme'],
                file_exists($theme_dir . '/assets/css/simple.css') ? (string) filemtime($theme_dir . '/assets/css/simple.css') : $version
            );
        }
    }

    $script_handle = $is_newsite_page ? 'ontario-newsite' : 'ontario-theme';
    $script_file = $is_newsite_page ? '/assets/js/newsite.js' : '/assets/js/main.js';

    wp_enqueue_script(
        $script_handle,
        $theme_uri . $script_file,
        [],
        file_exists($theme_dir . $script_file) ? (string) filemtime($theme_dir . $script_file) : $version,
        true
    );

    if ($site_display_mode === 'choice') {
        wp_enqueue_script(
            'ontario-display-mode',
            $theme_uri . '/assets/js/display-mode.js',
            [],
            file_exists($theme_dir . '/assets/js/display-mode.js') ? (string) filemtime($theme_dir . '/assets/js/display-mode.js') : $version,
            false
        );

        if (function_exists('wp_script_add_data')) {
            wp_script_add_data('ontario-display-mode', 'defer', true);
        }
    }

    if (function_exists('wp_script_add_data')) {
        wp_script_add_data($script_handle, 'defer', true);
    }

    if ($site_display_mode === 'choice') {
        $display_preference_key = 'ontario_display_preference';
        $display_preference_cookie = 'ontario_display_preference';

        if (! empty($current_site['is_preview']) && ! empty($current_site['id'])) {
            $suffix = '_preview_' . absint((int) $current_site['id']);
            $display_preference_key .= $suffix;
            $display_preference_cookie .= $suffix;
        }

        $early_display_script = <<<'JS'
(() => {
  const key = __DISPLAY_PREFERENCE_KEY__;
  const cookieName = __DISPLAY_PREFERENCE_COOKIE__;
  const root = document.documentElement;
  const cookiePattern = new RegExp('(?:^|;\\s*)' + cookieName.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '=(simple|full)(?:;|$)');
  let preference = '';

  const cookieMatch = document.cookie.match(cookiePattern);
  preference = cookieMatch ? cookieMatch[1] : '';

  if (!preference) {
    try {
      const stored = window.localStorage.getItem(key);
      if (stored === 'simple' || stored === 'full') {
        preference = stored;
        document.cookie = `${cookieName}=${stored}; path=/; max-age=15552000; SameSite=Lax`;
      }
    } catch (error) {}
  }

  root.classList.remove('ontario-display-full', 'ontario-display-simple', 'ontario-display-choice');
  root.classList.add(preference === 'simple' ? 'ontario-display-simple' : preference === 'full' ? 'ontario-display-full' : 'ontario-display-choice');
})();
JS;
        $early_display_script = str_replace(
            ['__DISPLAY_PREFERENCE_KEY__', '__DISPLAY_PREFERENCE_COOKIE__'],
            [wp_json_encode($display_preference_key), wp_json_encode($display_preference_cookie)],
            $early_display_script
        );
        wp_add_inline_script('ontario-display-mode', $early_display_script, 'before');
    }

    wp_localize_script($script_handle, 'ontarioSiteManager', [
        'restEndpoint' => esc_url_raw($rest_endpoint),
        'siteId' => (int) ($current_site['id'] ?? 0),
        'effectiveDisplayMode' => $effective_display_mode,
        'showWelcomeModal' => $effective_display_mode === 'full',
        'i18n' => [
            'required' => ontario_t('validation.required', [], 'This field is required.'),
            'invalidEmail' => ontario_t('validation.invalid_email', [], 'Enter a valid email address.'),
            'invalidCanadianPhone' => ontario_t('validation.invalid_ca_phone', [], 'Enter a valid Canadian phone number.'),
            'invalidPhone' => ontario_t('validation.invalid_phone', [], 'Enter a valid phone number.'),
            'submitError' => ontario_t('validation.submit_error', [], 'Unable to submit the form right now.'),
            'sendError' => ontario_t('validation.send_error', [], 'Unable to send the form.'),
            'submitting' => ontario_t('form.submitting', [], 'Submitting...'),
            'submitCase' => $is_newsite_page
                ? ontario_t('newsite.form.submit', [], 'Submit Your Case')
                : ontario_t('form.submit_case', [], 'Submit Case Review'),
            'nextStep' => ontario_t('form.next', [], 'Next Step'),
            'submitted' => ontario_t('form.sent', [], 'Submitted'),
            'sending' => ontario_t('form.sending', [], 'Sending...'),
            'sendMessage' => ontario_t($is_refundassist_site ? 'refundassist.quick_contact_submit' : 'quick_contact.send', [], $is_refundassist_site ? 'Get a free consultation' : 'Send Message'),
            'sendingMessage' => ontario_t('form.sending_message', [], 'Sending your message...'),
            'loadingTitle' => ontario_t('form.submitting_request', [], 'Submitting your request...'),
            'loadingCopy' => ontario_t('form.processing_copy', [], 'Please wait a moment while we process your form.'),
            'stepLabel' => ontario_t('form.step_label', ['current' => '{current}', 'total' => '{total}'], 'Step {current} of {total}'),
            'menuOpen' => ontario_t('site.language_switcher_open', [], 'Open language menu'),
            'menuClose' => ontario_t('site.language_switcher_close', [], 'Close language menu'),
        ],
    ]);

    if ($site_display_mode === 'choice') {
        wp_localize_script('ontario-display-mode', 'ontarioDisplayMode', [
            'siteMode' => $site_display_mode,
            'effectiveMode' => $effective_display_mode,
            'showChoiceModal' => ontario_should_show_display_choice_modal(),
            'isPreview' => ! empty($current_site['is_preview']),
            'siteId' => (int) ($current_site['id'] ?? 0),
            'storageKey' => $display_preference_key,
            'cookieName' => $display_preference_cookie,
            'title' => ontario_site_display_choice_title(),
            'description' => ontario_site_display_choice_description(),
            'simpleLabel' => ontario_site_display_choice_simple_label(),
            'fullLabel' => ontario_site_display_choice_full_label(),
        ]);
    }
});

add_action('wp_enqueue_scripts', static function (): void {
    if (is_admin() || ! is_front_page()) {
        return;
    }

    wp_dequeue_style('wp-block-library');
    wp_dequeue_style('wp-block-library-theme');
    wp_dequeue_style('global-styles');
    wp_dequeue_style('classic-theme-styles');

    wp_dequeue_script('clickceaseFrontEnd');
    wp_deregister_script('clickceaseFrontEnd');

    $wp_scripts = wp_scripts();
    if (! $wp_scripts instanceof WP_Scripts) {
        return;
    }

    $jquery_dependents = [];

    foreach ((array) $wp_scripts->queue as $handle) {
        if (in_array($handle, ['jquery', 'jquery-core', 'jquery-migrate'], true)) {
            continue;
        }

        $registered = $wp_scripts->registered[$handle] ?? null;
        $deps = is_object($registered) ? (array) $registered->deps : [];

        if (array_intersect($deps, ['jquery', 'jquery-core', 'jquery-migrate']) !== []) {
            $jquery_dependents[] = $handle;
        }
    }

    if ($jquery_dependents !== []) {
        return;
    }

    wp_dequeue_script('jquery');
    wp_dequeue_script('jquery-core');
    wp_dequeue_script('jquery-migrate');
}, 999);

/**
 * Some deployments still have the legacy ClickCease plugin enabled. It prints
 * vendor tags directly in wp_head, so WordPress cannot dequeue them and their
 * 200+ KiB of JavaScript competes with the first paint. Rewrite only those
 * known anti-fraud script tags before the response is sent. The small loader
 * retains the tags and starts them after the first interaction, or after five
 * seconds for visitors who do not interact.
 */
add_action('template_redirect', static function (): void {
    if (is_admin() || wp_doing_ajax() || wp_doing_cron() || is_feed() || is_robots()) {
        return;
    }

    ob_start(static function (string $html): string {
        $patterns = [
            '#<script\\b(?=[^>]*\\bsrc=["\']https?://[^"\']*clickcease\\.com/[^"\']*["\'])[^>]*>\\s*</script>#i',
            '#<script\\b(?=[^>]*\\bsrc=["\']https?://[^"\']*/i/[^"\']+\\.js[^"\']*["\'])(?=[^>]*\\bclass=["\'][^"\']*\\bct_clicktrue\\b[^"\']*["\'])[^>]*>\\s*</script>#i',
            '#<script\\b(?=[^>]*\\bsrc=["\']https?://crm\\.zoho\\.eu/crm/javascript/zcga\\.js[^"\']*["\'])[^>]*>\\s*</script>#i',
        ];

        $deferred = false;
        $html = preg_replace_callback(
            $patterns,
            static function (array $match) use (&$deferred): string {
                $deferred = true;
                $tag = $match[0];
                $tag = preg_replace('#\\s+async(?:=["\'][^"\']*["\'])?#i', '', $tag) ?? $tag;

                return preg_replace('#<script\\b#i', '<script type="text/plain" data-ontario-deferred-clickcease', $tag, 1) ?? $tag;
            },
            $html
        ) ?? $html;

        if (! $deferred || str_contains($html, '__ontarioDeferredClickceaseScheduled')) {
            return $html;
        }

        $loader = <<<'HTML'
<script>
(() => {
  if (window.__ontarioDeferredClickceaseScheduled) return;
  window.__ontarioDeferredClickceaseScheduled = true;
  let loaded = false;
  const events = ['pointerdown', 'keydown', 'touchstart', 'scroll'];
  const load = () => {
    if (loaded) return;
    loaded = true;
    events.forEach((event) => window.removeEventListener(event, load));
    document.querySelectorAll('script[data-ontario-deferred-clickcease]').forEach((source) => {
      const script = document.createElement('script');
      [...source.attributes].forEach((attribute) => {
        if (attribute.name !== 'type' && attribute.name !== 'data-ontario-deferred-clickcease') script.setAttribute(attribute.name, attribute.value);
      });
      script.async = true;
      source.replaceWith(script);
    });
  };
  const schedule = () => {
    events.forEach((event) => window.addEventListener(event, load, { once: true, passive: true }));
    window.setTimeout(load, 5000);
  };
  if (document.readyState === 'complete') schedule();
  else window.addEventListener('load', schedule, { once: true });
})();
</script>
HTML;

        return str_ireplace('</head>', $loader . '</head>', $html);
    });
}, 0);

add_action('init', static function (): void {
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'wp_oembed_add_discovery_links');
    remove_action('wp_head', 'rest_output_link_wp_head');
});

add_filter('emoji_svg_url', '__return_false');
