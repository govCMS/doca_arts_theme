<?php

/**
 * Implements hook_form_alter().
 * Conditionally remove the Archived state from publishing options if the node
 * has a currently published revision.
 */
function dcomms_admin_form_alter(&$form, &$form_state, $form_id) {
  if (!in_array($form_id, array('node_form', 'workbench_moderation_moderate_form'))) {
    return;
  }

  $node = NULL;
  if ($form_id == 'node_form') {
    $node = $form['#node'];
  } else if ($form_id == 'workbench_moderation_moderate_form' && isset($form['node']['#value'])) {
    $node = $form['node']['#value'];
  }

  if ($node && !empty($node->nid) && isset($node->workbench_moderation['published']->vid)) {
    unset($form['options']['workbench_moderation_state_new']['#options']['archive']);
  }
}