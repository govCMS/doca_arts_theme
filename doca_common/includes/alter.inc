<?php

/**
 * @file
 * Theme alter hooks.
 */

/**
 * Implements hook_node_view.
 *
 * @param array &$build
 *        A renderable array representing the node content.
 */
function doca_common_node_view_alter(&$build) {
  if ($build['#node']->type == 'alert' && $build['#view_mode'] == 'rss_feed') {
    $node = $build['#node'];
    if (!empty($node->field_priority_level[LANGUAGE_NONE][0]['tid'])) {
      $priority_level = $node->field_priority_level[LANGUAGE_NONE][0]['tid'];
      if ($priority_level = taxonomy_term_load($priority_level)) {
        $node->title = t('Alert Priority !priority: !title', [
          '!priority' => $priority_level->name,
          '!title' => $node->title,
        ]);
      }
    }
  }
}

/**
 * Implements hook_block_view_alter().
 */
function doca_common_block_view_alter(&$data, $block) {
  if ($block->module === 'search' && $block->delta === 'form') {
    $contexts = context_active_contexts();
    if (array_key_exists('display_sso_nav', $contexts) || array_key_exists('clone_of_display_sso_nav', $contexts)) {
      $data['subsite'] = theme_get_setting('sub_theme_1');
      $data['subsite_name'] = theme_get_setting('sub_theme_1_title');
    }
    if (array_key_exists('display_digitalbusiness_nav', $contexts) || array_key_exists('display_digitalbusiness_nav', $contexts)) {
      $data['subsite'] = theme_get_setting('sub_theme_3');
      $data['subsite_name'] = theme_get_setting('sub_theme_3_title');
    }
    if (array_key_exists('display_bcr_nav', $contexts) || array_key_exists('clone_of_display_bcr_nav', $contexts)) {
      $data['subsite'] = theme_get_setting('sub_theme_2');
      $data['subsite_name'] = theme_get_setting('sub_theme_2_title');
    }
  }
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function doca_common_form_webform_client_form_alter(&$form, &$form_state, $form_id) {
  $wf_nid = theme_get_setting('feedback_wform_nid');
  $wf_enabled = theme_get_setting('feedback_enabled');
  if ($wf_enabled && !empty($wf_nid) && ($form_id == 'webform_client_form_' . $wf_nid)) {
    $webform = $form['#node']->webform;

    // Reset webform action.
    $form['#action'] = url('node/' . $form['#node']->nid);

    // Add sid.
    // Add wrapper id.
    $form['site_feedback_sid'] = [
      '#type' => 'hidden',
    ];

    // Add wrapper id.
    $form['site_feedback_ajax_wrapper_id'] = [
      '#type' => 'hidden',
    ];

    // Set wrapper value.
    $form['site_feedback_ajax_wrapper_id']['#value'] = drupal_html_id('site-feedback-ajax-wrapper-' . $form['#node']->nid);

    // Wrap form into wrapper.
    $form['#prefix'] = '<div id="' . $form['site_feedback_ajax_wrapper_id']['#value'] . '">' . (isset($form['#prefix']) ? $form['#prefix'] : '');
    $form['#suffix'] = (isset($form['#suffix']) ? $form['#suffix'] : '') . '</div>';

    // Ajax button.
    foreach (['previous', 'next', 'submit', 'draft'] as $button) {
      if (isset($form['actions'][$button])) {
        $form['actions'][$button]['#ajax'] = [
          'callback' => 'site_feedback_webform_ajax_callback',
          'wrapper' => $form['site_feedback_ajax_wrapper_id']['#value'],
          'progress' => [
            'message' => '',
            'type' => 'throbber',
          ],
        ];
        if (in_array($button, ['next', 'submit'])) {
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

/**
 * AJAX callback for Webform.
 */
function site_feedback_webform_ajax_callback($form, &$form_state) {
  $output = [];
  // If user completed his submission, determine what to do.
  if (!empty($form_state['webform_completed']) && empty($form_state['save_draft'])) {
    $text = '<h2 class="site-feedback-thanks">Thanks for your feedback</h2>
             <p>This window will be automatically closed in <span class="site-feedback-thanks-countdown">5</span> seconds</p>';
    $status_messages = drupal_get_messages('status', FALSE);
    if (isset($status_messages['status'])) {
      // This is the message we want to erase.
      $confirmation = check_markup($form['#node']->webform['confirmation'], $form['#node']->webform['confirmation_format'], '', TRUE);
      $index = array_search($confirmation, $status_messages['status']);
      if ($index !== FALSE) {
        unset($status_messages['status'][$index]);
        drupal_get_messages('status');
        foreach ($status_messages['status'] as $message) {
          drupal_set_message($message);
        }
      }
    }
    $output = [
      '#type' => 'ajax',
      '#commands' => [
        ajax_command_replace('.site-feedback-form__content', $text),
        ajax_command_invoke('.site-feedback-thanks', 'trigger', ['click']),
      ],
    ];
  }
  // Else, we're just switching page, or saving draft.
  else {
    $output = $form;
  }

  return $output;
}

/**
 * Add site feedback webform submit handler.
 */
function site_feedback_webform_submit($form, &$form_state) {
  if (!empty($form_state['values']['site_feedback_sid'])) {
    $sid = $form_state['values']['site_feedback_sid'];
    $form_state['values']['details']['sid'] = $sid;
  }
}
