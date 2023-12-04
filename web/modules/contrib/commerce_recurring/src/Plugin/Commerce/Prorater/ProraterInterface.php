<?php

namespace Drupal\commerce_recurring\Plugin\Commerce\Prorater;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\commerce_recurring\BillingPeriod;

/**
 * Modifies unit prices to reflect partial billing periods.
 *
 * For example, if the order's billing period is Jun 1st - Jul 1st, but
 * the order item's billing period is Jun 1st - Jun 16th (because a plan
 * change or a cancellation happened then), the order item's unit price
 * should only be half of the usual price.
 */
interface ProraterInterface extends ConfigurableInterface, DependentPluginInterface, PluginFormInterface {

  /**
   * Prorates the given order item.
   *
   * When needed, the plugin can use separate logic for recurring and initial
   * order items by looking at the order item type:
   * @code
   * if (in_array($order_item->bundle(), commerce_recurring_order_item_types())) {
   *   // This is a recurring order item.
   * }
   * else {
   *   // This is an initial order item.
   * }
   * @endcode
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   * @param \Drupal\commerce_recurring\BillingPeriod $billing_period
   *   The partial billing period.
   * @param \Drupal\commerce_recurring\BillingPeriod $full_billing_period
   *   The full billing period.
   *
   * @return \Drupal\commerce_price\Price
   *   The prorated price.
   */
  public function prorateOrderItem(OrderItemInterface $order_item, BillingPeriod $billing_period, BillingPeriod $full_billing_period);

}
