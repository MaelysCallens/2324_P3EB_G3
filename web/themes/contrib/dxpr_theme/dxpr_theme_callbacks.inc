<?php

/**
 * @file
 * DXPR Theme helper functions.
 */

use Drupal\Core\Asset\CssOptimizer;
use Drupal\Core\File\FileSystemInterface;

/**
 * Helper function returns path of css cache file.
 *
 * @param string $theme
 *   Theme machine name.
 *
 * @return string
 *   A valid path of css cache file.
 */
function _dxpr_theme_css_cache_file($theme) {
  return 'public://dxpr_theme/css/themesettings-' . $theme . '.css';
}

/**
 * Write css files for the color settings.
 *
 * @param string $theme
 *   Theme machine name.
 */
function dxpr_theme_color_module_css_write($theme) {
  // Stuff copied from color module, because this code is not encapsulated
  // in a function other than theme submit.
  \Drupal::moduleHandler()->loadInclude('module', 'color');
  $info = color_get_info($theme);
  $palette = color_get_palette($theme);
  $config = \Drupal::configFactory()->getEditable('color.theme.' . $theme);

  $file_system = \Drupal::service('file_system');
  // Delete old files.
  $files = $config->get('files');
  if (isset($files)) {
    foreach ($files as $file) {
      @$file_system->unlink($file);
    }
  }
  if (isset($file) && $file = dirname($file)) {
    @\Drupal::service('file_system')->rmdir($file);
  }

  // No change in color config, use the standard theme from color.inc.
  if (implode(',', color_get_palette($theme, TRUE)) == implode(',', $palette)) {
    $config->delete();
    return;
  }

  // Prepare target locations for generated files.
  $id = $theme . '-' . substr(hash('sha256', serialize($palette) . microtime()), 0, 8);
  $paths['color'] = 'public://color';
  $paths['target'] = $paths['color'] . '/' . $id;
  /** @var \Drupal\Core\File\FileSystemInterface $file_system */
  $file_system = \Drupal::service('file_system');
  foreach ($paths as $path) {
    $file_system->prepareDirectory($path, FileSystemInterface::CREATE_DIRECTORY);
  }
  $paths['target'] = $paths['target'] . '/';
  $paths['id'] = $id;
  $paths['source'] = \Drupal::service('extension.list.theme')->getPath($theme) . '/';
  $paths['files'] = $paths['map'] = [];

  // Save palette and logo location.
  $config
    ->set('palette', $palette)
    ->set('logo', $paths['target'] . 'logo.svg')
    ->save();

  // Copy over neutral images.
  /** @var \Drupal\Core\File\FileSystemInterface $file_system */
  $file_system = \Drupal::service('file_system');
  foreach ($info['copy'] as $file) {
    $base = $file_system->basename($file);
    $source = $paths['source'] . $file;
    try {
      $filepath = $file_system->copy($source, $paths['target'] . $base);
    }
    catch (FileException $e) {
      $filepath = FALSE;
    }
    $paths['map'][$file] = $base;
    $paths['files'][] = $filepath;
  }

  // Render new images, if image has been provided.
  if (isset($info['base_image'])) {
    _color_render_images($theme, $info, $paths, $palette);
  }

  // Rewrite theme stylesheets.
  $css = [];
  foreach ($info['css'] as $stylesheet) {
    // Build a temporary array with CSS files.
    $files = [];
    if (file_exists($paths['source'] . $stylesheet)) {
      $files[] = $stylesheet;
    }

    foreach ($files as $file) {
      $css_optimizer = new CssOptimizer(\Drupal::service('file_url_generator'));
      // Aggregate @imports recursively for each configured top level CSS file
      // without optimization. Aggregation and optimization will be
      // handled by drupal_build_css_cache() only.
      $style = $css_optimizer->loadFile($paths['source'] . $file, FALSE);

      // Return the path to where this CSS file originated from, stripping
      // off the name of the file at the end of the path.
      $css_optimizer->rewriteFileURIBasePath = base_path() . dirname($paths['source'] . $file) . '/';

      // Prefix all paths within this CSS file, ignoring absolute paths.
      $style = preg_replace_callback('/url\([\'"]?(?![a-z]+:|\/+)([^\'")]+)[\'"]?\)/i',
      [$css_optimizer, 'rewriteFileURI'], $style);
      // Rewrite stylesheet with new colors.
      $style = _color_rewrite_stylesheet($theme, $info, $paths, $palette, $style);
      $base_file = $file_system->basename($file);
      $css[] = $paths['target'] . $base_file;
      _color_save_stylesheet($paths['target'] . $base_file, $style, $paths);
    }
  }

  // Maintain list of files.
  $config
    ->set('stylesheets', $css)
    ->set('files', $paths['files'])
    ->save();
}

/**
 * Theme CSS generator from DXPR Theme-settings.php.
 *
 * Custom theme settings might need a lot of CSS.
 * So we put it in a file for optimal performance.
 *
 * @param string $theme
 *   Theme name.
 */
function dxpr_theme_css_cache_build($theme) {
  $dxpr_theme_css_file = _dxpr_theme_css_cache_file($theme);
  $dir = 'public://dxpr_theme/css/';
  $dir_writable = \Drupal::service('file_system')
    ->prepareDirectory($dir, FileSystemInterface::CREATE_DIRECTORY);

  if (\Drupal::moduleHandler()->moduleExists('color')) {
    $palette = color_get_palette($theme);
  }
  else {
    $palette = [];
  }
  // Construct CSS file:
  $css = '';
  // Load Sooper Features CSS.
  foreach (\Drupal::service('file_system')->scanDirectory(\Drupal::service('extension.list.theme')->getPath('dxpr_theme') . '/features', '/css.inc/i') as $file) {
    require_once $file->uri;
    $function_name = basename($file->filename, '.inc');
    $function_name = str_replace('-', '_', $function_name);
    if (function_exists($function_name)) {
      $function_name($theme, $css, $palette);
    }
  }
  $file_object = \Drupal::service('file.repository')->writeData($css, $dxpr_theme_css_file, FileSystemInterface::EXISTS_REPLACE);
  if ($dir_writable && $file_object) {
    $message = t('DXPR Theme CSS file cache built for %theme', ['%theme' => $theme]);
    \Drupal::messenger()->addMessage($message);
    \Drupal::logger('dxpr_theme')->notice($message);
  }
  else {
    $message = t('Cannot write theme-settings file, please check your file system. (See status report page)');
    \Drupal::messenger()->addMessage($message, 'error');
    \Drupal::logger('dxpr_theme')->error($message);
  }

  // If the CSS & JS aggregation are enabled we need to clear the caches.
  drupal_flush_all_caches();
  \Drupal::service('asset.css.collection_optimizer')->deleteAll();
  \Drupal::service('asset.js.collection_optimizer')->deleteAll();
}
