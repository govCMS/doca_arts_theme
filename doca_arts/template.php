<?php

/**
 * @file
 * Doca communication site custom theme.
 */

/**
 * Implements hook_preprocess_page().
 */
function doca_theme_preprocess_html(&$variables, $hook) {
  // Add offscreen class to body for mobile navigation.
  $variables['classes_array'][] = 'offscreen';
}

/**
 * Implements hook_js_alter().
 */
function doca_theme_js_alter(&$javascript) {
  $tabs_js_path = drupal_get_path('module',
      'field_group') . '/horizontal-tabs/horizontal-tabs.js';
  unset($javascript[$tabs_js_path]);
}

/**
 * Implements hook_preprocess_page().
 */
function doca_theme_preprocess_page(&$variables, $hook) {
  // Add pathToTheme to Drupal.settings in JS.
  drupal_add_js('jQuery.extend(Drupal.settings, { "pathToTheme": "' . path_to_theme() . '" });',
    'inline');

  // Site pages feedback.
  if (theme_get_setting('feedback_enabled')) {
    $wf_nid = theme_get_setting('feedback_wform_nid');
    drupal_add_js(['sitePagesFeedback' => ['nid' => $wf_nid]], 'setting');
    $variables['site_pages_feedback_form'] = _doca_theme_webform_render($wf_nid);
  }

  // Add the correct google analytics code for the active environment.
  $ga_code = variable_get('googleanalytics_account');
  drupal_add_js(['gaSettings' => ['gaCode' => $ga_code]], 'setting');

  // Create template variables for the header menu block.
  $variables['header_search'] = _doca_theme_block_render('search', 'form');
  $variables['header_menu'] = _doca_theme_block_render('system', 'main-menu');
  // Create template variables for the footer menu blocks.
  $variables['footer_menu'] = _doca_theme_block_render('menu',
    'menu-footer-menu');
  $variables['footer_auxilary_menu'] = _doca_theme_block_render('menu',
    'menu-footer-sub-menu');

  $header = drupal_get_http_header("status");
  if ($header === "404 Not Found") {
    $variables['theme_hook_suggestions'][] = 'page__404';
    $element = [
      '#tag' => 'meta',
      '#attributes' => [
        'http-equiv' => 'refresh',
        'content' => '10;url=/',
      ],
    ];
    drupal_add_html_head($element, 'page_404_redirect');
  }

  if ($header === "403 Forbidden") {
    $variables['theme_hook_suggestions'][] = 'page__403';
  }

  // If this is the 'iframe_portrait' or 'iframe_landscape' Consultation page.
  if (array_search('page__consultations__iframe_portrait',
      $variables['theme_hook_suggestions']) || array_search('page__consultations__iframe_landscape',
      $variables['theme_hook_suggestions'])
  ) {
    // Extend the theme hook suggestions to include a stripped page.
    $variables['theme_hook_suggestions'][] = 'page__stripped';
  }

  // Define page top announcement variable.
  $page_top_announcement_paths = drupal_strtolower(theme_get_setting('page_top_announcement_paths'));
  $current_path = drupal_strtolower(drupal_get_path_alias($_GET['q']));
  $page_match = drupal_match_path($current_path, $page_top_announcement_paths);
  if ($current_path != $_GET['q']) {
    $page_match = $page_match || drupal_match_path($_GET['q'],
        $page_top_announcement_paths);
  }
  if ($page_match) {
    $variables['top_announcements'] = theme_get_setting('page_top_announcement_messages');
  }
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
function doca_theme_get_standard_page_menu_children($item) {
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
 * Implements hook_preprocess_entity().
 */
function doca_theme_preprocess_entity(&$variables, $hook) {
  if ($variables['entity_type'] === 'bean' && $variables['bean']->type === 'standard_page_children' && $variables['view_mode'] === 'coloured_links_grid') {
    // Get menu link of current page.
    $item = menu_link_get_preferred();

    // Get children menu items that are standard pages.
    $nids = doca_theme_get_standard_page_menu_children($item);

    // Render the nodes in coloured grid view mode.
    $node_elements = [];
    foreach ($nids as $nid) {
      $node = node_load($nid);
      $node_elements[] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['featured__grid-item'],
        ],
        'node' => node_view($node, 'coloured_links_grid'),
      ];
    }

    // Render content.
    if (!empty($node_elements)) {
      $variables['content'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['featured-palette__wrapper'],
        ],
        'content' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['featured__grid-container', 'featured-palette'],
          ],
          'nodes' => $node_elements,
        ],
      ];
    }

    if ($variables['elements']['#bundle'] == 'accordion_item') {

      if (isset($variables['elements']['field_pbundle_image'])) {
        $variables['classes_array'][] = 'accordion__item--with-image';
      }

      else {
        $variables['classes_array'][] = 'accordion__item';
      }
    }
  }

  if ($variables['entity_type'] === 'paragraphs_item') {
    if ($variables['elements']['#bundle'] === 'subscribe_block') {
      drupal_add_js([
        'doca_theme' => [
          'alertHideName' => $variables['field_hide_name_field'][0]['value'],
          'alertFullName' => $variables['field_single_full_name'][0]['value'],
          'alertHideNumber' => $variables['field_hide_contact_number_field'][0]['value'],
          'alertMailGroup' => $variables['field_mail_groups'][0]['value'],
          'microSite' => variable_get('doca_theme_micro_site',
            'http://ministryofthearts.e-newsletter.com.au'),
          'apicall' => variable_get('doca_theme_api_call', 'updateall'),
          'errorMessage' => variable_get('doca_theme_error_message',
            t('Please check your email address and try again, if you are still having issues please <a href="mailto:media@communications.gov.au?Subject=Arts website newsletter subscriber">email us</a> your details.')),
          'alertSuccessMessage' => $variables['field_success_message'][0]['value'],
        ],
      ], 'setting');
    }
  }
}

/**
 * Implements hook_media_wysiwyg_token_to_markup_alter().
 */
function doca_theme_media_wysiwyg_token_to_markup_alter(
  &$element,
  &$tag_info,
  $settings
) {
  // Add the relevant styles to the generated media wysiwyg dom elements. This
  // needs to be done in slightly different ways for certain view modes.
  if (isset($element['content']['file']['#attributes']['style'])) {
    $styles = $element['content']['file']['#attributes']['style'];
    $parts = explode(";", $styles);
    for ($i = 0; $i < count($parts); $i++) {
      if (substr(trim($parts[$i]), 0, 5) == 'float') {
        // Move the float to the parent element.
        $element['content']['#attributes']['class'][] = 'doca-media-' . trim(explode(':',
            $parts[$i])[1]);
        $element['content']['#attributes']['style'] = $parts[$i];
        unset($parts[$i]);
        $element['content']['file']['#attributes']['style'] = implode(";",
          $parts);
        break;
      }
    }
  }
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
function _doca_theme_related_content_category_term(
  &$related_content_nids,
  $limit,
  $node,
  $field_name
) {
  if (count($related_content_nids) < $limit && isset($node->{$field_name}[LANGUAGE_NONE][0]['tid'])) {
    $query = db_select('node', 'n')
      ->fields('n', ['nid']);
    $query->join('field_data_' . $field_name, 'tags',
      'n.nid = tags.entity_id AND n.vid = tags.revision_id');
    $query->condition('n.status', 1, '=')
      ->condition('n.type', $node->type, '=')
      ->condition('n.nid', $node->nid, '<>');
    if (!empty($related_content_nids)) {
      $query->condition('n.nid', $related_content_nids, 'NOT IN');
    }
    $query->condition('tags.' . $field_name . '_tid',
      $node->{$field_name}[LANGUAGE_NONE][0]['tid'], '=')
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
function _doca_theme_related_content($node) {
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
    $query->join('field_data_field_tags', 'tags',
      'n.nid = tags.entity_id AND n.vid = tags.revision_id');
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
  _doca_theme_related_content_category_term($related_content_nids, $limit,
    $node, 'field_business_area');

  // Next fill related content with content of same type in this stream.
  _doca_theme_related_content_category_term($related_content_nids, $limit,
    $node, 'field_stream');

  // Next fill related content with content of same type in this audience.
  _doca_theme_related_content_category_term($related_content_nids, $limit,
    $node, 'field_audience');

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
 * Implements hook_preprocess_panels_pane().
 */
function doca_theme_preprocess_panels_pane(&$variables) {
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
function doca_theme_field_group_build_pre_render_alter(&$element) {
  if (isset($element['#node'])) {
    $object = $element['#node'];
    if ($object->type != 'funding') {
      $object = NULL;
    }
  }
  if (!empty($object) && !empty($object->field_funding_item) && $object->field_funding_item[LANGUAGE_NONE][0]['value'] == 'support') {
    $element['group_formal_submissions']['#prefix'] = str_replace('Funding applications',
      'Support applications', $element['group_formal_submissions']['#prefix']);
  }
}

/**
 * Implements template_preprocess_views_view_fields().
 */
function doca_theme_preprocess_views_view_field(&$variables) {
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
function doca_theme_form_alter(&$form, &$form_state, $form_id) {
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
function doca_theme_read_more_link($href, $text, $external = FALSE) {
  $template_file = drupal_get_path('theme',
      'doca_theme') . '/templates/read-more-link.tpl.php';

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
 * Returns HTML for a menu with a heading and wrapper.
 */
function _doca_theme_block_render($module, $delta) {
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
function doca_theme_menu_tree__main_menu($variables) {
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
    $output .= l(t('Search'), 'search',
      ['attributes' => ['class' => ['header-search__icon--link']]]);
    $output .= '</li>';

    $output .= '</ul>';
  }

  return $output;
}

/**
 * Implements theme_menu_link__MENU_NAME().
 */
function doca_theme_menu_link__main_menu(array $variables) {
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

  $output = l(check_plain($element['#title']), $element['#href'],
    $element['#localized_options']);

  return '<li class="' . $item_class . '">' . $output . "</li>\n";
}

/**
 * Implements theme_menu_tree__MENU_NAME().
 */
function doca_theme_menu_tree__menu_footer_menu($variables) {
  return '<ul class="footer-menu">' . $variables['tree'] . '</ul>';
}

/**
 * Implements theme_menu_link__MENU_NAME().
 */
function doca_theme_menu_link__menu_footer_menu(array $variables) {
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

  $output = l(check_plain($element['#title']), $element['#href'],
    $element['#localized_options']);

  return '<li class="' . $item_class . '">' . $output . $sub_menu . "</li>\n";
}

/**
 * Implements theme_menu_tree__MENU_NAME().
 */
function doca_theme_menu_tree__menu_footer_sub_menu($variables) {
  return '<ul class="list-unstyled list-inline">' . $variables['tree'] . '</ul>';
}

/**
 * Implements theme_menu_link__MENU_NAME().
 */
function doca_theme_menu_link__menu_footer_sub_menu(array $variables) {
  $element = $variables['element'];
  $sub_menu = '';

  if (isset($element['#below'])) {
    $sub_menu = drupal_render($element['#below']);
  }
  $element['#localized_options']['attributes']['class'][] = 'footer_menu__link';
  $element['#localized_options']['html'] = TRUE;
  $output = l(check_plain($element['#title']), $element['#href'],
    $element['#localized_options']);

  return '<li class="footer-menu__item">' . $output . $sub_menu . "</li>\n";
}

/**
 * Implements theme_file_icon().
 */
function doca_theme_file_icon($variables) {
  $file = $variables['file'];
  $icon_directory = drupal_get_path('theme',
      'doca_theme') . '/dist/images/document';

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
function _dcomms_poll_type($nid) {
  $node = node_load($nid);
  $choices = count($node->choice);
  $poll_type = 'binary';
  if ($choices > '2') {
    $poll_type = 'multiple';
  }

  return $poll_type;
}

/**
 * Implements theme_breadcrumb().
 */
function doca_theme_breadcrumb($variables) {
  $breadcrumb = $variables['breadcrumb'];
  $output = '';

  // Determine if we are to display the breadcrumb.
  $show_breadcrumb = theme_get_setting('zen_breadcrumb');
  if ($show_breadcrumb == 'yes' || $show_breadcrumb == 'admin' && arg(0) == 'admin') {

    // Optionally get rid of the homepage link.
    $show_breadcrumb_home = theme_get_setting('zen_breadcrumb_home');
    if (!$show_breadcrumb_home) {
      array_shift($breadcrumb);
    }

    // Return the breadcrumb with separators.
    if (!empty($breadcrumb)) {
      $breadcrumb_separator = "<svg class='breadcrumb__separator' xmlns='http://www.w3.org/2000/svg' height='15' version='1.1' viewBox='0 0 416 416' width='10' xml:space='preserve'><polygon points='160,115.4 180.7,96 352,256 180.7,416 160,396.7 310.5,256 '></polygon></svg>";
      $trailing_separator = $title = '';
      if (theme_get_setting('zen_breadcrumb_title')) {
        $item = menu_get_item();
        if (!empty($item['tab_parent'])) {
          // If we are on a non-default tab, use the tab's title.
          $breadcrumb[] = check_plain($item['title']);
        }
        else {
          $breadcrumb[] = drupal_get_title();
        }
      }
      elseif (theme_get_setting('zen_breadcrumb_trailing')) {
        $trailing_separator = $breadcrumb_separator;
      }

      // Provide a navigational heading to give context for breadcrumb links to
      // screen-reader users.
      if (empty($variables['title'])) {
        $variables['title'] = t('You are here');
      }
      // Unless overridden by a preprocess function, make the heading invisible.
      if (!isset($variables['title_attributes_array']['class'])) {
        $variables['title_attributes_array']['class'][] = 'element-invisible';
      }

      // Build the breadcrumb trail.
      $output = '<nav class="breadcrumb" role="navigation">';
      $output .= '<h2' . drupal_attributes($variables['title_attributes_array']) . '>' . $variables['title'] . '</h2>';
      $output .= '<ol class="breadcrumb__list"><li class="breadcrumb__item">' . implode($breadcrumb_separator . '</li><li class="breadcrumb__item">',
          $breadcrumb) . $trailing_separator . '</li></ol>';
      $output .= '</nav>';
    }
  }

  return $output;
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
function doca_theme_trim($markup, $trim_length) {
  return truncate_utf8(strip_tags($markup), $trim_length, TRUE, TRUE);
}

/**
 * Implements hook_ds_pre_render_alter().
 */
function doca_theme_ds_pre_render_alter(
  &$layout_render_array,
  $context,
  &$variables
) {
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

      $subsites = _doca_theme_get_subsites();
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
 * Implements template_preprocess_poll_results().
 */
function doca_theme_preprocess_poll_results(&$variables) {
  $node = node_load($variables['nid']);
  $keys = array_keys($node->choice);
  $variables['votes_1'] = $node->choice[$keys[0]]['chvotes'];
  $variables['votes_2'] = $node->choice[$keys[1]]['chvotes'];
}

/**
 * Implements hook_block_view_alter().
 */
function doca_theme_block_view_alter(&$data, $block) {
  if ($block->module === 'search' && $block->delta === 'form') {
    $contexts = context_active_contexts();
    if (array_key_exists('display_sso_nav',
        $contexts) || array_key_exists('clone_of_display_sso_nav', $contexts)
    ) {
      $data['subsite'] = theme_get_setting('sub_theme_1');
      $data['subsite_name'] = theme_get_setting('sub_theme_1_title');
    }
    if (array_key_exists('display_digitalbusiness_nav',
        $contexts) || array_key_exists('display_digitalbusiness_nav', $contexts)
    ) {
      $data['subsite'] = theme_get_setting('sub_theme_3');
      $data['subsite_name'] = theme_get_setting('sub_theme_3_title');
    }
    if (array_key_exists('display_bcr_nav',
        $contexts) || array_key_exists('clone_of_display_bcr_nav', $contexts)
    ) {
      $data['subsite'] = theme_get_setting('sub_theme_2');
      $data['subsite_name'] = theme_get_setting('sub_theme_2_title');
    }
  }
}

/**
 * Implements template_preprocess_views_view().
 */
function doca_theme_preprocess_views_view(&$variables) {
  if ($variables['name'] === 'formal_submissions') {
    $node = menu_get_object();
    if (isset($node->field_hide_submission_filters[LANGUAGE_NONE][0]['value']) && $node->field_hide_submission_filters[LANGUAGE_NONE][0]['value'] === '1') {
      $variables['exposed'] = FALSE;
    }
  }

  if ($variables['name'] === 'consultations_other') {
    if ($variables['view']->total_rows >= '3' && $variables['display_id'] == 'block') {
      $variables['classes_array'][] = 'grid-stream--grid-at-three';
    }
  }
  if ($variables['name'] == 'whats_new_grid' && $variables['display_id'] == 'block_1') {
    if (count($variables['view']->result) == 2) {
      $variables['classes_array'][] = 'grid-stream__2-col';
    }
  }
}

/**
 * Implements hook_theme().
 */
function doca_theme_theme($existing, $type, $theme, $path) {
  return [
    'share_row' => [
      'template' => 'templates/share-row',
      'variables' => [
        'title' => NULL,
        'url' => NULL,
      ],
    ],
    'list_arrow' => [
      'template' => 'templates/list-arrow',
      'variables' => [
        'items' => NULL,
      ],
    ],
  ];
}

/**
 * Implements hook_theme().
 */
function doca_theme_item_list($variables) {
  $items = $variables['items'];
  $title = $variables['title'];
  $type = $variables['type'];
  $attributes = $variables['attributes'];

  // Only output the list container and title, if there are any list items.
  // Check to see whether the block title exists before adding a header.
  // Empty headers are not semantic and present accessibility challenges.
  $output = '';
  if (isset($title) && $title !== '') {
    $output .= '<h3>' . $title . '</h3>';
  }

  if (!empty($items)) {
    $output .= "<$type" . drupal_attributes($attributes) . '>';
    $num_items = count($items);
    $i = 0;
    foreach ($items as $item) {
      $attributes = [];
      $children = [];
      $data = '';
      $i++;
      if (is_array($item)) {
        foreach ($item as $key => $value) {
          if ($key == 'data') {
            $data = $value;
          }
          elseif ($key == 'children') {
            $children = $value;
          }
          else {
            $attributes[$key] = $value;
          }
        }
      }
      else {
        $data = $item;
      }
      if (count($children) > 0) {
        // Render nested list.
        $data .= theme_item_list([
          'items' => $children,
          'title' => NULL,
          'type' => $type,
          'attributes' => $attributes,
        ]);
      }
      if ($i == 1) {
        $attributes['class'][] = 'first';
      }
      if ($i == $num_items) {
        $attributes['class'][] = 'last';
      }
      $output .= '<li' . drupal_attributes($attributes) . '>' . $data . "</li>\n";
    }
    $output .= "</$type>";
  }

  return $output;
}

/**
 * Implements theme_pager().
 */
function doca_theme_pager($variables) {
  $tags = $variables['tags'];
  $element = $variables['element'];
  $parameters = $variables['parameters'];
  $quantity = $variables['quantity'];
  global $pager_page_array, $pager_total;

  // Calculate various markers within this pager piece:
  // Middle is used to "center" pages around the current page.
  $pager_middle = ceil($quantity / 2);
  // Current is the page we are currently paged to.
  $pager_current = $pager_page_array[$element] + 1;
  // First is the first page listed by this pager piece (re quantity).
  $pager_first = $pager_current - $pager_middle + 1;
  // Last is the last page listed by this pager piece (re quantity).
  $pager_last = $pager_current + $quantity - $pager_middle;
  // Max is the maximum page number.
  $pager_max = $pager_total[$element];
  // End of marker calculations.

  // Prepare for generation loop.
  $i = $pager_first;
  if ($pager_last > $pager_max) {
    // Adjust "center" if at end of query.
    $i = $i + ($pager_max - $pager_last);
    $pager_last = $pager_max;
  }
  if ($i <= 0) {
    // Adjust "center" if at start of query.
    $pager_last = $pager_last + (1 - $i);
    $i = 1;
  }
  // End of generation loop preparation.

  $li_first = theme('pager_first', [
    'text' => (isset($tags[0]) ? $tags[0] : t('« first')),
    'element' => $element,
    'parameters' => $parameters,
  ]);
  $li_previous = theme('pager_previous', [
    'text' => (isset($tags[1]) ? $tags[1] : t('‹ previous')),
    'element' => $element,
    'interval' => 1,
    'parameters' => $parameters,
  ]);
  $li_next = theme('pager_next', [
    'text' => (isset($tags[3]) ? $tags[3] : t('next ›')),
    'element' => $element,
    'interval' => 1,
    'parameters' => $parameters,
  ]);
  $li_last = theme('pager_last', [
    'text' => (isset($tags[4]) ? $tags[4] : t('last »')),
    'element' => $element,
    'parameters' => $parameters,
  ]);

  if ($pager_total[$element] > 1) {
    if ($li_first) {
      $items[] = [
        'class' => ['pager-first'],
        'data' => $li_first,
      ];
    }
    if ($li_previous) {
      $items[] = [
        'class' => ['pager-previous'],
        'data' => $li_previous,
      ];
    }

    // When there is more than one page, create the pager list.
    if ($i != $pager_max) {
      if ($i > 1) {
        $items[] = [
          'class' => ['pager-ellipsis'],
          'data' => '…',
        ];
      }
      // Now generate the actual pager piece.
      for (; $i <= $pager_last && $i <= $pager_max; $i++) {
        if ($i < $pager_current) {
          $items[] = [
            'class' => ['pager-item'],
            'data' => theme('pager_previous', [
              'text' => $i,
              'element' => $element,
              'interval' => ($pager_current - $i),
              'parameters' => $parameters,
            ]),
          ];
        }
        if ($i == $pager_current) {
          $items[] = [
            'class' => ['pager-current'],
            'data' => '<span>' . $i . '</span>',
          ];
        }
        if ($i > $pager_current) {
          $items[] = [
            'class' => ['pager-item'],
            'data' => theme('pager_next', [
              'text' => $i,
              'element' => $element,
              'interval' => ($i - $pager_current),
              'parameters' => $parameters,
            ]),
          ];
        }
      }
      if ($i < $pager_max) {
        $items[] = [
          'class' => ['pager-ellipsis'],
          'data' => '…',
        ];
      }
    }
    // End generation.
    if ($li_next) {
      $items[] = [
        'class' => ['pager-next'],
        'data' => $li_next,
      ];
    }
    if ($li_last) {
      $items[] = [
        'class' => ['pager-last'],
        'data' => $li_last,
      ];
    }

    $output = '<div class="pager__wrapper">';
    $output .= '<h2 class="element-invisible">' . t('Pages') . '</h2>' . theme('item_list',
        [
          'items' => $items,
          'attributes' => ['class' => ['pager']],
        ]
      );
    $output .= "</div>";

    return $output;
  }
}

/**
 * Implements hook_node_view.
 *
 * @param array &$build
 *        A renderable array representing the node content.
 */
function doca_theme_node_view_alter(&$build) {
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
 * Helper function to get the theme settings for mini sites by term ID.
 *
 * @return array
 *         An array of Theme minisite settings by term ID.
 */
function _doca_theme_get_subsites() {
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
