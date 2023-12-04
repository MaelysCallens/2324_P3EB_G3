<?php

namespace Drupal\commerce_recurring;

use Drupal\commerce\CommerceContentEntityStorage;
use Drupal\commerce_order\Entity\OrderItemInterface;

/**
 * Defines the subscription storage.
 */
class SubscriptionStorage extends CommerceContentEntityStorage implements SubscriptionStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function createFromOrderItem(OrderItemInterface $order_item, array $values = []) {
    $values += [
      'purchased_entity' => $order_item->getPurchasedEntity(),
      'title' => $order_item->getTitle(),
      'quantity' => $order_item->getQuantity(),
      // The subscription unit price is populated from the resolved
      // order item unit price, then used for all future recurring orders.
      // This allows regular Commerce pricing to be used to select a price
      // per currency, customer group, etc. It also allows the purchased
      // entity price to change in the future without automatically
      // affecting existing subscriptions.
      'unit_price' => $order_item->getUnitPrice(),
    ];
    if ($order = $order_item->getOrder()) {
      $values += [
        'store_id' => $order->getStoreId(),
        'uid' => $order->getCustomerId(),
        'initial_order' => $order->id(),
      ];
    }
    /** @var \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription */
    $subscription = $this->create($values);
    // Notify the subscription type to allow it to populate additional fields.
    $subscription_type = $subscription->getType();
    $subscription_type->onSubscriptionCreate($subscription, $order_item);

    return $subscription;
  }

}
