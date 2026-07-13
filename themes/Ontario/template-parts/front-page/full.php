<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}
?>
<main id="top">
  <section class="hero">
    <div class="container hero-grid">
      <div class="hero-content">
        <div class="eyebrow"><?php echo esc_html(ontario_t('hero.badge', [], 'Private blockchain intelligence for fraud victims')); ?></div>
        <h1>
          <?php echo wp_kses_post(ontario_t('hero.title', [], 'Trace lost digital assets with <span class="gradient-text">forensic precision.</span>')); ?>
        </h1>
        <p class="hero-copy">
          <?php echo wp_kses_post(ontario_t('hero.copy', ['brand_name' => $brand_name], $brand_name . ' helps victims of online financial fraud map crypto transactions.')); ?>
        </p>

        <div class="hero-actions">
          <a class="btn btn-primary" href="#quick-contact-modal" data-modal-open><?php echo esc_html(ontario_t('hero.cta_primary', [], 'Get A Free Consultation')); ?></a>
          <a class="btn btn-secondary" href="#report"><?php echo esc_html(ontario_t('hero.cta_secondary', [], 'View Report Preview')); ?></a>
        </div>

        <div class="micro-trust" aria-label="<?php echo esc_attr(ontario_t('site.trust_signals', [], 'Trust signals')); ?>">
          <span><b></b> <?php echo esc_html(ontario_t('hero.trust.encrypted', [], 'Encrypted submission')); ?></span>
          <span><b></b> <?php echo esc_html(ontario_t('hero.trust.pipeda', [], 'PIPEDA compliant')); ?></span>
          <span><b></b> <?php echo esc_html(ontario_t('hero.trust.soc2', [], 'SOC 2 Type II')); ?></span>
          <?php if ($working_hours !== '') : ?>
            <span><b></b> <?php echo esc_html($working_hours); ?></span>
          <?php endif; ?>
        </div>
      </div>

      <div class="hero-visual" aria-label="<?php echo esc_attr(ontario_t('hero.visual_aria', [], 'Forensic dashboard illustration')); ?>">
        <div class="hero-image-frame">
          <img src="<?php echo esc_url($theme_uri . '/assets/images/hero-forensic-dashboard.png'); ?>" alt="<?php echo esc_attr(ontario_t('hero.visual_alt', [], 'Forensic blockchain tracing dashboard with wallet flow analysis')); ?>" />
        </div>
      </div>
    </div>
  </section>

  <div class="strip">
    <div class="container strip-inner">
      <div class="strip-card">
        <strong><?php echo esc_html(ontario_t('strip.distinction_title', [], 'Important distinction')); ?></strong>
        <p><?php echo esc_html(ontario_t('strip.distinction_copy', [], 'We do not reverse blockchain transactions or guarantee recovery. We trace, document, and guide.')); ?></p>
      </div>
      <div class="strip-card">
        <strong><?php echo esc_html(ontario_t('strip.crypto_title', [], 'Crypto & digital assets')); ?></strong>
        <p><?php echo esc_html(ontario_t('strip.crypto_copy', [], 'Wallets, transaction hashes, exchanges, bridges, mixers, and suspicious fund movement.')); ?></p>
      </div>
      <div class="strip-card">
        <strong><?php echo esc_html(ontario_t('strip.reports_title', [], 'Evidence-based reports')); ?></strong>
        <p><?php echo esc_html(ontario_t('strip.reports_copy', [], 'Structured documentation for victims, institutions, counsel, and reporting workflows.')); ?></p>
      </div>
      <?php if ($address !== '') : ?>
        <div class="strip-card">
          <strong><?php echo esc_html(ontario_t('strip.support_title', [], 'Canadian support')); ?></strong>
          <p><?php echo esc_html($address); ?></p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <section id="services">
    <div class="container">
      <div class="section-head">
        <h2><?php echo esc_html(ontario_t('services.title', ['brand_name' => $brand_name], 'What ' . $brand_name . ' actually does.')); ?></h2>
        <p><?php echo esc_html(ontario_t('services.copy', [], 'A clear forensic workflow for people who lost funds to online scams, fake investment platforms, wallet drains, phishing, or fraudulent brokers.')); ?></p>
      </div>

      <div class="grid-4">
        <article class="card">
          <div class="icon">⌁</div>
          <h3><?php echo esc_html(ontario_t('services.trace_title', [], 'Transaction Tracing')); ?></h3>
          <p><?php echo esc_html(ontario_t('services.trace_copy', [], 'We analyze wallet addresses, transaction hashes, fund movement, exchange exposure, and on-chain patterns where technically possible.')); ?></p>
        </article>

        <article class="card">
          <div class="icon">◌</div>
          <h3><?php echo esc_html(ontario_t('services.mapping_title', [], 'Wallet & Entity Mapping')); ?></h3>
          <p><?php echo esc_html(ontario_t('services.mapping_copy', [], 'We map connected wallets, suspicious clusters, possible service touchpoints, bridges, mixers, and destination patterns.')); ?></p>
        </article>

        <article class="card">
          <div class="icon">▣</div>
          <h3><?php echo esc_html(ontario_t('services.report_title', [], 'Evidence Report')); ?></h3>
          <p><?php echo esc_html(ontario_t('services.report_copy', [], 'You receive a structured report with transaction paths, timeline, wallet details, findings, and recommended documentation steps.')); ?></p>
        </article>

        <article class="card">
          <div class="icon">→</div>
          <h3><?php echo esc_html(ontario_t('services.guidance_title', [], 'Next-Step Guidance')); ?></h3>
          <p><?php echo esc_html(ontario_t('services.guidance_copy', [], 'We help you understand practical reporting, legal, institutional, and documentation options based on the case facts.')); ?></p>
        </article>
      </div>
    </div>
  </section>

  <section id="scanner">
    <div class="container scanner">
      <div class="scanner-panel">
        <div class="eyebrow"><?php echo esc_html(ontario_t('scanner.badge', [], 'Case Eligibility Scanner')); ?></div>
        <h2><?php echo esc_html(ontario_t('scanner.title', [], 'Submit your case for a free initial review.')); ?></h2>
        <p><?php echo esc_html(ontario_t('scanner.copy', [], 'The stronger your evidence, the more useful the tracing analysis can be. Prepare wallet addresses, transaction hashes, screenshots, platform URLs, chat logs, emails, and payment records.')); ?></p>

        <ul class="check-list">
          <li><?php echo esc_html(ontario_t('scanner.point_1', [], 'Secure intake for online fraud and crypto loss cases.')); ?></li>
          <li><?php echo esc_html(ontario_t('scanner.point_2', [], 'Initial feasibility check before deeper forensic work.')); ?></li>
          <li><?php echo esc_html(ontario_t('scanner.point_3', [], 'Clear explanation of what can and cannot be done.')); ?></li>
          <li><?php echo esc_html(ontario_t('scanner.point_4', [], 'No guaranteed recovery claims or misleading promises.')); ?></li>
        </ul>
      </div>

      <?php include locate_template('template-parts/front-page/case-form.php'); ?>
    </div>
  </section>

  <section id="report">
    <div class="container split">
      <div>
        <div class="eyebrow"><?php echo esc_html(ontario_t('report.badge', [], 'Forensic report preview')); ?></div>
        <h2 class="section-title">
          <?php echo esc_html(ontario_t('report.title', [], 'Turn fragmented evidence into a structured investigation file.')); ?>
        </h2>
        <p class="section-copy-wide"><?php echo esc_html(ontario_t('report.copy', [], 'A clear report helps organize the timeline, wallet data, transaction paths, suspected services, case notes, and realistic next-step options.')); ?></p>

        <div class="grid-3">
          <article class="card">
            <h3><?php echo esc_html(ontario_t('report.card_path_title', [], 'Transaction path')); ?></h3>
            <p><?php echo esc_html(ontario_t('report.card_path_copy', [], 'Source wallet, intermediary wallets, exchanges, bridges, and destination clusters.')); ?></p>
          </article>
          <article class="card">
            <h3><?php echo esc_html(ontario_t('report.card_timeline_title', [], 'Evidence timeline')); ?></h3>
            <p><?php echo esc_html(ontario_t('report.card_timeline_copy', [], 'Chronological structure for transfers, communication, fake platform activity, and reporting history.')); ?></p>
          </article>
          <article class="card">
            <h3><?php echo esc_html(ontario_t('report.card_notes_title', [], 'Actionable notes')); ?></h3>
            <p><?php echo esc_html(ontario_t('report.card_notes_copy', [], 'Practical next steps based on the findings, not generic promises or pressure tactics.')); ?></p>
          </article>
        </div>
      </div>

      <aside class="report" aria-label="<?php echo esc_attr(ontario_t('site.sample_report', [], 'Sample report')); ?>">
        <div class="report-header">
          <strong><?php echo esc_html(ontario_t('report.header_title', [], 'Sample Tracing Report')); ?></strong>
          <span><?php echo esc_html(ontario_t('report.header_preview', [], 'Preview')); ?></span>
        </div>
        <div class="report-body">
          <div class="report-row">
            <small><?php echo esc_html(ontario_t('report.case_id', [], 'Case ID')); ?></small>
            <div class="hash">OR-CA-2026-0418</div>
          </div>
          <div class="report-row">
            <small><?php echo esc_html(ontario_t('report.scam_type', [], 'Scam type')); ?></small>
            <div><?php echo esc_html(ontario_t('report.value_scam_type', [], 'Fake crypto investment platform')); ?></div>
          </div>
          <div class="report-row">
            <small><?php echo esc_html(ontario_t('report.source_tx', [], 'Source TX')); ?></small>
            <div class="hash">0x49d7...a81c9f7b2e</div>
          </div>
          <div class="report-row">
            <small><?php echo esc_html(ontario_t('report.path', [], 'Observed path')); ?></small>
            <div><?php echo esc_html(ontario_t('report.value_path', [], 'Victim wallet → intermediary wallets → mixer exposure → exchange deposit cluster')); ?></div>
          </div>
          <div class="report-row">
            <small><?php echo esc_html(ontario_t('report.findings', [], 'Findings')); ?></small>
            <div><?php echo esc_html(ontario_t('report.value_findings', [], 'Multiple linked wallets identified. Further documentation recommended before institutional outreach.')); ?></div>
          </div>
        </div>
      </aside>
    </div>
  </section>

  <section>
    <div class="container">
      <div class="section-head">
        <h2><?php echo esc_html(ontario_t('scam_types.title', [], 'Scam types we analyze.')); ?></h2>
        <p><?php echo esc_html(ontario_t('scam_types.copy', [], 'Built for modern online financial fraud, especially cases involving crypto transfers, fake platforms, social engineering, and stolen wallet access.')); ?></p>
      </div>

      <div class="grid-4">
        <article class="card"><div class="icon">₿</div><h3><?php echo esc_html(ontario_t('scam_types.crypto_title', [], 'Crypto Investment Scams')); ?></h3><p><?php echo esc_html(ontario_t('scam_types.crypto_copy', [], 'Fake platforms, manipulated dashboards, blocked withdrawals, and deposit requests.')); ?></p></article>
        <article class="card"><div class="icon">FX</div><h3><?php echo esc_html(ontario_t('scam_types.forex_title', [], 'Forex & Trading Fraud')); ?></h3><p><?php echo esc_html(ontario_t('scam_types.forex_copy', [], 'Fraudulent brokers, fake profits, liquidation pressure, and withdrawal traps.')); ?></p></article>
        <article class="card"><div class="icon">♡</div><h3><?php echo esc_html(ontario_t('scam_types.romance_title', [], 'Romance / Pig-Butchering')); ?></h3><p><?php echo esc_html(ontario_t('scam_types.romance_copy', [], 'Long-term manipulation leading to crypto deposits or fake investment accounts.')); ?></p></article>
        <article class="card"><div class="icon">JOB</div><h3><?php echo esc_html(ontario_t('scam_types.job_title', [], 'Remote Job Scams')); ?></h3><p><?php echo esc_html(ontario_t('scam_types.job_copy', [], 'Task platforms, fake payroll, upfront fees, and crypto payment loops.')); ?></p></article>
        <article class="card"><div class="icon">⚠</div><h3><?php echo esc_html(ontario_t('scam_types.phishing_title', [], 'Phishing & Wallet Drains')); ?></h3><p><?php echo esc_html(ontario_t('scam_types.phishing_copy', [], 'Malicious links, fake approvals, compromised wallets, and unauthorized transfers.')); ?></p></article>
        <article class="card"><div class="icon">EX</div><h3><?php echo esc_html(ontario_t('scam_types.exchange_title', [], 'Fake Exchanges')); ?></h3><p><?php echo esc_html(ontario_t('scam_types.exchange_copy', [], 'Withdrawal blocks, verification fees, tax demands, and fake support teams.')); ?></p></article>
        <article class="card"><div class="icon">↔</div><h3><?php echo esc_html(ontario_t('scam_types.bridge_title', [], 'Bridge / Mixer Exposure')); ?></h3><p><?php echo esc_html(ontario_t('scam_types.bridge_copy', [], 'Complex fund movement across chains, services, wallets, and obfuscation tools.')); ?></p></article>
        <article class="card"><div class="icon">ID</div><h3><?php echo esc_html(ontario_t('scam_types.marketplace_title', [], 'Marketplace Fraud')); ?></h3><p><?php echo esc_html(ontario_t('scam_types.marketplace_copy', [], 'Fake buyers, sellers, escrow services, delivery traps, and payment manipulation.')); ?></p></article>
      </div>
    </div>
  </section>

  <section id="process" class="process-section">
    <div class="container process-layout">
      <div class="process-copy-card">
        <div class="eyebrow"><?php echo esc_html(ontario_t('process.badge', [], 'Investigation pipeline')); ?></div>
        <h2 class="section-title"><?php echo esc_html(ontario_t('process.title', [], 'A practical process from intake to evidence.')); ?></h2>
        <p class="section-copy-wide"><?php echo esc_html(ontario_t('process.copy', [], 'The objective is to move from confusion to structured facts: what happened, where funds moved, what can be documented, and what options are realistic.')); ?></p>

        <div class="process-points">
          <div class="process-point"><span>1</span><div><strong><?php echo esc_html(ontario_t('process.step_1_title', [], 'Evidence first')); ?></strong><p><?php echo esc_html(ontario_t('process.step_1_copy', [], 'The workflow starts with documents, hashes, screenshots, links, and timeline notes.')); ?></p></div></div>
          <div class="process-point"><span>2</span><div><strong><?php echo esc_html(ontario_t('process.step_2_title', [], 'Technical feasibility')); ?></strong><p><?php echo esc_html(ontario_t('process.step_2_copy', [], 'Only cases with usable evidence move into meaningful on-chain analysis.')); ?></p></div></div>
          <div class="process-point"><span>3</span><div><strong><?php echo esc_html(ontario_t('process.step_3_title', [], 'Structured output')); ?></strong><p><?php echo esc_html(ontario_t('process.step_3_copy', [], 'The final report turns scattered facts into a clear investigation file.')); ?></p></div></div>
        </div>
      </div>

      <div class="process-image-frame" aria-label="<?php echo esc_attr(ontario_t('process.visual_aria', [], 'Investigation process illustration')); ?>">
        <img src="<?php echo esc_url($theme_uri . '/assets/images/process-pipeline.png'); ?>" alt="<?php echo esc_attr(ontario_t('process.visual_alt', [], 'Five-step investigation pipeline from case intake to next steps')); ?>" />
      </div>
    </div>
  </section>

  <section>
    <div class="container warning-panel">
      <div class="eyebrow eyebrow-warning"><?php echo esc_html(ontario_t('warning.badge', [], 'No false promises')); ?></div>
      <h2><?php echo esc_html(ontario_t('warning.title', [], 'Protect yourself from recovery scams.')); ?></h2>
      <p><?php echo esc_html(ontario_t('warning.copy', ['brand_name' => $brand_name], 'Be cautious of anyone who guarantees recovery.')); ?></p>
    </div>
  </section>

  <section id="faq">
    <div class="container">
      <div class="section-head">
        <h2><?php echo esc_html(ontario_t('faq.title', [], 'Questions before you submit.')); ?></h2>
        <p><?php echo esc_html(ontario_t('faq.copy', [], 'Clear answers reduce pressure, confusion, and unrealistic expectations.')); ?></p>
      </div>

      <div class="faq">
        <div class="faq-item open"><button class="faq-question" type="button"><?php echo esc_html(ontario_t('faq.q1', ['brand_name' => $brand_name], 'Can ' . $brand_name . ' guarantee my money back?')); ?><span>-</span></button><div class="faq-answer"><?php echo esc_html(ontario_t('faq.a1', [], 'No. No private tracing provider can honestly guarantee recovery.')); ?></div></div>
        <div class="faq-item"><button class="faq-question" type="button"><?php echo esc_html(ontario_t('faq.q2', [], 'What information should I prepare?')); ?><span>+</span></button><div class="faq-answer"><?php echo esc_html(ontario_t('faq.a2', [], 'Wallet addresses, transaction hashes, screenshots, emails, chat logs, fake platform URLs, payment receipts, bank records, exchange records, and a timeline of events.')); ?></div></div>
        <div class="faq-item"><button class="faq-question" type="button"><?php echo esc_html(ontario_t('faq.q3', [], 'Should I share my seed phrase or private key?')); ?><span>+</span></button><div class="faq-answer"><?php echo esc_html(ontario_t('faq.a3', [], 'No. Never share seed phrases, private keys, passwords, 2FA codes, or exchange login credentials.')); ?></div></div>
        <div class="faq-item"><button class="faq-question" type="button"><?php echo esc_html(ontario_t('faq.q4', [], 'What happens after I submit the form?')); ?><span>+</span></button><div class="faq-answer"><?php echo esc_html(ontario_t('faq.a4', ['brand_name' => $brand_name], 'Your case is reviewed for available evidence and technical traceability.')); ?></div></div>
        <div class="faq-item"><button class="faq-question" type="button"><?php echo esc_html(ontario_t('faq.q5', [], 'Do you work with banks, exchanges, or authorities?')); ?><span>+</span></button><div class="faq-answer"><?php echo esc_html(ontario_t('faq.a5', [], 'We can help organize evidence and documentation.')); ?></div></div>
      </div>
    </div>
  </section>

  <section>
    <div class="container cta-band">
      <div>
        <h2><?php echo esc_html(ontario_t('cta.title', [], 'Start with a free case review.')); ?></h2>
        <p><?php echo esc_html(ontario_t('cta.copy', [], 'Submit what you have. We will help determine whether your case has enough information for meaningful tracing and evidence preparation.')); ?></p>
      </div>
      <a class="btn btn-primary" href="#scanner"><?php echo esc_html(ontario_t('cta.button', [], 'Check Your Case')); ?></a>
    </div>
  </section>
</main>
