<?php

namespace Drupal\commerce_recurring;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\entity\Routing\DefaultHtmlRouteProvider;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for the Subscription entity.
 */
class SubscriptionRouteProvider extends DefaultHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);

    if ($cancel_form_route = $this->getCancelFormRoute($entity_type)) {
      $collection->add('entity.commerce_subscription.cancel_form', $cancel_form_route);
    }

    if ($customer_view_route = $this->getCustomerViewRoute($entity_type)) {
      $collection->add('entity.commerce_subscription.customer_view', $customer_view_route);
    }

    if ($customer_edit_form_route = $this->getCustomerEditFormRoute($entity_type)) {
      $collection->add('entity.commerce_subscription.customer_edit_form', $customer_edit_form_route);
    }

    return $collection;
  }

  /**
   * Gets the cancel-form route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route
   *   The generated route.
   */
  protected function getCancelFormRoute(EntityTypeInterface $entity_type) {
    $route = new Route($entity_type->getLinkTemplate('cancel-form'));
    $route
      ->addDefaults([
        '_entity_form' => 'commerce_subscription.cancel',
        '_title_callback' => '\Drupal\Core\Entity\Controller\EntityController::title',
      ])
      ->setRequirement('_entity_access', 'commerce_subscription.cancel')
      ->setRequirement('commerce_subscription', '\d+')
      ->setOption('parameters', [
        'commerce_subscription' => [
          'type' => 'entity:commerce_subscription',
        ],
      ]);

    return $route;
  }

  /**
   * Gets the customer-edit-form route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route
   *   The generated route.
   */
  protected function getCustomerEditFormRoute(EntityTypeInterface $entity_type) {
    $route = new Route($entity_type->getLinkTemplate('customer-edit-form'));
    $route
      ->addDefaults([
        '_entity_form' => 'commerce_subscription.customer',
        '_title_callback' => '\Drupal\Core\Entity\Controller\EntityController::title',
      ])
      ->setRequirement('_entity_access', 'commerce_subscription.update')
      ->setRequirement('commerce_subscription', '\d+')
      ->setOption('parameters', [
        'user' => [
          'type' => 'entity:user',
        ],
        'commerce_subscription' => [
          'type' => 'entity:commerce_subscription',
        ],
      ]);

    return $route;
  }

  /**
   * Gets the customer-view route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route
   *   The generated route.
   */
  protected function getCustomerViewRoute(EntityTypeInterface $entity_type) {
    $route = new Route($entity_type->getLinkTemplate('customer-view'));
    $route
      ->addDefaults([
        '_entity_view' => 'commerce_subscription.customer',
        '_title_callback' => '\Drupal\Core\Entity\Controller\EntityController::title',
      ])
      ->setRequirement('_entity_access', 'commerce_subscription.view')
      ->setRequirement('commerce_subscription', '\d+')
      ->setOption('parameters', [
        'user' => [
          'type' => 'entity:user',
        ],
        'commerce_subscription' => [
          'type' => 'entity:commerce_subscription',
        ],
      ]);

    return $route;
  }

}
