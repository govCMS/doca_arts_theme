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

  Drupal.behaviors.formalSubmissionToggle = {
    attach: function() {
      // Attach toggle functionality for Have Your Say webform.

      if ($("[data-js*='webform-toggle']").length > 0) {
        var webform_toggle = $("[data-js*='webform-toggle']");

        $(webform_toggle).once('weformToggle', function() {
          var toggled_form_id = $(this).attr('data-toggle');
          var form = document.getElementById(toggled_form_id);
          $(form).hide();

          $(webform_toggle).click(function () {
            var toggled_form_id = $(this).attr('data-toggle');
            var form = document.getElementById(toggled_form_id);

            $(this).parent().hide();
            $(form).show();
            $('input', form).first().focus();
          });
        });
      }

    }
  };

  Drupal.behaviors.twitterFeed = {
    attach: function () {
      setTimeout(function () {
        $("iframe[id^=twitter-widget-").each(function () {
          var head = $(this).contents().find('head');
          if (head.length) {
            head.append('<style type="text/css">.timeline { max-width: none !important; width: 100% !important; } .timeline .stream { max-width: none !important; width: 100% !important; } </style>');
          }
        });
      }, 2000);
    }
  };

  Drupal.behaviors.uploadMultipleSubmissions = {
    attach: function(context) {
      // Attach toggle functionality for Have Your Say webform.
      $('.webform-component--hys-formal-uploads', context).each(function() {
        var $parent = $(this);
        var $button = $('<input type="button" value="Upload another file" class="button--normal"/>');
        $button.appendTo($parent);
        // Click to show next item.
        $button.bind('click', function() {
          var $button = $(this);
          var $divs = $button.parent().children('.fieldset-wrapper').children('div');
          // Show next hidden div.
          $divs.each(function() {
            if (!$(this).is(':visible')) {
              $(this).show();
              return false;
            }
          });
          // Hide button if all divs are visible.
          if ($divs.filter(":hidden").length === 0) {
            $button.hide();
          }
        });
        // Hide all items initially.
        $parent.children('.fieldset-wrapper').children('div').each(function(index, item) {
          if (index != 0) {
            $(item).hide();
          }
        });
      });
    }
  };


})(jQuery, Drupal);
