/**
 * @file
 * A JavaScript file for the Site Feedback Popup.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.SiteFeedbackPopupClose = {
    attach: function (context, settings) {
      // Set an event for Drupal aJAX.
      $(".site-feedback-thanks").click(function () {
        $(".site-feedback-form").trigger('siteFeedbackPopupClose');
      });

      /*
       Close Popup window.
       */
      $(document).delegate('.site-feedback-form', 'siteFeedbackPopupClose', function () {
        // Init count.
        var count = 5;

        // Set countdown interval.
        var countInterval = setInterval(countDown, 1000);

        // Count down function.
        function countDown() {
          $(".site-feedback-thanks-countdown").text(count);
          count = count - 1;
          if (count <= 0) {
            clearInterval(countInterval);
          }
        }

        // Start count down.
        countDown();

        // Close Pop up.
        setTimeout(function () {
          var magnificPopup;
          magnificPopup = $.magnificPopup.instance;
          magnificPopup.close();
        }, 5000);
      });
    }
  };

})(jQuery, Drupal);
