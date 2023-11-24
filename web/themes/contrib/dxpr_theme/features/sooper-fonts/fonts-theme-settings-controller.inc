<?php

/**
 * @file
 * Applying theme font settings.
 */

use Drupal\Core\File\FileSystemInterface;

/**
 * Process theme font theme settings.
 *
 * @param array $variables
 *   Variables array for the html template.
 *
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function fonts_theme_settings_controller(array &$variables) {
  $dxpr_theme_fonts = [];
  $dxpr_theme_fonts[] = theme_get_setting('body_font_face');
  $dxpr_theme_fonts[] = theme_get_setting('headings_font_face');
  $dxpr_theme_fonts[] = theme_get_setting('nav_font_face');
  $dxpr_theme_fonts[] = theme_get_setting('sitename_font_face');
  $dxpr_theme_fonts[] = theme_get_setting('blockquote_font_face');

  $dxpr_theme_fonts = array_unique($dxpr_theme_fonts);
  $requested_google_fonts = [];

  foreach ($dxpr_theme_fonts as $dxpr_theme_font) {
    if (isset($dxpr_theme_font[0]) && $dxpr_theme_font[0] === '0') {
      $dxpr_theme_font = trim(substr($dxpr_theme_font, 1), ':');
      $requested_google_fonts[] = $dxpr_theme_font;
    }
    elseif (isset($dxpr_theme_font[0]) && $dxpr_theme_font[0] === '1') {
      _dxpr_theme_add_local_font($dxpr_theme_font, $variables);
    }
  }

  if (!empty($requested_google_fonts)) {
    _dxpr_theme_add_google_fonts($requested_google_fonts, $variables);
  }
}

/**
 * Helper function to add google fonts to the page.
 *
 * @param array $google_fonts
 *   Requested Google fonts.
 * @param array $variables
 *   Variables array.
 *
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function _dxpr_theme_add_google_fonts(array $google_fonts, array &$variables) {
  $state = \Drupal::state();
  $planned_fonts_update = $state->get('dxpr_theme_fonts_update');
  $stored_fonts = $state->get('dxpr_theme_stored_fonts');

  $active_theme = \Drupal::service('theme.manager')->getActiveTheme();
  /** @var \Drupal\Core\File\FileSystemInterface $file_system */
  $file_system = \Drupal::service('file_system');

  // Google font files are stored in public files for each theme.
  $fonts_folder_uri = 'public://' . $active_theme->getName() . '/fonts';

  // Check if fonts were already saved to files folder.
  $file_system->prepareDirectory($fonts_folder_uri, FileSystemInterface::CREATE_DIRECTORY);
  $font_family_exists = file_exists($fonts_folder_uri . '/font-face.css');

  $obsolete_fonts_cache = time() > $planned_fonts_update ||
    $stored_fonts !== implode(',', $google_fonts);

  if ($obsolete_fonts_cache || !$font_family_exists) {
    _dxpr_theme_remove_google_fonts();
    _dxpr_theme_cache_google_fonts($google_fonts, $fonts_folder_uri);
  }

  if ($wrapper = \Drupal::service('stream_wrapper_manager')->getViaUri($fonts_folder_uri . '/' . 'font-face.css')) {
    $local_path = $wrapper->realpath();

    if (!empty($local_path)) {
      $file_content = file_get_contents($local_path);
      $file_content = str_replace(["\r", "\n"], '', $file_content);

      $element = [
        '#tag' => 'style',
        '#value' => $file_content,
      ];

      $variables['#attached']['html_head'][] = [
        $element,
        'dxpr_theme_google_fonts',
      ];
    }
  }

}

/**
 * Cache requested Google fonts in public folder.
 *
 * @param array $google_fonts
 *   Requested Google fonts.
 * @param string $fonts_folder_uri
 *   Path to "cached" Google fonts.
 *
 * @throws \Drupal\Core\Entity\EntityStorageException
 * @throws \GuzzleHttp\Exception\GuzzleException
 */
function _dxpr_theme_cache_google_fonts(array $google_fonts, string $fonts_folder_uri) {
  $fonts_url = '';
  $state = \Drupal::state();
  /** @var \GuzzleHttp\ClientInterface $http_client */
  $http_client = \Drupal::httpClient();
  /** @var \Drupal\file\FileRepositoryInterface $file_repository */
  $file_repository = \Drupal::service('file.repository');

  // Build url to request Google fonts.
  foreach ($google_fonts as $google_font) {
    $font_params = explode(':', $google_font);

    if (!isset($font_params[1])) {
      $fonts_url .= 'family=' . $google_font . '&';
    }
    elseif (is_numeric($font_params[1])) {
      $fonts_url .= 'family=' . $font_params[0] . ':wght@' . $font_params[1] . '&';
    }
    elseif ($font_params[1] === 'italic') {
      $fonts_url .= 'family=' . $font_params[0] . ':ital@0;1&';
    }
    else {
      preg_match_all('/\d+/', $font_params[1], $matches);
      $font_weight = $matches[0][0];
      $fonts_url .= 'family=' . $font_params[0] . ':ital,wght@1,' . $font_weight . '&';
    }
  }

  // Store font-face css file.
  try {
    $response = $http_client->request('GET', '//fonts.googleapis.com/css2?' . $fonts_url . 'display=swap', [
      'headers' => [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/89.0.4389.82 Safari/537.36',
      ],
    ]);
    $font_face = $response->getBody()->getContents();
  }
  catch (\Exception $exception) {
    \Drupal::logger('default')->error($exception->getMessage());
    return;
  }

  // Store font-face source.
  preg_match_all('/src[\s]*:[\s]*url\([\'"]*(.+?)[\'"]*\)[\s]*format\([\'"]*(.+?)[\'"]*\)/i', $font_face, $font_source_matches);
  foreach ($font_source_matches[1] as $font_source) {
    try {
      $response = $http_client->get($font_source);
      $google_font_source = $response->getBody()->getContents();
    }
    catch (\Exception $exception) {
      \Drupal::logger('default')->error($exception->getMessage());
      continue;
    }

    preg_match('/[A-Za-z0-9_\-\.]+\.[A-Za-z0-9]+$/i', $font_source, $font_name);
    $file_name = $fonts_folder_uri . '/' . $font_name[0];
    $file_repository->writeData($google_font_source, $file_name, FileSystemInterface::EXISTS_REPLACE);

    // Update font-face to use local links.
    if ($wrapper = \Drupal::service('stream_wrapper_manager')->getViaUri($file_name)) {
      $relative_file_source = \Drupal::service('file_url_generator')
        ->transformRelative($wrapper->getExternalUrl());
      $font_face = str_replace($font_source, $relative_file_source, $font_face);
    }
  }

  $file_repository->writeData($font_face, $fonts_folder_uri . '/' . 'font-face.css', FileSystemInterface::EXISTS_REPLACE);
  $state->set('dxpr_theme_fonts_update', strtotime("+1 week"));
  $state->set('dxpr_theme_stored_fonts', implode(',', $google_fonts));
}

/**
 * Delete existing "cached" Google fonts for before re-download.
 */
function _dxpr_theme_remove_google_fonts() {
  $active_theme = \Drupal::service('theme.manager')->getActiveTheme();
  $fonts_folder = 'public://' . $active_theme->getName() . '/fonts';

  $iterator = new RecursiveDirectoryIterator($fonts_folder, RecursiveDirectoryIterator::SKIP_DOTS);
  foreach ($iterator as $file) {
    \Drupal::service('file_system')->deleteRecursive($fonts_folder . '/' . $file->getFilename());
  }
}

/**
 * Helper function to add local fonts to the page.
 *
 * Add query string to stylesheet to prevent identically named files
 * to overwrite each other.
 *
 * @param string $font
 *   Font family name.
 * @param array $variables
 *   Variables array.
 */
function _dxpr_theme_add_local_font($font, array &$variables) {
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
        'preload' => 'preload',
      ],
    ];
    $variables['#attached']['html_head'][] = [$element, $font[2]];
    $added_stylesheets[$path] = $path;
  }

  _dxpr_theme_add_local_font_preload_link($path, $font[2], $variables);
}

/**
 * Helper function to add preload links for local fonts to the page header.
 *
 * @param string $path
 *   Path to the fonts stylesheet file.
 * @param string $font_family
 *   Font family name.
 * @param array $variables
 *   Variables array.
 */
function _dxpr_theme_add_local_font_preload_link($path, $font_family, array &$variables) {
  $font_families = _dxpr_theme_add_local_font_parse_font_families($path);
  if (!empty($font_families[$font_family])) {
    $element = [
      '#type' => 'html_tag',
      '#tag' => 'link',
      '#attributes' => [
        'rel' => 'preload',
        'href' => '/' . dirname($path) . '/' . $font_families[$font_family]['url'],
        'as' => 'font',
        'type' => 'font/' . $font_families[$font_family]['format'],
      ],
    ];
    $variables['#attached']['html_head'][] = [
      $element,
      $font_family . '_preload',
    ];
  }
}

/**
 * Parse stylesheet file to fetch font files url and format.
 *
 * @param string $path
 *   Path to the fonts stylesheet file.
 *
 * @return array
 *   Array of the parsed data for the provided stylesheet file.
 */
function _dxpr_theme_add_local_font_parse_font_families($path) {
  $font_families = &drupal_static(__FUNCTION__);
  $font_families = $font_families ?? [];
  if (!isset($font_families[$path])) {
    $font_families[$path] = [];
    if ($wrapper = \Drupal::service('stream_wrapper_manager')->getViaUri($path)) {
      $local_path = $wrapper->realpath();
      $content = file_get_contents($local_path);
      preg_match_all('/@font-face\s*{([\s\S]*?)}/i', $content, $font_faces);
      foreach ($font_faces[1] as $font_face) {
        if (preg_match('/font-family: [\'"](.*?)[\'"]/i', $font_face, $font_family)) {
          $font_families[$path][$font_family[1]] = [];
          if (preg_match('/src[\s]*:[\s]*url\([\'"]*(.+?)[\'"]*\)[\s]*format\([\'"]*(.+?)[\'"]*\)/i', $font_face, $matches)) {
            $font_families[$path][$font_family[1]] = [
              'url' => $matches[1],
              'format' => $matches[2],
            ];
          }
        }
      }
    }
  }
  return $font_families[$path];
}
