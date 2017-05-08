<?php

/**
 * @file
 * Theme alter hooks.
 */

/**
 * Implements hook_form_FORM_ID_alter().
 */
function dcomms_theme_form_webform_client_form_alter(&$form, &$form_state, $form_id) {
  $wf_nid = theme_get_setting('feedback_wform_nid');
  if (!empty($wf_nid) && ($form_id == 'webform_client_form_' . $wf_nid)) {
    $webform = $form['#node']->webform;

    // Reset webform action.
    $form['#action'] = url('node/' . $form['#node']->nid);

    // Add sid.
    // Add wrapper id.
    $form['site_feedback_sid'] = array(
      '#type' => 'hidden',
    );

    // Add wrapper id.
    $form['site_feedback_ajax_wrapper_id'] = array(
      '#type' => 'hidden',
    );

    // Set wrapper value.
    $form['site_feedback_ajax_wrapper_id']['#value'] = drupal_html_id('site-feedback-ajax-wrapper-' . $form['#node']->nid);

    // Wrap form into wrapper.
    $form['#prefix'] = '<div id="' . $form['site_feedback_ajax_wrapper_id']['#value'] . '">' . (isset($form['#prefix']) ? $form['#prefix'] : '');
    $form['#suffix'] = (isset($form['#suffix']) ? $form['#suffix'] : '') . '</div>';

    // Ajax button.
    foreach (array('previous', 'next', 'submit', 'draft') as $button) {
      if (isset($form['actions'][$button])) {
        $form['actions'][$button]['#ajax'] = array(
          'callback' => 'site_feedback_webform_ajax_callback',
          'wrapper' => $form['site_feedback_ajax_wrapper_id']['#value'],
          'progress' => array(
            'message' => '',
            'type' => 'throbber',
          ),
        );
        if (in_array($button, array('next', 'submit'))) {
          $form['actions'][$button]['#ajax']['event'] = 'click';
        }
        // Workaround for Drupal core bug http://drupal.org/node/1548976.
        // As long as buttons HTML id are causing problems, and it has bad
        // consequences on pages where Webform AJAX is active, we'll force
        // custom ids on AJAXed Webform's buttons.
        $submit_id = drupal_html_id('edit-site-feedback-ajax-' . $button . '-' . $form['#node']->nid);
        $form['actions'][$button]['#attributes']['id'] = $submit_id;
        $form['actions'][$button]['#id'] = $submit_id;
      }
    }

    // Add submit handler.
    array_unshift($form['#submit'], 'site_feedback_webform_submit');
  }
}
