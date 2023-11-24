<?php

/**
 * @file
 * Theme fonts settings.
 */

use Drupal\Core\Render\Markup;

/**
 * Add font options to the theme settings form.
 *
 * @param array $form
 *   Form array.
 * @param string $theme
 *   Theme machine name.
 */
function fonts_theme_settings(array &$form, $theme) {
  $font_list = dxpr_theme_font_list();
  $font_count = count($font_list, COUNT_RECURSIVE);

  $form['dxpr_theme_settings']['fonts'] = [
    '#title' => t('Fonts'),
    '#type' => 'details',
    '#group' => 'dxpr_theme_settings',
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#weight' => 20,
  ];

  // Regular Fonts Settings.
  $form['dxpr_theme_settings']['fonts']['body_font_face'] = [
    '#type' => 'select',
    '#title' => t('Base font'),
    '#default_value' => theme_get_setting('body_font_face'),
    '#options' => $font_list,
    '#prefix' => '<p>' . t('Choose from @count webfonts', ['@count' => $font_count]) . '</p>',
  ];

  $form['dxpr_theme_settings']['fonts']['body_font_face_selector'] = [
    '#type' => 'textfield',
    '#title' => t('CSS Selector'),
    '#default_value' => ((theme_get_setting('body_font_face_selector') !== NULL)) ? theme_get_setting('body_font_face_selector') : 'body',
  ];

  $form['dxpr_theme_settings']['fonts']['headings_font_face'] = [
    '#type' => 'select',
    '#title' => t('Headings'),
    '#default_value' => theme_get_setting('headings_font_face'),
    '#options' => $font_list,
  ];

  $form['dxpr_theme_settings']['fonts']['headings_font_face_selector'] = [
    '#type' => 'textfield',
    '#title' => '&nbsp;',
    '#default_value' => ((theme_get_setting('headings_font_face_selector') !== NULL)) ? theme_get_setting('headings_font_face_selector') : 'h1,h2,h3,h4,h5,h6,label,.field--label,.page-title, .html .tp-caption',
  ];

  $form['dxpr_theme_settings']['fonts']['nav_font_face'] = [
    '#type' => 'select',
    '#title' => t('Navigation'),
    '#default_value' => theme_get_setting('nav_font_face'),
    '#options' => $font_list,
  ];

  $form['dxpr_theme_settings']['fonts']['nav_font_face_selector'] = [
    '#type' => 'textfield',
    '#title' => '&nbsp;',
    '#default_value' => ((theme_get_setting('nav_font_face_selector') !== NULL)) ? theme_get_setting('nav_font_face_selector') : 'nav,nav ul li,nav a',
  ];

  $form['dxpr_theme_settings']['fonts']['sitename_font_face'] = [
    '#type' => 'select',
    '#title' => t('Site name'),
    '#default_value' => theme_get_setting('sitename_font_face'),
    '#options' => $font_list,
  ];

  $form['dxpr_theme_settings']['fonts']['sitename_font_face_selector'] = [
    '#type' => 'textfield',
    '#title' => '&nbsp;',
    '#default_value' => ((theme_get_setting('sitename_font_face_selector') !== NULL)) ? theme_get_setting('sitename_font_face_selector') : '.site-name, .navbar-brand',
  ];

  $form['dxpr_theme_settings']['fonts']['blockquote_font_face'] = [
    '#type' => 'select',
    '#title' => t('Blockquote'),
    '#default_value' => theme_get_setting('blockquote_font_face'),
    '#options' => $font_list,
  ];

  $form['dxpr_theme_settings']['fonts']['blockquote_font_face_selector'] = [
    '#type' => 'textfield',
    '#title' => '&nbsp;',
    '#default_value' => ((theme_get_setting('blockquote_font_face_selector') !== NULL)) ? theme_get_setting('blockquote_font_face_selector') : 'blockquote, blockquote p',
  ];

  $form['dxpr_theme_settings']['fonts']['font_preview'] = [
    '#title' => t('Browse & View Fonts'),
    '#type' => 'details',
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<br class="clear-both"><br class="clear-both">',
    '#suffix' => _dxpr_theme_type_preview(),
  ];

  $form['dxpr_theme_settings']['fonts']['font_preview']['preview'] = [
    '#type' => 'markup',
    '#markup' => _dxpr_theme_font_previews(),
  ];

  // Load local fonts to use in preview.
  foreach (dxpr_theme_local_webfonts() as $key => $font_name) {
    _dxpr_theme_add_preview_font($key, $form);
  }
}

/**
 * Helper function to get list of locally hosted fonts.
 */
function dxpr_theme_local_webfonts() {
  $theme = \Drupal::service('theme.manager')->getActiveTheme();
  $theme_chain = [$theme->getName()];
  if (!empty($theme->getExtension()->base_themes)) {
    $theme_chain = array_merge($theme_chain, array_keys($theme->getExtension()->base_themes));
  }

  $fonts = [];
  foreach ($theme_chain as $theme) {
    $theme_path = \Drupal::service('extension.list.theme')->getPath($theme);
    if (!is_dir($theme_path . '/fonts')) {
      continue;
    }

    foreach (\Drupal::service('file_system')->scanDirectory($theme_path . '/fonts', '/.css/i') as $file) {
      $font_path = str_replace($theme_path, '', $file->uri);
      $css = file_get_contents($file->uri);
      if ($css) {
        preg_match_all('/font-family: \'(.*?)\'/i', $css, $matches);
      }
      else {
        \Drupal::messenger()->addMessage(t('Cannot read font files, please check permissions on the fonts folder in the theme.'), 'error');
        continue;
      }
      foreach ($matches[1] as $font) {
        if ($font) {
          $key = '1' . $theme . '|' . $font_path . '|' . $font;
          $fonts[$key] = preg_replace('/(?<!\ ) {A-Z]/', ' $0', $font);
        }
      }
    }
  }

  asort($fonts, SORT_STRING);
  return $fonts;
}

/**
 * Helper function to provide a list of fonts for select list in theme settings.
 */
function dxpr_theme_font_list() {
  $fonts = [
    'Sans-serif web-safe' => [
      'SF_Segoe_Roboto' => 'San Francisco/Segoe/Roboto',
      'helvetica' => 'Helvetica/Arial',
      'myriad' => 'Myriad/Calibri',
      'verdana' => 'Verdana',
      'lucida' => 'Lucida Sans',
      'geneva' => 'Geneva',
      'tahoma' => 'Tahoma',
      'trebuchet' => 'Trebuchet',
      'century' => 'Century Gothic',
    ],
    'Serif web-safe' => [
      'garamond' => 'Garamond',
      'georgia' => 'Georgia',
      'palatino' => 'Palatino Linotype',
      'times' => 'Times New Roman',
    ],
  ];
  $fonts['Local webfonts'] = dxpr_theme_local_webfonts();
  $fonts['Google webfonts'] = dxpr_theme_google_webfonts();

  return $fonts;
}

/**
 * Helper function to get list of google web fonts.
 *
 * Source: https://raw.githubusercontent.com/jonathantneal/google-fonts-complete/master/api-response.json.
 */
function dxpr_theme_google_webfonts() {
  $json_webfonts = file_get_contents(__DIR__ . '/google-webfonts.json');
  $webfonts = json_decode($json_webfonts);
  $fonts = [];
  foreach ($webfonts as $font_family) {
    $fam_name = $font_family->family;
    foreach ($font_family->variants as $font) {
      if ($font == 'regular') {
        $font = '';
      }

      $font_name = $fam_name . ' ' . $font;
      $font_name_safe = str_replace(' ', '+', $fam_name) . ':' . $font;
      $font_name_key = '0' . $font_name_safe;
      $fonts[$font_name_key] = $font_name;
    }
  }
  return $fonts;
}

/**
 * Helper function to display font previews in the settings form.
 */
function _dxpr_theme_font_previews() {
  $output = '<div class="fonts-previews">';
  $output .= '  <h3>Google fonts preview</h3>';
  $output .= '  <p>Check out <a href="http://www.google.com/webfonts">google.com/webfonts</a> for previews of google web fonts.</p>';
  $output .= '  <h3>Local fonts preview</h3>';
  $output .= '  <p>DXPR Theme will automatically detect and load any Fontsquirrel-generated @font-face package in the exmapletheme/fonts folder. See <a href="https://www.fontsquirrel.com/tools/webfont-generator">Fontsquirrel.com</a></p>';
  foreach (dxpr_theme_local_webfonts() as $key => $font_name) {
    $font = explode('|', $key);
    $local_name = $font[2];
    $output .= '  <div class="font-preview font-local" style="font-family:' . $local_name . '">';
    $output .= '  <p class="font-name">' . $font_name . '</p><p class="font-test">The quick brown fox jumps over the lazy dog.</p>';
    $output .= '  <p class="font-test-small">The quick brown fox jumps over the lazy dog.</p>';
    $output .= '  <p class="font-test-accents">&#36; &euro; &pound; &yen; &agrave; &egrave; &igrave; &ograve; &ugrave; &Agrave; &Egrave; &Igrave; &Ograve; &Ugrave; &aacute; &eacute; &iacute; &oacute; &uacute; &yacute; &Aacute; &Eacute; &Iacute; &Oacute; &Uacute; &Yacute;</p>';
    $output .= '  </div>';
  }
  $output .= '</div>';
  return Markup::create($output);
}

/**
 * Loads font to use in preview form.
 *
 * @param string $font
 *   Requested font.
 * @param array $form
 *   An associative array containing the structure of the form.
 */
function _dxpr_theme_add_preview_font($font, array &$form) {
  global $base_url;

  $added_stylesheets = &drupal_static(__FUNCTION__);
  $added_stylesheets = $added_stylesheets ?? [];

  $font = explode('|', substr($font, 1));
  $path = \Drupal::service('extension.list.theme')->getPath($font[0]) . $font[1];
  if (empty($added_stylesheets[$path])) {
    $element = [
      '#tag' => 'link',
      '#attributes' => [
        'href' => $base_url . '/' . $path,
        'rel' => 'stylesheet',
        'type' => 'text/css',
      ],
    ];
    $form['#attached']['html_head'][] = [$element, $font[2]];
    $added_stylesheets[$path] = $path;
  }
}
