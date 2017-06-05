<?php

/**
 * @file
 * Doca common base theme.
 */

/**
 * Include common theme functions.
 */
include_once dirname(__FILE__) . '/includes/common_templates.func.inc';

/**
 * Include preprocess functions.
 */
include_once dirname(__FILE__) . '/includes/preprocess.inc';

/**
 * Include theme hook functions.
 */
include_once dirname(__FILE__) . '/includes/theme.inc';

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
function _doca_common_related_content_category_term(&$related_content_nids, $limit, $node, $field_name) {
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
function _doca_common_related_content($node) {
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
  _doca_common_related_content_category_term($related_content_nids, $limit, $node, 'field_business_area');

  // Next fill related content with content of same type in this stream.
  _doca_common_related_content_category_term($related_content_nids, $limit, $node, 'field_stream');

  // Next fill related content with content of same type in this audience.
  _doca_common_related_content_category_term($related_content_nids, $limit, $node, 'field_audience');

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
 * Implements hook_preprocess_node().
 */
function doca_common_preprocess_node(&$variables, $hook) {
  $node = $variables['node'];
  // Adjust the submitted date format.
  $variables['pubdate'] = '<time pubdate datetime="' . format_date($variables['node']->created, 'custom', 'c') . '">' . format_date($variables['node']->created, 'custom', 'jS M Y') . '</time>';
  if ($variables['display_submitted']) {
    $variables['submitted'] = t('Published !datetime', ['!datetime' => $variables['pubdate']]);
  }

  // Add a theme hook suggestion for type and view mode.
  $variables['theme_hook_suggestions'][] = 'node__' . $variables['type'] . '__' . $variables['view_mode'];

  // Conditionally remove Progress bar from all view modes where relevant.
  if ($variables['type'] == 'consultation') {
    // Add first/second update item to grid_stream_landscape view mode if exist.
    if ($variables['view_mode'] == 'grid_stream_landscape') {
      $wrapped_entity = entity_metadata_wrapper('node', $variables['node']);
      if (isset($wrapped_entity->field_updates[0]) && isset($wrapped_entity->field_updates[1])) {
        $variables['update_1'] = $wrapped_entity->field_updates[0]->view('teaser');
        $variables['update_2'] = $wrapped_entity->field_updates[1]->view('teaser');
      }
    }

    // Conditionally remove Formal Submission fields where relevant.
    if ($variables['view_mode'] == 'full') {

      // Include Consultation specific script.
      drupal_add_js(drupal_get_path('theme', 'doca_common') . '/dist/js/script-consultation.js', ['file']);
      drupal_add_js(['doca_common' => ['webform_nid' => theme_get_setting('have_your_say_wform_nid')]], 'setting');

      _consultation_vars($variables, $variables['node']);
      $consultation = $variables['consultation'];

      // Return if formal submissions are not accepted.
      if (!empty($consultation['hide_form'])) {
        field_group_hide_field_groups($variables['elements'], ['group_formal_submission_form']);
        hide($variables['content']['formal_submission_webform']);
        // Only hide inro/outro if there is no embedded webform.
        if (empty($variables['content']['field_other_embedded_webform'])) {
          hide($variables['content']['field_formal_submission_cta_1']);
          hide($variables['content']['field_formal_submission_cta_2']);
        }
      }

      if ($consultation['in_review']) {
        show($variables['content']['field_formal_submission_cta_1']);
      }

      // Add formal submission field to JS Drupal.settings if a value is present.
      if (isset($variables['content']['field_formal_submission_notify']['#items'][0]['value'])) {
        drupal_add_js([
          'doca_common' => [
            'formalSubmissionNotify' => check_plain($variables['content']['field_formal_submission_notify']['#items'][0]['value']),
          ],
        ], 'setting');
      }
      hide($variables['content']['field_formal_submission_notify']);

    }

    // Create the entity metadata wrapper.
    $wrapper = entity_metadata_wrapper('node', $node);

    _consultation_vars($variables, $variables['node']);
    $consultation = $variables['consultation'];

    if ($consultation['date_status'] === 'upcoming') {
      field_group_hide_field_groups($variables['elements'], ['group_formal_submissions']);
      hide($variables['content']['hys_progress_bar']);
      hide($variables['content']['formal_submission_webform']);
      hide($variables['content']['field_formal_submission_cta_1']);
      hide($variables['content']['field_formal_submission_cta_2']);
      hide($variables['content']['field_other_embedded_webform']);
    }

    // Set default values.
    $short_comments_enabled = $file_uploads_enabled = FALSE;
    // Create the entity metadata wrapper.
    $wrapper = entity_metadata_wrapper('node', $node);

    // If the 'Short comments enabled' field exists and is TRUE.
    if (isset($node->field_short_comments_enabled) && $wrapper->field_short_comments_enabled->value()) {
      $short_comments_enabled = TRUE;
    }

    // If the 'File upload enabled' field exists and is TRUE.
    if (isset($node->field_file_uploads_enabled) && $wrapper->field_file_uploads_enabled->value()) {
      $file_uploads_enabled = TRUE;
    }

    // Add the above results to javascript.
    drupal_add_js([
      'doca_common' => [
        'shortCommentsEnabled' => $short_comments_enabled,
        'fileUploadsEnabled' => $file_uploads_enabled,
      ],
    ], 'setting');

    // Get the end consultation date.
    $end_consultation_date = _doca_admin_return_end_consultation_date($node, $wrapper);
    // Get the current timestamp.
    $time = time();

    // Check if a fso has been provided.
    if (isset($_GET['fso'])) {
      // Check if the node is able to accept late submissions.
      $accept_late_submissions = _doca_admin_accept_late_submission($node);
      // If the node can accept late submissions.
      if ($accept_late_submissions) {
        // Get the salted hash for this nid.
        $salted_hash = _doca_admin_return_salted_hash($node->nid);
        // If the salted hash and the fso are equal.
        if ($_GET['fso'] == $salted_hash) {
          // Show the relevant HYS sections.
          show($variables['content']['formal_submission_webform']);

          // Build up the message to let the user know of the special case.
          $message = t("Please note that acceptance of submissions for this round of the consultation has closed. It is at the Departments' discretion if late submissions are accepted. Thank you.");
          // Output the status message.
          $variables['status_message'] = $message;
        }
      } // If the 'Enable late submissions' value is not TRUE and the end consultation date is less than now.
      elseif (isset($node->field_enable_late_submissions) && $wrapper->field_enable_late_submissions->value() !== TRUE && $end_consultation_date < $time) {
        // Redirect the user to the custom 404 page.
        drupal_goto('page-404-consultations');
      }
    }

    // Hide 'Discussion Forum' related fields initially.
    hide($variables['content']['field_discussion_forum_heading']);
    hide($variables['content']['field_discussion_forum_intro']);
    // Create an entity metadata wrapper.
    $wrapper = entity_metadata_wrapper('node', $node);

    if (!$wrapper->field_short_comments_enabled->value()) {
      $variables['classes_array'][] = 'hide_short_comments';
    }
    if (!$wrapper->field_file_uploads_enabled->value()) {
      $variables['classes_array'][] = 'hide_files';
    }

    // If comments are open.
    if ($variables['comment'] == COMMENT_NODE_OPEN) {
      // If the heading 'Discussion Forum' heading field exists and is not blank.
      if (isset($node->field_discussion_forum_heading) && $wrapper->field_discussion_forum_heading->value() != '') {
        // Show the 'Discussion Forum' heading field.
        show($variables['content']['field_discussion_forum_heading']);
      }
      // If the 'Discussion Forum' introduction field eixsts and is not blank.
      if (isset($node->field_discussion_forum_intro) && $wrapper->field_discussion_forum_intro->value() != '') {
        // Show the 'Discussion Forum' introduction field.
        show($variables['content']['field_discussion_forum_intro']);
      }
    }

    // Check to see if $user has the administrator role then show form.
    global $user;
    $submit_formal_submission_roles = [
      "Site builder",
      "Site editor",
      "Publisher",
      "administrator",
    ];

    if (array_intersect($submit_formal_submission_roles, array_values($user->roles))) {
      $variables['formal_submission_block'] = module_invoke('webform', 'block_view', 'client-block-' . theme_get_setting('have_your_say_wform_nid'));
    }
  }

  // Conditionally remove Progress bar from all view modes where relevant.
  if ($variables['type'] == 'funding') {
    // Add first/second update item to grid_stream_landscape view mode if exist.
    $wrapper = entity_metadata_wrapper('node', $node);

    if ($variables['view_mode'] == 'grid_stream_landscape') {
      // Create an entity metadata wrapper.

      $wrapped_entity = entity_metadata_wrapper('node', $variables['node']);
      if (isset($wrapped_entity->field_updates[0]) && isset($wrapped_entity->field_updates[1])) {
        $variables['update_1'] = $wrapped_entity->field_updates[0]->view('teaser');
        $variables['update_2'] = $wrapped_entity->field_updates[1]->view('teaser');
      }
    }

    // Conditionally remove Formal Submission fields where relevant and add support class.
    if ($variables['view_mode'] == 'full') {
      if ($wrapper->field_funding_item->value() == 'support') {
        $variables['classes_array'][] = 'palette__dark-grey--group';
      }
      // Include Funding specific script.
      hide($variables['content']['field_funding_app_webform']);
      if (isset($variables['field_funding_app_webform'][0]) && isset($variables['field_funding_app_webform'][0]['target_id'])) {
        drupal_add_js(drupal_get_path('theme', 'doca_common') . '/dist/js/script-consultation.js', ['file']);
        drupal_add_js(['doca_common' => ['fund_webform_nid' => $variables['field_funding_app_webform'][0]['target_id']]], 'setting');
      }
      else {
        hide($variables['content']['formal_submission_webform']);
      }

      _consultation_vars($variables, $variables['node']);
      $funding = $variables['consultation'];

      // Return if formal submissions are not accepted.
      if (!empty($funding['hide_form'])) {
        field_group_hide_field_groups($variables['elements'], ['group_formal_submission_form']);
        hide($variables['content']['formal_submission_webform']);
        // Only hide inro/outro if there is no embedded webform.
        if (empty($variables['content']['field_other_embedded_webform'])) {
          hide($variables['content']['field_formal_submission_cta_1']);
          hide($variables['content']['field_formal_submission_cta_2']);
        }
      }

      if ($funding['in_review']) {
        show($variables['content']['field_formal_submission_cta_1']);
      }

      // Add formal submission field to JS Drupal.settings if a value is present.
      if (isset($variables['content']['field_formal_submission_notify']['#items'][0]['value'])) {
        drupal_add_js([
          'doca_common' => [
            'formalSubmissionNotify' => check_plain($variables['content']['field_formal_submission_notify']['#items'][0]['value']),
          ],
        ], 'setting');
      }
      hide($variables['content']['field_formal_submission_notify']);

    }

    // Create the entity metadata wrapper.
    $wrapper = entity_metadata_wrapper('node', $node);

    _consultation_vars($variables, $variables['node']);
    $funding = $variables['consultation'];

    $hide_progress = $wrapper->field_hide_progress_bar->value();
    $hide_cta = $wrapper->field_hide_cta->value();
    if ($funding['date_status'] === 'upcoming' || ($hide_progress && $hide_cta)) {
      field_group_hide_field_groups($variables['elements'], ['group_formal_submissions']);
      hide($variables['content']['formal_submission_webform']);
      hide($variables['content']['field_formal_submission_cta_1']);
      hide($variables['content']['field_formal_submission_cta_2']);
      hide($variables['content']['field_other_embedded_webform']);
    }
    if ($hide_progress) {
      hide($variables['content']['hys_progress_bar']);
    }
    if ($hide_cta) {
      hide($variables['content']['field_formal_submission_cta_1']);
      hide($variables['content']['field_formal_submission_cta_2']);
    }

    // Set default values.
    $short_comments_enabled = $file_uploads_enabled = FALSE;

    // Add the above results to javascript.
    drupal_add_js([
      'doca_common' => [
        'shortCommentsEnabled' => $short_comments_enabled,
        'fileUploadsEnabled' => $file_uploads_enabled,
      ],
    ], 'setting');

    // Get the end funding date.
    $end_consultation_date = _doca_admin_return_end_consultation_date($node, $wrapper);
    // Get the current timestamp.
    $time = time();

    // Hide 'Discussion Forum' related fields initially.
    hide($variables['content']['field_discussion_forum_heading']);
    hide($variables['content']['field_discussion_forum_intro']);
    // Create an entity metadata wrapper.
    $wrapper = entity_metadata_wrapper('node', $node);

    // If comments are open.
    if ($variables['comment'] == COMMENT_NODE_OPEN) {
      // If the heading 'Discussion Forum' heading field exists and is not blank.
      if (isset($node->field_discussion_forum_heading) && $wrapper->field_discussion_forum_heading->value() != '') {
        // Show the 'Discussion Forum' heading field.
        show($variables['content']['field_discussion_forum_heading']);
      }
      // If the 'Discussion Forum' introduction field eixsts and is not blank.
      if (isset($node->field_discussion_forum_intro) && $wrapper->field_discussion_forum_intro->value() != '') {
        // Show the 'Discussion Forum' introduction field.
        show($variables['content']['field_discussion_forum_intro']);
      }
    }
  }

  // Variables for optional display of child links grid, 'on this page', suggested content.
  if (in_array($variables['type'], [
      'alert',
      'bcr_data',
      'blog_article',
      'consultation',
      'funding',
      'news_article',
      'policy',
      'page',
    ])
    && $variables['view_mode'] == 'full'
  ) {
    $wrapped_entity = entity_metadata_wrapper('node', $variables['node']);
    if ($variables['type'] == 'page') {
      $hide_child_pages = $variables['hide_child_pages'] = $wrapped_entity->field_hide_child_pages->value();
      $variables['hide_on_this_page'] = $wrapped_entity->field_hide_on_this_page->value();
    }
    $hide_related_content = $wrapped_entity->field_hide_related_content->value();

    // Child pages.
    if (isset($hide_child_pages) && !$hide_child_pages) {
      $block = module_invoke('bean', 'block_view', 'standard-page-children---coloure');
      $variables['child_pages_block'] = render($block['content']);
    }

    // Related content.
    if (isset($hide_related_content) && !$hide_related_content) {
      $variables['content']['related_content'] = _doca_common_related_content($variables['node']);
    }
  }

  $variables['read_more_text'] = t('Learn more');
  if (!empty($node->field_read_more_text[LANGUAGE_NONE][0]['safe_value'])) {
    $variables['read_more_text'] = $node->field_read_more_text[LANGUAGE_NONE][0]['safe_value'];
  }

  // Find out whether the node has an 'External source' filled in.
  $external_source = _doca_admin_return_node_has_external_source($node);
  $variables['external_source'] = $external_source;

  // Build service links.
  $variables['service_links'] = NULL;
  // If 'Hide social links' field is not set/empty.
  if (!isset($variables['field_social_links'][LANGUAGE_NONE]) || $variables['field_social_links'][LANGUAGE_NONE][0]['value'] == 0) {
    // And it's the full view mode.
    if ($variables['view_mode'] === 'full') {
      $options = ['absolute' => TRUE];
      $variables['service_links'] = theme('share_row', [
        'title' => $node->title,
        'url' => url('node/' . $node->nid, $options),
      ]);
    }
  }

  if ($variables['type'] == 'alert') {
    if (isset($variables['field_priority_level']) && count($variables['field_priority_level'])) {
      $priority_level = $variables['field_priority_level'][LANGUAGE_NONE][0]['tid'];
      if ($priority_level = taxonomy_term_load($priority_level)) {
        $variables['classes_array'][] = 'alert-priority-' . strtolower(trim($priority_level->name));
        $variables['alert_priority'] = $priority_level->name;
      }
    }
  }
}

/**
 * Implements hook_preprocess_panels_pane().
 */
function doca_common_preprocess_panels_pane(&$variables) {
  if (isset($variables['content']['bean'])) {
    $bean = reset($variables['content']['bean']);
    if ($bean['#bundle'] == 'qna_pair_alt') {
      $variables['theme_hook_suggestions'][] = 'panels_pane__qna_pair_alt';
    }
    elseif ($bean['#bundle'] == 'qna_pair') {
      $variables['theme_hook_suggestions'][] = 'panels_pane__qna_pair';
    }
  }
}

/**
 * Implements hook__field_group_build_pre_render_alter().
 */
function doca_common_field_group_build_pre_render_alter(&$element) {
  if (isset($element['#node'])) {
    $object = $element['#node'];
    if ($object->type != 'funding') {
      $object = NULL;
    }
  }
  if (!empty($object) && !empty($object->field_funding_item) && $object->field_funding_item[LANGUAGE_NONE][0]['value'] == 'support') {
    $element['group_formal_submissions']['#prefix'] = str_replace('Funding applications', 'Support applications', $element['group_formal_submissions']['#prefix']);
  }
}

/**
 * Implements template_preprocess_views_view_fields().
 */
function doca_common_preprocess_views_view_field(&$variables) {
  if ($variables["field"]->options["id"] == "value_2") {
    $nid = $variables['field']->options['webform_nid'];
    $sid = $variables['row']->sid;
    $full_submission = webform_get_submission($nid, $sid);
    if (isset($full_submission->data[24]) && ($full_submission->data[24][0] === 'anonymous')) {
      // If anonymous (component 24) checked title should be "Anonymous".
      $variables["output"] = "Anonymous";
    }
    elseif (isset($full_submission->data[2]) && !empty($full_submission->data[2][0])) {
      // If anonymous not checked but organisation is set, it displays as title.
      $variables["output"] = $full_submission->data[2][0];
    }
  }
}

/**
 * Implements hook_form_alter().
 */
function doca_common_form_alter(&$form, &$form_state, $form_id) {
  if ($form_id == 'webform_client_form_' . theme_get_setting('have_your_say_wform_nid')) {
    $component_key = "privacy";
    if (isset($form['field_short_comments_enabled'][$component_key])) {
      $form['actions'][$component_key] = $form['field_short_comments_enabled'][$component_key];
      unset($form['submitted'][$component_key]);
    }
    // Check if the 'Short comments' field is available.
    if (isset($form['submitted']['short_comments'])) {
      // Update the attributes and set the maxlength.
      $form['submitted']['short_comments']['#attributes']['maxlength'] = 500;
    }
  }

  if ($form_id == 'workbench_moderation_moderate_form' && !empty($form['node']['#value'])) {
    $node = $form['node']['#value'];
    if (!empty($node->nid) && isset($node->workbench_moderation['published']->vid)) {
      unset($form['state']['#options']['archive']);
    }
  }

  if (($form_id == 'views_exposed_form') && ($form['#id'] == 'views-exposed-form-book-search-default') || $form['#id'] == 'views-exposed-form-book-search-book-search') {
    // Change label for winners all.
    $form['field_winner_value']['#options']['All'] = 'Shortlists';
    $form['field_winner_value']['#options'][1] = 'Winners';
    unset($form['field_winner_value']['#options'][0]);
  }
}

/**
 * Implements hook_preprocess_block().
 */
function doca_common_preprocess_block(&$variables) {
  // Theming various blocks.
  switch ($variables['block_html_id']) {
    case 'block-system-main-menu':
      $variables['classes_array'][] = 'header-menu';
      $variables['title_attributes_array']['class'] = ['element-invisible'];
      break;

    case 'block-menu-menu-footer-menu':
      $variables['classes_array'][] = 'layout-centered';
      $variables['classes_array'][] = 'clearfix';
      $variables['title_attributes_array']['class'] = ['element-invisible'];
      break;

    case 'block-menu-menu-footer-sub-menu':
      $variables['classes_array'][] = 'layout-centered';
      $variables['classes_array'][] = 'clearfix';
      $variables['title_attributes_array']['class'] = ['element-invisible'];
      break;
  }

  // Block template per bean type.
  if ($variables['block']->module === 'bean') {
    $beans = $variables['elements']['bean'];
    $bean_keys = element_children($beans);
    $bean = $beans[reset($bean_keys)];
    // Add template suggestions for bean types.
    $variables['theme_hook_suggestions'][] = 'block__bean__' . $bean['#bundle'];
  }
}

/**
 * Returns HTML for a menu with a heading and wrapper.
 */
function _doca_common_block_render($module, $delta) {
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
 * Implements theme_menu_tree__MENU_NAME().
 */
function doca_common_menu_tree__main_menu($variables) {
  if (strpos($variables['tree'], 'subsite-header__item') !== FALSE) {
    // If it's a menu block menu.
    $output = '<ul class="subsite-header__list">' . $variables['tree'] . '</ul>';
  }
  else {
    // Otherwise it's the system menu.
    $output = '<ul class="header-menu__menu">';
    $output .= $variables['tree'];

    // Include the search link.
    $output .= '<li class="header-search__icon-wrapper">';
    $output .= l(t('Search'), 'search', ['attributes' => ['class' => ['header-search__icon--link']]]);
    $output .= '</li>';

    $output .= '</ul>';
  }

  return $output;
}

/**
 * Implements theme_menu_link__MENU_NAME().
 */
function doca_common_menu_link__main_menu(array $variables) {
  $element = $variables['element'];

  if (isset($element['#bid'])) {
    // If it's a menu block menu.
    $item_class = 'subsite-header__item';
    if (in_array('is-active-trail', $element['#attributes']['class'])) {
      $item_class = 'subsite-header__item is-active';
    }
    $link_class = 'subsite-header__link';
  }
  else {
    // Otherwise it's the system menu.
    $item_class = 'header-menu__item';
    if (in_array('is-active-trail', $element['#attributes']['class'])) {
      $item_class = 'header-menu__item is-active';
    }
    $link_class = 'header-menu__link';
  }

  $element['#localized_options']['attributes']['class'][] = $link_class;
  $element['#localized_options']['html'] = TRUE;

  $output = l(check_plain($element['#title']), $element['#href'], $element['#localized_options']);

  return '<li class="' . $item_class . '">' . $output . "</li>\n";
}

/**
 * Implements theme_menu_tree__MENU_NAME().
 */
function doca_common_menu_tree__menu_footer_menu($variables) {
  return '<ul class="footer-menu">' . $variables['tree'] . '</ul>';
}

/**
 * Implements theme_menu_link__MENU_NAME().
 */
function doca_common_menu_link__menu_footer_menu(array $variables) {
  $element = $variables['element'];
  $sub_menu = '';
  if ($element['#original_link']['depth'] === '1') {
    $item_class = 'footer-menu__column';
    $link_class = 'footer-menu__title';
  }
  else {
    $item_class = 'footer-menu__item';
    $link_class = '';
  }
  if (isset($element['#below'])) {
    $sub_menu = drupal_render($element['#below']);
  }
  $element['#localized_options']['attributes']['class'][] = $link_class;
  $element['#localized_options']['html'] = TRUE;

  $output = l(check_plain($element['#title']), $element['#href'], $element['#localized_options']);

  return '<li class="' . $item_class . '">' . $output . $sub_menu . "</li>\n";
}

/**
 * Implements theme_menu_tree__MENU_NAME().
 */
function doca_common_menu_tree__menu_footer_sub_menu($variables) {
  return '<ul class="list-unstyled list-inline">' . $variables['tree'] . '</ul>';
}

/**
 * Implements theme_menu_link__MENU_NAME().
 */
function doca_common_menu_link__menu_footer_sub_menu(array $variables) {
  $element = $variables['element'];
  $sub_menu = '';

  if (isset($element['#below'])) {
    $sub_menu = drupal_render($element['#below']);
  }
  $element['#localized_options']['attributes']['class'][] = 'footer_menu__link';
  $element['#localized_options']['html'] = TRUE;
  $output = l(check_plain($element['#title']), $element['#href'], $element['#localized_options']);

  return '<li class="footer-menu__item">' . $output . $sub_menu . "</li>\n";
}

/**
 * Implements theme_file_icon().
 */
function doca_common_file_icon($variables) {
  $file = $variables['file'];
  $icon_directory = drupal_get_path('theme', 'doca_common') . '/dist/images/document';

  $mime = check_plain($file->filemime);
  $icon_url = file_icon_path($file, $icon_directory);

  if ($icon_url == FALSE) {
    $icon_url = $icon_directory . '/generic.png';
  }

  return '<img alt="" class="file__icon" src="' . base_path() . $icon_url . '" title="' . $mime . '" />';
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
 * Implements hook_ds_pre_render_alter().
 */
function doca_common_ds_pre_render_alter(&$layout_render_array, $context, &$variables) {
  if (isset($variables['type'])) {
    $feature_types = ['page', 'blog_article', 'alert', 'news_article'];
    if ($variables['type'] === 'consultation' || $variables['type'] === 'poll' || $variables['type'] === 'funding') {
      // If viewed in iframe mode - add additional class.
      if (isset($variables['view']) && $variables['view']->name === 'consultations_iframe') {
        $variables['classes_array'][] = 'grid-stream__item--iframe';
      }
      // Modify the class if the node has a Featured Image.
      $modifier_class = '';
      if (!empty($variables['field_feature_image'])) {
        $modifier_class = '--has-image';
      }
      // Add the relevant class to the template.
      if ($variables['view_mode'] === 'grid_stream_portrait') {
        $variables['classes_array'][] = 'grid-stream__item--vertical' . $modifier_class;
      }
      if ($variables['view_mode'] === 'grid_stream_portrait_small' && $variables['type'] === 'funding') {
        $variables['classes_array'][] = 'grid-stream__item--vertical';
      }
      elseif ($variables['view_mode'] === 'grid_stream_landscape') {
        $variables['classes_array'][] = 'clearfix__overflow grid-stream__item--landscape-small' . $modifier_class;
      }
      elseif ($variables['view_mode'] === 'grid_stream_upcoming') {
        if (!empty($variables['field_feature_image'])) {
          $modifier_class = '--has-image-description';
        }
        $variables['classes_array'][] = 'clearfix__overflow grid-stream__item--landscape-small' . $modifier_class;
      }
    }
    elseif (in_array($variables['type'], $feature_types)) {
      if ($variables['view_mode'] === 'grid_stream_portrait') {
        $variables['classes_array'][] = 'grid-stream__item--portrait';
      }
      elseif ($variables['view_mode'] === 'grid_stream_landscape') {
        $variables['classes_array'][] = 'clearfix__overflow grid-stream__item--landscape';
      }
      elseif ($variables['view_mode'] === 'grid_stream_portrait_small') {
        $variables['classes_array'][] = 'grid-stream__item--portrait-small';
      }
    }
    if ($variables['type'] === 'news_article' && $variables['view_mode'] === 'teaser') {
      $variables['classes_array'][] = 'news-list__item';
    }
    // Add business area class to relevant items where relevant.
    if (isset($variables['field_business_area']) && !empty($variables['field_business_area']) && $variables['view_mode'] != 'full') {
      $hide_stream = FALSE;
      if (isset($variables['field_business_area'][LANGUAGE_NONE]) && is_array($variables['field_business_area'][LANGUAGE_NONE][0])) {
        $business_area_tid = $variables['field_business_area'][LANGUAGE_NONE][0]['tid'];
      }
      else {
        $business_area_tid = $variables['field_business_area'][0]['tid'];
      }

      $subsites = doca_common_get_subsites();
      $business_area_name = isset($subsites[$business_area_tid]) ? $subsites[$business_area_tid] : $business_area_tid;

      $variables['classes_array'][] = 'grid-stream__item--business-area';
      $variables['classes_array'][] = 'subsite__' . $business_area_name;

      if ($hide_stream === TRUE) {
        $variables['classes_array'][] = 'grid-stream__item--business-area--hide-stream';
      }
    }

    // Add different classes to relevant priority levels of SSO Alerts.
    if ($variables['type'] == 'alert') {
      if (isset($variables['field_priority_level']) && count($variables['field_priority_level'])) {
        $priority_level = $variables['field_priority_level'][LANGUAGE_NONE][0]['tid'];
        if ($priority_level = taxonomy_term_load($priority_level)) {
          $variables['classes_array'][] = 'alert-priority-' . strtolower(trim($priority_level->name));
          $variables['alert_priority'] = $priority_level->name;
        }
      }
    }
  }
}

/**
 * Clear any previously set element_info() static cache.
 *
 * If element_info() was invoked before the theme was fully initialized, this
 * can cause the theme's alter hook to not be invoked.
 *
 * @see https://www.drupal.org/node/2351731
 */
drupal_static_reset('element_info');

/**
 * Include alter functions.
 */
include_once dirname(__FILE__) . '/includes/alter.inc';
