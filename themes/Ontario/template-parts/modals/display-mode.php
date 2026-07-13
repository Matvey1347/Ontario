<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="modal display-choice-modal" id="display-choice-modal" aria-hidden="true">
  <div class="modal-backdrop"></div>
  <div class="modal-dialog display-choice-dialog" role="dialog" aria-modal="true" aria-labelledby="displayChoiceTitle" aria-describedby="displayChoiceDescription">
    <div class="display-choice-card">
      <div class="success-eyebrow"><?php echo esc_html(ontario_t('display.badge', [], 'Display options')); ?></div>
      <h2 id="displayChoiceTitle"><?php echo esc_html(ontario_site_display_choice_title()); ?></h2>
      <p id="displayChoiceDescription"><?php echo esc_html(ontario_site_display_choice_description()); ?></p>
      <div class="display-choice-actions">
        <button class="btn btn-primary" type="button" data-display-choice="simple"><?php echo esc_html(ontario_site_display_choice_simple_label()); ?></button>
        <button class="btn btn-secondary" type="button" data-display-choice="full"><?php echo esc_html(ontario_site_display_choice_full_label()); ?></button>
      </div>
    </div>
  </div>
</div>
