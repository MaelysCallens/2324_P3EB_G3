<?php

/**
 * @file
 * Theme header settings.
 */

/**
 * Add header options to the theme settings form.
 *
 * @param array $form
 *   Form array.
 * @param string $theme
 *   Theme machine name.
 */
function header_theme_settings(array &$form, $theme) {
  $form['dxpr_theme_settings']['header'] = [
    '#title' => t('Header & Main Menu'),
    '#type' => 'details',
    '#group' => 'dxpr_theme_settings',
    '#weight' => 10,
  ];

  $form['dxpr_theme_settings']['header']['header_position'] = [
    '#type' => 'checkbox',
    '#title' => t('Side-Header'),
    '#default_value' => ((theme_get_setting('header_position') !== NULL)) ? theme_get_setting('header_position') : 0,
  ];

  $form['dxpr_theme_settings']['header']['header_top'] = [
    '#title' => t('Top Header Options'),
    '#type' => 'details',
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#states' => [
      'visible' => [
        ':input[name="header_position"]' => ['checked' => FALSE],
      ],
    ],
  ];

  $form['dxpr_theme_settings']['header']['header_top']['header_top_layout'] = [
    '#type' => 'radios',
    '#title' => t('Layout'),
    '#description' => t('Different header layouts give a different view at your menu and logo.'),
    '#default_value' => ((theme_get_setting('header_top_layout') !== NULL)) ? theme_get_setting('header_top_layout') : '0',
    '#options' => [
      '0' => t('Normal (Logo left, Menu Right)'),
      'centered' => t('Centered (stacked)'),
      'centered_inline' => t('Centered (inline)'),
      'logo_left_menu_left' => t('Logo Left, Menu Left'),
      'logo_center_menu_left' => t('Logo Center, Menu Left'),
      'logo_center_menu_right' => t('Logo Center, Menu Right'),
      'logo_right_menu_left' => t('Logo Right, Menu Left'),
    ],
  ];

  $form['dxpr_theme_settings']['header']['header_top']['header_style'] = [
    '#type' => 'radios',
    '#title' => t('Header Style'),
    '#description' => t('Create transparent headers and free floating navbars. Overlays the header over the page title and content.'),
    '#default_value' => ((theme_get_setting('header_style') !== NULL)) ? theme_get_setting('header_style') : 'normal',
    '#options' => [
      'normal' => t('Normal'),
      'overlay' => t('Overlay'),
      'navbar_pull_down' => t('Pull-down Navbar'),
    ],
  ];

  $form['dxpr_theme_settings']['header']['header_top']['header_top_bg_opacity'] = [
    '#type' => 'textfield',
    '#title' => t('Header Opacity'),
    '#default_value' => ((theme_get_setting('header_top_bg_opacity') !== NULL)) ? theme_get_setting('header_top_bg_opacity') : 1,
    '#size' => 9,
    '#maxlength' => 9,
    '#description' => t('Creates RGBa translucent background color. 0 is fully transparent and 1 is fully opaque.'),
  ];

  $form['dxpr_theme_settings']['header']['header_top']['header_top_height'] = [
    '#type' => 'textfield',
    '#title' => t('Height'),
    '#default_value' => ((theme_get_setting('header_top_height') !== NULL)) ? theme_get_setting('header_top_height') : '100',
    '#size' => 9,
    '#maxlength' => 9,
    '#description' => t('Initial height of the header. 10px - 200px. Default is 100.'),
  ];

  $form['dxpr_theme_settings']['header']['header_top']['header_top_fixed'] = [
    '#type' => 'checkbox',
    '#title' => t('Fixed Position'),
    '#default_value' => ((theme_get_setting('header_top_fixed') !== NULL)) ? theme_get_setting('header_top_fixed') : FALSE,
    '#description' => t('A Fixed header stays in the top of the browser when a user scrolls.'),
  ];

  $form['dxpr_theme_settings']['header']['header_top']['header_top_sticky'] = [
    '#type' => 'checkbox',
    '#title' => t('Sticky Header'),
    '#default_value' => ((theme_get_setting('header_top_sticky') !== NULL)) ? theme_get_setting('header_top_sticky') : FALSE,
    '#description' => t('A sticky can first be positioned at a distance from the top edge of the window and when the user scrolls past a certain point it will stick to the top of the window.'),
    '#states' => [
      'visible' => [
        ':input[name="header_top_fixed"]' => ['checked' => TRUE],
      ],
    ],
  ];

  $form['dxpr_theme_settings']['header']['header_top']['second_header_sticky'] = [
    '#type' => 'checkbox',
    '#title' => t('Sticky Second Header'),
    '#default_value' => ((theme_get_setting('second_header_sticky') !== NULL)) ? theme_get_setting('second_header_sticky') : FALSE,
    '#description' => t('A sticky can first be positioned at a distance from the top edge of the window and when the user scrolls past a certain point it will stick to the top of the window.'),
    '#states' => [
      'visible' => [
        ':input[name="header_style"]' => ['value' => 'overlay'],
        ':input[name="header_top_sticky"]' => ['checked' => TRUE],
      ],
    ],
  ];

  $form['dxpr_theme_settings']['header']['header_top']['sticky'] = [
    '#title' => t('Sticky Header Options'),
    '#type' => 'details',
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#states' => [
      'visible' => [
        ':input[name="header_top_fixed"]' => ['checked' => TRUE],
        ':input[name="header_top_sticky"]' => ['checked' => TRUE],
      ],
    ],
  ];

  $form['dxpr_theme_settings']['header']['header_top']['sticky']['header_top_sticky_show'] = [
    '#type' => 'radios',
    '#title' => t('Hide before Scrolling'),
    '#default_value' => ((theme_get_setting('header_top_sticky_show') !== NULL)) ? theme_get_setting('header_top_sticky_show') : 'always',
    '#options' => [
      'always' => t('Always Show'),
      'after_scroll' => t('Hide initially. Show after scroll offset'),
    ],
    '#description' => t('Hide menu before or after scrolling. Turn on "Overlay Mode" to prevent your menu creating an empty space at the top of your layout.'),
    '#states' => [
      'visible' => [
        ':input[name="header_top_fixed"]' => ['checked' => TRUE],
        ':input[name="header_top_sticky"]' => ['checked' => TRUE],
      ],
    ],
  ];

  $form['dxpr_theme_settings']['header']['header_top']['sticky']['header_top_height_sticky_offset'] = [
    '#type' => 'textfield',
    '#title' => t('Scroll offset'),
    '#default_value' => ((theme_get_setting('header_top_height_sticky_offset') !== NULL)) ? theme_get_setting('header_top_height_sticky_offset') : '60',
    '#size' => 9,
    '#maxlength' => 9,
    '#description' => t('Scroll distance before header jumps to its fixed position at the top of page. 0 - 2096px. Default is 60.'),
    '#states' => [
      'visible' => [
        ':input[name="header_top_fixed"]' => ['checked' => TRUE],
        ':input[name="header_top_sticky"]' => ['checked' => TRUE],
      ],
    ],
  ];

  $form['dxpr_theme_settings']['header']['header_top']['sticky']['header_top_height_scroll'] = [
    '#type' => 'textfield',
    '#title' => t('Height'),
    '#default_value' => ((theme_get_setting('header_top_height_scroll') !== NULL)) ? theme_get_setting('header_top_height_scroll') : '50',
    '#size' => 9,
    '#maxlength' => 9,
    '#description' => t('Header height after scrolling past scroll offset. Default is 50.'),
    '#states' => [
      'visible' => [
        ':input[name="header_top_fixed"]' => ['checked' => TRUE],
        ':input[name="header_top_sticky"]' => ['checked' => TRUE],
      ],
    ],
  ];

  $form['dxpr_theme_settings']['header']['header_top']['sticky']['header_top_bg_opacity_scroll'] = [
    '#type' => 'textfield',
    '#title' => t('Background Opacity'),
    '#default_value' => ((theme_get_setting('header_top_bg_opacity_scroll') !== NULL)) ? theme_get_setting('header_top_bg_opacity_scroll') : 1,
    '#size' => 9,
    '#maxlength' => 9,
    '#description' => t('Creates RGBa translucent background color. 0 is fully transparent and 1 is fully opaque.'),
    '#states' => [
      'visible' => [
        ':input[name="header_top_fixed"]' => ['checked' => TRUE],
        ':input[name="header_top_sticky"]' => ['checked' => TRUE],
      ],
    ],
  ];

  $form['dxpr_theme_settings']['header']['header_top']['navcolors'] = [
    '#title' => t('Customize Navigation Colors'),
    '#type' => 'details',
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  ];

  $form['dxpr_theme_settings']['header']['header_top']['navcolors']['navbar_background'] = [
    '#type' => 'select',
    '#title' => t('Navbar Background'),
    '#default_value' => theme_get_setting('navbar_background'),
    '#options' => _dxpr_theme_color_options($theme),
  ];

  $form['dxpr_theme_settings']['header']['header_top']['navcolors']['navbar_background_custom'] = [
    '#type' => 'textfield',
    '#title' => t('Custom Navbar Background'),
    '#default_value' => ((theme_get_setting('navbar_background_custom') !== NULL)) ? theme_get_setting('navbar_background_custom') : theme_get_setting('navbar_background_custom'),
    '#states' => [
      'visible' => [
        ':input[name="navbar_background"]' => ['value' => 'custom'],
      ],
    ],
  ];

  $form['dxpr_theme_settings']['header']['header_top']['navcolors']['header_block_background'] = [
    '#type' => 'select',
    '#title' => t('Block Background'),
    '#default_value' => theme_get_setting('header_block_background'),
    '#options' => _dxpr_theme_color_options($theme),
  ];

  $form['dxpr_theme_settings']['header']['header_top']['navcolors']['header_block_background_custom'] = [
    '#type' => 'textfield',
    '#title' => t('Custom Block Background'),
    '#default_value' => ((theme_get_setting('header_block_background_custom') !== NULL)) ? theme_get_setting('header_block_background_custom') : theme_get_setting('header_block_background_custom'),
    '#states' => [
      'visible' => [
        ':input[name="header_block_background"]' => ['value' => 'custom'],
      ],
    ],
  ];

  $form['dxpr_theme_settings']['header']['header_top']['navcolors']['header_block_text_color'] = [
    '#type' => 'select',
    '#title' => t('Block Text'),
    '#default_value' => theme_get_setting('header_block_text_color'),
    '#options' => _dxpr_theme_color_options($theme),
  ];

  $form['dxpr_theme_settings']['header']['header_top']['navcolors']['header_block_text_color_custom'] = [
    '#type' => 'textfield',
    '#title' => t('Custom Block Text Color'),
    '#default_value' => ((theme_get_setting('header_block_text_color_custom') !== NULL)) ? theme_get_setting('header_block_text_color_custom') : theme_get_setting('header_block_text_color_custom'),
    '#states' => [
      'visible' => [
        ':input[name="header_block_text_color"]' => ['value' => 'custom'],
      ],
    ],
  ];

  $form['dxpr_theme_settings']['header']['header_top']['navcolors']['menu_background'] = [
    '#type' => 'select',
    '#title' => t('Menu Background'),
    '#default_value' => theme_get_setting('menu_background'),
    '#options' => _dxpr_theme_color_options($theme),
  ];

  $form['dxpr_theme_settings']['header']['header_top']['navcolors']['menu_background_custom'] = [
    '#type' => 'textfield',
    '#title' => t('Custom Menu Background'),
    '#default_value' => ((theme_get_setting('menu_background_custom') !== NULL)) ? theme_get_setting('menu_background_custom') : theme_get_setting('menu_background_custom'),
    '#states' => [
      'visible' => [
        ':input[name="menu_background"]' => ['value' => 'custom'],
      ],
    ],
  ];

  $form['dxpr_theme_settings']['header']['header_top']['navcolors']['menu_text_color'] = [
    '#type' => 'select',
    '#title' => t('Menu Text'),
    '#default_value' => theme_get_setting('menu_text_color'),
    '#options' => _dxpr_theme_color_options($theme),
  ];

  $form['dxpr_theme_settings']['header']['header_top']['navcolors']['menu_text_color_custom'] = [
    '#type' => 'textfield',
    '#title' => t('Custom Menu Text Color'),
    '#default_value' => ((theme_get_setting('menu_text_color_custom') !== NULL)) ? theme_get_setting('menu_text_color_custom') : theme_get_setting('menu_text_color_custom'),
    '#states' => [
      'visible' => [
        ':input[name="menu_text_color"]' => ['value' => 'custom'],
      ],
    ],
  ];

  $form['dxpr_theme_settings']['header']['header_top']['navcolors']['menu_hover_background'] = [
    '#type' => 'select',
    '#title' => t('Menu Hover Background'),
    '#default_value' => theme_get_setting('menu_hover_background'),
    '#options' => _dxpr_theme_color_options($theme),
  ];

  $form['dxpr_theme_settings']['header']['header_top']['navcolors']['menu_hover_background_custom'] = [
    '#type' => 'textfield',
    '#title' => t('Custom Menu Hover Background'),
    '#default_value' => ((theme_get_setting('menu_hover_background_custom') !== NULL)) ? theme_get_setting('menu_hover_background_custom') : theme_get_setting('menu_hover_background_custom'),
    '#states' => [
      'visible' => [
        ':input[name="menu_hover_background"]' => ['value' => 'custom'],
      ],
    ],
  ];

  $form['dxpr_theme_settings']['header']['header_top']['navcolors']['menu_hover_text_color'] = [
    '#type' => 'select',
    '#title' => t('Menu Hover Text'),
    '#default_value' => theme_get_setting('menu_hover_text_color'),
    '#options' => _dxpr_theme_color_options($theme),
  ];

  $form['dxpr_theme_settings']['header']['header_top']['navcolors']['menu_hover_text_color_custom'] = [
    '#type' => 'textfield',
    '#title' => t('Custom Menu Hover Text Color'),
    '#default_value' => ((theme_get_setting('menu_hover_text_color_custom') !== NULL)) ? theme_get_setting('menu_hover_text_color_custom') : theme_get_setting('menu_hover_text_color_custom'),
    '#states' => [
      'visible' => [
        ':input[name="menu_hover_text_color"]' => ['value' => 'custom'],
      ],
    ],
  ];

  $form['dxpr_theme_settings']['header']['header_top']['navcolors']['dropdown_background'] = [
    '#type' => 'select',
    '#title' => t('Dropdown Background'),
    '#default_value' => theme_get_setting('dropdown_background'),
    '#options' => _dxpr_theme_color_options($theme),
  ];

  $form['dxpr_theme_settings']['header']['header_top']['navcolors']['dropdown_background_custom'] = [
    '#type' => 'textfield',
    '#title' => t('Custom Dropdown Background'),
    '#default_value' => ((theme_get_setting('dropdown_background_custom') !== NULL)) ? theme_get_setting('dropdown_background_custom') : theme_get_setting('dropdown_background_custom'),
    '#states' => [
      'visible' => [
        ':input[name="dropdown_background"]' => ['value' => 'custom'],
      ],
    ],
  ];

  $form['dxpr_theme_settings']['header']['header_top']['navcolors']['dropdown_text_color'] = [
    '#type' => 'select',
    '#title' => t('Dropdown Text'),
    '#default_value' => theme_get_setting('dropdown_text_color'),
    '#options' => _dxpr_theme_color_options($theme),
  ];

  $form['dxpr_theme_settings']['header']['header_top']['navcolors']['dropdown_text_color_custom'] = [
    '#type' => 'textfield',
    '#title' => t('Custom Dropdown Text Color'),
    '#default_value' => ((theme_get_setting('dropdown_text_color_custom') !== NULL)) ? theme_get_setting('dropdown_text_color_custom') : theme_get_setting('dropdown_text_color_custom'),
    '#states' => [
      'visible' => [
        ':input[name="dropdown_text_color"]' => ['value' => 'custom'],
      ],
    ],
  ];

  $form['dxpr_theme_settings']['header']['header_top']['navcolors']['dropdown_hover_background'] = [
    '#type' => 'select',
    '#title' => t('Dropdown Hover Background'),
    '#default_value' => theme_get_setting('dropdown_hover_background'),
    '#options' => _dxpr_theme_color_options($theme),
  ];

  $form['dxpr_theme_settings']['header']['header_top']['navcolors']['dropdown_hover_background_custom'] = [
    '#type' => 'textfield',
    '#title' => t('Custom Dropdown Hover Background'),
    '#default_value' => ((theme_get_setting('dropdown_hover_background_custom') !== NULL)) ? theme_get_setting('dropdown_hover_background_custom') : theme_get_setting('dropdown_hover_background_custom'),
    '#states' => [
      'visible' => [
        ':input[name="dropdown_hover_background"]' => ['value' => 'custom'],
      ],
    ],
  ];

  $form['dxpr_theme_settings']['header']['header_top']['navcolors']['dropdown_hover_text_color'] = [
    '#type' => 'select',
    '#title' => t('Dropdown Hover Text'),
    '#default_value' => theme_get_setting('dropdown_hover_text_color'),
    '#options' => _dxpr_theme_color_options($theme),
  ];

  $form['dxpr_theme_settings']['header']['header_top']['navcolors']['dropdown_hover_text_color_custom'] = [
    '#type' => 'textfield',
    '#title' => t('Custom Dropdown Hover Text Color'),
    '#default_value' => ((theme_get_setting('dropdown_hover_text_color_custom') !== NULL)) ? theme_get_setting('dropdown_hover_text_color_custom') : theme_get_setting('dropdown_hover_text_color_custom'),
    '#states' => [
      'visible' => [
        ':input[name="dropdown_hover_text_color"]' => ['value' => 'custom'],
      ],
    ],
  ];

  $form['dxpr_theme_settings']['header']['header_side'] = [
    '#title' => t('Side Header & Mobile Menu Options'),
    '#type' => 'details',
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  ];

  $form['dxpr_theme_settings']['header']['header_side']['header_side_align'] = [
    '#type' => 'radios',
    '#title' => t('Align Content'),
    '#description' => t('Different header aligns give a different view at your menu and logo.'),
    '#default_value' => ((theme_get_setting('header_side_align') !== NULL)) ? theme_get_setting('header_side_align') : 'left',
    '#options' => [
      'left' => t('Left'),
      'center' => t('Center'),
      'right' => t('Right'),
    ],
  ];

  $form['dxpr_theme_settings']['header']['header_side']['header_side_width'] = [
    '#type' => 'textfield',
    '#title' => t('Width'),
    '#default_value' => ((theme_get_setting('header_side_width') !== NULL)) ? theme_get_setting('header_side_width') : '300',
    '#size' => 9,
    '#maxlength' => 9,
    '#description' => t('Width of the side-header. Default is 300.'),
  ];

  $form['dxpr_theme_settings']['header']['header_side']['header_side_direction'] = [
    '#type' => 'radios',
    '#title' => t('Mobile menu open direction'),
    '#default_value' => ((theme_get_setting('header_side_direction') !== NULL)) ? theme_get_setting('header_side_direction') : 'left',
    '#description' => t('Open menu from the right'),
    '#options' => [
      'left' => t('Left to Right'),
      'right' => t('Right to Left'),
    ],
  ];

  $form['dxpr_theme_settings']['header']['header_side']['hamburger_menu'] = [
    '#type' => 'radios',
    '#title' => t('Hamburger Menu'),
    '#description' => t('Hamburger menu.'),
    '#default_value' => ((theme_get_setting('hamburger_menu') !== NULL)) ? theme_get_setting('hamburger_menu') : 'three-dash',
    '#options' => [
      'three-dash' => t('Three dash'),
      'two-dash' => t('Two dash'),
    ],
  ];

  $form['dxpr_theme_settings']['header']['header_side']['hamburger_animation'] = [
    '#type' => 'radios',
    '#title' => t('Hamburger Animation'),
    '#description' => t('Hamburger Animation.'),
    '#default_value' => ((theme_get_setting('hamburger_animation') !== NULL)) ? theme_get_setting('hamburger_animation') : 'cross',
    '#options' => [
      'none' => t('None'),
      'cross' => t('Cross'),
    ],
  ];

  $form['dxpr_theme_settings']['header']['menu'] = [
    '#title' => t('Main Menu Links'),
    '#type' => 'details',
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  ];

  $form['dxpr_theme_settings']['header']['menu']['mark_menu_with_children'] = [
    '#type' => 'checkbox',
    '#title' => t('Mark items with children'),
    '#default_value' => ((theme_get_setting('mark_menu_with_children') !== NULL)) ? theme_get_setting('mark_menu_with_children') : FALSE,
  ];

  $form['dxpr_theme_settings']['header']['menu']['dropdown_width'] = [
    '#type' => 'textfield',
    '#title' => t('Dropdown width'),
    '#default_value' => ((theme_get_setting('dropdown_width') !== NULL)) ? theme_get_setting('dropdown_width') : '160',
    '#type' => 'textfield',
    '#size' => 9,
    '#maxlength' => 9,
  ];

  $form['dxpr_theme_settings']['header']['menu']['menu_type'] = [
    '#type' => 'radios',
    '#title' => t('Font Style'),
    '#default_value' => ((theme_get_setting('menu_type') !== NULL)) ? theme_get_setting('menu_type') : '',
    '#options' => [
      '' => t('Normal'),
      'uppercase' => t('Uppercase'),
      'bold' => t('Bold'),
      'lead' => t('Lead text'),
    ],
  ];

  $form['dxpr_theme_settings']['header']['menu']['menu_hover'] = [
    '#type' => 'radios',
    '#title' => t('Hover Style'),
    '#default_value' => ((theme_get_setting('menu_hover') !== NULL)) ? theme_get_setting('menu_hover') : 'opacity',
    '#options' => [
      'opacity' => t('Opacity'),
      'background' => t('Colored Background'),
      'text' => t('Colored text'),
      'border' => t('Border'),
    ],
  ];

  // Would like to use FAPI states here but doesn't work:
  // https://www.drupal.org/node/2751693
  $form['dxpr_theme_settings']['header']['menu']['menu_border'] = [
    '#title' => t('Customize Menu Border'),
    '#type' => 'details',
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  ];

  $form['dxpr_theme_settings']['header']['menu']['menu_border']['menu_border_position'] = [
    '#type' => 'radios',
    '#title' => t('Hover Style'),
    '#default_value' => ((theme_get_setting('menu_border_position') !== NULL)) ? theme_get_setting('menu_border_position') : 'bottom',
    '#options' => [
      'top' => t('Top'),
      'bottom' => t('Bottom'),
    ],
  ];

  $form['dxpr_theme_settings']['header']['menu']['menu_border']['menu_border_animate'] = [
    '#type' => 'radios',
    '#title' => t('Animation'),
    '#default_value' => ((theme_get_setting('menu_border_animate') !== NULL)) ? theme_get_setting('menu_border_animate') : 0,
    '#options' => [
      '' => t('No Animation'),
      'ltr' => t('Left to Right'),
      'rtl' => t('Right to Left'),
      'middle' => t('Middle Out'),
      'drop' => t('Fade in from Top'),
    ],
  ];

  $form['dxpr_theme_settings']['header']['menu']['menu_border']['menu_border_position_offset'] = [
    '#type' => 'textfield',
    '#title' => t('Position Offset'),
    '#default_value' => ((theme_get_setting('menu_border_position_offset') !== NULL)) ? theme_get_setting('menu_border_position_offset') : '0',
    '#size' => 9,
    '#maxlength' => 9,
    '#states' => [
      'visible' => [
        ':input[name="header_position"]' => ['checked' => FALSE],
      ],
    ],
  ];

  $form['dxpr_theme_settings']['header']['menu']['menu_border']['menu_border_position_offset_sticky'] = [
    '#type' => 'textfield',
    '#title' => t('Position Offset for Sticky Header'),
    '#default_value' => ((theme_get_setting('menu_border_position_offset_sticky') !== NULL)) ? theme_get_setting('menu_border_position_offset_sticky') : '0',
    '#size' => 9,
    '#maxlength' => 9,
    '#states' => [
      'visible' => [
        ':input[name="header_position"]' => ['checked' => FALSE],
        ':input[name="header_top_fixed"]' => ['checked' => TRUE],
        ':input[name="header_top_sticky"]' => ['checked' => TRUE],
      ],
    ],
  ];

  $form['dxpr_theme_settings']['header']['menu']['menu_border']['menu_border_size'] = [
    '#type' => 'textfield',
    '#title' => t('Border Thickness'),
    '#default_value' => ((theme_get_setting('menu_border_size') !== NULL)) ? theme_get_setting('menu_border_size') : '2',
    '#size' => 9,
    '#maxlength' => 9,
  ];

  $form['dxpr_theme_settings']['header']['menu']['menu_border']['menu_border_color'] = [
    '#type' => 'select',
    '#title' => t('Border Color'),
    '#default_value' => theme_get_setting('menu_border_color'),
    '#options' => _dxpr_theme_color_options($theme),
  ];

  $form['dxpr_theme_settings']['header']['menu']['menu_border']['menu_border_color_custom'] = [
    '#type' => 'textfield',
    '#title' => t('Custom Value'),
    '#default_value' => ((theme_get_setting('menu_border_color_custom') !== NULL)) ? theme_get_setting('menu_border_color_custom') : theme_get_setting('menu_border_color_custom'),
    '#states' => [
      'visible' => [
        'select[name="menu_border_color"]' => ['value' => 'custom'],
      ],
    ],
  ];

  // Header in mobile mode.
  $form['dxpr_theme_settings']['header']['header_mobile'] = [
    '#title' => t('Mobile Header'),
    '#type' => 'details',
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  ];

  $form['dxpr_theme_settings']['header']['header_mobile']['header_mobile_breakpoint'] = [
    '#type' => 'textfield',
    '#title' => t('Mobile Breakpoint'),
    '#default_value' => ((theme_get_setting('header_mobile_breakpoint') !== NULL)) ? theme_get_setting('header_mobile_breakpoint') : '1200',
    '#size' => 9,
    '#maxlength' => 9,
    '#description' => t('Point below where DXPR Theme switches to mobile header and navigation. If you set this to the maximum value (4100) the desktop style will never show. Default is 1200.'),
  ];

  $form['dxpr_theme_settings']['header']['header_mobile']['header_mobile_height'] = [
    '#type' => 'textfield',
    '#title' => t('Height'),
    '#default_value' => ((theme_get_setting('header_mobile_height') !== NULL)) ? theme_get_setting('header_mobile_height') : '60',
    '#size' => 9,
    '#maxlength' => 9,
    '#description' => t('Height of header in mobile view. 10px - 200px. Default is 60.'),
  ];

  $form['dxpr_theme_settings']['header']['header_mobile']['header_mobile_fixed'] = [
    '#type' => 'checkbox',
    '#title' => t('Fixed Position'),
    '#default_value' => ((theme_get_setting('header_mobile_fixed') !== NULL)) ? theme_get_setting('header_mobile_fixed') : FALSE,
    '#description' => t('A Fixed header stays in the top of the browser when a user scrolls.'),
  ];
}
