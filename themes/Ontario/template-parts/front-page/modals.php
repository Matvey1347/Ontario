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
      <div class="success-eyebrow">Please note</div>
      <h2 id="welcomeModalTitle">Only If You Lost $2,000+</h2>
      <div class="welcome-copy">
        <p>We will only accept your case if your losses exceed $2,000.</p>
      </div>
      <button class="btn btn-primary welcome-close" type="button" data-welcome-close>I Understand</button>
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
          <strong id="quickContactTitle">Quick Contact</strong>
        </div>
        <button class="modal-close" type="button" aria-label="Close quick contact form" data-modal-close>x</button>
      </div>

      <div class="field">
        <label for="quickName">Name</label>
        <input id="quickName" name="name" type="text" placeholder="Enter first name" required data-label="Name" />
      </div>
      <div class="field">
        <label for="quickPhone">Phone number</label>
        <div class="phone-input">
          <span class="phone-prefix" aria-hidden="true"><span class="phone-flag">🇨🇦</span> +1</span>
          <input id="quickPhone" name="phone" type="tel" inputmode="numeric" autocomplete="tel" placeholder="(506) 234-5678" maxlength="14" required data-label="Phone number" />
        </div>
      </div>
      <div class="field">
        <label for="quickEmail">Email address</label>
        <input id="quickEmail" name="email" type="email" placeholder="Enter your email" required data-label="Email address" />
      </div>
      <div class="field">
        <label for="quickMessage">Message</label>
        <textarea id="quickMessage" name="message" placeholder="Briefly describe what happened (amount, dates, platform or wallets)" required data-label="Message"></textarea>
      </div>
      <label class="checkbox-field" for="lossConfirm">
        <input id="lossConfirm" name="lossConfirm" type="checkbox" required data-label="Loss confirmation" />
        <span>I confirm my losses exceed $2,000</span>
      </label>
      <button class="btn btn-primary short-submit" type="submit">Send Message</button>
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
      <div class="success-eyebrow">Submission received</div>
      <h2 id="successTitle"><?php echo esc_html($brand_name); ?></h2>
      <div class="success-copy">
        <p>Our case review team will contact you shortly.</p>
      </div>
      <button class="btn btn-primary success-close" type="button" data-success-close>Close</button>
    </div>
  </div>
</div>
