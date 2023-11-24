<?php

/**
 * @file
 * Theme page title settings.
 */

/**
 * Add page title options to the theme settings form.
 *
 * @param array $form
 *   Form array.
 * @param string $theme
 *   Theme machine name.
 */
function page_title_theme_settings(array &$form, $theme) {
  $form['dxpr_theme_settings']['page_title'] = [
    '#title' => t('Page Title'),
    '#type' => 'details',
    '#group' => 'dxpr_theme_settings',
    '#weight' => 10,
  ];

  $form['dxpr_theme_settings']['page_title']['page_title_breadcrumbs'] = [
    '#type' => 'checkbox',
    '#title' => t('Show Breadcrumbs'),
    '#default_value' => ((theme_get_setting('page_title_breadcrumbs') !== NULL)) ? theme_get_setting('page_title_breadcrumbs') : 1,
  ];

  $form['dxpr_theme_settings']['page_title']['page_title_breadcrumbs_align'] = [
    '#type' => 'radios',
    '#title' => t('Align Breadcrumbs'),
    '#default_value' => ((theme_get_setting('page_title_breadcrumbs_align') !== NULL)) ? theme_get_setting('page_title_breadcrumbs_align') : 'right',
    '#options' => [
      'left' => t('Left'),
      'center' => t('Center'),
      'right' => t('Right'),
    ],
    '#states' => [
      'visible' => [
        ':input[name="page_title_breadcrumbs"]' => ['checked' => TRUE],
      ],
    ],
  ];

  $form['dxpr_theme_settings']['page_title']['page_title_breadcrumbs_separator'] = [
    '#type' => 'textfield',
    '#title' => t('Breadcrumbs separator'),
    '#default_value' => (theme_get_setting('page_title_breadcrumbs_separator')) ? theme_get_setting('page_title_breadcrumbs_separator') : '/',
    '#description' => t('Add custom breadcrumbs separator symbol. Leave empty for default separator.'),
    '#states' => [
      'visible' => [
        ':input[name="page_title_breadcrumbs"]' => ['checked' => TRUE],
      ],
    ],
  ];

  $form['dxpr_theme_settings']['page_title']['page_title_home_hide'] = [
    '#type' => 'checkbox',
    '#title' => t('Hide on Homepage'),
    '#default_value' => ((theme_get_setting('page_title_home_hide') !== NULL)) ? theme_get_setting('page_title_home_hide') : 1,
  ];

  $form['dxpr_theme_settings']['page_title']['page_title_align'] = [
    '#type' => 'radios',
    '#title' => t('Align Title'),
    '#default_value' => ((theme_get_setting('page_title_align') !== NULL)) ? theme_get_setting('page_title_align') : 'left',
    '#options' => [
      'left' => t('Left'),
      'center' => t('Center'),
      'right' => t('Right'),
    ],
    '#description' => t('Choose position of Page Title within page title header.'),
  ];

  $form['dxpr_theme_settings']['page_title']['title_type'] = [
    '#type' => 'checkboxes',
    '#title' => t('Font Style'),
    '#default_value' => ((theme_get_setting('title_type') !== NULL)) ? theme_get_setting('title_type') : [],
    '#options' => [
      'uppercase' => t('Uppercase'),
      'bold' => t('Bold'),
      'italic' => t('Italic'),
    ],
  ];

  $form['dxpr_theme_settings']['page_title']['page_title_height'] = [
    '#type' => 'textfield',
    '#title' => t('Height'),
    '#default_value' => ((theme_get_setting('page_title_height') !== NULL)) ? theme_get_setting('page_title_height') : '120',
    '#size' => 9,
    '#maxlength' => 9,
    '#description' => t('height of the header. 50px - 500px. Default is 120px.'),
  ];

  $form['dxpr_theme_settings']['page_title']['page_title_animate'] = [
    '#type' => 'radios',
    '#title' => t('Animation'),
    '#default_value' => ((theme_get_setting('page_title_animate') !== NULL)) ? theme_get_setting('page_title_animate') : '0',
    '#options' => [
      '0' => t('No Animation'),
      'bounce' => t('Bounce'),
      'flash' => t('Flash'),
      'pulse' => t('Pulse'),
      'fadeIn' => t('Fade in'),
      'bounceInDown' => t('Bounce in down'),
      'bounceInUp' => t('Bounce in up'),
      'fadeInDown' => t('Fade in down'),
      'fadeInUp' => t('Fade in up'),
    ],
    '#prefix' => '<br class="clear-both"><br class="clear-both">',
    '#description' => t('Choose <a target="_blank" href="@animatecss">animate.css</a> appear animation', ['@drupal-handbook' => 'https://daneden.github.io/animate.css/']),
  ];

  $form['dxpr_theme_settings']['page_title']['background'] = [
    '#title' => t('Background Image'),
    '#type' => 'details',
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  ];

  // @see dxpr_theme_helper.module dxpr_theme_helper_settings_form_submit()
  $form['dxpr_theme_settings']['page_title']['background']['page_title_image_path'] = [
    '#type' => 'textfield',
    '#title' => t('Path to background image'),
    '#default_value' => theme_get_setting('page_title_image_path'),
    '#description' => t('The path to the file you would like to use as your page title background image. If you upload a file below it will automatically populate this text field'),
  ];
  $form['dxpr_theme_settings']['page_title']['background']['page_title_image'] = [
    '#type' => 'file',
    '#title' => t('Upload Background'),
    '#description' => t('The default page title image can also be changed per node, using the Header image field in the node form.'),
  ];

  $form['dxpr_theme_settings']['page_title']['background']['page_title_image_opacity'] = [
    '#type' => 'textfield',
    '#title' => t('Backound Image Opacity'),
    '#default_value' => ((theme_get_setting('page_title_image_opacity') !== NULL)) ? theme_get_setting('page_title_image_opacity') : 1,
    '#size' => 9,
    '#maxlength' => 9,
    '#description' => t('Use this to blend the background image with a color. 0 is fully transparent and 1 is fully opaque.'),
  ];

  $form['dxpr_theme_settings']['page_title']['background']['page_title_image_style'] = [
    '#type' => 'radios',
    '#title' => t('Background Style'),
    '#default_value' => ((theme_get_setting('page_title_image_style') !== NULL)) ? theme_get_setting('page_title_image_style') : 'cover',
    '#options' => [
      'cover' => t('Cover'),
      'contain' => t('Contain'),
      'no_repeat' => t('No Repeat'),
      'repeat' => t('Repeat'),
    ],
  ];

  $form['dxpr_theme_settings']['page_title']['background']['page_title_image_mode'] = [
    '#type' => 'radios',
    '#title' => t('Background Mode'),
    '#default_value' => ((theme_get_setting('page_title_image_mode') !== NULL)) ? theme_get_setting('page_title_image_mode') : 'normal',
    '#options' => [
      'normal' => t('Normal'),
      'fixed' => t('Fixed (Parallax)'),
    ],
  ];

  $form['dxpr_theme_settings']['page_title']['background']['page_title_image_position'] = [
    '#type' => 'radios',
    '#title' => t('Background Position'),
    '#default_value' => ((theme_get_setting('page_title_image_position') !== NULL)) ? theme_get_setting('page_title_image_position') : 'center_center',
    '#options' => [
      'center_center' => t('Center Center'),
      'left_top' => t('Left Top'),
      'left_center' => t('Left Center'),
      'left_bottom' => t('Left Bottom'),
      'right_top' => t('Right Top'),
      'right_center' => t('Right Center'),
      'right_bottom' => t('Right Bottom'),
      'center_bottom' => t('Center Bottom'),
    ],
  ];
}
