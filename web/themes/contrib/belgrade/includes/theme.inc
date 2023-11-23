<?php

/**
 * @file
 * Custom theme hooks.
 */
use Drupal\node\NodeInterface;
use Drupal\commerce_product\Entity\ProductInterface;

/**
 * Implements hook_theme_suggestions_HOOK_alter() for node templates.
 */
function belgrade_theme_suggestions_node_alter(array &$suggestions, array $variables) {
  $node = $variables['elements']['#node'];
  $view_mode = $variables['elements']['#view_mode'];
  if(!empty($node) && $node instanceof NodeInterface && !is_null($view_mode)) {
    _belgrade_theme_alter_entity_suggestions($suggestions, $node, $view_mode);
  }
}

/**
 * Implements hook_theme_suggestions_HOOK_alter() for commerce_product templates.
 */
function belgrade_theme_suggestions_commerce_product_alter(array &$suggestions, array $variables) {
  // View mode and theme settings
  $product = $variables['elements']['#commerce_product'];
  $view_mode = $variables['elements']['#view_mode'];
  if (!empty($product) && $product instanceof ProductInterface && !is_null($view_mode)) {
    _belgrade_theme_alter_entity_suggestions($suggestions, $product, $view_mode);
  }
  // Limiting to catalog display mode
  $display = theme_get_setting('product_teaser');
  if ($display) {
    switch($view_mode) {
      case 'teaser':
        $suggestions[] = 'commerce_product__' . $view_mode . '__' . $display;
        break;
    }
  }
}
