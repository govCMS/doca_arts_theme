<?php

/**
 * @file
 * Doca common admin theme.
 */

// Include the helper functions to make sharing between the main and admin themes easier.
require_once drupal_get_path('theme', 'doca_common') . '/includes/common_templates.func.inc';

/**
 * Implements hook_form_alter().
 * Conditionally remove the Archived state from publishing options if the node
 * has a currently published revision.
 */
function dcomms_admin_form_node_form_alter(&$form, &$form_state, $form_id) {
  $node = $form['#node'];
  if (!empty($node->nid) && isset($node->workbench_moderation['published']->vid)) {
    unset($form['options']['workbench_moderation_state_new']['#options']['archive']);
  }
  if ($node->type == 'funding') {
    $options = &$form['field_consultation_date_status'][LANGUAGE_NONE]['#options'];
    $options['upcoming'] = str_replace('consultation', 'funding', $options['upcoming']);
    $options['current'] = str_replace('consultation', 'funding', $options['current']);
  }
}

/**
 * Implements hook_form_FORM_ID_alter().
 *
 * @param array &$form
 *        The drupal form array.
 * @param array &$form_state
 *        The drupal form_state array.
 */
function doca_admin_form_consultation_node_form_alter(&$form, &$form_state) {
  // Get the node.
  $node = $form['#node'];

  // Get the value for whether the user should have access.
  $access = _doca_admin_return_user_has_role();
  // If the user has access.
  if ($access) {
    // Work out if this node can validly accept late submissions.
    $accept_late_submissions = _doca_admin_accept_late_submission($node);

    // If able to accept late submissions.
    if ($accept_late_submissions) {
      // Get the late submission URL.
      $url = _doca_admin_return_late_submission_url($node);
      // Create a message to let the admin know the URL.
      $args = [
        '!url' => $url,
      ];
      $message = t('Use the following URL for late submissions: !url', $args);
      // Finally output the message.
      drupal_set_message($message);
    }
  }
}

function dcomms_admin_form_workbench_moderation_moderate_form_alter(&$form, &$form_state, $form_id) {
  if (!empty($form['node']['#value'])) {
    $node = $form['node']['#value'];
    if (!empty($node->nid) && isset($node->workbench_moderation['published']->vid)) {
      unset($form['state']['#options']['archive']);
    }
  }
}

/**
 * Validation callback for funding node forms.
 */
function doca_admin_clear_updates($form, &$form_state) {
  if (taxonomy_term_load($form_state['values']['field_funding_type'][LANGUAGE_NONE][0]['tid'])->name == 'Rolling') {
    // Don't validate on insert, only on changes.
    if (!isset($form['#node']->field_consultation_date) || empty($form['#node']->field_consultation_date)) {
      return;
    }

    // Grab current field value from node.
    $current_field = reset($form_state['node']->field_consultation_date[$form_state['node']->language]);

    // Check validity of form input.
    if (!isset($form_state['values']['field_consultation_date']) || empty($form_state['values']['field_consultation_date'])) {
      // Nothing to validate. Bail.
      return;
    }

    // Get new field value from form_state.
    $new_field = reset($form_state['values']['field_consultation_date'][$form_state['node']->language]);

    // Compare versions of the start dates.
    if ($current_field['value'] != $new_field['value']) {
      // The funding start date is being changed, remove updates!
      if (isset($form_state['values']['field_updates']) && !empty($form_state['values']['field_updates'])) {
        doca_base_paragraphs_deleteconfirm($form, $form_state);
      }
    }
  }
}

