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
$is_newsite_page = function_exists('ontario_uses_newsite_design') && ontario_uses_newsite_design();
$current_host = strtolower((string) (wp_parse_url(home_url('/'), PHP_URL_HOST) ?: ''));
$is_refundassist_site = $current_host === 'refundassist.info' || $current_host === 'www.refundassist.info';
$has_contact_info = $brand_name !== '' || $address !== '' || ($phone_href !== '' && $phone_number !== '') || $working_hours !== '' || $public_email !== '';
?>
<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-brand">
        <?php if ($logo_url !== '') : ?>
          <img src="<?php echo esc_url($logo_url); ?>" width="300" height="35" alt="<?php echo esc_attr($brand_name); ?>" />
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
          <?php if (! $is_newsite_page) : ?>
            <span>🔒 <?php echo esc_html(ontario_t('footer.security', [], 'Encrypted in transit & at rest · PIPEDA compliant · SOC 2 Type II')); ?></span>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="footer-bottom">
      <div><?php echo esc_html(ontario_t('footer.copyright', ['year' => (string) gmdate('Y'), 'brand_name' => $brand_name], '© ' . gmdate('Y') . ' ' . $brand_name . '. All Rights Reserved.')); ?></div>
      <div class="footer-links">
        <a href="<?php echo esc_url(ontario_legal_page_url('terms')); ?>"><?php echo esc_html(ontario_t('footer.terms', [], 'Terms & Conditions')); ?></a>
        <a href="<?php echo esc_url(ontario_legal_page_url('privacy')); ?>"><?php echo esc_html(ontario_t('footer.privacy', [], 'Privacy Policy')); ?></a>
        <?php if (ontario_site_display_mode() === 'choice') : ?>
          <button class="display-mode-switch" type="button" id="displayModeSwitch"><?php echo esc_html(ontario_t('footer.change_display_mode', [], 'Change display mode')); ?></button>
        <?php endif; ?>
      </div>
    </div>
    <?php if ($is_refundassist_site) : ?>
      <p class="refundassist-disclosure"><?php echo esc_html(ontario_t('refundassist.footer_disclosure', [], 'Sitename is a private blockchain intelligence and forensic investigation firm providing software, analytics, and expert services to qualified organizations and individuals. We are a non-government entity (operated by CHARITY ACCOUNTING SERVICES LTD, registered in England and Wales) providing value-added paid services. We are not a law enforcement agency and do not provide legal, investment, or financial advice. On-chain analysis cannot guarantee identification or recovery of funds; outcomes vary by case. We do not accept U.S. citizens or residents as clients. Not affiliated with any government agency or regulator. Not affiliated with the Canada Revenue Agency (CRA).')); ?></p>
    <?php endif; ?>
  </div>
</footer>

<a class="sticky-case-review" href="<?php echo esc_url(home_url('/#scanner')); ?>" data-sticky-case-review aria-hidden="true" tabindex="-1">
  <span><?php echo esc_html(ontario_t('nav.free_consultation', [], 'Request a Case Review')); ?></span>
  <svg aria-hidden="true" viewBox="0 0 20 20" focusable="false"><path d="M10 3.5v12m-5-5 5 5 5-5" /></svg>
</a>

<?php
if (ontario_site_display_mode() === 'choice') {
    include locate_template('template-parts/modals/display-mode.php');
}
?>
<?php wp_footer(); ?>
<?php echo ontario_render_tracking_code('body_close'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</body>
</html>
