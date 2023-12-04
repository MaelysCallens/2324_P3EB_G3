<?php

namespace Drupal\commerce_recurring;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;

/**
 * Defines the interface for subscription storage.
 */
interface SubscriptionStorageInterface extends ContentEntityStorageInterface {

  /**
   * Constructs a new subscription using the given order item.
   *
   * The new subscription isn't saved.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   * @param array $values
   *   (optional) An array of values to set, keyed by property name.
   *
   * @return \Drupal\commerce_recurring\Entity\SubscriptionInterface
   *   The created subscription.
   */
  public function createFromOrderItem(OrderItemInterface $order_item, array $values = []);

}
