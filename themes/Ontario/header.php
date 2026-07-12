<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <?php echo ontario_render_tracking_code('head_open'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
  <meta charset="<?php bloginfo('charset'); ?>" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="description" content="<?php echo esc_attr(ontario_site_field('meta_description', 'Ontario Refunds helps victims of online financial fraud trace digital assets, prepare evidence-based reports, and understand practical next steps.')); ?>" />
  <link rel="preconnect" href="<?php echo esc_url(home_url('/')); ?>" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<?php
$brand_name = ontario_site_brand_name();
$logo_url = ontario_site_logo_url();
$phone_href = ontario_site_field('phone_href');
$phone_number = ontario_site_field('phone_number');
?>
<div class="top-notice">
  <div class="container">
    Only cases with losses from $2,000+ • Free consultation • We do not accept US citizens • Not a government agency
  </div>
</div>

<header class="site-header">
  <div class="container">
    <nav class="nav" id="nav">
      <a class="brand" href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php echo esc_attr(ontario_site_brand_name()); ?> home">
        <?php if ($logo_url !== '') : ?>
          <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($brand_name); ?>" />
        <?php else : ?>
          <span><?php echo esc_html($brand_name); ?></span>
        <?php endif; ?>
      </a>

      <div class="nav-links" aria-label="Primary navigation">
        <a href="#services">Services</a>
        <a href="#scanner">Case Review</a>
        <a href="#report">Report Preview</a>
        <a href="#process">Process</a>
        <a href="#faq">FAQ</a>
      </div>

      <div class="nav-actions">
        <?php if ($phone_href !== '' && $phone_number !== '') : ?>
          <a class="phone" href="tel:<?php echo esc_attr($phone_href); ?>"><?php echo esc_html($phone_number); ?></a>
        <?php endif; ?>
        <a class="btn btn-primary" href="#quick-contact-modal" data-modal-open>Free Consultation</a>
        <button class="mobile-menu-btn" type="button" aria-label="Open menu" id="menuBtn">☰</button>
      </div>
    </nav>
  </div>
</header>
