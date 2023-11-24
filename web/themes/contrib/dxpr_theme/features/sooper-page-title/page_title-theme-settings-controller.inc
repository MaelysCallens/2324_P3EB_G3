<?php

/**
 * @file
 * Applying theme page title settings.
 */

/**
 * Process page title theme settings.
 *
 * @param array $variables
 *   Variables array for the html template.
 */
function page_title_theme_settings_controller(array &$variables) {
  $page_title_animate = theme_get_setting('page_title_animate');
  if ($page_title_animate) {
    $variables['#attached']['library'][] = 'dxpr_theme/dxpr-theme-animatecss-custom';
  }
}
