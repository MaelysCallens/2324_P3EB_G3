<?php

/**
 * @file
 * Theme and preprocess functions for fields.
 */

function belgrade_preprocess_field(&$variables, $hook) {
  $element = $variables['element'];
  if ($element['#field_name'] == 'cklb_cta') {
    $variables['items'][0]['content']['#options']['attributes']['class'][] = 'btn btn-primary btn-lg';
  }
}
