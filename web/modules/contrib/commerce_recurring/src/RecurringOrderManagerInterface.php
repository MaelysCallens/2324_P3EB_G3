<?php

namespace Drupal\commerce_recurring;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_recurring\Entity\SubscriptionInterface;

/**
 * Manages recurring orders.
 *
 * Recurring orders are automatically started, kept up to date, closed, and
 * renewed, for the purpose of paying for a trial or billing period.
 *
 * Recurring orders are always of type "recurring", and have billing_period
 * and billing_schedule fields. Each order item is of a "recurring_" type
 * (e.g. "recurring_standalone") and has billing_period and subscription fields.
 * The order item's billing_period is compared with the order's billing_period
 * during prorating.
 *
 * @see \Drupal\commerce_recurring\Plugin\Commerce\Prorater\ProraterInterface
 */
interface RecurringOrderManagerInterface {

  /**
   * Starts the trial for the given subscription.
   *
   * Creates a recurring order covering the trial period.
   * The order will be closed once the trial period is over.
   *
   * Since there can only be a single trial period, the trial order is one-off,
   * never renewed. A new recurring order is created by startRecurring() once
   * the subscription is activated.
   *
   * @param \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription
   *   The trial subscription.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   The trial recurring order.
   *
   * @throws \InvalidArgumentException
   *   Thrown if subscription state is not "trial".
   */
  public function startTrial(SubscriptionInterface $subscription);

  /**
   * Starts the recurring process for the given subscription.
   *
   * Creates a recurring order covering the first billing period.
   * The order will be closed and renewed once the billing period is over.
   *
   * @param \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription
   *   The active subscription.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   The recurring order.
   *
   * @throws \InvalidArgumentException
   *   Thrown if subscription state is not "active".
   */
  public function startRecurring(SubscriptionInterface $subscription);

  /**
   * Refreshes the given recurring order.
   *
   * Each subscription's order items will be rebuilt based on the most
   * recent charges.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The recurring order.
   */
  public function refreshOrder(OrderInterface $order);

  /**
   * Closes the given recurring order.
   *
   * A payment will be created and the order will be placed.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The recurring order.
   *
   * @throws \Drupal\commerce_payment\Exception\HardDeclineException
   *   Thrown when no payment method was found.
   * @throws \Drupal\commerce_payment\Exception\PaymentGatewayException
   *   Thrown when the transaction fails for any reason. This includes
   *   child exceptions such as HardDeclineException and SoftDeclineException.
   */
  public function closeOrder(OrderInterface $order);

  /**
   * Renews the given recurring order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The recurring order.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface|null
   *   The next recurring order, or NULL if none remain.
   */
  public function renewOrder(OrderInterface $order);

  /**
   * Collects all subscriptions that belong to an order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return \Drupal\commerce_recurring\Entity\SubscriptionInterface[]
   *   The subscriptions.
   */
  public function collectSubscriptions(OrderInterface $order);

}
