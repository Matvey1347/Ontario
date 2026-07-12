<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}
?>
<main id="top" class="simple-front-page">
  <section class="simple-hero">
    <div class="container simple-hero-grid">
      <div class="simple-hero-copy">
        <div class="eyebrow">Clear private case review</div>
        <h1>Get calm, step-by-step help reviewing a suspected financial fraud case.</h1>
        <p class="simple-lead">
          <?php echo esc_html($brand_name); ?> helps people organize evidence, understand what happened, and learn what practical next steps may be available.
        </p>
        <p class="simple-hero-note">
          We do not guarantee fund recovery and we do not make false promises. We focus on documentation, tracing, and clear guidance.
        </p>
        <div class="simple-hero-actions">
          <a class="btn btn-primary" href="#scanner">Start Free Case Review</a>
          <a class="btn btn-secondary" href="#report">See Report Example</a>
        </div>
        <ul class="simple-trust-list">
          <li>Private consultation</li>
          <li>Encrypted submission</li>
          <?php if ($working_hours !== '') : ?>
            <li><?php echo esc_html($working_hours); ?></li>
          <?php endif; ?>
        </ul>
      </div>

      <div class="simple-hero-visual">
        <img src="<?php echo esc_url(ontario_simple_image_uri('hero')); ?>" alt="Consultant helping a couple review a financial fraud case" />
      </div>
    </div>
  </section>

  <section class="simple-summary">
    <div class="container simple-summary-grid">
      <article class="simple-summary-card">
        <h2>What we help with</h2>
        <p>We review transfers, fake platforms, suspicious wallets, screenshots, emails, and timelines to understand how the case developed.</p>
      </article>
      <article class="simple-summary-card">
        <h2>What you receive</h2>
        <p>A structured explanation of the evidence, the observed fund movement, and practical documentation steps for your situation.</p>
      </article>
      <article class="simple-summary-card">
        <h2>Important note</h2>
        <p>We do not reverse blockchain transactions and we do not guarantee recovery. Our role is evidence review and guidance.</p>
      </article>
      <?php if ($address !== '') : ?>
        <article class="simple-summary-card">
          <h2>Support location</h2>
          <p><?php echo esc_html($address); ?></p>
        </article>
      <?php endif; ?>
    </div>
  </section>

  <section id="services" class="simple-services">
    <div class="container">
      <div class="section-head simple-section-head simple-section-head-stack simple-section-head-wide">
        <h2>How <?php echo esc_html($brand_name); ?> can help?</h2>
        <p>Every step is designed to make the case easier to understand, document, and review with confidence.</p>
      </div>

      <div class="simple-services-grid">
        <article class="simple-service-card"><h3>Case review</h3><p>We review the available information to understand the transaction path, the platform involved, and the evidence you already have.</p></article>
        <article class="simple-service-card"><h3>Evidence organization</h3><p>We help organize screenshots, wallet addresses, transfers, and timeline details into a format that is easier to review and document.</p></article>
        <article class="simple-service-card"><h3>Tracing analysis</h3><p>When the evidence supports it, we analyze how digital assets moved between wallets, services, or exchanges.</p></article>
        <article class="simple-service-card"><h3>Next-step guidance</h3><p>We explain what realistic reporting, legal, institutional, or documentation steps may make sense for the case.</p></article>
      </div>
    </div>
  </section>

  <section id="scanner" class="simple-scanner">
    <div class="container simple-scanner-grid">
      <div class="simple-scanner-copy">
        <div class="eyebrow">Free initial review</div>
        <h2>Submit your case in a clear and secure way.</h2>
        <p>Prepare any wallet addresses, transaction hashes, screenshots, links, emails, payment records, and notes that explain what happened.</p>
        <img src="<?php echo esc_url(ontario_simple_image_uri('scanner')); ?>" alt="Secure case submission checklist" />
      </div>

      <?php include locate_template('template-parts/front-page/case-form.php'); ?>
    </div>
  </section>

  <section id="report" class="simple-report">
    <div class="container simple-report-grid">
      <div class="simple-report-copy">
        <div class="eyebrow">Report example</div>
        <h2>See how the findings can be organized into a structured review.</h2>
        <p>A clear report can make it easier to understand the timeline, the observed transfers, the suspicious touchpoints, and the next practical steps.</p>
        <ul class="simple-report-list">
          <li>Transaction path summary</li>
          <li>Case timeline and supporting evidence</li>
          <li>Observed wallets and service touchpoints</li>
          <li>Documentation and follow-up guidance</li>
        </ul>
      </div>
      <aside class="report simple-report-card" aria-label="Sample report">
        <div class="report-header">
          <strong>Sample Case Review</strong>
          <span>Preview</span>
        </div>
        <div class="report-body">
          <div class="report-row">
            <small>Case focus</small>
            <div>Crypto investment fraud review</div>
          </div>
          <div class="report-row">
            <small>Included</small>
            <div>Timeline, wallet checks, screenshots, transfer path summary</div>
          </div>
          <div class="report-row">
            <small>Purpose</small>
            <div>Help organize the facts and explain practical next-step options</div>
          </div>
          <div class="report-row">
            <small>Output</small>
            <div>Clear documentation for review, follow-up, and reporting</div>
          </div>
        </div>
      </aside>
    </div>
  </section>

  <section id="process" class="simple-process">
    <div class="container simple-process-grid">
      <div class="simple-process-visual">
        <img src="<?php echo esc_url(ontario_simple_image_uri('process')); ?>" alt="Five-step case review and investigation process" />
      </div>
      <div class="simple-process-copy">
        <div class="eyebrow">Simple process</div>
        <h2>What the review process usually looks like.</h2>
        <ol class="simple-process-list">
          <li><strong>Contact us</strong><span>Share the main facts and any available evidence.</span></li>
          <li><strong>Review</strong><span>We assess the case details and technical feasibility.</span></li>
          <li><strong>Trace</strong><span>When possible, we analyze transaction movement and connected activity.</span></li>
          <li><strong>Report</strong><span>We organize the findings into a clear evidence-based summary.</span></li>
          <li><strong>Next steps</strong><span>We explain realistic documentation and follow-up options.</span></li>
        </ol>
      </div>
    </div>
  </section>

  <section class="simple-warning">
    <div class="container simple-warning-shell">
      <h2>Be careful of recovery scams.</h2>
      <p>Be cautious of anyone who guarantees they can get your money back, asks for urgent crypto payments, or requests secret wallet information. Legitimate review does not require your seed phrase or private keys.</p>
    </div>
  </section>

  <section id="faq" class="simple-faq">
    <div class="container">
      <div class="section-head simple-section-head simple-section-head-stack">
        <h2>Common questions</h2>
        <p>Clear answers can reduce pressure and confusion.</p>
      </div>

      <div class="faq">
        <div class="faq-item open"><button class="faq-question" type="button">Can <?php echo esc_html($brand_name); ?> guarantee my money back?<span>-</span></button><div class="faq-answer">No. We do not guarantee recovery. We review evidence, analyze available data, and explain realistic next steps.</div></div>
        <div class="faq-item"><button class="faq-question" type="button">What should I prepare before I submit?<span>+</span></button><div class="faq-answer">Prepare screenshots, emails, platform links, payment records, transaction hashes, wallet addresses, and a basic timeline.</div></div>
        <div class="faq-item"><button class="faq-question" type="button">Do I need to share my passwords or seed phrase?<span>+</span></button><div class="faq-answer">No. Never share private keys, seed phrases, passwords, or one-time security codes.</div></div>
        <div class="faq-item"><button class="faq-question" type="button">What happens after I submit?<span>+</span></button><div class="faq-answer">Your case is reviewed for available evidence and technical traceability. If it appears suitable, we may contact you to discuss the next stage.</div></div>
      </div>
    </div>
  </section>

  <section class="simple-cta">
    <div class="container simple-cta-shell">
      <h2>Start with a free case review.</h2>
      <p>Submit what you have and we will review whether the case has enough information for meaningful analysis.</p>
      <a class="btn btn-primary" href="#scanner">Start Free Case Review</a>
    </div>
  </section>
</main>
