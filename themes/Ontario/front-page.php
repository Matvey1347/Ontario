<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

$theme_uri = get_template_directory_uri();
$brand_name = ontario_site_brand_name();
$phone_number = ontario_site_field('phone_number');
$working_hours = ontario_site_field('working_hours');
$address = ontario_site_field('address');

get_header();
?>
<main id="top">
  <section class="hero">
    <div class="container hero-grid">
      <div class="hero-content">
        <div class="eyebrow">Private blockchain intelligence for fraud victims</div>
        <h1>
          Trace lost digital assets with <span class="gradient-text">forensic precision.</span>
        </h1>
        <p class="hero-copy">
          <?php echo esc_html($brand_name); ?> helps victims of online financial fraud map crypto transactions,
          organize evidence, and understand realistic recovery options.
          <strong>No false promises. Evidence-first analysis.</strong>
        </p>

        <div class="hero-actions">
          <a class="btn btn-primary" href="#quick-contact-modal" data-modal-open>Get A Free Consultation</a>
          <a class="btn btn-secondary" href="#report">View Report Preview</a>
        </div>

        <div class="micro-trust" aria-label="Trust signals">
          <span><b></b> Encrypted submission</span>
          <span><b></b> PIPEDA compliant</span>
          <span><b></b> SOC 2 Type II</span>
          <?php if ($working_hours !== '') : ?>
            <span><b></b> <?php echo esc_html($working_hours); ?></span>
          <?php endif; ?>
        </div>
      </div>

      <div class="hero-visual" aria-label="Forensic dashboard illustration">
        <div class="hero-image-frame">
          <img src="<?php echo esc_url($theme_uri . '/assets/images/hero-forensic-dashboard.png'); ?>" alt="Forensic blockchain tracing dashboard with wallet flow analysis" />
        </div>
      </div>
    </div>
  </section>

  <div class="strip">
    <div class="container strip-inner">
      <div class="strip-card">
        <strong>Important distinction</strong>
        <p>We do not reverse blockchain transactions or guarantee recovery. We trace, document, and guide.</p>
      </div>
      <div class="strip-card">
        <strong>Crypto &amp; digital assets</strong>
        <p>Wallets, transaction hashes, exchanges, bridges, mixers, and suspicious fund movement.</p>
      </div>
      <div class="strip-card">
        <strong>Evidence-based reports</strong>
        <p>Structured documentation for victims, institutions, counsel, and reporting workflows.</p>
      </div>
      <?php if ($address !== '') : ?>
        <div class="strip-card">
          <strong>Canadian support</strong>
          <p><?php echo esc_html($address); ?></p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <section id="services">
    <div class="container">
      <div class="section-head">
        <h2>What <?php echo esc_html($brand_name); ?> actually does.</h2>
        <p>
          A clear forensic workflow for people who lost funds to online scams,
          fake investment platforms, wallet drains, phishing, or fraudulent brokers.
        </p>
      </div>

      <div class="grid-4">
        <article class="card">
          <div class="icon">⌁</div>
          <h3>Transaction Tracing</h3>
          <p>We analyze wallet addresses, transaction hashes, fund movement, exchange exposure, and on-chain patterns where technically possible.</p>
        </article>

        <article class="card">
          <div class="icon">◌</div>
          <h3>Wallet &amp; Entity Mapping</h3>
          <p>We map connected wallets, suspicious clusters, possible service touchpoints, bridges, mixers, and destination patterns.</p>
        </article>

        <article class="card">
          <div class="icon">▣</div>
          <h3>Evidence Report</h3>
          <p>You receive a structured report with transaction paths, timeline, wallet details, findings, and recommended documentation steps.</p>
        </article>

        <article class="card">
          <div class="icon">→</div>
          <h3>Next-Step Guidance</h3>
          <p>We help you understand practical reporting, legal, institutional, and documentation options based on the case facts.</p>
        </article>
      </div>
    </div>
  </section>

  <section id="scanner">
    <div class="container scanner">
      <div class="scanner-panel">
        <div class="eyebrow">Case Eligibility Scanner</div>
        <h2>Submit your case for a free initial review.</h2>
        <p>
          The stronger your evidence, the more useful the tracing analysis can be.
          Prepare wallet addresses, transaction hashes, screenshots, platform URLs,
          chat logs, emails, and payment records.
        </p>

        <ul class="check-list">
          <li>Secure intake for online fraud and crypto loss cases.</li>
          <li>Initial feasibility check before deeper forensic work.</li>
          <li>Clear explanation of what can and cannot be done.</li>
          <li>No guaranteed recovery claims or misleading promises.</li>
        </ul>
      </div>

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
    </div>
  </section>

  <section id="report">
    <div class="container split">
      <div>
        <div class="eyebrow">Forensic report preview</div>
        <h2 class="section-title">
          Turn fragmented evidence into a structured investigation file.
        </h2>
        <p class="section-copy-wide">
          A clear report helps organize the timeline, wallet data, transaction paths,
          suspected services, case notes, and realistic next-step options.
        </p>

        <div class="grid-3">
          <article class="card">
            <h3>Transaction path</h3>
            <p>Source wallet, intermediary wallets, exchanges, bridges, and destination clusters.</p>
          </article>
          <article class="card">
            <h3>Evidence timeline</h3>
            <p>Chronological structure for transfers, communication, fake platform activity, and reporting history.</p>
          </article>
          <article class="card">
            <h3>Actionable notes</h3>
            <p>Practical next steps based on the findings, not generic promises or pressure tactics.</p>
          </article>
        </div>
      </div>

      <aside class="report" aria-label="Sample report">
        <div class="report-header">
          <strong>Sample Tracing Report</strong>
          <span>Preview</span>
        </div>
        <div class="report-body">
          <div class="report-row">
            <small>Case ID</small>
            <div class="hash">OR-CA-2026-0418</div>
          </div>
          <div class="report-row">
            <small>Scam type</small>
            <div>Fake crypto investment platform</div>
          </div>
          <div class="report-row">
            <small>Source TX</small>
            <div class="hash">0x49d7...a81c9f7b2e</div>
          </div>
          <div class="report-row">
            <small>Observed path</small>
            <div>Victim wallet → intermediary wallets → mixer exposure → exchange deposit cluster</div>
          </div>
          <div class="report-row">
            <small>Findings</small>
            <div>Multiple linked wallets identified. Further documentation recommended before institutional outreach.</div>
          </div>
        </div>
      </aside>
    </div>
  </section>

  <section>
    <div class="container">
      <div class="section-head">
        <h2>Scam types we analyze.</h2>
        <p>
          Built for modern online financial fraud, especially cases involving crypto transfers,
          fake platforms, social engineering, and stolen wallet access.
        </p>
      </div>

      <div class="grid-4">
        <article class="card">
          <div class="icon">₿</div>
          <h3>Crypto Investment Scams</h3>
          <p>Fake platforms, manipulated dashboards, blocked withdrawals, and deposit requests.</p>
        </article>
        <article class="card">
          <div class="icon">FX</div>
          <h3>Forex &amp; Trading Fraud</h3>
          <p>Fraudulent brokers, fake profits, liquidation pressure, and withdrawal traps.</p>
        </article>
        <article class="card">
          <div class="icon">♡</div>
          <h3>Romance / Pig-Butchering</h3>
          <p>Long-term manipulation leading to crypto deposits or fake investment accounts.</p>
        </article>
        <article class="card">
          <div class="icon">JOB</div>
          <h3>Remote Job Scams</h3>
          <p>Task platforms, fake payroll, upfront fees, and crypto payment loops.</p>
        </article>
        <article class="card">
          <div class="icon">⚠</div>
          <h3>Phishing &amp; Wallet Drains</h3>
          <p>Malicious links, fake approvals, compromised wallets, and unauthorized transfers.</p>
        </article>
        <article class="card">
          <div class="icon">EX</div>
          <h3>Fake Exchanges</h3>
          <p>Withdrawal blocks, verification fees, tax demands, and fake support teams.</p>
        </article>
        <article class="card">
          <div class="icon">↔</div>
          <h3>Bridge / Mixer Exposure</h3>
          <p>Complex fund movement across chains, services, wallets, and obfuscation tools.</p>
        </article>
        <article class="card">
          <div class="icon">ID</div>
          <h3>Marketplace Fraud</h3>
          <p>Fake buyers, sellers, escrow services, delivery traps, and payment manipulation.</p>
        </article>
      </div>
    </div>
  </section>

  <section id="process" class="process-section">
    <div class="container process-layout">
      <div class="process-copy-card">
        <div class="eyebrow">Investigation pipeline</div>
        <h2 class="section-title">
          A practical process from intake to evidence.
        </h2>
        <p class="section-copy-wide">
          The objective is to move from confusion to structured facts:
          what happened, where funds moved, what can be documented, and what options are realistic.
        </p>

        <div class="process-points">
          <div class="process-point">
            <span>1</span>
            <div>
              <strong>Evidence first</strong>
              <p>The workflow starts with documents, hashes, screenshots, links, and timeline notes.</p>
            </div>
          </div>
          <div class="process-point">
            <span>2</span>
            <div>
              <strong>Technical feasibility</strong>
              <p>Only cases with usable evidence move into meaningful on-chain analysis.</p>
            </div>
          </div>
          <div class="process-point">
            <span>3</span>
            <div>
              <strong>Structured output</strong>
              <p>The final report turns scattered facts into a clear investigation file.</p>
            </div>
          </div>
        </div>
      </div>

      <div class="process-image-frame" aria-label="Investigation process illustration">
        <img src="<?php echo esc_url($theme_uri . '/assets/images/process-pipeline.png'); ?>" alt="Five-step investigation pipeline from case intake to next steps" />
      </div>
    </div>
  </section>

  <section>
    <div class="container warning-panel">
      <div class="eyebrow eyebrow-warning">No false promises</div>
      <h2>Protect yourself from recovery scams.</h2>
      <p>
        Be cautious of anyone who guarantees recovery, claims they can reverse blockchain transactions,
        asks for crypto payments to "unlock" funds, requests your seed phrase, or pressures you to pay urgent fees.
        <?php echo esc_html($brand_name); ?> provides private forensic analysis and guidance - not guaranteed fund recovery.
      </p>
    </div>
  </section>

  <section id="faq">
    <div class="container">
      <div class="section-head">
        <h2>Questions before you submit.</h2>
        <p>Clear answers reduce pressure, confusion, and unrealistic expectations.</p>
      </div>

      <div class="faq">
        <div class="faq-item open">
          <button class="faq-question" type="button">
            Can <?php echo esc_html($brand_name); ?> guarantee my money back?
            <span>-</span>
          </button>
          <div class="faq-answer">
            No. No private tracing provider can honestly guarantee recovery. We can analyze available evidence,
            trace digital asset movement where possible, prepare structured reports, and explain realistic next steps.
          </div>
        </div>

        <div class="faq-item">
          <button class="faq-question" type="button">
            What information should I prepare?
            <span>+</span>
          </button>
          <div class="faq-answer">
            Wallet addresses, transaction hashes, screenshots, emails, chat logs, fake platform URLs,
            payment receipts, bank records, exchange records, and a timeline of events.
          </div>
        </div>

        <div class="faq-item">
          <button class="faq-question" type="button">
            Should I share my seed phrase or private key?
            <span>+</span>
          </button>
          <div class="faq-answer">
            No. Never share seed phrases, private keys, passwords, 2FA codes, or exchange login credentials.
            A legitimate case review does not require those details.
          </div>
        </div>

        <div class="faq-item">
          <button class="faq-question" type="button">
            What happens after I submit the form?
            <span>+</span>
          </button>
          <div class="faq-answer">
            Your case is reviewed for available evidence and technical traceability. If the case appears suitable,
            <?php echo esc_html($brand_name); ?> may contact you to discuss documentation, analysis, and next-step options.
          </div>
        </div>

        <div class="faq-item">
          <button class="faq-question" type="button">
            Do you work with banks, exchanges, or authorities?
            <span>+</span>
          </button>
          <div class="faq-answer">
            We can help organize evidence and documentation that may support communication with relevant institutions,
            legal counsel, or reporting channels. We do not act as law enforcement.
          </div>
        </div>
      </div>
    </div>
  </section>

  <section>
    <div class="container cta-band">
      <div>
        <h2>Start with a free case review.</h2>
        <p>
          Submit what you have. We will help determine whether your case has enough information
          for meaningful tracing and evidence preparation.
        </p>
      </div>
      <a class="btn btn-primary" href="#scanner">Check Your Case</a>
    </div>
  </section>
</main>

<div class="modal" id="quick-contact-modal" aria-hidden="true">
  <div class="modal-backdrop" data-modal-close></div>
  <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="quickContactTitle">
    <form class="form-card short-form" id="quickForm" novalidate>
      <input type="hidden" name="formType" value="quickForm" />
      <input type="text" name="website" class="hp-field" tabindex="-1" autocomplete="off" />
      <div class="form-top">
        <div>
          <strong id="quickContactTitle">Quick Contact</strong>
          <span>Short form</span>
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
        <textarea id="quickMessage" name="message" placeholder="Message" required data-label="Message"></textarea>
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
<?php
get_footer();
