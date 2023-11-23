<?php

/**
 * @file
 * Theme and preprocess functions for blocks.
 */

use Drupal\block\Entity\Block;

/**
 *
 */
function belgrade_theme_suggestions_block_alter(array &$suggestions, array $variables) {
  if (isset($variables['elements']['#id']) && isset($variables['elements']['#base_plugin_id'])) {
    $region = Block::load($variables['elements']['#id'])->getRegion();
    $suggestions[] = 'block__' . $variables['elements']['#base_plugin_id'] . '__' . $region;
  }

  if (isset($variables['elements']['content']['#block_content'])) {
    $suggestions[] = 'block__' . $variables['elements']['content']['#block_content']->bundle();
  }
}

/**
 *
 */
function belgrade_preprocess_block(&$variables) {
  switch ($variables['base_plugin_id']) {
    case 'system_branding_block':
      if (!empty($variables['site_logo'])) {
        // Inline SVG logo.
        $logo_path_info = pathinfo($variables['site_logo']);
        if (theme_get_setting('inline_logo') === 1 && $logo_path_info['extension'] === 'svg') {
          $variables['inline_logo'] = true;
        }
      }
      break;
  }

  // Adding custom attribute class for block.
  if ($variables['elements']['#base_plugin_id'] == 'block_content') {
    $blockType = strtr($variables['content']['#block_content']->bundle(), '_', '-');
    $variables['attributes']['class'][] = 'block--type-' . $blockType;
  }
}
