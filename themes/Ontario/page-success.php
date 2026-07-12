<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

$brand_name = ontario_site_brand_name();

get_header();
?>
<main class="success-page">
  <section class="success-page-section">
    <div class="container">
      <div class="success-card success-page-card">
        <div class="success-icon" aria-hidden="true">
          <span>✓</span>
        </div>
        <div class="success-eyebrow">Submission received</div>
        <h1><?php echo esc_html($brand_name); ?></h1>
        <div class="success-copy">
          <p>Our case review team will contact you shortly.</p>
          <p>Thank you for submitting your information. We will review the details and follow up as soon as possible.</p>
        </div>
        <div class="success-page-actions">
          <a class="btn btn-primary success-close" href="<?php echo esc_url(home_url('/')); ?>">Back To Home</a>
        </div>
      </div>
    </div>
  </section>
  <?php echo ontario_render_success_tracking_code(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</main>
<?php
get_footer();
