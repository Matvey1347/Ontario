<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}
?><!doctype html>
<html <?php language_attributes(); ?> class="notranslate" translate="no">
<head>
  <?php
  $effective_display_mode = function_exists('ontario_effective_display_mode') ? ontario_effective_display_mode() : 'full';
  $hero_preload = function_exists('ontario_theme_asset_uri') ? ontario_theme_asset_uri('assets/images/old-1.webp') : get_template_directory_uri() . '/assets/images/old-1.webp';
  ?>
  <?php echo ontario_render_tracking_code('head_open'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
  <meta charset="<?php bloginfo('charset'); ?>" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="google" content="notranslate" />
  <meta name="googlebot" content="notranslate" />
  <meta name="translator" content="no" />
  <meta name="description" content="<?php echo esc_attr(ontario_site_field('meta_description', ontario_t('hero.copy', ['brand_name' => ontario_site_brand_name()], 'Ontario Refunds helps victims of online financial fraud trace digital assets, prepare evidence-based reports, and understand practical next steps.'))); ?>" />
  <?php if (is_front_page() && $effective_display_mode === 'simple') : ?>
    <link rel="preload" as="image" href="<?php echo esc_url($hero_preload); ?>" type="image/webp" />
  <?php endif; ?>
  <?php wp_head(); ?>
</head>
<body <?php body_class('notranslate'); ?> translate="no">
<?php wp_body_open(); ?>
<?php
$brand_name = ontario_site_brand_name();
$logo_url = ontario_site_logo_url();
$phone_href = ontario_site_field('phone_href');
$phone_number = ontario_site_field('phone_number');
$enabled_languages = ontario_enabled_languages();
$current_language = ontario_current_language();
?>
<div class="top-notice">
  <div class="container">
    <?php echo esc_html(ontario_t('site.notice', [], 'Only cases with losses from $2,000+ • Free consultation • We do not accept US citizens • Not a government agency')); ?>
  </div>
</div>

<header class="site-header">
  <div class="container">
    <nav class="nav" id="nav">
      <a class="brand" href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php echo esc_attr(ontario_t('site.brand_home', ['brand_name' => $brand_name], $brand_name . ' home')); ?>">
        <?php if ($logo_url !== '') : ?>
          <img src="<?php echo esc_url($logo_url); ?>" width="300" height="35" fetchpriority="high" decoding="async" alt="<?php echo esc_attr($brand_name); ?>" />
        <?php else : ?>
          <span><?php echo esc_html($brand_name); ?></span>
        <?php endif; ?>
      </a>

      <div class="nav-links" aria-label="<?php echo esc_attr(ontario_t('site.primary_navigation', [], 'Primary navigation')); ?>">
        <a href="#services"><?php echo esc_html(ontario_t('nav.services', [], 'Services')); ?></a>
        <a href="#scanner"><?php echo esc_html(ontario_t('nav.case_review', [], 'Case Review')); ?></a>
        <a href="#report"><?php echo esc_html(ontario_t('nav.report_preview', [], 'Report Preview')); ?></a>
        <a href="#process"><?php echo esc_html(ontario_t('nav.process', [], 'Process')); ?></a>
        <a href="#faq"><?php echo esc_html(ontario_t('nav.faq', [], 'FAQ')); ?></a>
      </div>

      <div class="nav-actions">
        <?php if (count($enabled_languages) > 1) : ?>
          <div class="language-switcher" data-language-switcher>
            <button class="language-switcher__button" type="button" aria-expanded="false" aria-controls="ontario-language-menu" aria-label="<?php echo esc_attr(ontario_t('site.language_switcher', [], 'Select website language')); ?>">
              <?php
              foreach ($enabled_languages as $language) :
                  if ($language['code'] !== $current_language) {
                      continue;
                  }
                  ?>
                <span class="language-switcher__flag"><?php echo esc_html((string) $language['flag']); ?></span>
              <?php endforeach; ?>
            </button>
            <div class="language-switcher__menu" id="ontario-language-menu" hidden>
              <?php foreach ($enabled_languages as $language) : ?>
                <a class="language-switcher__item<?php echo $language['code'] === $current_language ? ' is-active' : ''; ?>" href="<?php echo esc_url(ontario_language_switch_url((string) $language['code'])); ?>">
                  <span class="language-switcher__flag"><?php echo esc_html((string) $language['flag']); ?></span>
                  <span><?php echo esc_html((string) $language['native_name']); ?></span>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
        <?php if ($phone_href !== '' && $phone_number !== '') : ?>
          <a class="phone" href="tel:<?php echo esc_attr($phone_href); ?>"><?php echo esc_html($phone_number); ?></a>
        <?php endif; ?>
        <a class="btn btn-primary" href="#quick-contact-modal" data-modal-open><?php echo esc_html(ontario_t('nav.free_consultation', [], 'Free Consultation')); ?></a>
        <button class="mobile-menu-btn" type="button" aria-label="<?php echo esc_attr(ontario_t('site.open_menu', [], 'Open menu')); ?>" aria-expanded="false" id="menuBtn">☰</button>
      </div>
    </nav>
  </div>
</header>
