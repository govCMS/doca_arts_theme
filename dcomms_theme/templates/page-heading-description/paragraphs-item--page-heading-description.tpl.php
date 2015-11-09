<?php
/**
 * @file
 * Channels theme implementation for a container.
 */
?>
<?php if(isset($content['field_pbundle_title'])): ?>
  <h1><?php print render($content['field_pbundle_title']); ?></h1>
<?php endif; ?>
<div class="layout-sidebar layout-max spacer">
    <div class="layout-sidebar__main spacer--bottom-large">
        <div <?php print ($is_front) ? 'id="skip-content"' : ''; ?> class="page-description__content">
            <?php print render($content['field_pbundle_text']); ?>
        </div>
        <?php print render($content['field_pbundle_destination']); ?>
    </div>
</div>
