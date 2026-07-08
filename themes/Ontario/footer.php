<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}
?>
<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-brand">
        <img src="<?php echo esc_url(ontario_site_logo_url()); ?>" alt="<?php echo esc_attr(ontario_site_brand_name()); ?>" />
        <p>
          <?php echo esc_html(ontario_site_brand_name()); ?> provides private blockchain intelligence and forensic case analysis for victims of online financial fraud.
          We trace digital asset movement, prepare evidence-based documentation, and explain practical next steps.
        </p>
      </div>

      <div class="footer-contact">
        <strong><?php echo esc_html(ontario_site_brand_name()); ?></strong>
        <span><?php echo esc_html(ontario_site_field('address', '10-40 WEST WILMOT ST., RICHMOND HILL ON L4B 1H8')); ?></span>
        <a href="tel:<?php echo esc_attr(ontario_site_field('phone_href', '+16474780877')); ?>"><?php echo esc_html(ontario_site_field('phone_number', '+1 647 478 0877')); ?></a>
        <span><?php echo esc_html(ontario_site_field('working_hours', '11AM-7PM ET')); ?></span>
        <?php if (ontario_site_field('public_email') !== '') : ?>
          <a href="mailto:<?php echo esc_attr(ontario_site_field('public_email')); ?>"><?php echo esc_html(ontario_site_field('public_email')); ?></a>
        <?php endif; ?>
        <span>🔒 Encrypted in transit & at rest · PIPEDA compliant · SOC 2 Type II</span>
      </div>
    </div>

    <div class="footer-bottom">
      <div>© <?php echo esc_html((string) gmdate('Y')); ?> <?php echo esc_html(ontario_site_brand_name()); ?>. All Rights Reserved.</div>
      <div class="footer-links">
        <a href="/termsandconditions/">Terms &amp; Conditions</a>
        <a href="/privacypolicy/">Privacy Policy</a>
      </div>
    </div>
  </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
