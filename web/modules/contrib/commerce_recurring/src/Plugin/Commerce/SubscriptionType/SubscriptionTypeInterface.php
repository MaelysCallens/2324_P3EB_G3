<?php

namespace Drupal\commerce_recurring\Plugin\Commerce\SubscriptionType;

use Drupal\entity\BundlePlugin\BundlePluginInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_recurring\BillingPeriod;
use Drupal\commerce_recurring\Entity\SubscriptionInterface;

/**
 * Defines the interface for subscription types.
 *
 * Subscription types act as subscription bundles, providing additional fields.
 * They also contain billing logic such as calculating charges and manipulating
 * recurring orders.
 */
interface SubscriptionTypeInterface extends BundlePluginInterface {

  /**
   * Gets the subscription type label.
   *
   * @return string
   *   The subscription type label.
   */
  public function getLabel();

  /**
   * Gets the subscription type's purchasable entity type ID.
   *
   * E.g, if subscriptions of this type are used for subscribing to
   * product variations, the ID will be 'commerce_product_variation'.
   *
   * @return string
   *   The purchasable entity type ID, or NULL if the subscription isn't
   *   backed by a purchasable entity.
   */
  public function getPurchasableEntityTypeId();

  /**
   * Collects charges for a subscription's trial period.
   *
   * @param \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription
   *   The subscription.
   * @param \Drupal\commerce_recurring\BillingPeriod $trial_period
   *   The trial period.
   *
   * @return \Drupal\commerce_recurring\Charge[]
   *   The charges.
   */
  public function collectTrialCharges(SubscriptionInterface $subscription, BillingPeriod $trial_period);

  /**
   * Collects charges for a subscription's billing period.
   *
   * @param \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription
   *   The subscription.
   * @param \Drupal\commerce_recurring\BillingPeriod $billing_period
   *   The billing period.
   *
   * @return \Drupal\commerce_recurring\Charge[]
   *   The charges.
   */
  public function collectCharges(SubscriptionInterface $subscription, BillingPeriod $billing_period);

  /**
   * Acts on a subscription after it has been created from an order item.
   *
   * Called before the subscription is saved.
   *
   * @param \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription
   *   The subscription.
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   */
  public function onSubscriptionCreate(SubscriptionInterface $subscription, OrderItemInterface $order_item);

  /**
   * Acts on a subscription after a trial has been started.
   *
   * Called before the subscription and trial recurring order are saved.
   *
   * @param \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription
   *   The subscription.
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The trial recurring order.
   */
  public function onSubscriptionTrialStart(SubscriptionInterface $subscription, OrderInterface $order);

  /**
   * Acts on a subscription after a trial has been canceled.
   *
   * Called before the subscription is saved.
   *
   * @param \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription
   *   The subscription.
   */
  public function onSubscriptionTrialCancel(SubscriptionInterface $subscription);

  /**
   * Acts on a subscription after it has been activated.
   *
   * If a trial just ended, $subscription->getTrialEndTime() will be non-empty
   * and $subscription->getOrders() will have a single order (the trial order).
   *
   * Called before the subscription and recurring order are saved.
   *
   * @param \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription
   *   The subscription.
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The recurring order.
   */
  public function onSubscriptionActivate(SubscriptionInterface $subscription, OrderInterface $order);

  /**
   * Acts on a subscription after it has been renewed.
   *
   * Called before the subscription and next recurring order are saved.
   *
   * @param \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription
   *   The subscription.
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The recurring order.
   * @param \Drupal\commerce_order\Entity\OrderInterface $next_order
   *   The next recurring order.
   */
  public function onSubscriptionRenew(SubscriptionInterface $subscription, OrderInterface $order, OrderInterface $next_order);

  /**
   * Acts on a subscription after it has expired.
   *
   * Called before the subscription is saved.
   *
   * @param \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription
   *   The subscription.
   */
  public function onSubscriptionExpire(SubscriptionInterface $subscription);

  /**
   * Acts on a subscription after it has been canceled.
   *
   * Called before the subscription is saved.
   *
   * @param \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription
   *   The subscription.
   */
  public function onSubscriptionCancel(SubscriptionInterface $subscription);

}
