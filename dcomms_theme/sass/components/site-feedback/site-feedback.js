(function ($, Drupal, window, document, undefined) {

  'use strict';

  Drupal.behaviors.siteFeedback = {
    attach: function () {
      $('a.popup-site-feedback-form').magnificPopup({
        type: 'inline',
        midClick: true
      });
    }
  };

})(jQuery, Drupal, this, this.document);
