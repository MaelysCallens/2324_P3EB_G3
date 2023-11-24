<?php

/**
 * @file
 * DXPR Theme settings.
 */

use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\node\Entity\NodeType;

/**
 * Implements hook_form_FORM_ID_alter().
 */
function dxpr_theme_form_system_theme_settings_alter(&$form, &$form_state, $form_id = NULL) {
  global $base_path;
  // @code
  // A bug in D7 and D8 causes the theme to load twice.
  // Only the second time $form
  // will contain the color module data. So we ignore the first
  // @see https://www.drupal.org/project/drupal/issues/943212#comment-12102383
  // form_id is only present the second time around.
  if (!isset($form_id)) {
    return;
  };
  if (theme_get_setting('boxed_layout') === 1) {
    if (theme_get_setting('box_max_width') < '1200') {
      \Drupal::messenger()->addStatus('You set a Boxed Container Max Width of less than 1200px. To preserve the layout of the settings form we are overriding this setting specifically for this page. Your setting is applied on other pages.');

      $form['dxpr_theme_boxed_container_styles'] = [
        '#type' => 'html_tag',
        '#tag' => 'style',
        '#value' => '.dxpr-theme-boxed-container { max-width: 1300px !important; }',
      ];
    }
  }
  elseif (theme_get_setting('layout_max_width') < '1200') {
    \Drupal::messenger()->addStatus('You set a Content Max Width of less than 1200px. To preserve the layout of the settings form we are overriding this setting specifically for this page. Your setting is applied on other pages.');

    $form['dxpr_theme_container_styles'] = [
      '#type' => 'html_tag',
      '#tag' => 'style',
      '#value' => '.container { max-width: 1300px !important; }',
    ];
  }

  $build_info = $form_state->getBuildInfo();
  $subject_theme = $build_info['args'][0];
  $dxpr_theme_theme_path = \Drupal::service('extension.list.theme')->getPath('dxpr_theme') . '/';
  $themes = \Drupal::service('theme_handler')->listInfo();

  $img = '<img width="40" height="15" src="' . $base_path . $dxpr_theme_theme_path . 'images/dxpr-logo-dark.svg" />';
  if (!empty($themes[$subject_theme]->info['version'])) {
    $version = $themes[$subject_theme]->info['version'];
  }
  else {
    $version = 'dev';
  }
  $form['dxpr_theme_settings'] = [
    // SETTING TYPE TO DETAILS OR VERTICAL_TABS
    // STOPS RENDERING OF ALL ELEMENTS INSIDE.
    '#type' => 'vertical_tabs',
    '#weight' => -20,
    '#prefix' => '<h2><small>' . $img . ' ' . $themes[$subject_theme]->info['name'] . ' ' . $version . ' <span class="small">(' . $themes['bootstrap5']->info['name'] . ' base theme ' . $themes['bootstrap5']->info['version'] . ')</span>' . '</small></h2>',
  ];
  // $form['color']['#group'] = 'dxpr_theme_settings';
  if (!empty($form['update'])) {
    $form['update']['#group'] = 'global';
  }
  if (!empty($form['color'])) {
    $form['color']['#group'] = 'dxpr_theme_settings';
    $form['color']['#title'] = t('Colors');
  }
  $form['core_theme_settings'] = [
    '#type' => 'vertical_tabs',
    '#weight' => -20,
    '#prefix' => '<h2><small>' . t('Core theme settings') . '</small></h2>',
  ];
  $form['theme_settings']['#group'] = 'core_theme_settings';
  $form['logo']['#group'] = 'core_theme_settings';
  $form['favicon']['#group'] = 'core_theme_settings';
  unset($form['body_details']);
  unset($form['nav_details']);
  unset($form['footer_details']);
  unset($form['subtheme']);
  unset($form['styleguide']);
  unset($form['text_formats']);

  /**
   * DXPR Theme cache builder
   * Cannot run as submit function because  it will set outdated values by
   * using theme_get_setting to retrieve settings from database before the db is
   * updated. Cannot put cache builder in form scope and use $form_state because
   * it also needs to initialize default settings by reading the .info file.
   * By calling the cache builder here it will run twice: once before the
   * settings are saved and once after the redirect with the updated settings.
   * @todo come up with a less 'icky' solution
   */
  require_once \Drupal::service('extension.list.theme')->getPath('dxpr_theme') . '/dxpr_theme_callbacks.inc';

  $dxpr_theme_css_file = _dxpr_theme_css_cache_file($subject_theme);
  if (!file_exists($dxpr_theme_css_file)) {
    dxpr_theme_css_cache_build($subject_theme);
  }

  foreach (\Drupal::service('file_system')->scanDirectory(\Drupal::service('extension.list.theme')->getPath('dxpr_theme') . '/features', '/settings.inc/i') as $file) {
    require_once $file->uri;
    $function_name = basename($file->filename, '.inc');
    $function_name = str_replace('-', '_', $function_name);
    if (function_exists($function_name)) {
      $function_name($form, $subject_theme);
    }
  }
  $form['#attached']['library'][] = 'dxpr_theme/admin.themesettings';

  if ((\Drupal::moduleHandler()->moduleExists('color')) && ($palette = color_get_palette($subject_theme))) {
    // dxpr_themeSetting vs dxpr_theme namespace otherwise
    // if deletes other .dxpr_theme data.
    $form['#attached']['drupalSettings']['dxpr_themeSettings'] = ['palette' => $palette];
  }

  array_unshift($form['#submit'], 'dxpr_theme_form_system_theme_settings_submit');
  array_unshift($form['#validate'], 'dxpr_theme_form_system_theme_settings_validate');

  $form['#submit'][] = 'dxpr_theme_form_system_theme_settings_after_submit';
}

/**
 * Validate callback for theme settings form.
 *
 * @see \Drupal\system\Form\ThemeSettingsForm::validateForm()
 */
function dxpr_theme_form_system_theme_settings_validate(&$form, &$form_state) {
  if (\Drupal::moduleHandler()->moduleExists('file')) {
    // Handle file uploads.
    $validators = ['file_validate_is_image' => []];

    // Check for a new uploaded logo.
    $file = file_save_upload('page_title_image', $validators, FALSE, 0);
    if (isset($file)) {
      // File upload was attempted.
      if ($file) {
        // Put the temporary file in form_values so we can save it on submit.
        $form_state->setValue('page_title_image', $file);
      }
      else {
        // File upload failed.
        $form_state->setErrorByName('page_title_image', t('The logo could not be uploaded.'));
      }
    }

    // Check for a new uploaded background image.
    $file = file_save_upload('background_image', $validators, FALSE, 0);
    if (isset($file)) {
      // File upload was attempted.
      if ($file) {
        // Put the temporary file in form_values so we can save it on submit.
        $form_state->setValue('background_image', $file);
      }
      else {
        // File upload failed.
        $form_state->setErrorByName('background_image', t('The background image could not be uploaded.'));
      }
    }

    // If the user provided a path for a logo or background image file,
    // make sure a file exists at that path.
    if ($form_state->getValue('page_title_image_path')) {
      $path = _dxpr_theme_validate_path($form_state->getValue('page_title_image_path'));
      if (!$path) {
        $form_state->setErrorByName('page_title_image_path', t('The custom logo path is invalid.'));
      }
    }
    if ($form_state->getValue('background_image_path')) {
      $path = _dxpr_theme_validate_path($form_state->getValue('background_image_path'));
      if (!$path) {
        $form_state->setErrorByName('background_image_path', t('The custom background image path is invalid.'));
      }
    }

    // Handle file uploads.
    $validators = ['file_validate_is_image' => []];
    // $validators = [];
    // Check for a new uploaded logo.
    $file = file_save_upload('page_title_image', $validators, FALSE, 0);
    if (isset($file)) {
      // File upload was attempted.
      if ($file) {
        // Put the temporary file in form_values so we can save it on submit.
        $form_state->setValue('page_title_image', $file);
      }
      else {
        // File upload failed.
        $form_state->setErrorByName('page_title_image', t('The logo could not be uploaded.'));
      }
    }
  }
}

/**
 * Submit callback for theme settings form.
 *
 * @see \Drupal\system\Form\ThemeSettingsForm::submitForm()
 */
function dxpr_theme_form_system_theme_settings_submit(&$form, &$form_state) {
  // If the user uploaded a new image, save it to a permanent location.
  /** @var \Drupal\Core\File\FileSystemInterface $file_system */
  $file_system = \Drupal::service('file_system');
  $directory = 'public://dxpr_theme/images/';

  // Create dxpr_theme/images directory at the public folder
  // if it doesn't exist.
  try {
    $file_system->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);
  }
  catch (FileException $e) {
    \Drupal::messenger()->addMessage($e->getMessage(), 'error');
    \Drupal::logger('dxpr_theme')->error($e->getMessage());
  }

  $value = $form_state->getValue('page_title_image');
  if (!empty($value)) {
    $form_state->setValue('page_title_image', '');
    try {
      $filename = $file_system->copy($value->getFileUri(), $directory . $value->getFilename());
      $form_state->setValue('page_title_image_path', $filename);
    }
    catch (FileException $e) {
      \Drupal::messenger()->addMessage($e->getMessage(), 'error');
      \Drupal::logger('dxpr_theme')->error($e->getMessage());
    }
  }

  $value = $form_state->getValue('background_image');
  if (!empty($value)) {
    $form_state->setValue('background_image', '');
    try {
      $filename = $file_system->copy($value->getFileUri(), $directory . $value->getFilename());
      $form_state->setValue('background_image_path', $filename);
    }
    catch (FileException $e) {
      \Drupal::messenger()->addMessage($e->getMessage(), 'error');
      \Drupal::logger('dxpr_theme')->error($e->getMessage());
    }
  }

  // If the user entered a path relative to the system files directory for
  // a logo or favicon, store a public:// URI so the theme system can handle it.
  if (!empty($form_state->getValue('page_title_image_path'))) {
    $path = _dxpr_theme_validate_path($form_state->getValue('page_title_image_path'));
    $form_state->setValue('page_title_image_path', $path);
  }
  if (!empty($form_state->getValue('background_image_path'))) {
    $path = _dxpr_theme_validate_path($form_state->getValue('background_image_path'));
    $form_state->setValue('background_image_path', $path);
  }
}

/**
 * Retrieves the Color module information for a particular theme.
 */
function _dxpr_theme_get_color_names($theme = NULL) {
  static $theme_info = [];
  if (!isset($theme)) {
    $theme = \Drupal::config('system.theme');
  }

  if (isset($theme_info[$theme])) {
    return $theme_info[$theme];
  }

  $path = \Drupal::service('extension.list.theme')->getPath($theme);
  $file = DRUPAL_ROOT . '/' . $path . '/color/color.inc';
  if ($path && file_exists($file)) {
    include $file;
    // phpcs:disable
    $theme_info[$theme] = $info['fields']; // @phpstan-ignore-line
    return $info['fields']; // @phpstan-ignore-line
    // phpcs:enable
  }
  else {
    return [];
  }
}

/**
 * Color options for theme settings.
 *
 * @param string $theme
 *   Theme machine name.
 *
 * @return array
 *   Color options.
 */
function _dxpr_theme_color_options($theme) {
  $colors = [
    '' => t('None (Theme Default)'),
    'white' => t('White'),
    'custom' => t('Custom Color'),
  ];
  $theme_colors = _dxpr_theme_get_color_names($theme);
  $colors = array_merge($colors, $theme_colors);
  return $colors;
}

/**
 * Get available node bundles.
 *
 * @return array
 *   Available node bundles.
 */
function _dxpr_theme_node_types_options() {
  $types = [];
  foreach (NodeType::loadMultiple() as $key => $value) {
    $types[$key] = $value->get('name');
  }
  return $types;
}

/**
 * Generate node type preview markup.
 */
function _dxpr_theme_type_preview() {
  $output = <<<EOT
<div class="type-preview">
  <div class="type-container type-title-container">
    <h1>Beautiful Typography</h1>
  </div>

  <div class="type-container">
    <h2>Typewriter delectus cred. Thundercats, sed scenester before they sold out et aesthetic</h2>
    <hr>
    <p class="lead">Lead Text Direct trade gluten-free blog, fanny pack cray labore skateboard before they sold out adipisicing non magna id Helvetica freegan. Disrupt aliqua Brooklyn church-key lo-fi dreamcatcher.</p>


    <h3>Truffaut disrupt sartorial deserunt</h3>

    <p>Cosby sweater plaid shabby chic kitsch pour-over ex. Try-hard fanny pack mumblecore cornhole cray scenester. Assumenda narwhal occupy, Blue Bottle nihil culpa fingerstache. Meggings kogi vinyl meh, food truck banh mi Etsy magna 90's duis typewriter banjo organic leggings Vice.</p>

    <ul>
      <li>Roof party put a bird on it incididunt sed umami craft beer cred.</li>
      <li>Carles literally normcore, Williamsburg Echo Park fingerstache photo booth twee keffiyeh chambray whatever.</li>
      <li>Scenester High Life Banksy, proident master cleanse tousled squid sriracha ad chillwave post-ironic retro.</li>
    </ul>

    <h4>Fingerstache nesciunt lomo nostrud hoodie</h4>

    <blockquote>
      <p>Cosby sweater plaid shabby chic kitsch pour-over ex. Try-hard fanny pack mumblecore cornhole cray scenester. Assumenda narwhal occupy, Blue Bottle nihil culpa fingerstache. Meggings kogi vinyl meh, food truck banh mi Etsy magna 90's duis typewriter banjo organic leggings Vice.</p>
      <footer>Someone famous in <cite title="Source Title">Source Title</cite></footer>
    </blockquote>
  </div>
</div>
EOT;
  return $output;
}

/**
 * Helper function for the system_theme_settings form.
 *
 * Attempts to validate normal system paths, paths relative to the public files
 * directory, or stream wrapper URIs. If the given path is any of the above,
 * returns a valid path or URI that the theme system can display.
 *
 * @param string $path
 *   A path relative to the Drupal root or to the public files directory, or
 *   a stream wrapper URI.
 *
 * @return mixed
 *   A valid path that can be displayed through the theme system, or FALSE if
 *   the path could not be validated.
 *
 * @see \Drupal\system\Form\ThemeSettingsForm::validatePath()
 */
function _dxpr_theme_validate_path($path) {
  // Absolute local file paths are invalid.
  if (\Drupal::service('file_system')->realpath($path) == $path) {
    return FALSE;
  }
  // A path relative to the Drupal root or a fully qualified URI is valid.
  if (is_file($path)) {
    return $path;
  }
  // Prepend 'public://' for relative file paths within public filesystem.
  if (\Drupal::service('stream_wrapper_manager')->getScheme($path) === FALSE) {
    $path = 'public://' . $path;
  }
  if (is_file($path)) {
    return $path;
  }
  return FALSE;
}

/**
 * Submit callback for theme settings form.
 *
 * This is the last handler in the submit queue.
 *
 * @see \Drupal\system\Form\ThemeSettingsForm::submitForm()
 */
function dxpr_theme_form_system_theme_settings_after_submit(&$form, &$form_state) {
  require_once \Drupal::service('extension.list.theme')->getPath('dxpr_theme') . '/dxpr_theme_callbacks.inc';

  $build_info = $form_state->getBuildInfo();
  $subject_theme = $build_info['args'][0];
  // It is needed to clear the theme cache.
  $theme_cache =&drupal_static('theme_get_setting', []);
  $theme_cache = [];
  dxpr_theme_css_cache_build($subject_theme);
}
