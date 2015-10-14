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
    '#type'          => 'textarea',
    '#title'         => t('Cookie Message'),
    '#default_value' => theme_get_setting('cookie_textarea'),
    '#description'   => t("This is the message that will appear in the cookie notification at the top of your site."),
  );
}
