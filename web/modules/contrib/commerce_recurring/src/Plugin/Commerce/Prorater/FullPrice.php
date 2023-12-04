<?php

namespace Drupal\commerce_recurring\Plugin\Commerce\Prorater;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_recurring\BillingPeriod;

/**
 * Provides a full price prorater.
 *
 * @CommerceProrater(
 *   id = "full_price",
 *   label = @Translation("None (always charge the full price)"),
 * )
 */
class FullPrice extends ProraterBase implements ProraterInterface {

  /**
   * {@inheritdoc}
   */
  public function prorateOrderItem(OrderItemInterface $order_item, BillingPeriod $billing_period, BillingPeriod $full_billing_period) {
    return $order_item->getUnitPrice();
  }

}
