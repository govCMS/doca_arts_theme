<?php

/**
 * @file
 * Doca communication site custom theme.
 */

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
