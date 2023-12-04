<?php

namespace Drupal\commerce_recurring\Entity;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\UserInterface;

/**
 * Defines the interface for subscriptions.
 */
interface SubscriptionInterface extends ContentEntityInterface, EntityOwnerInterface {

  /**
   * Gets the subscription type.
   *
   * @return \Drupal\commerce_recurring\Plugin\Commerce\SubscriptionType\SubscriptionTypeInterface
   *   The subscription type.
   */
  public function getType();

  /**
   * Gets the store.
   *
   * @return \Drupal\commerce_store\Entity\StoreInterface
   *   The store.
   */
  public function getStore();

  /**
   * Gets the store ID.
   *
   * @return int
   *   The store ID.
   */
  public function getStoreId();

  /**
   * Gets the billing schedule.
   *
   * @return \Drupal\commerce_recurring\Entity\BillingScheduleInterface
   *   The billing schedule.
   */
  public function getBillingSchedule();

  /**
   * Sets the billing schedule.
   *
   * @param \Drupal\commerce_recurring\Entity\BillingScheduleInterface $billing_schedule
   *   The billing schedule.
   *
   * @return $this
   */
  public function setBillingSchedule(BillingScheduleInterface $billing_schedule);

  /**
   * Gets the customer.
   *
   * @return \Drupal\user\UserInterface
   *   The customer.
   */
  public function getCustomer();

  /**
   * Sets the customer.
   *
   * @param \Drupal\user\UserInterface $account
   *   The customer.
   *
   * @return $this
   */
  public function setCustomer(UserInterface $account);

  /**
   * Gets the customer ID.
   *
   * @return int
   *   The customer ID.
   */
  public function getCustomerId();

  /**
   * Sets the customer ID.
   *
   * @param int $uid
   *   The customer ID.
   *
   * @return $this
   */
  public function setCustomerId($uid);

  /**
   * Gets the payment method.
   *
   * @return \Drupal\commerce_payment\Entity\PaymentMethodInterface|null
   *   The payment method, or NULL.
   */
  public function getPaymentMethod();

  /**
   * Sets the payment method.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method
   *   The payment method.
   *
   * @return $this
   */
  public function setPaymentMethod(PaymentMethodInterface $payment_method);

  /**
   * Gets the payment method ID.
   *
   * @return int|null
   *   The payment method ID, or NULL.
   */
  public function getPaymentMethodId();

  /**
   * Sets the payment method ID.
   *
   * @param int $payment_method_id
   *   The payment method ID.
   *
   * @return $this
   */
  public function setPaymentMethodId($payment_method_id);

  /**
   * Gets whether the subscription has a purchased entity.
   *
   * @return bool
   *   TRUE if the subscription has a purchased entity, FALSE otherwise.
   */
  public function hasPurchasedEntity();

  /**
   * Gets the purchased entity.
   *
   * @return \Drupal\commerce\PurchasableEntityInterface|null
   *   The purchased entity, or NULL.
   */
  public function getPurchasedEntity();

  /**
   * Sets the purchased entity.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $purchased_entity
   *   The purchased entity.
   *
   * @return $this
   */
  public function setPurchasedEntity(PurchasableEntityInterface $purchased_entity);

  /**
   * Gets the purchased entity ID.
   *
   * @return int|null
   *   The purchased entity ID, or NULL.
   */
  public function getPurchasedEntityId();

  /**
   * Gets the subscription title.
   *
   * @return string
   *   The subscription title
   */
  public function getTitle();

  /**
   * Sets the subscription title.
   *
   * @param string $title
   *   The subscription title.
   *
   * @return $this
   */
  public function setTitle($title);

  /**
   * Gets the subscription quantity.
   *
   * @return string
   *   The subscription quantity
   */
  public function getQuantity();

  /**
   * Sets the subscription quantity.
   *
   * @param string $quantity
   *   The subscription quantity.
   *
   * @return $this
   */
  public function setQuantity($quantity);

  /**
   * Gets the subscription unit price.
   *
   * @return \Drupal\commerce_price\Price
   *   The subscription unit price.
   */
  public function getUnitPrice();

  /**
   * Sets the subscription unit price.
   *
   * @param \Drupal\commerce_price\Price $unit_price
   *   The subscription unit price.
   *
   * @return $this
   */
  public function setUnitPrice(Price $unit_price);

  /**
   * Gets the subscription state.
   *
   * @return \Drupal\state_machine\Plugin\Field\FieldType\StateItemInterface
   *   The subscription state.
   */
  public function getState();

  /**
   * Sets the subscription state.
   *
   * @param string $state_id
   *   The new state ID.
   *
   * @return $this
   */
  public function setState($state_id);

  /**
   * Gets the initial order.
   *
   * This is the non-recurring order which started the subscription.
   * Might not be available for manually created subscriptions.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface|null
   *   The initial order, or NULL if not known.
   */
  public function getInitialOrder();

  /**
   * Sets the initial order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $initial_order
   *   The initial order.
   *
   * @return $this
   */
  public function setInitialOrder(OrderInterface $initial_order);

  /**
   * Gets the initial order ID.
   *
   * @return int|null
   *   The initial order ID, or NULL if not known.
   */
  public function getInitialOrderId();

  /**
   * Gets the current recurring order.
   *
   * Note that this method could potentially return recurring orders of
   * different states ("state" and "needs_payment").
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface|null
   *   The current recurring order, or NULL if none found.
   */
  public function getCurrentOrder();

  /**
   * Gets the recurring order IDs.
   *
   * @return int[]
   *   The recurring order IDs.
   */
  public function getOrderIds();

  /**
   * Gets the recurring orders.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface[]
   *   The recurring orders.
   */
  public function getOrders();

  /**
   * Sets the recurring orders.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface[] $orders
   *   The recurring orders.
   *
   * @return $this
   */
  public function setOrders(array $orders);

  /**
   * Adds a recurring order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The recurring order.
   *
   * @return $this
   */
  public function addOrder(OrderInterface $order);

  /**
   * Removes a recurring order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The recurring order.
   *
   * @return $this
   */
  public function removeOrder(OrderInterface $order);

  /**
   * Checks whether the order has a given recurring order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The recurring order.
   *
   * @return bool
   *   TRUE if the recurring order was found, FALSE otherwise.
   */
  public function hasOrder(OrderInterface $order);

  /**
   * Gets the created timestamp.
   *
   * @return int
   *   The created timestamp.
   */
  public function getCreatedTime();

  /**
   * Sets the created timestamp.
   *
   * @param int $timestamp
   *   The created timestamp.
   *
   * @return $this
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the next renewal timestamp.
   *
   * @return int
   *   The next renewal timestamp.
   */
  public function getNextRenewalTime();

  /**
   * Sets the next renewal timestamp.
   *
   * @param int $timestamp
   *   The next renewal timestamp.
   *
   * @return $this
   */
  public function setNextRenewalTime($timestamp);

  /**
   * Gets the next renewal timestamp as a DrupalDateTime object.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime|null
   *   The next renewal date/time, or NULL if not known.
   */
  public function getNextRenewalDate();

  /**
   * Gets the renewal timestamp.
   *
   * @return int
   *   The renewal timestamp.
   */
  public function getRenewedTime();

  /**
   * Sets the renewal timestamp.
   *
   * @param int $timestamp
   *   The renewal timestamp.
   *
   * @return $this
   */
  public function setRenewedTime($timestamp);

  /**
   * Gets the trial start timestamp.
   *
   * @return int
   *   The trial start timestamp.
   */
  public function getTrialStartTime();

  /**
   * Sets the trial start timestamp.
   *
   * @param int $timestamp
   *   The trial start timestamp.
   *
   * @return $this
   */
  public function setTrialStartTime($timestamp);

  /**
   * Gets the trial end timestamp.
   *
   * @return int
   *   The trial end timestamp.
   */
  public function getTrialEndTime();

  /**
   * Sets the trial end timestamp.
   *
   * @param int $timestamp
   *   The trial end timestamp.
   *
   * @return $this
   */
  public function setTrialEndTime($timestamp);

  /**
   * Gets the trial start timestamp as a DrupalDateTime object.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   The trial start date/time.
   */
  public function getTrialStartDate();

  /**
   * Gets the trial end timestamp as a DrupalDateTime object.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime|null
   *   The trial end date/time, or NULL if not yet known.
   */
  public function getTrialEndDate();

  /**
   * Gets the start timestamp.
   *
   * @return int
   *   The start timestamp.
   */
  public function getStartTime();

  /**
   * Sets the start timestamp.
   *
   * @param int $timestamp
   *   The start timestamp.
   *
   * @return $this
   */
  public function setStartTime($timestamp);

  /**
   * Gets the end timestamp.
   *
   * @return int
   *   The end timestamp.
   */
  public function getEndTime();

  /**
   * Sets the end timestamp.
   *
   * @param int $timestamp
   *   The end timestamp.
   *
   * @return $this
   */
  public function setEndTime($timestamp);

  /**
   * Gets the start timestamp as a DrupalDateTime object.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   The start date/time.
   */
  public function getStartDate();

  /**
   * Gets the end timestamp as a DrupalDateTime object.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime|null
   *   The end date/time, or NULL if not yet known.
   */
  public function getEndDate();

  /**
   * Gets the billing period value object for the current order.
   *
   * @return \Drupal\commerce_recurring\BillingPeriod|null
   *   The billing period object, or null if not set.
   */
  public function getCurrentBillingPeriod();

  /**
   * Gets whether the subscription has scheduled changes.
   *
   * @return bool
   *   TRUE if the subscription has scheduled changes, FALSE otherwise.
   */
  public function hasScheduledChanges();

  /**
   * Gets the scheduled changes.
   *
   * @return \Drupal\commerce_recurring\ScheduledChange[]
   *   The scheduled changes.
   */
  public function getScheduledChanges();

  /**
   * Sets the scheduled changes.
   *
   * @param \Drupal\commerce_recurring\ScheduledChange[] $scheduled_changes
   *   The scheduled changes.
   *
   * @return $this
   */
  public function setScheduledChanges(array $scheduled_changes);

  /**
   * Adds a scheduled change for the given field.
   *
   * @param string $field_name
   *   The field_name.
   * @param mixed $value
   *   The value.
   *
   * @return $this
   */
  public function addScheduledChange($field_name, $value);

  /**
   * Removes the scheduled changes.
   *
   * @param string $field_name
   *   (optional) The field name. If provided, only scheduled changes for that
   *   field will be removed. Otherwise, all scheduled changes will be removed.
   *
   * @return $this
   */
  public function removeScheduledChanges($field_name = NULL);

  /**
   * Determines if a scheduled change for the given field exists.
   *
   * @param string $field_name
   *   The field_name.
   * @param mixed $value
   *   (optional) The value.
   *
   * @return bool
   *   TRUE if the given change is scheduled, FALSE otherwise.
   */
  public function hasScheduledChange($field_name, $value = NULL);

  /**
   * Apply the scheduled changes.
   *
   * @return $this
   */
  public function applyScheduledChanges();

  /**
   * Cancel the subscription.
   *
   * @param bool $schedule
   *   Whether to schedule the cancellation.
   *
   * @return $this
   */
  public function cancel($schedule = TRUE);

}
