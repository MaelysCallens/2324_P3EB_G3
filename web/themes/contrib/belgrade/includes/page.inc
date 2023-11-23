<?php

/**
 * @file
 * Theme and preprocess functions for pages.
 */

/**
 * Adds theme path alias suggestion for use in system pages bundle.
 */

use Drupal\Component\Utility\Html;
use Drupal\node\NodeInterface;
use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\layout_builder\Entity\LayoutEntityDisplayInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Implements hook_preprocess_HOOK() for page templates.
 */
function belgrade_preprocess_page(&$variables) {
  // Main container
  if (theme_get_setting('main_container_class')) {
    $main_container_class = explode(' ', theme_get_setting('main_container_class'));
    foreach ($main_container_class as $class) {
      $variables['attributes']['class'][] = Html::cleanCssIdentifier($class);
    }
  }

  if (theme_get_setting('main_container')) {
    $main_container = theme_get_setting('main_container');
    $variables['main_container'] = $main_container;
  }

  if (theme_get_setting('local_tasks_fixed') === 1) {
    $variables['local_tasks_fixed'] = true;
  }
}

/**
 * Implements hook_theme_suggestions_HOOK_alter() for page templates.
 * @param array $suggestions
 * @param array $variables
 */
function belgrade_theme_suggestions_page_alter(array &$suggestions, array $variables) {
  $current_path = \Drupal::service('path.current')->getPath();
  $result = \Drupal::service('path_alias.manager')->getAliasByPath($current_path);

  $path_alias = trim($result, '/');
  $path_alias = str_replace('/', '-', $path_alias);
  $path_alias = str_replace('-', '_', $path_alias);

  $suggestions[] = 'page__path__' . $path_alias;

  // Defines custom theme suggestions based on the route.
  $route_name = \Drupal::request()->attributes->get('_route');
  if ('system.404' === $route_name) {
    $suggestions[] = 'page__404';
  }
  if ('system.403' === $route_name) {
    $suggestions[] = 'page__403';
  }

  if ($product = \Drupal::routeMatch()->getParameter('commerce_product')) {
    $type = $product->bundle();
    // Add product type suggestions
    if (!empty($product) && $product instanceof ProductInterface) {
      array_splice($suggestions, 1, 0, 'page__product__' . $type);
      // Add layout builder enabled template suggestions
      _belgrade_theme_alter_page_suggestions($suggestions, $product);
    }
  }

  // * Adds page node type theme suggestion.
  if ($node = \Drupal::routeMatch()->getParameter('node')) {
    if ($node_revision = \Drupal::routeMatch()->getParameter('node_revision')) {
      if ($node_revision = \Drupal::entityTypeManager()->getStorage('node')->loadRevision($node_revision)) {
        $node = $node_revision;
      }
    }
    if (!empty($node) && $node instanceof NodeInterface) {
      $type = $node->getType();
      // Add content type suggestions.
      array_splice($suggestions, 1, 0, 'page__node__' . $type);
      // Add layout builder enabled template suggestions
      _belgrade_theme_alter_page_suggestions($suggestions, $node);
    }
  }

  // * Add view mode theme suggestions based on the vocabulary
  if (\Drupal::routeMatch()->getRouteName() == 'entity.taxonomy_term.canonical' && $tid = \Drupal::routeMatch()->getRawParameter('taxonomy_term')) {
    $term = Term::load($tid);
    $suggestions[] = 'page__taxonomy__' . $term->bundle();
  }
}


/**
 * Implements hook_page_attachments_alter().
 */
function belgrade_page_attachments_alter(array &$page) {
  // Font libraries attachments
  $font = theme_get_setting('font_set');
  if ($font) {
    $page['#attached']['library'][] = 'belgrade/font.' . $font;
  }

  // Belgrade icons.
  if (theme_get_setting('belgrade_icons') === 1) {
    $page['#attached']['library'][] = 'belgrade/icons';
  }

  // Add Breakpoints to drupalSettings
  $breakpoints = \Drupal::service('breakpoint.manager')->getBreakpointsByGroup('bootstrap');

  if (!empty($breakpoints)) {
    $media_queries = [];
    foreach ($breakpoints as $id => $breakpoint) {
      $media_queries[$id] = $breakpoint->getMediaQuery();
    }
    $page['#attached']['drupalSettings']['responsive']['breakpoints'] = $media_queries;
  }
}


/* Helpers */

/**
 * Belgrade theme alter entity suggestions.
 */
function _belgrade_theme_alter_entity_suggestions(array &$suggestions, ContentEntityInterface $entity, string $view_mode) {
  $entity_type_id = $entity->getEntityTypeId();
  /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository */
  $entity_display_repository = \Drupal::service('entity_display.repository');
  $display = $entity_display_repository->getViewDisplay($entity_type_id, $entity->bundle());
  if ($display instanceof LayoutEntityDisplayInterface && $display->isLayoutBuilderEnabled()) {
    if ($view_mode === "full") {
      array_splice($suggestions, 1, 0, $entity_type_id . '__layout__full__' . $entity->bundle());
      array_splice($suggestions, 1, 0, $entity_type_id . '__layout__full');
    }
    array_splice($suggestions, 1, 0, $entity_type_id . '__layout__' . $entity->bundle());
    array_splice($suggestions, 1, 0, $entity_type_id . '__layout');
  }
}

/**
 * Belgrade theme alter page suggestions.
 */
function _belgrade_theme_alter_page_suggestions(array &$suggestions, ContentEntityInterface $entity) {
  $entity_type_id = $entity->getEntityTypeId();
  /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository */
  $entity_display_repository = \Drupal::service('entity_display.repository');
  $display = $entity_display_repository->getViewDisplay($entity_type_id, $entity->bundle());
  if ($display instanceof LayoutEntityDisplayInterface && $display->isLayoutBuilderEnabled()) {
    array_splice($suggestions, 1, 0, 'page__' . str_replace('commerce_', '', $entity_type_id) . '__layout__' . $entity->bundle());
    array_splice($suggestions, 1, 0, 'page__' . str_replace('commerce_', '', $entity_type_id) . '__layout');
  }
}
