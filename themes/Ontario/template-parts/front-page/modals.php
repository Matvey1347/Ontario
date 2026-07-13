<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="modal welcome-modal" id="welcome-modal" aria-hidden="true">
  <div class="modal-backdrop"></div>
  <div class="modal-dialog welcome-dialog" role="dialog" aria-modal="true" aria-labelledby="welcomeModalTitle">
    <div class="welcome-card">
      <div class="success-eyebrow"><?php echo esc_html(ontario_t('welcome.badge', [], 'Please note')); ?></div>
      <h2 id="welcomeModalTitle"><?php echo esc_html(ontario_t('welcome.title', [], 'Only If You Lost $2,000+')); ?></h2>
      <div class="welcome-copy">
        <p><?php echo esc_html(ontario_t('welcome.copy', [], 'We will only accept your case if your losses exceed $2,000.')); ?></p>
      </div>
      <button class="btn btn-primary welcome-close" type="button" data-welcome-close><?php echo esc_html(ontario_t('welcome.button', [], 'I Understand')); ?></button>
    </div>
  </div>
</div>

<div class="modal" id="quick-contact-modal" aria-hidden="true">
  <div class="modal-backdrop" data-modal-close></div>
  <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="quickContactTitle">
    <form class="form-card short-form" id="quickForm" novalidate>
      <input type="hidden" name="formType" value="quickForm" />
      <input type="text" name="website" class="hp-field" tabindex="-1" autocomplete="off" />
      <div class="form-top">
        <div>
          <strong id="quickContactTitle"><?php echo esc_html(ontario_t('quick_contact.title', [], 'Quick Contact')); ?></strong>
        </div>
        <button class="modal-close" type="button" aria-label="<?php echo esc_attr(ontario_t('quick_contact.close_aria', [], 'Close quick contact form')); ?>" data-modal-close>x</button>
      </div>

      <div class="field">
        <label for="quickName"><?php echo esc_html(ontario_t('form.quick_name', [], 'Name')); ?></label>
        <input id="quickName" name="name" type="text" placeholder="<?php echo esc_attr(ontario_t('form.quick_name_placeholder', [], 'Enter first name')); ?>" required data-label="<?php echo esc_attr(ontario_t('form.quick_name', [], 'Name')); ?>" />
      </div>
      <div class="field">
        <label for="quickPhone"><?php echo esc_html(ontario_t('form.phone', [], 'Phone number')); ?></label>
        <div class="phone-input<?php echo ontario_phone_country_selector_enabled() ? ' phone-input--with-country' : ''; ?>">
          <?php if (ontario_phone_country_selector_enabled()) : ?>
            <div class="phone-country-select">
              <label class="screen-reader-text" for="quickPhoneCountry"><?php echo esc_html(ontario_t('form.phone_country', [], 'Country')); ?></label>
              <select id="quickPhoneCountry" name="phoneCountry" data-phone-country>
                <?php foreach (ontario_phone_countries() as $country) : ?>
                  <option value="<?php echo esc_attr((string) $country['iso2']); ?>" data-dial-code="<?php echo esc_attr((string) $country['dial_code']); ?>"<?php selected((string) $country['iso2'], 'CA'); ?>><?php echo esc_html((string) $country['flag'] . ' ' . $country['name'] . ' ' . $country['dial_code']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          <?php else : ?>
            <span class="phone-prefix" aria-hidden="true"><span class="phone-flag">🇨🇦</span> +1</span>
          <?php endif; ?>
          <div class="phone-number-input">
            <input id="quickPhone" name="phone" type="tel" inputmode="numeric" autocomplete="tel" placeholder="<?php echo esc_attr(ontario_t(ontario_phone_country_selector_enabled() ? 'form.phone_placeholder_intl' : 'form.phone_placeholder_ca', [], ontario_phone_country_selector_enabled() ? 'Enter national phone number' : '(506) 234-5678')); ?>" maxlength="<?php echo esc_attr(ontario_phone_country_selector_enabled() ? '20' : '14'); ?>" required data-label="<?php echo esc_attr(ontario_t('form.phone', [], 'Phone number')); ?>" data-phone-mode="<?php echo esc_attr(ontario_phone_country_selector_enabled() ? 'international' : 'canada'); ?>" />
          </div>
        </div>
      </div>
      <div class="field">
        <label for="quickEmail"><?php echo esc_html(ontario_t('form.email', [], 'Email address')); ?></label>
        <input id="quickEmail" name="email" type="email" placeholder="<?php echo esc_attr(ontario_t('form.quick_email_placeholder', [], 'Enter your email')); ?>" required data-label="<?php echo esc_attr(ontario_t('form.email', [], 'Email address')); ?>" />
      </div>
      <div class="field">
        <label for="quickMessage"><?php echo esc_html(ontario_t('form.quick_message', [], 'Message')); ?></label>
        <textarea id="quickMessage" name="message" placeholder="<?php echo esc_attr(ontario_t('form.quick_message_placeholder', [], 'Briefly describe what happened (amount, dates, platform or wallets)')); ?>" required data-label="<?php echo esc_attr(ontario_t('form.quick_message', [], 'Message')); ?>"></textarea>
      </div>
      <label class="checkbox-field" for="lossConfirm">
        <input id="lossConfirm" name="lossConfirm" type="checkbox" required data-label="<?php echo esc_attr(ontario_t('form.loss_confirm', [], 'I confirm my losses exceed $2,000')); ?>" />
        <span><?php echo esc_html(ontario_t('form.loss_confirm', [], 'I confirm my losses exceed $2,000')); ?></span>
      </label>
      <button class="btn btn-primary short-submit" type="submit"><?php echo esc_html(ontario_t('quick_contact.send', [], 'Send Message')); ?></button>
    </form>
  </div>
</div>

<div class="modal success-modal" id="success-modal" aria-hidden="true">
  <div class="modal-backdrop" data-success-close></div>
  <div class="modal-dialog success-dialog" role="dialog" aria-modal="true" aria-labelledby="successTitle">
    <div class="success-card">
      <div class="success-icon" aria-hidden="true">
        <span>✓</span>
      </div>
      <div class="success-eyebrow"><?php echo esc_html(ontario_t('success.badge', [], 'Submission received')); ?></div>
      <h2 id="successTitle"><?php echo esc_html($brand_name); ?></h2>
      <div class="success-copy">
        <p><?php echo esc_html(ontario_t('success.copy_short', [], 'Our case review team will contact you shortly.')); ?></p>
      </div>
      <button class="btn btn-primary success-close" type="button" data-success-close><?php echo esc_html(ontario_t('success.close', [], 'Close')); ?></button>
    </div>
  </div>
</div>
