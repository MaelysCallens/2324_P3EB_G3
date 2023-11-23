<?php

use Drupal\Core\Template\Attribute;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Xss;

/**
 * @file
 * Theme and preprocess functions for forms.
 */

/**
 * Implements hook_theme_suggestions_HOOK_alter().
 */
function belgrade_theme_suggestions_form_alter(array &$suggestions, array &$variables) {
  $element = $variables['element'];

  // Add a suggestion based on the form id name.
  if (isset($element['#form_id'])) {
    $suggestions[] = $variables['theme_hook_original'] . '__' . $element['#form_id'];
  }
}

/**
 * Implements hook_theme_suggestions_HOOK_alter().
 */
function belgrade_theme_suggestions_form_element_alter(array &$suggestions, array &$variables) {
  $element = $variables['element'];

  // Add a suggestion based on the element type.
  if (isset($element['#type'])) {
    $suggestions[] = $variables['theme_hook_original'] . '__' . $element['#type'];
  }
}

/**
 * Implements hook_preprocess_input().
 */
function belgrade_preprocess_input(&$variables) {
  $type = $variables['attributes']['type'];
  $form_control = [
    'text',
    'email',
    'tel',
    'file',
    'number',
    'search',
    'password',
    'url',
    'month',
    'week',
  ];

  if (in_array($type, $form_control)) {
    $variables['attributes']['class'][] = 'form-control';
  }

  if (in_array($type, ['checkbox', 'radio'])) {
    $variables['attributes']['class'][] = 'form-check-input';
  }

  if ($type == 'submit') {

    $variables['attributes'] = new Attribute($variables['attributes']);

    if (theme_get_setting('belgrade_submit_button')) {
      $variables['submit_button'] = TRUE;
      $variables['safe_value_label'] = Xss::filter($variables['attributes']['value']);
    }

    // Get value.
    $string = $variables['attributes']->offsetGet('value')->value();

    $text_data = [
      // Text that match these specific strings are checked first.
      'matches' => [
        // Primary class.
        t('Add to cart')->render() => 'primary',
        t('Download feature')->render() => 'primary',
        t('Save and add')->render() => 'primary',
        t('Add another item')->render() => 'primary',
        t('Update style')->render() => 'primary',

        // Success class.
        t('Add effect')->render() => 'success',
        t('Add and configure')->render() => 'success',
        t('Save configuration')->render() => 'success',
        t('Install and set as default')->render() => 'success',
        t('Add the entire list to cart')->render() => 'success',

        // Secondary class.
        t('Edit')->render() => 'secondary',
      ],

      // Text containing these words anywhere in the string are checked last.
      'contains' => [
        // Primary class.
        t('Confirm')->render() => 'primary',
        t('Filter')->render() => 'primary',
        t('Log in')->render() => 'primary',
        t('Submit')->render() => 'primary',
        t('Search')->render() => 'primary',
        t('Settings')->render() => 'primary',
        t('Upload')->render() => 'primary',
        t('Apply')->render() => 'primary',
        t('Checkout')->render() => 'primary',

        // Secondary

        // Danger class.
        t('Delete')->render() => 'danger',
        t('Remove')->render() => 'danger',
        t('Reset')->render() => 'danger',
        t('Uninstall')->render() => 'danger',

        // Success class.
        t('Create')->render() => 'success',
        t('Install')->render() => 'success',
        t('Save')->render() => 'success',
        t('Send')->render() => 'success',
        t('Write')->render() => 'success',
        t('Continue')->render() => 'success',
        t('Pay')->render() => 'success',

        // Warning class.
        t('Export')->render() => 'warning',
        t('Import')->render() => 'warning',
        t('Restore')->render() => 'warning',
        t('Rebuild')->render() => 'warning',

        // Outline Primary class.
        t('Update')->render() => 'outline-primary',

        // Outline Success class.
        t('Recalculate')->render() => 'outline-success',
        t('Preview')->render() => 'outline-success',
      ],
    ];

    // Allow sub themes to alter the array.
    \Drupal::service('theme.manager')->alter('belgrade_submit_button_class', $text_data);

    $icon_data = [
      // Text that match these specific strings are checked first.
      'matches' => [
        t('Add to cart')->render() => 'basket',
      ],

      // Text containing these words anywhere in the string are checked last.
      'contains' => [
        t('Add')->render() => 'add',
        t('Pay')->render() => 'card',
        t('Wishlist')->render() => 'heart',
      ],
    ];

    // List of classes to add white icons.
    $classes = [
      "btn-primary",
      "btn-secondary",
      "btn-success",
      "btn-info",
      "btn-warning",
      "btn-danger",
      "btn-dark",
    ];

    // Check if classes are already there
    $add_white_icon = TRUE;

    foreach ($classes as $class) {
      if ($variables['attributes']->hasClass($class)) {
        $add_white_icon = FALSE;
      }
    }

    //  Iterate over the array.
    foreach ($text_data as $pattern => $strings) {
      foreach ($strings as $text => $class) {
        switch ($pattern) {
          case 'matches':
            if ($string === $text) {
              if (!$variables['attributes']->hasClass('btn-' . $class)) {
                $variables['attributes']->addClass(['btn', 'btn-' . $class]);
              }
            }
            break;

          case 'contains':
            if (strpos(mb_strtolower($string), mb_strtolower($text)) !== FALSE) {
              if (!$variables['attributes']->hasClass('btn-' . $class)) {
                $variables['attributes']->addClass(['btn', 'btn-' . $class]);
              }
            }
            break;
        }
      }
    }

    if ($add_white_icon) {
      foreach ($icon_data as $pattern => $strings) {
        foreach ($strings as $text => $class) {
          switch ($pattern) {
            case 'matches':
              if ($string === $text) {
                $variables['attributes']->addClass('bi-btn-' . $class);
              }
              break;

            case 'contains':
              if (strpos(mb_strtolower($string), mb_strtolower($text)) !== FALSE) {
                $variables['attributes']->addClass(['bi-btn-' . $class]);
              }
              break;
          }
        }
      }
    }
  }
}

/**
 * Implements hook_preprocess_form_element().
 */
function belgrade_preprocess_form_element(&$variables) {
  $element = $variables['element'];

  // Add required class for checkbox and radio labels.
  if (in_array($element['#type'], ['checkbox', 'radio'])) {
    $variables['label']['#attributes']['class'][] = 'form-check-label';
  }
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function belgrade_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  switch ($form_id) {
    case 'search_block_form':
      $form['keys']['#title'] = '';
      $form['keys']['#size'] = 20;
      $form['keys']['#placeholder'] = t('Search');
      break;
    case 'views_exposed_form':
      $form['#attributes']['class'][] = 'form-inline';
      $form['search_api_fulltext']['#placeholder'] = t('Search products');
      $form['actions']['#attributes']['class'][] = 'ms-auto';
      break;
  }
}

/**
 * Implements hook_theme_suggestions_container_alter().
 */
function belgrade_theme_suggestions_container_alter(array &$suggestions, array $variables) {

  $suggestions = [];

  $element = $variables['element'];

  if (isset($element['#type']) && $element['#type'] != 'container') {
    $suggestions[] = 'container__' . $element['#type'];
  }

  if (isset($element['#id'])) {
    $suggestions[] =
      'container__' . str_replace('-', '_', $element['#id']);
  }

  if (isset($element['keys']['#theme'])) {
    $suggestions[] =
      'container__' . str_replace('-', '_', $element['keys']['#theme']);
  }

  if (isset($element['#type']) && $element['#type'] == 'container' && isset($element['children']['#type'])) {
    $suggestions[] = 'container__' . $element['children']['#type'];
  }

  return $suggestions;
}
/**
 * Implements hook_theme_suggestions_fieldset().
 */
function belgrade_theme_suggestions_fieldset_alter(array &$suggestions, &$variables, $hook) {
  $current_path = \Drupal::service('path.current')->getPath();
  $patterns = "/checkout/*\n/user\n/user/login\n/user/password";
  $match = \Drupal::service('path.matcher')->matchPath($current_path, $patterns);

  if (isset($variables['element']['#theme']) && in_array($variables['element']['#theme'], array('bef_radios', 'bef_checkboxes'))) {
    $suggestions[] = 'fieldset__accordion';
  }

  if (theme_get_setting('fieldset_accordion') && $match) {
    $suggestions[] = 'fieldset__accordion';
  }
}
