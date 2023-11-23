<?php

/**
 * @file
 * Theme and preprocess functions for html wrapper.
 */

use Drupal\node\NodeInterface;

/**
 *
 */
function belgrade_preprocess_html(&$variables) {
  $node = \Drupal::routeMatch()->getParameter('node');
  if ($node instanceof NodeInterface) {
    // * Add content type machine name body class.
    $variables['attributes']['class'][] = 'node-type--' . str_replace('_', '-', $node->bundle());
  }

  // * Check different error conditions to add body classes.
  $route_name = \Drupal::request()->attributes->get('_route');
  if ('system.404' === $route_name) {
    $variables['attributes']['class'][] = 'error404';
  }
  if ('system.403' === $route_name) {
    $variables['attributes']['class'][] = 'error403';
  }

  // * Get theme path in JS
  $variables['#attached']['drupalSettings']['path']['themeUrl'] = \Drupal::theme()->getActiveTheme()->getPath();
}
