<?php

/**
 * @file
 * Custom css settings css styles.
 */

/**
 * Generate custom css theme css styles.
 *
 * @param string $theme
 *   Theme machine name.
 * @param string $css
 *   Generated css styles string.
 * @param array $palette
 *   Theme palette array.
 */
function custom_css_theme_settings_css($theme, &$css, array $palette) {
  if (theme_get_setting('custom_css_site', $theme)) {
    $css .= theme_get_setting('custom_css_site', $theme);
  }
}
