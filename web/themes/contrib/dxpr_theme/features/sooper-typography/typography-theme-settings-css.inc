<?php

/**
 * @file
 * Typography settings css styles.
 */

/**
 * Generate typography theme css styles.
 *
 * @param string $theme
 *   Theme machine name.
 * @param string $css
 *   Generated css styles string.
 * @param array $palette
 *   Theme palette array.
 */
function typography_theme_settings_css($theme, &$css, array $palette) {
  $css .= "body, p, .lead { line-height:  " . theme_get_setting('body_line_height', $theme) . " } \n";
  $css .= "body { font-size:  " . theme_get_setting('body_font_size', $theme) . "px } \n";
  if (theme_get_setting('menu_type', $theme) != 'lead') {
    $css .= ".dxpr-theme-header--top #dxpr-theme-main-menu .nav > li > a, .dxpr-theme-header--top #dxpr-theme-main-menu .nav > li > span, .dxpr-theme-header--side #dxpr-theme-main-menu .nav a, .dxpr-theme-header--side #dxpr-theme-main-menu .nav span, .body--dxpr-theme-nav-desktop .dxpr-theme-header--top .nav .dropdown-menu li>a, .body--dxpr-theme-nav-desktop .dxpr-theme-header--top .nav .dropdown-menu li>span { font-size:  " . theme_get_setting('nav_font_size', $theme) . "px } \n";
  }
  $css .= "h1,h2,h3,h4,h5,h6 { line-height:  " . theme_get_setting('headings_line_height', $theme) . "; } \n";
  $css .= "h1, .html #page-title .page-title { font-size:  " . theme_get_setting('h1_font_size', $theme) . "px; } \n";
  $css .= "h2 { font-size:  " . theme_get_setting('h2_font_size', $theme) . "px; } \n";
  $css .= "h3 { font-size:  " . theme_get_setting('h3_font_size', $theme) . "px; } \n";
  $css .= "h4 { font-size:  " . theme_get_setting('h4_font_size', $theme) . "px; } \n";
  $css .= "blockquote, .blockquote p { font-size:  " . theme_get_setting('blockquote_font_size', $theme) . "px } \n";

  if ($letter_spacing = theme_get_setting('headings_letter_spacing', $theme)) {
    $css .= "h1,h2,h3,h4,h5,h6 { letter-spacing:  " . $letter_spacing . "em; } \n";
  }
  if (theme_get_setting('headings_uppercase', $theme)) {
    $css .= "h1,h2,h3,h4,h5,h6 { text-transform: uppercase; } \n";
  }
  if (theme_get_setting('headings_bold', $theme)) {
    $css .= "h1,h2,h3,h4,h5,h6 { font-weight: bold; } \n";
  }
  if ($height = theme_get_setting('divider_thickness', $theme)) {
    $css .= "hr { border-top-width:  " . $height . "px; } \n";
  }
  if ($length = theme_get_setting('divider_length', $theme)) {
    $css .= "hr { width:  " . $length . "px; } \n";
  }
  $position = theme_get_setting('divider_position', $theme);
  switch ($position) {
    case 1:
      $css .= "hr { margin-left: 0;margin-right: auto; }\n";
      break;

    case 2:
      $css .= "hr { margin-left: auto;margin-right: auto; }\n";
      break;

    case 3:
      $css .= "hr { margin-left: auto;margin-right: 0; }\n";
      break;
  }

  if ($value = theme_get_setting('divider_color', $theme)) {
    if (theme_get_setting('divider_color', $theme) == 'custom') {
      $value = theme_get_setting('divider_color_custom', $theme);
    }
    elseif (isset($palette[$value])) {
      $value = $palette[$value];
    }
    $css .= "hr {  background-color:  $value; } \n";
  }

  $css .= "@media screen and (max-width: 768px) { \n";
  $css .= "  body, p { font-size:  " . theme_get_setting('body_mobile_font_size', $theme) . "px; } \n";
  if (theme_get_setting('menu_type', $theme) != 'lead') {
    $css .= "  .dxpr-theme-header--top #dxpr-theme-main-menu .nav > li > a, .dxpr-theme-header--top #dxpr-theme-main-menu .nav > li > span, .dxpr-theme-header--side #dxpr-theme-main-menu .nav a, .dxpr-theme-header--side #dxpr-theme-main-menu .nav span, .body--dxpr-theme-nav-desktop .dxpr-theme-header--top .nav .dropdown-menu li>a, .body--dxpr-theme-nav-desktop .dxpr-theme-header--top .nav .dropdown-menu li>span { font-size:  " . theme_get_setting('nav_mobile_font_size', $theme) . "px } \n";
  }
  $css .= "  h1, .html #page-title .page-title { font-size:  " . theme_get_setting('h1_mobile_font_size', $theme) . "px; } \n";
  $css .= "  h2 { font-size:  " . theme_get_setting('h2_mobile_font_size', $theme) . "px; } \n";
  $css .= "  h3 { font-size:  " . theme_get_setting('h3_mobile_font_size', $theme) . "px; } \n";
  $css .= "  h4 { font-size:  " . theme_get_setting('h4_mobile_font_size', $theme) . "px; } \n";
  $css .= "  blockquote, .blockquote p { font-size:  " . theme_get_setting('blockquote_mobile_font_size', $theme) . "px; } \n";
  $css .= "} \n\n";
}
