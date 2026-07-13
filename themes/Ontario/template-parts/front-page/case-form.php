<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}
?>
<?php $phone_selector_enabled = ontario_phone_country_selector_enabled(); ?>
<form class="form-card" id="caseForm" novalidate>
  <input type="hidden" name="formType" value="caseForm" />
  <input type="text" name="website" class="hp-field" tabindex="-1" autocomplete="off" />
  <div class="form-top">
    <div>
      <strong><?php echo esc_html(ontario_t('form.case.title', [], 'Start Free Case Review')); ?></strong>
      <span id="stepLabel"><?php echo esc_html(ontario_t('form.step_label', ['current' => '1', 'total' => '3'], 'Step 1 of 3')); ?></span>
    </div>
    <div class="progress" aria-hidden="true"><i id="progressBar"></i></div>
  </div>

  <div class="form-step active" data-step="1">
    <div class="field">
      <label for="lossAmount"><?php echo esc_html(ontario_t('form.loss_amount', [], 'Approximate amount lost')); ?></label>
      <select id="lossAmount" name="lossAmount" required data-label="<?php echo esc_attr(ontario_t('form.loss_amount', [], 'Approximate amount lost')); ?>">
        <option value=""><?php echo esc_html(ontario_t('form.loss_placeholder', [], 'Select range')); ?></option>
        <option value="$500-$1,999"><?php echo esc_html(ontario_t('form.loss_1', [], '$500-$1,999')); ?></option>
        <option value="$2,000-$9,999"><?php echo esc_html(ontario_t('form.loss_2', [], '$2,000-$9,999')); ?></option>
        <option value="$10,000-$49,999"><?php echo esc_html(ontario_t('form.loss_3', [], '$10,000-$49,999')); ?></option>
        <option value="$50,000-$99,999"><?php echo esc_html(ontario_t('form.loss_4', [], '$50,000-$99,999')); ?></option>
        <option value="$100,000+"><?php echo esc_html(ontario_t('form.loss_5', [], '$100,000+')); ?></option>
      </select>
    </div>
  </div>

  <div class="form-step" data-step="2">
    <div class="field">
      <label for="transferMethod"><?php echo esc_html(ontario_t('form.transfer_method', [], 'How were funds transferred?')); ?></label>
      <select id="transferMethod" name="transferMethod" required data-label="<?php echo esc_attr(ontario_t('form.transfer_method', [], 'How were funds transferred?')); ?>">
        <option value=""><?php echo esc_html(ontario_t('form.transfer_placeholder', [], 'Select method')); ?></option>
        <option value="Cryptocurrency transfer"><?php echo esc_html(ontario_t('form.transfer.crypto', [], 'Cryptocurrency transfer')); ?></option>
        <option value="Bank wire / e-transfer"><?php echo esc_html(ontario_t('form.transfer.bank', [], 'Bank wire / e-transfer')); ?></option>
        <option value="Credit or debit card"><?php echo esc_html(ontario_t('form.transfer.card', [], 'Credit or debit card')); ?></option>
        <option value="Payment app"><?php echo esc_html(ontario_t('form.transfer.app', [], 'Payment app')); ?></option>
        <option value="Multiple methods"><?php echo esc_html(ontario_t('form.transfer.multiple', [], 'Multiple methods')); ?></option>
        <option value="Not sure"><?php echo esc_html(ontario_t('form.transfer.unknown', [], 'Not sure')); ?></option>
      </select>
    </div>
    <div class="field">
      <label for="caseDetails"><?php echo esc_html(ontario_t('form.case_details', [], 'Brief case details')); ?></label>
      <textarea id="caseDetails" name="caseDetails" placeholder="<?php echo esc_attr(ontario_t('form.case_details_placeholder', [], 'Describe what happened, the platform name, wallet addresses, links, or transaction details.')); ?>" data-label="<?php echo esc_attr(ontario_t('form.case_details', [], 'Brief case details')); ?>"></textarea>
    </div>
  </div>

  <div class="form-step" data-step="3">
    <div class="field">
      <label for="fullName"><?php echo esc_html(ontario_t('form.full_name', [], 'Full name')); ?></label>
      <input id="fullName" name="fullName" type="text" placeholder="<?php echo esc_attr(ontario_t('form.full_name_placeholder', [], 'Your full name')); ?>" required data-label="<?php echo esc_attr(ontario_t('form.full_name', [], 'Full name')); ?>" />
    </div>
    <div class="field">
      <label for="email"><?php echo esc_html(ontario_t('form.email', [], 'Email address')); ?></label>
      <input id="email" name="email" type="email" placeholder="<?php echo esc_attr(ontario_t('form.email_placeholder', [], 'you@example.com')); ?>" required data-label="<?php echo esc_attr(ontario_t('form.email', [], 'Email address')); ?>" />
    </div>
    <div class="field">
      <label for="phone"><?php echo esc_html(ontario_t('form.phone', [], 'Phone number')); ?></label>
      <div class="phone-input<?php echo $phone_selector_enabled ? ' phone-input--with-country' : ''; ?>">
        <?php if ($phone_selector_enabled) : ?>
          <div class="phone-country-select">
            <label class="screen-reader-text" for="phoneCountry"><?php echo esc_html(ontario_t('form.phone_country', [], 'Country')); ?></label>
            <select id="phoneCountry" name="phoneCountry" data-phone-country>
              <?php foreach (ontario_phone_countries() as $country) : ?>
                <option value="<?php echo esc_attr((string) $country['iso2']); ?>" data-dial-code="<?php echo esc_attr((string) $country['dial_code']); ?>"<?php selected((string) $country['iso2'], 'CA'); ?>><?php echo esc_html((string) $country['flag'] . ' ' . $country['name'] . ' ' . $country['dial_code']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php else : ?>
          <span class="phone-prefix" aria-hidden="true"><span class="phone-flag">🇨🇦</span> +1</span>
        <?php endif; ?>
        <div class="phone-number-input">
          <input id="phone" name="phone" type="tel" inputmode="numeric" autocomplete="tel" placeholder="<?php echo esc_attr(ontario_t($phone_selector_enabled ? 'form.phone_placeholder_intl' : 'form.phone_placeholder_ca', [], $phone_selector_enabled ? 'Enter national phone number' : '(647) 478-0877')); ?>" maxlength="<?php echo esc_attr($phone_selector_enabled ? '20' : '14'); ?>" required data-label="<?php echo esc_attr(ontario_t('form.phone', [], 'Phone number')); ?>" data-phone-mode="<?php echo esc_attr($phone_selector_enabled ? 'international' : 'canada'); ?>" />
        </div>
      </div>
    </div>
  </div>

  <div class="form-actions">
    <button class="btn btn-secondary" type="button" id="prevBtn"><?php echo esc_html(ontario_t('form.back', [], 'Back')); ?></button>
    <button class="btn btn-primary" type="button" id="nextBtn"><?php echo esc_html(ontario_t('form.next', [], 'Next Step')); ?></button>
  </div>

  <p class="form-note">
    🔒 <?php echo esc_html(ontario_t('form.note', [], 'Encrypted in transit & at rest · PIPEDA compliant · SOC 2 Type II')); ?>
  </p>
</form>
