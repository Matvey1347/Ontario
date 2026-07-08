<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="description" content="<?php echo esc_attr(ontario_site_field('meta_description', 'Ontario Refunds helps victims of online financial fraud trace digital assets, prepare evidence-based reports, and understand practical next steps.')); ?>" />
  <link rel="preconnect" href="<?php echo esc_url(home_url('/')); ?>" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div class="top-notice">
  <div class="container">
    Only cases with losses from $2,000+ • Free consultation • We do not accept US citizens • Not a government agency
  </div>
</div>

<header class="site-header">
  <div class="container">
    <nav class="nav" id="nav">
      <a class="brand" href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php echo esc_attr(ontario_site_brand_name()); ?> home">
        <img src="<?php echo esc_url(ontario_site_logo_url()); ?>" alt="<?php echo esc_attr(ontario_site_brand_name()); ?>" />
      </a>

      <div class="nav-links" aria-label="Primary navigation">
        <a href="#services">Services</a>
        <a href="#scanner">Case Review</a>
        <a href="#report">Report Preview</a>
        <a href="#process">Process</a>
        <a href="#faq">FAQ</a>
      </div>

      <div class="nav-actions">
        <a class="phone" href="tel:<?php echo esc_attr(ontario_site_field('phone_href', '+16474780877')); ?>"><?php echo esc_html(ontario_site_field('phone_number', '+1 647 478 0877')); ?></a>
        <a class="btn btn-primary" href="#quick-contact-modal" data-modal-open>Free Consultation</a>
        <button class="mobile-menu-btn" type="button" aria-label="Open menu" id="menuBtn">☰</button>
      </div>
    </nav>
  </div>
</header>
