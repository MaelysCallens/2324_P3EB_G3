<?php

use Drupal\Component\Utility\Html;

/**
 * Implements hook_preprocess_HOOK() for region.html.twig.
 */
function belgrade_preprocess_region(&$variables) {

  $region = $variables['elements']['#region'];

  if (theme_get_setting('region_class_' . $region) !== NULL) {
    $classes = explode(' ', theme_get_setting('region_class_' . $region));
    foreach ($classes as $class) {
      $variables['attributes']['class'][] = Html::cleanCssIdentifier($class);
    }
  }

  if (theme_get_setting('region_container_' . $region) !== NULL) {
    $variables['region_container'] = theme_get_setting('region_container_' . $region);
  }

  if ($region === 'top_bar') {
    // Menu toggle text
    $variables['navigation_toggle_text'] = t(theme_get_setting('navigation_toggle_text'));
  }

  if ($region === 'navigation') {
    $variables['navigation_body_scrolling'] = "false";
    $variables['navigation_backdrop'] = "false";

    if (theme_get_setting('navigation_logo') === 1) {
      $variables['site_logo'] = \Drupal::service('file_url_generator')->generateString(theme_get_setting('logo.url'));
      $logo_path_info = pathinfo($variables['site_logo']);
      if (theme_get_setting('inline_logo') === 1 && $logo_path_info['extension'] === 'svg') {
        $variables['inline_logo'] = true;
      }
    }

    $variables['navigation_position'] = theme_get_setting('navigation_position');
    if (theme_get_setting('navigation_body_scrolling') === 1) {
      $variables['navigation_body_scrolling'] = "true";
    }
    if (theme_get_setting('navigation_backdrop') === 1) {
      $variables['navigation_backdrop'] = "true";
    }
  }
}
