<?php

namespace Drupal\commerce_recurring\Event;

final class RecurringEvents {

  /**
   * Name of the event fired when a payment is declined.
   *
   * Subscribers can respond to this email to send dunning emails or modify
   * the recurring order before it is saved.
   *
   * @Event
   *
   * @see \Drupal\commerce_recurring\Plugin\AdvancedQueue\JobType\RecurringOrderClose
   */
  const PAYMENT_DECLINED = 'commerce_recurring.payment_declined';

  /**
   * Name of the event fired after loading a subscription.
   *
   * @Event
   *
   * @see \Drupal\commerce_recurring\Event\SubscriptionEvent
   */
  const SUBSCRIPTION_LOAD = 'commerce_recurring.commerce_subscription.load';

  /**
   * Name of the event fired after creating a new subscription.
   *
   * Fired before the subscription is saved.
   *
   * @Event
   *
   * @see \Drupal\commerce_recurring\Event\SubscriptionEvent
   */
  const SUBSCRIPTION_CREATE = 'commerce_recurring.commerce_subscription.create';

  /**
   * Name of the event fired before saving a subscription.
   *
   * @Event
   *
   * @see \Drupal\commerce_recurring\Event\SubscriptionEvent
   */
  const SUBSCRIPTION_PRESAVE = 'commerce_recurring.commerce_subscription.presave';

  /**
   * Name of the event fired after saving a new subscription.
   *
   * @Event
   *
   * @see \Drupal\commerce_recurring\Event\SubscriptionEvent
   */
  const SUBSCRIPTION_INSERT = 'commerce_recurring.commerce_subscription.insert';

  /**
   * Name of the event fired after saving an existing subscription.
   *
   * @Event
   *
   * @see \Drupal\commerce_recurring\Event\SubscriptionEvent
   */
  const SUBSCRIPTION_UPDATE = 'commerce_recurring.commerce_subscription.update';

  /**
   * Name of the event fired before deleting a subscription.
   *
   * @Event
   *
   * @see \Drupal\commerce_recurring\Event\SubscriptionEvent
   */
  const SUBSCRIPTION_PREDELETE = 'commerce_recurring.commerce_subscription.predelete';

  /**
   * Name of the event fired after deleting a subscription.
   *
   * @Event
   *
   * @see \Drupal\commerce_recurring\Event\SubscriptionEvent
   */
  const SUBSCRIPTION_DELETE = 'commerce_recurring.commerce_subscription.delete';

}
