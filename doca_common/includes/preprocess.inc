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
