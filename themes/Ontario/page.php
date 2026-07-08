<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

get_header();
?>
<main class="legal-page">
  <div class="container">
    <article class="legal-shell">
      <h1><?php the_title(); ?></h1>
      <div class="legal-content">
        <?php
        while (have_posts()) :
            the_post();
            the_content();
        endwhile;
        ?>
      </div>
    </article>
  </div>
</main>
<?php
get_footer();
