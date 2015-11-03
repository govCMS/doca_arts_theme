<?php
/**
 * @file
 * Template for read more link.
 */
?>
<a class="read-more<?php if ($external): ?> external-link<?php endif; ?>" href="<?php print $href; ?>" target="_parent">
  <?php print $text; ?>
  <svg focusable="false" xmlns='http://www.w3.org/2000/svg' height='15' version='1.1' viewBox='0 0 416 416' width='10' xml:space='preserve'><polygon points='160,115.4 180.7,96 352,256 180.7,416 160,396.7 310.5,256 '></polygon></svg>
</a>
