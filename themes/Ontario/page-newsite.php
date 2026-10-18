<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

$brand_name = ontario_site_brand_name();

get_header();
?>
<main id="top" class="newsite-page">
  <section class="newsite-hero" aria-labelledby="newsite-title">
    <div class="container newsite-hero__grid">
      <div>
        <p class="newsite-kicker"><?php echo esc_html(ontario_t('newsite.hero.kicker', [], 'Private, professional case assessment')); ?></p>
        <h1 id="newsite-title"><?php echo esc_html(ontario_t('newsite.hero.title', [], 'Support after an online scam starts with clear information.')); ?></h1>
        <p class="newsite-hero__lead"><?php echo esc_html(ontario_t('newsite.hero.copy', ['brand_name' => $brand_name], '{brand_name} reviews the details you can provide, helps organize the available information, and identifies practical next steps.')); ?></p>
        <div class="newsite-actions">
          <a class="btn btn-primary" href="#contact"><?php echo esc_html(ontario_t('newsite.hero.cta', [], 'Request a case assessment')); ?></a>
          <a class="newsite-text-link" href="#process"><?php echo esc_html(ontario_t('newsite.hero.secondary_cta', [], 'How the review works')); ?></a>
        </div>
        <p class="newsite-disclosure"><?php echo esc_html(ontario_t('newsite.hero.disclosure', [], 'We do not guarantee recovery or promise a particular outcome.')); ?></p>
      </div>
      <aside class="newsite-hero__panel" aria-label="<?php echo esc_attr(ontario_t('newsite.hero.panel_aria', [], 'Case assessment overview')); ?>">
        <h2><?php echo esc_html(ontario_t('newsite.hero.panel_title', [], 'Start with the facts you have')); ?></h2>
        <ul>
          <li><?php echo esc_html(ontario_t('newsite.hero.panel_1', [], 'A clear account of what happened')); ?></li>
          <li><?php echo esc_html(ontario_t('newsite.hero.panel_2', [], 'Transaction, payment, or platform details')); ?></li>
          <li><?php echo esc_html(ontario_t('newsite.hero.panel_3', [], 'Screenshots, messages, and a timeline if available')); ?></li>
        </ul>
      </aside>
    </div>
  </section>

  <section id="services" class="newsite-section">
    <div class="container">
      <div class="newsite-heading">
        <p class="newsite-kicker"><?php echo esc_html(ontario_t('newsite.services.kicker', [], 'A practical first response')); ?></p>
        <h2><?php echo esc_html(ontario_t('newsite.services.title', [], 'Help for online scam and cybercrime cases')); ?></h2>
        <p><?php echo esc_html(ontario_t('newsite.services.copy', [], 'Whether you need to report a scam in Canada, prepare a cyber crime complaint, or understand a suspected fraud, the first step is to preserve and organize the facts.')); ?></p>
      </div>
      <div class="newsite-card-grid">
        <article class="newsite-card"><h3><?php echo esc_html(ontario_t('newsite.services.card_1_title', [], 'Case assessment')); ?></h3><p><?php echo esc_html(ontario_t('newsite.services.card_1_copy', [], 'We review the information you provide to understand the incident, the payment path, and what evidence may be useful.')); ?></p></article>
        <article class="newsite-card"><h3><?php echo esc_html(ontario_t('newsite.services.card_2_title', [], 'Evidence organization')); ?></h3><p><?php echo esc_html(ontario_t('newsite.services.card_2_copy', [], 'We can help structure records such as payment receipts, wallet addresses, platform links, communications, and dates.')); ?></p></article>
        <article class="newsite-card"><h3><?php echo esc_html(ontario_t('newsite.services.card_3_title', [], 'Next-step guidance')); ?></h3><p><?php echo esc_html(ontario_t('newsite.services.card_3_copy', [], 'We explain possible documentation, reporting, institutional, or legal follow-up based on the circumstances of the case.')); ?></p></article>
      </div>
    </div>
  </section>

  <section id="scam-types" class="newsite-section newsite-section--soft">
    <div class="container">
      <div class="newsite-heading"><h2><?php echo esc_html(ontario_t('newsite.types.title', [], 'Common situations we can assess')); ?></h2><p><?php echo esc_html(ontario_t('newsite.types.copy', [], 'The same intake can cover multiple types of online fraud. Sharing the right details early can make later reporting and review more focused.')); ?></p></div>
      <div class="newsite-type-grid">
        <article><h3><?php echo esc_html(ontario_t('newsite.types.crypto_title', [], 'Crypto scam recovery questions')); ?></h3><p><?php echo esc_html(ontario_t('newsite.types.crypto_copy', [], 'Fake exchanges, investment platforms, wallet drains, unauthorized transfers, and requests for additional crypto payments.')); ?></p></article>
        <article><h3><?php echo esc_html(ontario_t('newsite.types.investment_title', [], 'Investment scam recovery help')); ?></h3><p><?php echo esc_html(ontario_t('newsite.types.investment_copy', [], 'Misleading investment offers, fraudulent brokers, manipulated dashboards, and blocked withdrawals.')); ?></p></article>
        <article><h3><?php echo esc_html(ontario_t('newsite.types.task_title', [], 'Online task scam recovery')); ?></h3><p><?php echo esc_html(ontario_t('newsite.types.task_copy', [], 'Task platforms, fake job offers, deposit requirements, and payment loops presented as employment.')); ?></p></article>
        <article><h3><?php echo esc_html(ontario_t('newsite.types.payment_title', [], 'Scammed out of money online')); ?></h3><p><?php echo esc_html(ontario_t('newsite.types.payment_copy', [], 'Card, bank, e-transfer, payment-app, marketplace, romance, phishing, and impersonation scams.')); ?></p></article>
      </div>
    </div>
  </section>

  <section id="process" class="newsite-section">
    <div class="container newsite-process">
      <div class="newsite-heading"><p class="newsite-kicker"><?php echo esc_html(ontario_t('newsite.process.kicker', [], 'What happens next')); ?></p><h2><?php echo esc_html(ontario_t('newsite.process.title', [], 'A clear process, without false promises')); ?></h2></div>
      <ol>
        <li><strong><?php echo esc_html(ontario_t('newsite.process.step_1_title', [], 'Share the available details')); ?></strong><span><?php echo esc_html(ontario_t('newsite.process.step_1_copy', [], 'Tell us what happened and include the documents or records you have.')); ?></span></li>
        <li><strong><?php echo esc_html(ontario_t('newsite.process.step_2_title', [], 'We review the case')); ?></strong><span><?php echo esc_html(ontario_t('newsite.process.step_2_copy', [], 'Our team considers the information and the possible scope for further work.')); ?></span></li>
        <li><strong><?php echo esc_html(ontario_t('newsite.process.step_3_title', [], 'Discuss possible next steps')); ?></strong><span><?php echo esc_html(ontario_t('newsite.process.step_3_copy', [], 'If appropriate, we explain useful evidence, reporting routes, and available options.')); ?></span></li>
      </ol>
    </div>
  </section>

  <section class="newsite-section newsite-safety"><div class="container"><h2><?php echo esc_html(ontario_t('newsite.safety.title', [], 'Choose help carefully')); ?></h2><p><?php echo esc_html(ontario_t('newsite.safety.copy', [], 'Be cautious of anyone presenting themselves as a funds recovery company that guarantees results, promises money back within a fixed time, or asks for secret wallet information. Legitimate case assessment does not require your seed phrase, private key, password, or one-time security code.')); ?></p></div></section>

  <section id="faq" class="newsite-section"><div class="container newsite-faq"><div class="newsite-heading"><h2><?php echo esc_html(ontario_t('newsite.faq.title', [], 'Questions before you submit')); ?></h2></div>
    <?php for ($faq_index = 1; $faq_index <= 4; $faq_index++) : ?>
      <details><summary><?php echo esc_html(ontario_t('newsite.faq.q' . $faq_index, [], '')); ?></summary><p><?php echo esc_html(ontario_t('newsite.faq.a' . $faq_index, ['brand_name' => $brand_name], '')); ?></p></details>
    <?php endfor; ?>
  </div></section>

  <section id="contact" class="newsite-contact"><div class="container newsite-contact__grid"><div><p class="newsite-kicker"><?php echo esc_html(ontario_t('newsite.contact.kicker', [], 'Private intake')); ?></p><h2><?php echo esc_html(ontario_t('newsite.contact.title', [], 'Request a Professional Case Assessment')); ?></h2><p><?php echo esc_html(ontario_t('newsite.contact.copy', [], 'Tell us about your situation and provide the available details. Our team can review the information and help identify possible next steps.')); ?></p></div><div class="newsite-contact__form"><?php include locate_template('template-parts/front-page/case-form.php'); ?></div></div></section>
</main>
<?php get_footer();
