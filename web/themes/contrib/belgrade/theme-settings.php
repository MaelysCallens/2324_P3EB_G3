<?php

/**
 * @file
 * theme-settings.php
 *
 * Provides theme settings
 *
 * @see ./includes/settings.inc
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Implementation of hook_form_system_theme_settings_alter()
 *
 * @param $form
 *   Nested array of form elements that comprise the form.
 *
 * @param $form_state
 *   A keyed array containing the current state of the form.
 */


function belgrade_form_system_theme_settings_alter(&$form, FormStateInterface $form_state) {

  // Theme info
  $theme = \Drupal::theme()->getActiveTheme()->getName();

  // Regions
  $region_list = system_region_list($theme, $show = REGIONS_ALL);
  $exclude_regions = array('hidden', 'page_top', 'page_bottom', 'navigation');

  // Vertical tabs
  $form['belgrade'] = array(
    '#type' => 'vertical_tabs',
    '#weight' => -10,
    '#description' => t('Cheatsheet of <a href="@link">Bootstrap components.</a>', ['@link' => Url::fromUri('internal:/' . \Drupal::service('extension.list.theme')->getPath($theme) . '/cheatsheet/index.html')->toString()]),
  );

  // General settings
  $form['settings'] = array(
    '#type' => 'details',
    '#title' => t('General'),
    '#group' => 'belgrade',
  );

  $form['settings']['general'] = array(
    '#type' => 'details',
    '#title' => 'General',
    '#collapsible' => true,
    '#open' => true,
  );

  // Inline SVG logo
  $form['settings']['general']['inline_logo'] = array(
    '#type' => 'checkbox',
    '#title' => t('Inline SVG logo'),
    '#description' => t('Place the logo SVG code in the DOM.'),
    '#default_value' => theme_get_setting('inline_logo')
  );

  // Input submit button.
  $form['settings']['general']['belgrade_submit_button'] = [
    '#type' => 'checkbox',
    '#title' => t('Convert input submit to button element'),
    '#default_value' => theme_get_setting('belgrade_submit_button'),
    '#description' => t('This can cause problems with AJAX.'),
  ];

  // Fieldset accordions
  $form['settings']['general']['fieldset_accordion'] = array(
    '#type' => 'checkbox',
    '#title' => t('Collapsible Fieldsets'),
    '#description' => t('Display Fieldsets as collapsible accordions on checkout and user form pages.'),
    '#default_value' => theme_get_setting('fieldset_accordion')
  );

  // Messages.
  $form['settings']['general']['message_type'] = [
    '#type' => 'select',
    '#title' => t('Messages type'),
    '#default_value' => theme_get_setting('message_type'),
    '#options' => [
      'alerts' => t('Alerts'),
      'toasts' => t('Toasts'),
      'color_toasts' => t('Colored Toasts'),
    ],
  ];

  $form['settings']['general']['toast_placement'] = array(
    '#type' => 'select',
    '#title' => t('Toast placement'),
    '#default_value' => theme_get_setting('toast_placement'),
    '#options' => [
      'top_left' => t('Top left'),
      'top_center' => t('Top center'),
      'top_right' => t('Top right'),
      'middle_left' => t('Middle left'),
      'middle_center' => t('Middle center'),
      'middle_right' => t('Middle right'),
      'bottom_left' => t('Bottom left'),
      'bottom_center' => t('Bottom center'),
      'bottom_right' => t('Bottom right'),
    ],
    '#states' => [
      'invisible',
      'visible' => [
        'select[name="message_type"]' => [
          ['value' => 'toasts'],
          ['value' => 'color_toasts']
        ],
      ],
    ],
  );


  $form['settings']['commerce'] = array(
    '#type' => 'details',
    '#title' => 'Commerce',
    '#collapsible' => true,
    '#open' => true,
  );

  $form['settings']['commerce']['product_teaser'] = [
    '#type' => 'select',
    '#title' => t('Product teaser'),
    '#empty_option' => t('None'),
    '#options' => [
      'card' => t('Card'),
      'belgrade' => t('Belgrade'),
      'zoom' => t('Zoom')
    ],
    '#default_value' => theme_get_setting('product_teaser'),
  ];

  // Layout
  $form['regions'] = array(
    '#type' => 'details',
    '#title' => t('Regions'),
    '#group' => 'belgrade',
    '#description' => t('Additional classes and container settings for each region')
  );

  $form['regions']['main_container'] = [
    '#type' => 'select',
    '#title' => t('Main container size'),
    '#empty_option' => t('None'),
    '#options' => [
      'container' => t('Fixed'),
      'container-sm' => t('Container SM'),
      'container-md' => t('Container MD'),
      'container-lg' => t('Container LG'),
      'container-xl' => t('Container XL'),
      'container-xxl' => t('Container XXL'),
      'container-fluid' => t('Fluid'),
    ],
    '#default_value' => theme_get_setting('main_container'),
  ];

  $form['regions']['main_container_class'] = array(
    '#type' => 'textfield',
    '#title' => t('Main content classes'),
    '#default_value' => theme_get_setting('main_container_class')
  );

  $form['regions']['navigation'] = array(
    '#type' => 'details',
    '#title' => 'Navigation (Offcanvas)',
    '#collapsible' => true,
  );

  $form['regions']['navigation']['navigation_toggle_text'] = array(
    '#type' => 'textfield',
    '#title' => t('Navigation toggle text'),
    '#default_value' => theme_get_setting('navigation_toggle_text')
  );

  $form['regions']['navigation']['navigation_position'] = array(
    '#type' => 'select',
    '#title' => t('Navigation placement'),
    '#options' => [
      'start' => t('Default (Left)'),
      'end' => t('Right'),
      'bottom' => t('Bottom'),
    ],
    '#default_value' => theme_get_setting('navigation_position'),
  );

  $form['regions']['navigation']['navigation_logo'] = array(
    '#type' => 'checkbox',
    '#title' => t('Display Logo'),
    '#description' => t(' show or hide logo in the navigation region.'),
    '#default_value' => theme_get_setting('navigation_logo')
  );


  $form['regions']['navigation']['navigation_body_scrolling'] = array(
    '#type' => 'checkbox',
    '#title' => t('Body Scrolling'),
    '#description' => t('Enables scrolling on the body when navigation is open'),
    '#default_value' => theme_get_setting('navigation_body_scrolling')
  );

  $form['regions']['navigation']['navigation_backdrop'] = array(
    '#type' => 'checkbox',
    '#title' => t('Body Backdrop'),
    '#description' => t('Disables scrolling and creates a backdrop over the body when navigation is open'),
    '#default_value' => theme_get_setting('navigation_backdrop')
  );

  $form['regions']['navigation']['region_class_navigation']= array(
    '#type' => 'textfield',
    '#title' => t('Navigation region classes'),
    '#default_value' => theme_get_setting('region_class_navigation')
  );

  // Regions
  foreach ($region_list as $name => $description) {
    if (!in_array($name, $exclude_regions)){
      if (theme_get_setting('region_class_' . $name) !== null) {
        $region_class = theme_get_setting('region_class_' . $name);
      } else {
        $region_class = '';
      }

      $form['regions'][$name] = array(
        '#type' => 'details',
        '#title' => $description,
        '#collapsible' => true,
        '#open' => false,
      );
      $form['regions'][$name]['region_class_' . $name] = array(
        '#type' => 'textfield',
        '#title' => t('@description classes', array('@description' => $description)),
        '#default_value' => $region_class
      );
      $form['regions'][$name]['region_container_' . $name] = [
        '#type' => 'select',
        '#title' => t('Container type'),
        '#empty_option' => t('None'),
        '#options' => [
          'container' => t('Fixed'),
          'container-sm' => t('Container SM'),
          'container-md' => t('Container MD'),
          'container-lg' => t('Container LG'),
          'container-xl' => t('Container XL'),
          'container-xxl' => t('Container XXL'),
          'container-fluid' => t('Fluid'),
        ],
        '#description' => t('<code>.container</code>, sets a max-width at each responsive breakpoint<br/>
                                   <code>.container-fluid</code>, is width: 100% at all breakpoints<br/>
                                   <code>.container-{breakpoint}</code>, is width: 100% until the specified breakpoint'),
        '#default_value' => theme_get_setting('region_container_' . $name),
        '#group' => 'container',
      ];
    }
  }

  // Fonts
  $form['fonts_and_icons'] = array(
    '#type' => 'details',
    '#title' => t('Fonts & Icons'),
    '#collapsible' => true,
    '#group' => 'belgrade',
  );

  // Icons
  $form['fonts_and_icons']['belgrade_icons'] = array(
    '#type' => 'checkbox',
    '#title' => t('Use icons'),
    '#description' => t('Checking this will add icons to certain buttons and links.'),
    '#default_value' => theme_get_setting('belgrade_icons')
  );

  $form['fonts_and_icons']['font_set'] = array(
    '#type' => 'select',
    '#title' => t('Font libraries'),
    '#default_value' => theme_get_setting('font_set'),
    '#empty_option' => t('None'),
    '#description' => t('A few predefined font libraries delivered from Google.<br/>All fonts are loaded with Regular, Italic and Bold variants.'),
    '#options' => array(
      'ibm_plex_sans' => 'IBM Plex Sans',
      'lato' => 'Lato',
      'montserrat' => 'Montserrat',
      'open_sans' => 'Open Sans',
      'raleway' => 'Raleway',
      'roboto' => 'Roboto'
    ),
  );

  // Layout Builder
  $form['layout_builder'] = array(
    '#type' => 'details',
    '#title' => t('Layout Builder'),
    '#collapsible' => true,
    '#group' => 'belgrade',
  );

  $form['layout_builder']['local_tasks_fixed'] = array(
    '#type' => 'checkbox',
    '#title' => t('Fixed local tasks'),
    '#description' => t('On pages that use layout builder position local tasks fixed to the left.'),
    '#default_value' => theme_get_setting('local_tasks_fixed')
  );

  // Change collapsible fieldsets (now details) to default #open => FALSE.
  $form['theme_settings']['#open'] = false;
  $form['logo']['#open'] = false;
  $form['favicon']['#open'] = false;
}
