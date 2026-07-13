<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

$brand_name = ontario_site_brand_name();
$logo_url = ontario_site_logo_url();
$address = ontario_site_field('address');
$phone_href = ontario_site_field('phone_href');
$phone_number = ontario_site_field('phone_number');
$working_hours = ontario_site_field('working_hours');
$public_email = ontario_site_field('public_email');
$has_contact_info = $brand_name !== '' || $address !== '' || ($phone_href !== '' && $phone_number !== '') || $working_hours !== '' || $public_email !== '';
?>
<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-brand">
        <?php if ($logo_url !== '') : ?>
          <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($brand_name); ?>" />
        <?php endif; ?>
        <p>
          <?php echo esc_html(ontario_t('footer.description', ['brand_name' => $brand_name], $brand_name . ' provides private blockchain intelligence and forensic case analysis for victims of online financial fraud.')); ?>
        </p>
      </div>

      <?php if ($has_contact_info) : ?>
        <div class="footer-contact">
          <?php if ($brand_name !== '') : ?>
            <strong><?php echo esc_html($brand_name); ?></strong>
          <?php endif; ?>
          <?php if ($address !== '') : ?>
            <span><?php echo esc_html($address); ?></span>
          <?php endif; ?>
          <?php if ($phone_href !== '' && $phone_number !== '') : ?>
            <a href="tel:<?php echo esc_attr($phone_href); ?>"><?php echo esc_html($phone_number); ?></a>
          <?php endif; ?>
          <?php if ($working_hours !== '') : ?>
            <span><?php echo esc_html($working_hours); ?></span>
          <?php endif; ?>
          <?php if ($public_email !== '') : ?>
            <a href="mailto:<?php echo esc_attr($public_email); ?>"><?php echo esc_html($public_email); ?></a>
          <?php endif; ?>
          <span>🔒 <?php echo esc_html(ontario_t('footer.security', [], 'Encrypted in transit & at rest · PIPEDA compliant · SOC 2 Type II')); ?></span>
        </div>
      <?php endif; ?>
    </div>

    <div class="footer-bottom">
      <div><?php echo esc_html(ontario_t('footer.copyright', ['year' => (string) gmdate('Y'), 'brand_name' => $brand_name], '© ' . gmdate('Y') . ' ' . $brand_name . '. All Rights Reserved.')); ?></div>
      <div class="footer-links">
        <a href="/termsandconditions/"><?php echo esc_html(ontario_t('footer.terms', [], 'Terms & Conditions')); ?></a>
        <a href="/privacypolicy/"><?php echo esc_html(ontario_t('footer.privacy', [], 'Privacy Policy')); ?></a>
        <?php if (ontario_site_display_mode() === 'choice') : ?>
          <button class="display-mode-switch" type="button" id="displayModeSwitch"><?php echo esc_html(ontario_t('footer.change_display_mode', [], 'Change display mode')); ?></button>
        <?php endif; ?>
      </div>
    </div>
  </div>
</footer>

<?php
if (ontario_site_display_mode() === 'choice') {
    include locate_template('template-parts/modals/display-mode.php');
}
?>
<?php wp_footer(); ?>
</body>
</html>
