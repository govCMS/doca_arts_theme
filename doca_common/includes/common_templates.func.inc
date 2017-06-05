<?php

/**
 * @file
 * Common templates functions.
 */

/**
 * Returns HTML for a menu with a heading and wrapper.
 */
function doca_common_block_render($module, $delta) {
  $output = '';
  $block = block_load($module, $delta);
  if (isset($block->bid)) {
    $block_content = _block_render_blocks([$block]);
    $block_array = _block_get_renderable_array($block_content);
    $output = drupal_render($block_array);
  }

  return $output;
}

/**
 * Get standard page node ids that are menu children of a given menu link.
 *
 * @param array $item
 *   A fully translated menu link.
 *
 * @return array
 *   Node ids that are menu children of $item.
 */
function doca_common_get_standard_page_menu_children($item) {
  if ($item === FALSE || empty($item['menu_name']) || !isset($item['mlid'])) {
    return [];
  }
  $sql = "SELECT SUBSTR(ml.link_path, 6) AS nid
FROM {menu_links} ml
JOIN {node} n ON (n.nid = SUBSTR(ml.link_path, 6))
WHERE
  ml.link_path LIKE 'node/%' AND
  ml.menu_name = :menu_name AND
  plid = :plid AND
  n.status = 1 AND
  n.type = 'page'
ORDER BY ml.weight";

  return db_query($sql, [
    ':menu_name' => $item['menu_name'],
    ':plid' => $item['mlid'],
  ])->fetchCol();
}

/**
 * Helper function to get the theme settings for mini sites by term ID.
 *
 * @return array
 *         An array of Theme minisite settings by term ID.
 */
function doca_common_get_subsites() {
  $subsites = &drupal_static(__FUNCTION__);
  if (!isset($subsites)) {
    $subsites = [
      theme_get_setting('sub_theme_1') => 'sub-theme-1',
      theme_get_setting('sub_theme_2') => 'sub-theme-2',
      theme_get_setting('sub_theme_3') => 'sub-theme-3',
      theme_get_setting('sub_theme_4') => 'sub-theme-4',
    ];
  }

  return $subsites;
}

/**
 * Returns the poll type based on number of choices.
 */
function doca_common_get_poll_type($nid) {
  $node = node_load($nid);
  $choices = count($node->choice);
  $poll_type = 'binary';
  if ($choices > '2') {
    $poll_type = 'multiple';
  }

  return $poll_type;
}

/**
 * Render a read more link.
 *
 * @param string $href
 *   URL of the read more link.
 * @param string $text
 *   Text of the read more link.
 * @param bool $external
 *   Whether the link is external or not. Defaults to FALSE.
 *
 * @return string
 *   HTML markup for read more link.
 */
function doca_common_read_more_link($href, $text, $external = FALSE) {
  $template_file = drupal_get_path('theme', 'doca_common') . '/templates/read-more-link.tpl.php';

  // Make sure relative links start with /.
  if (substr($href, 0, 4) != 'http' && substr($href, 0, 1) != '/') {
    $href = base_path() . $href;
  }

  return theme_render_template($template_file, [
    'href' => $href,
    'text' => $text,
    'external' => $external,
  ]);
}

/**
 * Fill related content with content from a category term.
 *
 * @param array $related_content_nids
 *   Array of related content node ids.
 * @param int $limit
 *   Maximum number of related content nodes.
 * @param object $node
 *   Drupal node.
 * @param string $field_name
 *   Field name with category term.
 */
function doca_common_related_content_category_term(&$related_content_nids, $limit, $node, $field_name) {
  if (count($related_content_nids) < $limit && isset($node->{$field_name}[LANGUAGE_NONE][0]['tid'])) {
    $query = db_select('node', 'n')
      ->fields('n', ['nid']);
    $query->join('field_data_' . $field_name, 'tags', 'n.nid = tags.entity_id AND n.vid = tags.revision_id');
    $query->condition('n.status', 1, '=')
      ->condition('n.type', $node->type, '=')
      ->condition('n.nid', $node->nid, '<>');
    if (!empty($related_content_nids)) {
      $query->condition('n.nid', $related_content_nids, 'NOT IN');
    }
    $query->condition('tags.' . $field_name . '_tid', $node->{$field_name}[LANGUAGE_NONE][0]['tid'], '=')
      ->orderBy('title', 'ASC');
    $query->addTag('node_access');
    $result = $query->range(0, $limit - count($related_content_nids))
      ->execute();
    foreach ($result as $row) {
      $related_content_nids[] = $row->nid;
    }
  }
}

/**
 * Generated related content for a node.
 *
 * @param object $node
 *   Node.
 *
 * @return array
 *   Render Array.
 */
function doca_common_related_content($node) {
  $limit = 4;
  $related_content_nids = [];

  // First fill related content with content of same type with highest number
  // of the same tags.
  $tids = [];
  $tags = field_get_items('node', $node, 'field_tags');
  if ($tags) {
    foreach ($tags as $term) {
      $tids[] = $term['tid'];
    }
  }
  if (!empty($tids)) {
    $query = db_select('node', 'n')->fields('n', ['nid']);
    $query->join('field_data_field_tags', 'tags', 'n.nid = tags.entity_id AND n.vid = tags.revision_id');
    $query->condition('n.status', 1, '=')
      ->condition('n.nid', $node->nid, '<>')
      ->condition('tags.field_tags_tid', $tids, 'IN')
      ->groupBy('nid')
      ->orderBy('nid_count', 'DESC')
      ->orderBy('title', 'ASC')
      ->addExpression('COUNT(nid)', 'nid_count');
    $query->addTag('node_access');
    $result = $query->range(0, $limit)
      ->execute();
    foreach ($result as $row) {
      $related_content_nids[] = $row->nid;
    }
  }

  // Next fill related content with content of same type in this business area.
  doca_common_related_content_category_term($related_content_nids, $limit, $node, 'field_business_area');

  // Next fill related content with content of same type in this stream.
  doca_common_related_content_category_term($related_content_nids, $limit, $node, 'field_stream');

  // Next fill related content with content of same type in this audience.
  doca_common_related_content_category_term($related_content_nids, $limit, $node, 'field_audience');

  // Finally fill related content with content of same type.
  if (count($related_content_nids) < $limit) {
    $query = db_select('node', 'n')
      ->fields('n', ['nid'])
      ->condition('n.status', 1, '=')
      ->condition('n.type', $node->type, '=')
      ->condition('n.nid', $node->nid, '<>');
    if (!empty($related_content_nids)) {
      $query->condition('n.nid', $related_content_nids, 'NOT IN');
    }
    $query->orderBy('title', 'ASC');
    $query->addTag('node_access');
    $result = $query->range(0, $limit - count($related_content_nids))
      ->execute();
    foreach ($result as $row) {
      $related_content_nids[] = $row->nid;
    }
  }

  // Get list of links from related content nodes.
  $items = [];
  foreach (node_load_multiple($related_content_nids) as $related_nid => $related_node) {
    $items[] = l($related_node->title, 'node/' . $related_nid);
  }

  return [
    '#theme' => 'list_arrow',
    '#items' => $items,
  ];
}

/**
 * Trim HTML into plain text of the given length.
 *
 * @param string $markup
 *   HTML to trim.
 * @param int $trim_length
 *   The trim length.
 *
 * @return string
 *   Plain text trimmed version of the HTML.
 */
function doca_common_trim($markup, $trim_length) {
  return truncate_utf8(strip_tags($markup), $trim_length, TRUE, TRUE);
}

/**
 * Helper function to add consultation variables to template files.
 */
function _consultation_vars(&$variables, $element_object) {
  $consultation['now'] = time();
  $consultation['wrapped_entity'] = entity_metadata_wrapper('node', $element_object);
  $date = $consultation['wrapped_entity']->field_consultation_date->value();
  $consultation['start'] = $date['value'];
  $consultation['end'] = $date['value2'];
  $consultation['duration'] = ($consultation['end'] - $consultation['start']);
  $consultation['date_status'] = $consultation['wrapped_entity']->field_consultation_date_status->value();
  $consultation['archived'] = _consultation_is_archived($consultation);
  $consultation['in_review'] = ($consultation['end'] < $consultation['now']) && !$consultation['archived'];
  $consultation['started_text'] = 'Started';
  if ($consultation['start'] > $consultation['now']) {
    $consultation['started_text'] = 'Starts';
  }
  $consultation['ended_text'] = 'Ends';
  if ($consultation['end'] < $consultation['now']) {
    $consultation['ended_text'] = 'Ended';
  }
  $consultation['percentage'] = _consultation_percentage($consultation);
  $consultation['days_total'] = round($consultation['duration'] / strtotime('1 day', 0));
  $consultation['days_remain'] = _consultation_days_remain($consultation);
  $consultation['submission_enabled'] = $consultation['wrapped_entity']->field_formal_submission_enabled->value();
  $consultation['status_class'] = ($consultation['percentage'] === 100 ? 'progress-bar--complete' : '');
  if ($element_object->type == 'consultation') {
    _consultation_status_message($consultation);
  }
  else {
    _consultation_status_message($consultation, 'Applications under consideration', 'Funding round finalised');
  }
  $consultation['status_msg_class'] = strtolower($consultation['status_msg_class']);
  $consultation['hide_form'] = !$consultation['submission_enabled'] || ($consultation['start'] > $consultation['now']) || ($consultation['end'] < $consultation['now']);
  $variables['consultation'] = $consultation;
  if ($element_object->type == 'funding') {
    $variables['funding'] = $consultation;
  }

  return $variables;
}

/**
 * Helper function - days_remain for progress bar.
 */
function _consultation_days_remain($consultation) {

  $days_total = round($consultation['duration'] / strtotime('1 day', 0));

  if ($consultation['percentage'] === 0) {
    $days_remain = $days_total;
  }
  elseif ($consultation['percentage'] === 100) {
    $days_remain = "0";
  }
  elseif (($consultation['end'] > $consultation['now']) && ($consultation['end'] - $consultation['now'] < strtotime('1 day', 0))) {
    $days_remain = "< 1";
  }
  else {
    $days_remain = round(($consultation['end'] - time()) / strtotime('1 day', 0));
  }

  return $days_remain;
}

/**
 * Helper function for the consultation status message.
 */
function _consultation_status_message(&$consultation, $in_review = 'Now under review', $public = 'Submissions now public') {

  $status_message = t('Open');
  $consultation['ongoing'] = FALSE;
  $node_wrapper = $consultation['wrapped_entity'];
  $is_funding = $node_wrapper->getBundle() == 'funding';

  if ($consultation['in_review']) {
    $status_message = t($in_review);
  }

  $formal_submission_public = isset($node_wrapper->field_formal_submission_public) && $node_wrapper->field_formal_submission_public->value();
  if ($formal_submission_public) {
    $status_message = t($public);
  }

  if (!$is_funding && $consultation['archived']) {
    $status_message = t('Archived');
  }

  if ($consultation['start'] > $consultation['now'] && $node_wrapper->field_consultation_date_status->value() == 'current') {
    $status_message = 'Upcoming';
  }

  if ($is_funding && !empty($node_wrapper->field_funding_type->value()) && $node_wrapper->field_funding_type->value()->name == 'Ongoing') {
    $status_message = 'Open';
    $consultation['ongoing'] = TRUE;
  }

  if ($is_funding && !is_null($node_wrapper->field_outcomes_posted->value())) {
    $status_message = 'This program has closed';
  }

  $consultation['status_msg_class'] = str_replace(' ', '-', $status_message);

  $consultation['status_message'] = $status_message;

}

/**
 * Helper function for hide form message.
 */
function _consultation_submissions_closed_message($consultation) {

  $submissions_closed_message = NULL;

  if ($consultation['in_review']) {
    $submissions_closed_message = t('Submissions are now closed for this consultation and are now under review');
  }

  $formal_submission_public = $consultation['wrapped_entity']->field_formal_submission_public->value();
  if ($formal_submission_public) {
    $submissions_closed_message = t('Submissions are now closed for this consultation and have been made public');
  }

  return $submissions_closed_message;

}

/**
 * Helper function to determine if consultation is archived.
 */
function _consultation_is_archived($consultation) {
  $outcomes_posted_date = $consultation['wrapped_entity']->field_outcomes_posted->value();
  if (isset($outcomes_posted_date)) {
    $archived_date = $outcomes_posted_date + strtotime('6 months', 0);
    $is_archived = ($consultation['now'] > $archived_date);

    return $is_archived;
  }
}

/**
 * Helper function to determine percentage complete.
 */
function _consultation_percentage($consultation) {
  $time_until_it_starts = ($consultation['now'] - $consultation['start']);
  $percentage = $time_until_it_starts / $consultation['duration'] * 100;
  $percentage = max(0, min(100, $percentage));

  return $percentage;
}

/*
 * Helper function: Return the 'External source' field value.
 */
function _doca_admin_return_external_source($node, $wrapper = NULL) {
  // Set default external source value.
  $external_source = FALSE;

  // If the wrapper is NULL.
  if (is_null($wrapper)) {
    // Create a custom wrapper.
    $wrapper = entity_metadata_wrapper('node', $node);
  }

  if (isset($node->field_external_source)) {
    $external_source_field_value = $wrapper->field_external_source->value();
    // If the 'External source' field exists and is not blank.
    if (!empty($external_source_field_value)) {
      // Get the 'External source' field.
      $external_source = $wrapper->field_external_source->value();
    }
  }

  return $external_source;
}

/**
 * Helper function: Return whether an entity has an 'External source' filled in.
 */
function _doca_admin_return_node_has_external_source($node) {
  return (_doca_admin_return_external_source($node)) != FALSE ? TRUE : FALSE;
}

/**
 * Helper function: Return whether a node can validly accept late submissions.
 *
 * Check that the 'Enable late submissions' value is set.
 * Check that the date has passed.
 */
function _doca_admin_accept_late_submission($node) {
  // Set a default answer.
  $answer = FALSE;
  // Create an entity metadata wrapper.
  $wrapper = entity_metadata_wrapper('node', $node);

  // Check if the 'Enable late submissions' field exists and is TRUE.
  if (isset($node->field_enable_late_submissions) && $wrapper->field_enable_late_submissions->value() === TRUE) {
    // Get the consultation date end.
    $consultation_date_end = _doca_admin_return_end_consultation_date($node, $wrapper);
    // Get the current time.
    $time = time();
    // Check if the current date is greater than the end date (i.e. it's in the past).
    if ($time > $consultation_date_end) {
      // Update the answer.
      $answer = TRUE;
    }
  }

  // Return the answer.
  return $answer;
}

/**
 * Helper function: Return salted hash for a given node ID.
 */
function _doca_admin_return_salted_hash($nid) {
  return crc32($nid);
}

/**
 * Helper function: Return formatted late submission URL.
 */
function _doca_admin_return_late_submission_url($node) {
  global $base_url;

  // Get the salted hash for this nid.
  $salted_hash = _doca_admin_return_salted_hash($node->nid);

  // Get the node path.
  $uri = entity_uri('node', $node);
  // Update the options with the query.
  $uri['options']['query']['fso'] = $salted_hash;
  // Output the URL.
  $url = $base_url . url($uri['path'], $uri['options']);

  // Return the URL.
  return $url;
}

/**
 * Helper function: Return whether a user has a given role.
 */
function _doca_admin_return_user_has_role($roles = []) {
  global $user;

  // Set default access value.
  $access = FALSE;

  // If no roles are provided.
  if (empty($roles)) {
    // Set some default roles.
    $roles = [
      'publisher',
      'site builder',
      'site editor',
      'administrator',
    ];
  }

  // Loop through the user roles.
  foreach ($user->roles as $role) {
    // If this role is one we are looking for.
    if (in_array(strtolower($role), $roles)) {
      // Update the access value.
      $access = TRUE;
      // There is no need to check the other roles; so we can safely end the loop.
      break;
    }
  }

  // Return the access value.
  return $access;
}

/**
 * Helper function: Return end consultation date.
 */
function _doca_admin_return_end_consultation_date($node, $wrapper) {
  // Set default consultation date end value.
  $consultation_date_end = '';

  // If the 'Consultation date' field exists and is not blank.
  $consultation_date_value = $wrapper->field_consultation_date->value();
  if (isset($node->field_consultation_date) && !empty($consultation_date_value)) {
    // Get the 'Consultation date' field.
    $consultation_date = $consultation_date_value;
    // Get the end date.
    $consultation_date_end = $consultation_date['value2'];
  }

  return $consultation_date_end;
}

/**
 * Render webform.
 */
function _doca_common_webform_render($nid = NULL) {
  $wnode = node_load($nid);
  $form = drupal_get_form('webform_client_form_' . $nid, $wnode, []);

  return render($form);
}
