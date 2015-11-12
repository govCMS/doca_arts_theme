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

  Drupal.behaviors.submissionCommentDisplay = {
    attach: function (context) {
      var is_mobile = null;
      var $window = $(window);
      var medium_breakpoint = 720 - 15;
      var accordion = '.document-list__inner--comment';
      var comment_toolbar = '.document-list__comment--comment-docs';
      var accordion_head = '.document-list__comment--comment-link';
      var accordion_body = '.document-list__desc--comment';

      // Set up toggle click
      $(accordion_head, context).bind('click', function() {
        $(this).toggleClass('link-open');
        $(this).closest(accordion).find(accordion_body).toggle();
      });
      // Set up mobile switch
      function windowResize() {
        // 720 - 15px for scrollbar
        if ($window.width() < medium_breakpoint) {
          if (!is_mobile || is_mobile === null) {
            $(accordion_body, context).each(function() {
              var acc_head = $(this).closest(accordion).find(accordion_head);
              $(this).insertAfter(acc_head);
            });
            is_mobile = true;
          }
        }
        else {
          if (is_mobile || is_mobile === null) {
            $(accordion_body, context).each(function() {
              var comm_toolbar = $(this).closest(accordion).find(comment_toolbar);
              $(this).insertAfter(comm_toolbar);
            });
            is_mobile = false;
          }
        }
      }
      $window.resize(windowResize);
      windowResize();
    }
  }


})(jQuery, Drupal);
