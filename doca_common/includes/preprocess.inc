<?php

/**
 * @file
 * Theme preprocess functions.
 */

/**
 * Implements hook_preprocess_page().
 */
function doca_common_preprocess_html(&$variables, $hook) {
  // Add offscreen class to body for mobile navigation.
  $variables['classes_array'][] = 'offscreen';
}

/**
 * Implements hook_preprocess_page().
 */
function doca_common_preprocess_page(&$variables, $hook) {
  // Add pathToTheme to Drupal.settings in JS.
  drupal_add_js('jQuery.extend(Drupal.settings, { "pathToTheme": "' . path_to_theme() . '" });', 'inline');

  // Site pages feedback.
  if (theme_get_setting('feedback_enabled')) {
    $wf_nid = theme_get_setting('feedback_wform_nid');
    drupal_add_js(['sitePagesFeedback' => ['nid' => $wf_nid]], 'setting');
    $variables['site_pages_feedback_form'] = _doca_common_webform_render($wf_nid);
  }

  // Add the correct google analytics code for the active environment.
  $ga_code = variable_get('googleanalytics_account');
  drupal_add_js(['gaSettings' => ['gaCode' => $ga_code]], 'setting');

  // Create template variables for the header menu block.
  $variables['header_search'] = _doca_common_block_render('search', 'form');
  $variables['header_menu'] = _doca_common_block_render('system', 'main-menu');
  // Create template variables for the footer menu blocks.
  $variables['footer_menu'] = _doca_common_block_render('menu', 'menu-footer-menu');
  $variables['footer_auxilary_menu'] = _doca_common_block_render('menu', 'menu-footer-sub-menu');

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
  if (array_search('page__consultations__iframe_portrait', $variables['theme_hook_suggestions']) || array_search('page__consultations__iframe_landscape', $variables['theme_hook_suggestions'])) {
    // Extend the theme hook suggestions to include a stripped page.
    $variables['theme_hook_suggestions'][] = 'page__stripped';
  }

  // Define page top announcement variable.
  $page_top_announcement_paths = drupal_strtolower(theme_get_setting('page_top_announcement_paths'));
  $current_path = drupal_strtolower(drupal_get_path_alias($_GET['q']));
  $page_match = drupal_match_path($current_path, $page_top_announcement_paths);
  if ($current_path != $_GET['q']) {
    $page_match = $page_match || drupal_match_path($_GET['q'], $page_top_announcement_paths);
  }
  if ($page_match) {
    $variables['top_announcements'] = theme_get_setting('page_top_announcement_messages');
  }
}

/**
 * Implements hook_preprocess_entity().
 */
function doca_common_preprocess_entity(&$variables, $hook) {
  if ($variables['entity_type'] === 'bean' && $variables['bean']->type === 'standard_page_children' && $variables['view_mode'] === 'coloured_links_grid') {
    // Get menu link of current page.
    $item = menu_link_get_preferred();

    // Get children menu items that are standard pages.
    $nids = doca_common_get_standard_page_menu_children($item);

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
        'doca_common' => [
          'alertHideName' => $variables['field_hide_name_field'][0]['value'],
          'alertFullName' => $variables['field_single_full_name'][0]['value'],
          'alertHideNumber' => $variables['field_hide_contact_number_field'][0]['value'],
          'alertMailGroup' => $variables['field_mail_groups'][0]['value'],
          'microSite' => variable_get('doca_common_micro_site', 'http://ministryofthearts.e-newsletter.com.au'),
          'apicall' => variable_get('doca_common_api_call', 'updateall'),
          'errorMessage' => variable_get('doca_common_error_message', t('Please check your email address and try again, if you are still having issues please <a href="mailto:media@communications.gov.au?Subject=Arts website newsletter subscriber">email us</a> your details.')),
          'alertSuccessMessage' => $variables['field_success_message'][0]['value'],
        ],
      ], 'setting');
    }
  }
}

/**
 * Implements template_preprocess_views_view().
 */
function doca_common_preprocess_views_view(&$variables) {
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
 * Implements template_preprocess_poll_results().
 */
function doca_common_preprocess_poll_results(&$variables) {
  $node = node_load($variables['nid']);
  $keys = array_keys($node->choice);
  $variables['votes_1'] = $node->choice[$keys[0]]['chvotes'];
  $variables['votes_2'] = $node->choice[$keys[1]]['chvotes'];
}

/**
 * Implements template_preprocess_field().
 */
function doca_common_preprocess_field(&$variables, $hook) {
  $element =& $variables['element'];
  $variables['theme_hook_suggestions'][] = 'field__' . $element['#field_name'] . '__' . $element['#view_mode'];
  $variables['theme_hook_suggestions'][] = 'field__' . $element['#bundle'] . '__' . $element['#view_mode'];
  $variables['theme_hook_suggestions'][] = 'field__' . $element['#bundle'] . '__' . $element['#view_mode'] . '__' . $element['#field_name'];

  if (($element['#field_type'] === 'text_with_summary' || $element['#field_type'] === 'text_long') && ($element['#formatter'] === 'text_summary_or_trimmed' || $element['#formatter'] === 'text_trimmed')) {
    $instance = field_info_instance($element['#entity_type'], $element['#field_name'], $element['#bundle']);
    $display = $instance['display'][$element['#view_mode']];
    $trim_length = $display['settings']['trim_length'];
    $use_summary = $element['#formatter'] === 'text_summary_or_trimmed';
    foreach ($element['#items'] as $delta => $item) {
      $markup = ($use_summary && !empty($item['safe_summary'])) ? $item['safe_summary'] : $item['safe_value'];
      $variables['items'][$delta]['#markup'] = doca_common_trim($markup, $trim_length);
    }
  }

  if ($element['#field_name'] === 'node_link') {
    $variables['read_more_text'] = t('Learn more');
    $node = $element['#object'];
    if (!empty($node->field_read_more_text[LANGUAGE_NONE][0]['safe_value'])) {
      $variables['read_more_text'] = $node->field_read_more_text[LANGUAGE_NONE][0]['safe_value'];
    }
  }

  if ($element['#field_type'] === 'image') {
    foreach ($variables['items'] as $delta => $item) {
      if (isset($item['#item'])) {
        if ($item['#item']['filemime'] === 'image/svg+xml') {
          unset($variables['items'][$delta]['#image_style']);
        }
      }

      if (isset($item['#file'])) {
        if ($item['#file']->filemime) {
          if ($item['#file']->filemime === 'image/svg+xml') {
            $variables['items'][$delta]['file']['#theme'] = 'image';
          }
        }
      }
    }
  }

  // Add consultation vars to relevant fields.
  $consultation_fields = [
    'hys_progress_bar',
    'field_formal_submission_cta_1',
    'field_consultation_date',
  ];
  $is_consultation_field = in_array($variables['element']['#field_name'], $consultation_fields);
  if ($is_consultation_field) {
    _consultation_vars($variables, $element['#object']);
  }

  if ($element['#field_name'] == 'field_consultation_date') {
    if (isset($element['#object']->field_display_fund_period[LANGUAGE_NONE]) && $element['#object']->field_display_fund_period[LANGUAGE_NONE][0]['value']) {
      $variables['hide_date'] = TRUE;
    }
  }

  // Replace title with promotional one-liner in non-full view modes.
  if ($element['#field_name'] == 'title' && $element['#view_mode'] != 'full') {
    if (isset($element['#object']->field_promotional_one_liner) && !empty($element['#object']->field_promotional_one_liner)) {
      $variables['one_liner'] = truncate_utf8($element['#object']->field_promotional_one_liner[LANGUAGE_NONE][0]['value'], 35, TRUE, TRUE);
    }
  }

  // Strip certain characters for Swift RSS integration.
  if ($element['#view_mode'] == 'rss_feed') {
    if ($element['#formatter'] == 'text_plain' && $element['#bundle'] == 'alert') {
      // Manually control markup for Alert content.
      $allowed_tags = '<p><br><h1><h2><h3><h4><h5><h6><a><b><strong><i><em><img>';
      $clean = strip_tags($element['#items'][0]['value'], $allowed_tags);
      $variables['items'][0]['#markup'] = check_markup($clean, 'rich_text');
    }

    // Replace incompatible characters.
    $variables['items'][0]['#markup'] = str_replace(
      [
        '—',
        '–',
        '“',
        '”',
        '’',
        '&nbsp;',
      ],
      [
        '-',
        '-',
        '"',
        '"',
        '\'',
        ' ',
      ],
      $variables['items'][0]['#markup']
    );

  }

  // Get the node.
  $node = $element['#object'];
  // Return whether a node has the 'External source' field filled in.
  $external_source = _doca_admin_return_node_has_external_source($node);
  $variables['external_source'] = $external_source;

  if ($variables['element']['#field_name'] == 'field_stackla_embed_para') {
    drupal_add_js(drupal_get_path('theme', 'doca_common') . '/vendor/js/stackla.js', ['file']);
  }
  if ($element['#field_name'] === 'formal_submission_webform') {
    if ($element['#bundle'] == 'funding') {
      if (isset($element['#object']->field_funding_app_webform[LANGUAGE_NONE])) {
        $variables['form_id'] = $element['#object']->field_funding_app_webform[LANGUAGE_NONE][0]['target_id'];
      }
    }
    else {
      $variables['form_id'] = theme_get_setting('have_your_say_wform_nid');
    }
  }

  if ($element['#field_name'] === 'field_feature_image' || $element['#field_name'] === 'field_pbundle_image' || $element['#field_name'] === 'field_image') {
    $img_class = '';
    $img_url = '';
    $has_caption = 0;
    $img_caption = '';
    $img_hover = '';
    $caption_d = '';
    $caption_mob = '';
    $variables['img_caption'] = '';
    foreach ($variables['items'] as $delta => $item) {
      if (isset($item['#item']['field_artist'][LANGUAGE_NONE][0]['safe_value'])) {
        $img_class = 'featured-with-caption';
        $has_caption = 1;
        $caption_d = '<span class="visible--md">' . $item['#item']['field_artist'][LANGUAGE_NONE][0]['safe_value'] . '<span class="feature-caption-link"> (detail) +</span></span>';
        $img_hover = '<div class="featured-overlay">' . $item['#item']['field_artist'][LANGUAGE_NONE][0]['safe_value'];
      }
      if (isset($item['#item']['field_file_image_title_text'][LANGUAGE_NONE][0]['safe_value'])) {
        $img_hover .= ' - ' . $item['#item']['field_file_image_title_text'][LANGUAGE_NONE][0]['safe_value'];
      }
      if (isset($item['#item']['field_read_more_text'][LANGUAGE_NONE][0]['safe_value'])) {
        $img_hover .= '<span>' . $item['#item']['field_read_more_text'][LANGUAGE_NONE][0]['safe_value'] . '</span>';
      }
      $img_hover .= '</div>';
      if (isset($item['#item']['field_link_to'][LANGUAGE_NONE][0]['url'])) {
        $img_url = $item['#item']['field_link_to'][LANGUAGE_NONE][0]['url'];
        $caption_mob = '<span class="visible--xs">Image credit +</span>';
      }
      if ($has_caption) :
        $img_caption .= '<div class="featured-with-caption__caption no-url">';
        if ($img_url) :
          $img_caption = '<div class="featured-with-caption__caption"><a href="' . $img_url . '"target="_blank">';
        endif;
        $img_caption .= $caption_mob;
        $img_caption .= $caption_d;
        if ($img_url) :
          $img_caption .= '</a>';
        endif;
        $img_caption .= '</div>' . $img_hover;
      endif;
      $variables['img_caption'] = $img_caption;
    }
    $variables['img_class'] = $img_class;
  }
}
