<?php
/**
 * @file
 * Default template implementation to display the value of a field.
 */
?>
<?php foreach ($items as $delta => $item): ?>
  <?php print_r($variables); ?>
  <?php print render($item); ?>
<?php endforeach; ?>
