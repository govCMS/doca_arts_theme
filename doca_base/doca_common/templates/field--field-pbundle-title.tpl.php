<?php
/**
 * @file
 * Default template implementation to display the value of a field.
 */
?>
<?php foreach ($items as $delta => $item): ?>
  <h2><?php print render($item); ?></h2>
<?php endforeach; ?>
