<?php
/**
 * @file
 * Returns the HTML for a node.
 *
 * Complete documentation for this file is available online.
 * @see https://drupal.org/node/1728164
 */
?>

<div class="layout-max spacer <?php if(!isset($service_links)): print 'spacer--bottom-large'; endif; ?>">
  <?php if($node->body[$node->language][0]['safe_summary']): ?>
    <div class="layout-sidebar__main page-description__content">
        <?php print render($node->body[$node->language][0]['safe_summary']);  ?>
    </div>
  <?php endif; ?>
</div>
<?php if(isset($service_links)): ?>
  <div class="layout-max">
    <?php print $service_links; ?>
  </div>
<?php endif; ?>
<div class="layout-max spacer spacer--bottom-large <?php print !empty($alert_priority) ? strtolower(trim('alert-priority-'.$alert_priority)) : ''; ?>">
  <?php if (!empty($alert_priority)):?>
  <div><?php print t('Alert Priority !priority', array('!priority' => $alert_priority)); ?></div>
  <?php endif;?>
  <?php print render($content['field_alert_date']); ?>
  <div class="layout-sidebar__main">
    <?php print render($content['body']); ?>
  </div>
</div>

<?php print render($content['field_embedded_poll']); ?>
<?php print render($content['field_creative_commons_license']); ?>
