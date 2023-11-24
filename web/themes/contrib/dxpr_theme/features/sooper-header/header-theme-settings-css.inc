<?php

/**
 * @file
 * Header settings css styles.
 */

/**
 * Convert hexdec color string to rgb(a) string.
 *
 * Check http://mekshq.com/how-to-convert-hexadecimal-color-code-to-rgb-or-rgba-using-php.
 *
 * @param string $color
 *   Color HEX string.
 * @param string $opacity
 *   Opacity value.
 *
 * @return string
 *   Converted color rgba value.
 */
function _dxpr_theme_hex2rgba($color, $opacity = NULL) {
  $default = 'rgb(0,0,0)';
  // Return default if no color provided.
  if (empty($color)) {
    return $default;
  }
  // Sanitize $color if "#" is provided.
  if ($color[0] == '#') {
    $color = substr($color, 1);
  }

  // Check if color has 6 or 3 characters and get values.
  if (strlen($color) == 6) {
    $hex = [
      $color[0] . $color[1],
      $color[2] . $color[3],
      $color[4] . $color[5],
    ];
  }
  elseif (strlen($color) == 3) {
    $hex = [
      $color[0] . $color[0],
      $color[1] . $color[1],
      $color[2] . $color[2],
    ];
  }
  else {
    return $default;
  }

  // Convert hexadec to rgb.
  $rgb = array_map('hexdec', $hex);

  // Check if opacity is set(rgba or rgb)
  if ($opacity !== NULL) {
    if (abs($opacity) > 1) {
      $opacity = 1.0;
    }
    $output = 'rgba(' . implode(",", $rgb) . ',' . $opacity . ')';
  }
  else {
    $output = 'rgb(' . implode(",", $rgb) . ')';
  }

  // Return rgb(a) color string.
  return $output;
}

/**
 * Generate header theme css styles.
 *
 * @param string $theme
 *   Theme machine name.
 * @param string $css
 *   Generated css styles string.
 * @param array $palette
 *   Theme palette array.
 */
function header_theme_settings_css($theme, &$css, array $palette) {
  // Header Vars.
  $align = theme_get_setting('header_side_align', $theme);
  $header_side_width = theme_get_setting('header_side_width', $theme);
  $header_top_height = theme_get_setting('header_top_height', $theme);
  $header_top_height_scroll = theme_get_setting('header_top_height_scroll', $theme);
  $header_top_opacity = $header_top_opacity_scroll = 1;
  $css .= <<<EOT
  #dxpr-theme-main-menu,
  #dxpr-theme-main-menu .menu__breadcrumbs,
  #dxpr-theme-main-menu .menu__level {
    text-align: $align;
  }

EOT;
  if ($align == 'right') {
    $css .= <<<EOT
    .dxpr-theme-header--side #dxpr-theme-main-menu .menu__link::after {
      right: auto;
      left: 20px;
    }

EOT;
  }

  $css .= <<<EOT
.body--dxpr-theme-nav-desktop.body--dxpr-theme-header-side #navbar.dxpr-theme-header {
  text-align: $align;
}
html.js .body--dxpr-theme-nav-desktop.body--dxpr-theme-header-side {
  padding-left: calc({$header_side_width}px + var(--gin-toolbar-x-offset, 256px));
}
.body--dxpr-theme-nav-desktop .dxpr-theme-header--side,
.body--dxpr-theme-nav-mobile .dxpr-theme-header--side #dxpr-theme-main-menu,
.dxpr-theme-header--side .menu__level {
  width: {$header_side_width}px;
}

EOT;

  if (!theme_get_setting('header_position', $theme)) {
    $header_top_rgba = $header_top_scroll_rgba = $header_top_scroll_rgba = $palette['header'] ?? '';
    if (theme_get_setting('header_top_sticky', $theme) && theme_get_setting('header_top_sticky_show', $theme) == 'after_scroll') {
      $header_top_opacity = 0;
    }
    if (in_array(theme_get_setting('header_style', $theme), [
      'normal',
      'overlay',
    ])) {
      if ($palette) {
        $color = $palette['header'];
      }
      else {
        $color = '#ffffff';
      }
      $opacity = theme_get_setting('header_top_bg_opacity', $theme);
      $opacity_scroll = theme_get_setting('header_top_bg_opacity_scroll', $theme);
      $header_top_rgba = _dxpr_theme_hex2rgba($color, $opacity);
      $header_top_scroll_rgba = _dxpr_theme_hex2rgba($color, $opacity_scroll);

      if (theme_get_setting('header_style') === 'normal') {
        $header_top_scroll_rgba = _dxpr_theme_hex2rgba($color, $opacity);
      }
    }
    $css .= <<<EOT

.body--dxpr-theme-nav-desktop #navbar.dxpr-theme-header--top {
  background-color: $header_top_rgba;
  opacity: $header_top_opacity;
}
.body--dxpr-theme-nav-desktop #navbar.dxpr-theme-header--top.affix {
  background-color: $header_top_scroll_rgba;
  opacity: $header_top_opacity_scroll;
}

.body--dxpr-theme-nav-desktop .dxpr-theme-header--top .nav > li > a,
.body--dxpr-theme-nav-desktop .dxpr-theme-header--top .nav > li > span,
.body--dxpr-theme-nav-desktop .dxpr-theme-header--top .wrap-branding,
.body--dxpr-theme-nav-desktop .dxpr-theme-header--top .block-dxpr-theme-helper {
  height: {$header_top_height}px;
  line-height: {$header_top_height}px;
}
.body--dxpr-theme-nav-desktop .dxpr-theme-header--top .dxpr-theme-main-menu {
  height: {$header_top_height}px;
}
.body--dxpr-theme-nav-desktop .dxpr-theme-header--top.affix .nav > li > a,
.body--dxpr-theme-nav-desktop .dxpr-theme-header--top.affix .nav > li > span,
.body--dxpr-theme-nav-desktop .dxpr-theme-header--top.affix .navbar-header .wrap-branding,
.body--dxpr-theme-nav-desktop .dxpr-theme-header--top.affix .block-dxpr-theme-helper  {
  height: {$header_top_height_scroll}px;
  line-height: {$header_top_height_scroll}px;
}
.body--dxpr-theme-nav-desktop .dxpr-theme-header--top.affix .dxpr-theme-main-menu {
  height: {$header_top_height_scroll}px;
}
.body--dxpr-theme-nav-desktop .nav-child-fixed-width {
  top: {$header_top_height}px;
}
.body--dxpr-theme-nav-desktop .dxpr-theme-header--top.affix .nav-child-fixed-width {
  top: $header_top_height_scroll;
}
html.js .body--dxpr-theme-nav-desktop.gin--vertical-toolbar.toolbar-fixed.toolbar-horizontal.body--dxpr-theme-header-fixed.body--dxpr-theme-header-top {
  padding-top: {$header_top_height}px !important;
}

EOT;

    if (theme_get_setting('header_style', $theme) == 'overlay') {
      // For overlay header add a big gap in the top of the page title
      // to make room for the header.
      $css .= ".body--dxpr-theme-nav-desktop.body--dxpr-theme-header-overlay .page-title-full-width-container  { padding-top: " . ($header_top_height + 30) . "px }\n\n";
    }
    elseif (theme_get_setting('header_top_fixed', $theme)) {
      // For fixed headers create small padding to prevent fixed
      // header from hiding toolbars.
      $css .= ".body--dxpr-theme-nav-desktop.body--dxpr-theme-header-fixed.body--dxpr-theme-header-not-overlay { padding-top: " . $header_top_height . "px !important }\n\n";
      $css .= "  html.js .body--dxpr-theme-nav-desktop.toolbar-fixed.toolbar-horizontal.body--dxpr-theme-header-fixed.body--dxpr-theme-header-not-overlay { padding-top: " . ($header_top_height + 39) . "px !important }\n\n";
      $css .= "  html.js .body--dxpr-theme-nav-desktop.toolbar-fixed.toolbar-horizontal.toolbar-tray-open.toolbar-horizontal.body--dxpr-theme-header-fixed.body--dxpr-theme-header-not-overlay { padding-top: " . ($header_top_height + 80) . "px !important }\n\n";
    }

    if (theme_get_setting('header_style', $theme) == 'navbar_pull_down') {
      $css .= ".body--dxpr-theme-nav-desktop.body--dxpr-theme-header-not-overlay .dxpr-theme-header--top .col-sm-12 {\n margin-bottom: -" . ($header_top_height / 2) . "px; }\n\n";
    }

  }

  // Mobile Header.
  $header_mobile_height = theme_get_setting('header_mobile_height', $theme);
  $css .= <<<EOT
.body--dxpr-theme-nav-mobile .dxpr-theme-header .navbar-header .wrap-branding,
.body--dxpr-theme-nav-mobile .dxpr-theme-header .navbar-toggle {
  height: {$header_mobile_height}px;
}
.body--dxpr-theme-nav-mobile .dxpr-theme-header .block-dxpr-theme-helper {
  height: {$header_mobile_height}px;
  line-height: {$header_mobile_height}px;
}

EOT;

  if (theme_get_setting('sticky_footer', $theme)) {
    if (theme_get_setting('header_top_layout', $theme) == 'centered') {
      $header_top_height = 2 * $header_top_height;
    }
    if (theme_get_setting('header_style', $theme) == 'navbar_pull_down') {
      $header_top_height = $header_top_height / 2;
    }
    $css .= <<<EOT
.html--dxpr-theme-sticky-footer .body--dxpr-theme-nav-desktop .dxpr-theme-header--top {
  min-height: {$header_top_height}px;
}
.html--dxpr-theme-sticky-footer .body--dxpr-theme-nav-desktop .dxpr-theme-header--top.affix {
  min-height: {$header_top_height_scroll}px;
}
.html--dxpr-theme-sticky-footer .body--dxpr-theme-nav-mobile .dxpr-theme-header {
  min-height: {$header_mobile_height}px;
}
.html--dxpr-theme-sticky-footer .body--dxpr-theme-nav-mobile .dxpr-theme-header.affix {
  min-height: {$header_mobile_height}px;
}

EOT;
  }

  /*
   * MENU OPTIONS
   */

  if ($dropdown_width = theme_get_setting('dropdown_width', $theme)) {
    $css .= <<<EOT
.dropdown-menu, .dropdown-menu li {
  min-width: {$dropdown_width}px;
}

EOT;

  }

  if (theme_get_setting('menu_type', $theme) == 'uppercase') {
    $css .= <<<EOT
.dxpr-theme-header .menu a {
  text-transform: uppercase;
}

EOT;
  }
  elseif (theme_get_setting('menu_type', $theme) == 'bold') {
    $css .= <<<EOT
.dxpr-theme-header .menu a {
  font-weight: bold;
}

EOT;
  }
  elseif (theme_get_setting('menu_type', $theme) == 'lead') {
    $css .= <<<EOT
.dxpr-theme-header .menu a ,
.dxpr-theme-header .menu span {
  font-size: 21px;
  font-weight: 300;
}

EOT;
  }

  // We only add border on header side if this is the desktop view too
  // To prevent header-top configuration and spacing
  // to look broken in mobile menus.
  if (theme_get_setting('menu_hover', $theme) == 'border') {
    $css .= <<<EOT
.body--dxpr-theme-header-side .dxpr-theme-header--side.dxpr-theme-header--hover-border .menu a::before,
.dxpr-theme-header--top.dxpr-theme-header--hover-border .nav > li > a::before {

EOT;
    if ($height = theme_get_setting('menu_border_size', $theme)) {
      $css .= "  height: {$height}px; \n";
    }
    if ($position = theme_get_setting('menu_border_position', $theme)) {
      $css .= "  {$position}: 0; \n";
      if (($margin = theme_get_setting('menu_border_position_offset', $theme)) && (theme_get_setting('header_position', $theme) == FALSE)) {
        $css .= "  margin-{$position}: {$margin}px; \n";
      }
    }
    if ($value = theme_get_setting('menu_border_color', $theme)) {
      if (theme_get_setting('menu_border_color', $theme) == 'custom') {
        $value = theme_get_setting('menu_border_color_custom', $theme);
      }
      elseif (isset($palette[$value])) {
        $value = $palette[$value];
      }
      $css .= "  background-color:  $value; \n";
    }
    if ($animation = theme_get_setting('menu_border_animate', $theme)) {
      $css .= "  transition: transform 300ms; \n";
      switch ($animation) {
        case 'ltr':
          $css .= "  transform-origin: 0 50%; \n";
          break;

        case 'rtl':
          $css .= "  transform-origin: 100% 50%; \n";
          break;

        case 'drop':
          $css .= "  transform: scale(0, 0) translate(10px, -10px); \n";
          $css .= "  transform-origin: 50% 0; \n";
          break;
      }
    }
    $css .= "\n } \n\n";
  }

  if (($position = theme_get_setting('menu_border_position', $theme))
    && ($margin_sticky = theme_get_setting('menu_border_position_offset_sticky', $theme))
    && (theme_get_setting('header_position', $theme) == FALSE)) {
    $css .= <<<EOT
.dxpr-theme-header--top.dxpr-theme-header--hover-border.affix .nav > li > a::before {
  margin-{$position}: {$margin_sticky}px;
}
EOT;
  }

  // Custom Colors.
  if ($navbarbg = theme_get_setting('navbar_background', $theme)) {
    if (theme_get_setting('navbar_background', $theme) == 'custom') {
      $navbarbg = theme_get_setting('navbar_background_custom', $theme);
    }
    elseif (isset($palette[$navbarbg])) {
      $navbarbg = $palette[$navbarbg];
    }
    $css .= ".html #navbar.dxpr-theme-header--top .container-row > .col-sm-12 { background-color: $navbarbg }\n\n";
  }
  if ($blockbg = theme_get_setting('header_block_background', $theme)) {
    if (theme_get_setting('header_block_background', $theme) == 'custom') {
      $blockbg = theme_get_setting('header_block_background_custom', $theme);
    }
    elseif (isset($palette[$blockbg])) {
      $blockbg = $palette[$blockbg];
    }
    $css .= "#navbar.dxpr-theme-header--top .block { background-color: $blockbg }\n\n";
  }
  if ($blocktxt = theme_get_setting('header_block_text_color', $theme)) {
    if (theme_get_setting('header_block_text_color', $theme) == 'custom') {
      $blocktxt = theme_get_setting('header_block_text_color_custom', $theme);
    }
    elseif (isset($palette[$blocktxt])) {
      $blocktxt = $palette[$blocktxt];
    }
    $css .= "#navbar.dxpr-theme-header--top .block, #navbar.dxpr-theme-header--top .block a, #navbar.dxpr-theme-header--top .block .btn-link { color: $blocktxt }\n\n";
  }
  if ($menubg = theme_get_setting('menu_background', $theme)) {
    if (theme_get_setting('menu_background', $theme) == 'custom') {
      $menubg = theme_get_setting('menu_background_custom', $theme);
    }
    elseif (isset($palette[$menubg])) {
      $menubg = $palette[$menubg];
    }
    $css .= "#navbar.dxpr-theme-header--top .menu { background-color: $menubg }\n\n";
  }
  if ($menutxt = theme_get_setting('menu_text_color', $theme)) {
    if (theme_get_setting('menu_text_color', $theme) == 'custom') {
      $menutxt = theme_get_setting('menu_text_color_custom', $theme);
    }
    elseif (isset($palette[$menutxt])) {
      $menutxt = $palette[$menutxt];
    }
    $css .= "#navbar.dxpr-theme-header--top .menu, #navbar.dxpr-theme-header--top .menu a { color: $menutxt }\n\n";
  }
  if ($menuhoverbg = theme_get_setting('menu_hover_background', $theme)) {
    if (theme_get_setting('menu_hover_background', $theme) == 'custom') {
      $menuhoverbg = theme_get_setting('menu_hover_background_custom', $theme);
    }
    elseif (isset($palette[$menuhoverbg])) {
      $menuhoverbg = $palette[$menuhoverbg];
    }
    $css .= "#navbar.dxpr-theme-header--top .menu a:hover, #navbar.dxpr-theme-header--top .menu a:focus { background-color: $menuhoverbg }\n\n";
  }
  if ($menuhovertxt = theme_get_setting('menu_hover_text_color', $theme)) {
    if (theme_get_setting('menu_hover_text_color', $theme) == 'custom') {
      $menuhovertxt = theme_get_setting('menu_hover_text_color_custom', $theme);
    }
    elseif (isset($palette[$menuhovertxt])) {
      $menuhovertxt = $palette[$menuhovertxt];
    }
    $css .= "#navbar.dxpr-theme-header--top .menu a:hover, #navbar.dxpr-theme-header--top .menu a:focus { color: $menuhovertxt }\n\n";
  }
  if ($dropdownbg = theme_get_setting('dropdown_background', $theme)) {
    if (theme_get_setting('dropdown_background', $theme) == 'custom') {
      $dropdownbg = theme_get_setting('dropdown_background_custom', $theme);
    }
    elseif (isset($palette[$dropdownbg])) {
      $dropdownbg = $palette[$dropdownbg];
    }
    $css .= "#navbar.dxpr-theme-header--top .dropdown-menu { background-color: $dropdownbg }\n\n";
  }
  if ($dropdowntxt = theme_get_setting('dropdown_text_color', $theme)) {
    if (theme_get_setting('dropdown_text_color', $theme) == 'custom') {
      $dropdowntxt = theme_get_setting('dropdown_text_color_custom', $theme);
    }
    elseif (isset($palette[$dropdowntxt])) {
      $dropdowntxt = $palette[$dropdowntxt];
    }
    $css .= "#navbar.dxpr-theme-header--top .dropdown, #navbar.dxpr-theme-header--top .dropdown-menu a { color: $dropdowntxt }\n\n";
  }
  if ($dropdownhoverbg = theme_get_setting('dropdown_hover_background', $theme)) {
    if (theme_get_setting('dropdown_hover_background', $theme) == 'custom') {
      $dropdownhoverbg = theme_get_setting('dropdown_hover_background_custom', $theme);
    }
    elseif (isset($palette[$dropdownhoverbg])) {
      $dropdownhoverbg = $palette[$dropdownhoverbg];
    }
    $css .= "#navbar.dxpr-theme-header--top .dropdown-menu a:hover,#navbar.dxpr-theme-header--top .dropdown-menu a:focus { background-color: $dropdownhoverbg }\n\n";
  }
  if ($dropdownhovertxt = theme_get_setting('dropdown_hover_text_color', $theme)) {
    if (theme_get_setting('dropdown_hover_text_color', $theme) == 'custom') {
      $dropdownhovertxt = theme_get_setting('dropdown_hover_text_color_custom', $theme);
    }
    elseif (isset($palette[$dropdownhovertxt])) {
      $dropdownhovertxt = $palette[$dropdownhovertxt];
    }
    $css .= "#navbar.dxpr-theme-header--top .dropdown-menu a:hover,#navbar.dxpr-theme-header--top .dropdown-menu a:focus { color: $dropdownhovertxt }\n\n";
  }
}
