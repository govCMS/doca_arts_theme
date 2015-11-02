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
      var fileUploadsEnabled = Drupal.settings.dcomms_theme.fileUploadsEnabled;
      var shortCommentsEnabled = Drupal.settings.dcomms_theme.shortCommentsEnabled;
      var message = 'It looks like you haven\'t added a submission. Please add a submission to have your say.';
      var $forms = $('#webform-client-form-15', context);

      $forms.each(function(index, item) {
        var $form = $(item);
        if (fileUploadsEnabled && !shortCommentsEnabled) {
          $form.find('input[name="files[submitted_hys_formal_uploads_hys_formal_upload_file_1]"]').attr('required', 'true');
        }
        else if (shortCommentsEnabled && !fileUploadsEnabled) {
          $form.find('input[name="submitted[short_comments]"]').attr('required', 'true');
        }
        else if (shortCommentsEnabled && fileUploadsEnabled) {
          $form.find('input[type=submit]').bind('click', function(e) {
            $form.find('.custom-message').remove();
            // Get fields
            var file = $form.find('input[name="files[submitted_hys_formal_uploads_hys_formal_upload_file_1]"]')[0];
            var $shortDescription = $form.find('input[name="submitted[short_comments]"]').val();
            var pass = false;

            // Check for at least one field to be populated
            if ($shortDescription.length > 0 || file.files.length > 0) {
              pass = true;
            }
            if (!pass) {
              // Show error message
              $form.find('h3').each(function() {
                if ($(this).html() === 'Your Submission') {
                  $(this).after('<div class="messages--error messages error custom-message">'+message+'</div>');
                }
              });
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

  Drupal.behaviors.uploadMultipleSubmissions = {
    attach: function(context) {
      // Attach toggle functionality for Have Your Say webform.
      $('fieldset[class*="--hys-formal-uploads"]', context).each(function() {

        var $parent = $(this);
        var $files = $parent.children('.fieldset-wrapper').children('div[id*="hys-formal-upload-file-"]');

        function refreshSubmissionView() {
          var currentFileSlot = 0;
          var fileSlots = [];

          $files.each(function(index, item) {
            var $file = $(item).find('input');
            fileSlots.push($file);
            // Find current file slot
            if ($file[0].value != undefined && $file[0].value.length > 0) {
              currentFileSlot++;
            }
          });

          // Hide unused
          $files.hide();

          for (var i = 0; i < (currentFileSlot + 1); i++) {
            if (fileSlots[i] !== undefined) {
              fileSlots[i].parent().parent().parent().show();
            }
          };
        }
        $files.unbind('change.multi_submission').bind('change.multi_submission', refreshSubmissionView);
        refreshSubmissionView();
      });
    }
  };


})(jQuery, Drupal);
