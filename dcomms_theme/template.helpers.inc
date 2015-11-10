<?php
/**
 * @file
 * Contains the theme's helper functions.
 */

/*
 * Helper function: Return the 'External source' field value.
 */
function _dcomms_admin_return_external_source($node, $wrapper = NULL) {
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
function _dcomms_admin_return_node_has_external_source($node) {
  return (_dcomms_admin_return_external_source($node)) != FALSE ? TRUE : FALSE;
}
