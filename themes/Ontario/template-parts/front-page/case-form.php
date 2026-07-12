<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}
?>
<form class="form-card" id="caseForm" novalidate>
  <input type="hidden" name="formType" value="caseForm" />
  <input type="text" name="website" class="hp-field" tabindex="-1" autocomplete="off" />
  <div class="form-top">
    <div>
      <strong>Start Free Case Review</strong>
      <span id="stepLabel">Step 1 of 3</span>
    </div>
    <div class="progress" aria-hidden="true"><i id="progressBar"></i></div>
  </div>

  <div class="form-step active" data-step="1">
    <div class="field">
      <label for="lossAmount">Approximate amount lost</label>
      <select id="lossAmount" name="lossAmount" required data-label="Approximate amount lost">
        <option value="">Select range</option>
        <option>$500-$1,999</option>
        <option>$2,000-$9,999</option>
        <option>$10,000-$49,999</option>
        <option>$50,000-$99,999</option>
        <option>$100,000+</option>
      </select>
    </div>
  </div>

  <div class="form-step" data-step="2">
    <div class="field">
      <label for="transferMethod">How were funds transferred?</label>
      <select id="transferMethod" name="transferMethod" required data-label="Transfer method">
        <option value="">Select method</option>
        <option>Cryptocurrency transfer</option>
        <option>Bank wire / e-transfer</option>
        <option>Credit or debit card</option>
        <option>Payment app</option>
        <option>Multiple methods</option>
        <option>Not sure</option>
      </select>
    </div>
    <div class="field">
      <label for="caseDetails">Brief case details</label>
      <textarea id="caseDetails" name="caseDetails" placeholder="Describe what happened, the platform name, wallet addresses, links, or transaction details." data-label="Case details"></textarea>
    </div>
  </div>

  <div class="form-step" data-step="3">
    <div class="field">
      <label for="fullName">Full name</label>
      <input id="fullName" name="fullName" type="text" placeholder="Your full name" required data-label="Full name" />
    </div>
    <div class="field">
      <label for="email">Email address</label>
      <input id="email" name="email" type="email" placeholder="you@example.com" required data-label="Email address" />
    </div>
    <div class="field">
      <label for="phone">Phone number</label>
      <div class="phone-input">
        <span class="phone-prefix" aria-hidden="true"><span class="phone-flag">🇨🇦</span> +1</span>
        <input id="phone" name="phone" type="tel" inputmode="numeric" autocomplete="tel" placeholder="(647) 478-0877" maxlength="14" required data-label="Phone number" />
      </div>
    </div>
  </div>

  <div class="form-actions">
    <button class="btn btn-secondary" type="button" id="prevBtn">Back</button>
    <button class="btn btn-primary" type="button" id="nextBtn">Next Step</button>
  </div>

  <p class="form-note">
    🔒 Encrypted in transit &amp; at rest · PIPEDA compliant · SOC 2 Type II
  </p>
</form>
