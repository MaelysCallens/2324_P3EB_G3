<?php

/**
 * @file
 * Theme custom css settings.
 */

/**
 * Add custom css options to the theme settings form.
 *
 * @param array $form
 *   Form array.
 * @param string $theme
 *   Theme machine name.
 */
function custom_css_theme_settings(array &$form, $theme) {
  $form['dxpr_theme_settings']['custom_css'] = [
    '#title' => t('Custom CSS & JS'),
    '#type' => 'details',
    '#group' => 'dxpr_theme_settings',
    '#description' => t("This CSS will be attached after the theme and allows you to customize your site without needing the additional complexity of a subtheme."),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#weight' => 30,
  ];

  $form['dxpr_theme_settings']['custom_css']['custom_css_site'] = [
    '#type' => 'textarea',
    '#title' => t('Sitewide CSS'),
    '#default_value' => theme_get_setting('custom_css_site'),
    '#rows' => 15,
  ];

  $form['dxpr_theme_settings']['custom_css']['custom_javascript_site'] = [
    '#type' => 'textarea',
    '#title' => t('Sitewide Javascript (include script tags)'),
    '#default_value' => theme_get_setting('custom_javascript_site'),
    '#rows' => 15,
  ];

}
