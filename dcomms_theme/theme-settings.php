<?php

/**
 * @file
 * Theme settings.
 */

/**
 * Implements hook_form_system_theme_settings_alter().
 */
function dcomms_theme_form_system_theme_settings_alter(&$form, $form_state) {
  $form['cookie_textarea'] = array(
    '#type' => 'textarea',
    '#title' => t('Cookie Message'),
    '#default_value' => theme_get_setting('cookie_textarea'),
    '#description' => t("This is the message that will appear in the cookie notification at the top of your site."),
  );
  $form['rss_author'] = array(
    '#type' => 'textfield',
    '#title' => t('RSS Author'),
    '#default_value' => theme_get_setting('rss_author'),
    '#description' => t('Set author name to appear in the dc:creator field in RSS feeds.'),
  );
  // Site pages feedback settings.
  $form['feedback']['settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Site Pages Feedback'),
    '#description' => t("Site pages feedback settings."),
  );
  $form['external_link_popup'] = array(
    '#type' => 'fieldset',
    '#title' => t('External link popup'),
  );
  $form['external_link_popup']['external_link_enable_popup'] = array(
    '#type'          => 'checkbox',
    '#title'         => t('Enable external link popup'),
    '#default_value' => theme_get_setting('external_link_enable_popup'),
    '#description'   => t('Display a popup modal window when an external link is clicked.'),
  );
  $form['external_link_popup']['external_link_popup_text'] = array(
    '#type'          => 'textarea',
    '#title'         => t('Text in the popup window'),
    '#default_value' => theme_get_setting('external_link_popup_text'),
    '#description'   => t('Text to be display in the external link popup.'),
  );
}
