<?php
/**
 * @file
 * Returns the HTML for a block.
 *
 * Complete documentation for this file is available online.
 * @see https://drupal.org/node/1728246
 */
?>
<div id="<?php print $block_html_id; ?>" class="<?php print $classes; ?>" <?php print $attributes; ?>>

  <?php print render($title_prefix); ?>
  <?php if ($title): ?>
    <h2<?php //print $title_attributes; ?> class="heading--4--no-space spacer--vertical bordered--top">
      <?php print $title; ?>
    </h2>
  <?php endif; ?>
  <?php print render($title_suffix); ?>
  <div class="list-arrow">
    <?php print $content; ?>
  </div>

</div>
