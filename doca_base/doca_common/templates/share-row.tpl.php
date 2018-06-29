<?php

/**
 * @file
 * Returns the HTML for the share row.
 */
?>
<div class="share-row spacer clearfix">
  <ul class="share-row__bordered">
    <li class="share-row__item"><?php print t('Share:'); ?></li>
    <li class="share-row__item">
      <a class="link-external__no-icon" aria-label="share on Facebook" target="_blank" href="http://www.facebook.com/share.php?u=<?php print $url; ?>">
        <span class="sr-only">on Facebook</span>
        <svg class="share-row__svg" width="1792" height="1792" viewBox="0 0 1792 1792"
             xmlns="http://www.w3.org/2000/svg">
          <path
            d="M1343 12v264h-157q-86 0-116 36t-30 108v189h293l-39 296h-254v759h-306v-759h-255v-296h255v-218q0-186 104-288.5t277-102.5q147 0 228 12z"/>
        </svg>
      </a>
    </li>
    <li class="share-row__item">
      <a class="link-external__no-icon" aria-label="share on Twitter" target="_blank" rel="nofollow"
         href="http://twitter.com/intent/tweet?status=<?php print $title; ?>+<?php print $url; ?>">
        <span class="sr-only">on Twitter</span>
        <svg class="share-row__svg" width="1792" height="1792" viewBox="0 0 1792 1792"
             xmlns="http://www.w3.org/2000/svg">
          <path
            d="M1684 408q-67 98-162 167 1 14 1 42 0 130-38 259.5t-115.5 248.5-184.5 210.5-258 146-323 54.5q-271 0-496-145 35 4 78 4 225 0 401-138-105-2-188-64.5t-114-159.5q33 5 61 5 43 0 85-11-112-23-185.5-111.5t-73.5-205.5v-4q68 38 146 41-66-44-105-115t-39-154q0-88 44-163 121 149 294.5 238.5t371.5 99.5q-8-38-8-74 0-134 94.5-228.5t228.5-94.5q140 0 236 102 109-21 205-78-37 115-142 178 93-10 186-50z"/>
        </svg>
      </a>
    </li>
    <li class="share-row__item">
      <a class="link-external__no-icon" aria-label="share on Google Plus" target="_blank" href="https://plus.google.com/share?url=<?php print $url; ?>">
        <span class="sr-only">on Google Plus</span>
        <svg class="share-row__svg" width="1792" height="1792" viewBox="0 0 1792 1792"
             xmlns="http://www.w3.org/2000/svg">
          <path
            d="M799 796q0 36 32 70.5t77.5 68 90.5 73.5 77 104 32 142q0 90-48 173-72 122-211 179.5t-298 57.5q-132 0-246.5-41.5t-171.5-137.5q-37-60-37-131 0-81 44.5-150t118.5-115q131-82 404-100-32-42-47.5-74t-15.5-73q0-36 21-85-46 4-68 4-148 0-249.5-96.5t-101.5-244.5q0-82 36-159t99-131q77-66 182.5-98t217.5-32h418l-138 88h-131q74 63 112 133t38 160q0 72-24.5 129.5t-59 93-69.5 65-59.5 61.5-24.5 66zm-146-96q38 0 78-16.5t66-43.5q53-57 53-159 0-58-17-125t-48.5-129.5-84.5-103.5-117-41q-42 0-82.5 19.5t-65.5 52.5q-47 59-47 160 0 46 10 97.5t31.5 103 52 92.5 75 67 96.5 26zm2 873q58 0 111.5-13t99-39 73-73 27.5-109q0-25-7-49t-14.5-42-27-41.5-29.5-35-38.5-34.5-36.5-29-41.5-30-36.5-26q-16-2-48-2-53 0-105 7t-107.5 25-97 46-68.5 74.5-27 105.5q0 70 35 123.5t91.5 83 119 44 127.5 14.5zm810-876h213v108h-213v219h-105v-219h-212v-108h212v-217h105v217z"/>
        </svg>
      </a>
    </li>
  </ul>
</div>
