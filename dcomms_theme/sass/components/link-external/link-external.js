/**
 * @file
 * A JavaScript file for the theme.
 *
 * In order for this JavaScript to be loaded on pages, see the instructions in
 * the README.txt next to this file.
 */

// JavaScript should be made compatible with libraries other than jQuery by
// wrapping it with an "anonymous closure". See:
// - https://drupal.org/node/1446420
// - http://www.adequatelygood.com/2010/3/JavaScript-Module-Pattern-In-Depth
(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.linkExternal = {
    attach: function() {
      $('[data-js*="external-links"] a').filter(function() {
        return this.hostname && this.hostname !== location.hostname && !$(this).hasClass('read-more') && !$(this).hasClass('link-external__no-icon') && !$(this).children("img").length;
      }).addClass('link-external').attr('target','_blank');
    }
  };

})(jQuery, Drupal);

