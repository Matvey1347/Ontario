<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

get_header();
$legal_page_key = ontario_legal_page_key();
$legal_page = $legal_page_key !== '' ? ontario_legal_page_content($legal_page_key) : [];
$website = (string) (ontario_current_site()['resolved_host'] ?? wp_parse_url(home_url('/'), PHP_URL_HOST) ?? '');
$brand_name = ontario_site_brand_name();
$email = ontario_site_field('public_email');
$phone = ontario_site_field('phone_number');
$address = ontario_site_field('address');
?>
<main class="legal-page">
  <div class="container">
    <article class="legal-shell">
      <h1><?php echo esc_html($legal_page !== [] ? (string) $legal_page['title'] : get_the_title()); ?></h1>
      <div class="legal-content">
        <?php if ($legal_page !== []) : ?>
          <p><strong><?php echo esc_html((string) $legal_page['effective_date']); ?></strong></p>
          <?php foreach ((array) ($legal_page['intro'] ?? []) as $paragraph) : ?>
            <p><?php echo esc_html((string) $paragraph); ?></p>
          <?php endforeach; ?>
          <?php foreach ((array) ($legal_page['sections'] ?? []) as $section) : ?>
            <h2><?php echo esc_html((string) ($section['heading'] ?? '')); ?></h2>
            <?php foreach ((array) ($section['paragraphs'] ?? []) as $paragraph) : ?>
              <p><?php echo esc_html((string) $paragraph); ?></p>
            <?php endforeach; ?>
            <?php if (! empty($section['list']) && is_array($section['list'])) : ?>
              <ul>
                <?php foreach ($section['list'] as $item) : ?>
                  <li><?php echo esc_html((string) $item); ?></li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
            <?php foreach ((array) ($section['closing'] ?? []) as $paragraph) : ?>
              <?php if (str_contains((string) $paragraph, ': ') && ! str_contains((string) $paragraph, '.')) : ?>
                <p><strong><?php echo esc_html((string) $paragraph); ?></strong></p>
              <?php else : ?>
                <p><?php echo esc_html((string) $paragraph); ?></p>
              <?php endif; ?>
            <?php endforeach; ?>
            <?php if (! empty($section['contact_block'])) : ?>
              <div class="legal-contact-block">
                <p><?php echo esc_html($brand_name); ?></p>
                <p><?php echo esc_html(ontario_t('legal.contact.website', [], 'Website:')); ?> <?php echo esc_html($website); ?></p>
                <?php if ($email !== '') : ?><p><?php echo esc_html(ontario_t('legal.contact.email', [], 'Email:')); ?> <?php echo esc_html($email); ?></p><?php endif; ?>
                <?php if ($phone !== '') : ?><p><?php echo esc_html(ontario_t('legal.contact.phone', [], 'Phone:')); ?> <?php echo esc_html($phone); ?></p><?php endif; ?>
                <?php if ($address !== '') : ?><p><?php echo esc_html(ontario_t('legal.contact.address', [], 'Address:')); ?> <?php echo esc_html($address); ?></p><?php endif; ?>
              </div>
            <?php endif; ?>
          <?php endforeach; ?>
        <?php else : ?>
          <?php
          while (have_posts()) :
              the_post();
              the_content();
          endwhile;
          ?>
        <?php endif; ?>
      </div>
    </article>
  </div>
</main>
<?php
get_footer();
