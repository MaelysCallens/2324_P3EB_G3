<?php
$info = [
  // Available colors and color labels used in theme.
  'fields' => [
    'primary' => t('Primary'),
    'primaryhover' => t('Primary hover'),
    'light' => t('Light'),
    'dark' => t('Dark'),
    'text' => t('Text Color'),
    'background' => t('Background Color')
  ],

  // Pre-defined color schemes.
  'schemes' => [
    'default' => [
      'title' => t('Default'),
      'colors' => [
         'primary' => '#41449f',
         'primaryhover' => '#373a87',
         'light' => '#e9e9f2',
         'dark' => '#272727',
         'text' => '#333333',
         'background' => '#ffffff'
      ],
    ],
    'blossom' => [
      'title' => t('Apple Blossom'),
      'colors' => [
         'primary' => '#9F4143',
         'primaryhover' => '#873739',
         'light' => '#F2E9E9',
         'dark' => '#592750',
         'text' => '#212121',
         'background' => '#ffffff'
      ],
    ],
    'marine' => [
      'title' => t('Marine'),
      'colors' => [
         'primary' => '#437A9E',
         'primaryhover' => '#396886',
         'light' => '#d0e5f1',
         'dark' => '#555B5E',
         'text' => '#333333',
         'background' => '#ffffff'
      ],
    ],
    'khaki' => [
      'title' => t('Khaki'),
      'colors' => [
         'primary' => '#9f6941',
         'primaryhover' => '#875937',
         'light' => '#ece4db',
         'dark' => '#272727',
         'text' => '#272727',
         'background' => '#ffffff'
      ],
    ],
  ],

  // CSS files (excluding @import) to rewrite with new color scheme.
  'css' => [
    'dist/color.css',
  ],

  // Files to copy.
  'copy' => [
    'logo.svg',
  ],

  // Preview files.
  'preview_library' => 'belgrade/color.preview',
  'preview_html' => 'color/preview.html',

  // Attachments.
  '#attached' => [
    'drupalSettings' => [
      'color' => [
        // Put the logo path into JavaScript for the live preview.
        'logo' => theme_get_setting('logo.url', 'belgrade'),
      ],
    ],
  ],
];
