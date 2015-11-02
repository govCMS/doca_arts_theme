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

  Drupal.behaviors.formalSubmissionToggle = {
    attach: function(context) {
      var fileUploadsEnabled   = Drupal.settings.dcomms_theme.fileUploadsEnabled;
      var shortCommentsEnabled = Drupal.settings.dcomms_theme.shortCommentsEnabled;
      var message              = 'It looks like you haven\'t added a submission. Please add a submission to have your say.';
      var shortCommentSelector = 'textarea[name$="[short_comments]"]';
      var firstFileSelector    = 'input[name$="formal_uploads_hys_formal_upload_file_1]"]';
      var $forms               = $('#webform-client-form-15', context);

      $forms.each(function(index, item) {
        var $form = $(item);
        if (fileUploadsEnabled && !shortCommentsEnabled) {
          $form.find(firstFileSelector).attr('required', 'true');
        }
        else if (shortCommentsEnabled && !fileUploadsEnabled) {
          $form.find(shortCommentSelector).attr('required', 'true');
        }
        else if (shortCommentsEnabled && fileUploadsEnabled) {
          $form.find('input[type=submit]').unbind('click.formalSubmissionToggle').bind('click.formalSubmissionToggle', function(e) {
            $form.find('.custom-message').remove();
            // Get fields
            var file = $form.find(firstFileSelector)[0];
            var $shortDescription = $form.find(shortCommentSelector).val();
            var pass = false;

            try {
              // Check for at least one field to be populated
              if ($shortDescription.length > 0 || file.files.length > 0) {
                pass = true;
              }
              if (!pass) {
                // Show error message
                $form.find('h3').each(function() {
                  if ($(this).html() === 'Your Submission') {
                    $(this).after('<div class="messages--error messages error custom-message">'+message+'</div>');
                    $(window).scrollTop($('.custom-message').position().top);
                  }
                });
              }
            }
            catch(e) {
              console.log('An error occured validating form. Allowing to pass. ' + e);
              pass = true;
            }
            return pass;
          });
        }
      });

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


})(jQuery, Drupal);
