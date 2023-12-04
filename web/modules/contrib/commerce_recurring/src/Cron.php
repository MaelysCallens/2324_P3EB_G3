<?php

namespace Drupal\commerce_recurring;

use Drupal\advancedqueue\Entity\QueueInterface;
use Drupal\advancedqueue\Job;
use Drupal\commerce_recurring\Entity\BillingScheduleInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Default cron implementation.
 */
class Cron implements CronInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The recurring order manager.
   *
   * @var \Drupal\commerce_recurring\RecurringOrderManagerInterface
   */
  protected $recurringOrderManager;

  /**
   * The time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new Cron object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_recurring\RecurringOrderManagerInterface $recurring_order_manager
   *   The recurring order manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RecurringOrderManagerInterface $recurring_order_manager, TimeInterface $time) {
    $this->entityTypeManager = $entity_type_manager;
    $this->recurringOrderManager = $recurring_order_manager;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    $queue_storage = $this->entityTypeManager->getStorage('advancedqueue_queue');
    /** @var \Drupal\advancedqueue\Entity\QueueInterface $recurring_queue */
    $recurring_queue = $queue_storage->load('commerce_recurring');

    $this->enqueueOrders($recurring_queue);
    $this->enqueueSubscriptions($recurring_queue);
  }

  /**
   * Enqueues ended recurring orders for closing/renewal.
   *
   * @param \Drupal\advancedqueue\Entity\QueueInterface $recurring_queue
   *   The recurring queue.
   */
  protected function enqueueOrders(QueueInterface $recurring_queue) {
    $order_storage = $this->entityTypeManager->getStorage('commerce_order');
    $order_ids = $order_storage->getQuery()
      ->condition('type', 'recurring')
      ->condition('state', 'draft')
      ->condition('billing_period.ends', $this->time->getRequestTime(), '<=')
      ->accessCheck(FALSE)
      ->execute();
    if (!$order_ids) {
      return;
    }

    /** @var \Drupal\commerce_order\Entity\OrderInterface[] $orders */
    $orders = $order_storage->loadMultiple($order_ids);
    foreach ($orders as $order) {
      $subscriptions = $this->recurringOrderManager->collectSubscriptions($order);
      if (!$subscriptions) {
        // The recurring order is malformed. The referenced subscription
        // might have been deleted manually.
        $order->set('state', 'canceled');
        $order->save();
        continue;
      }

      /** @var \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription */
      $subscription = reset($subscriptions);
      $skip_closing_order = FALSE;
      if ($subscription->hasScheduledChanges()) {
        // Apply changes that were scheduled to happen on the next billing
        // cycle like subscription cancellation.
        $subscription->applyScheduledChanges();
        $subscription->save();

        $billing_type = $subscription->getBillingSchedule()->getBillingType();
        // In case of prepaid subscriptions, if the subscription was scheduled
        // for cancellation, the customer shouldn't be charged for the current
        // recurring order about to be closed.
        if ($billing_type == BillingScheduleInterface::BILLING_TYPE_PREPAID &&
          $subscription->getState()->getId() !== 'active') {
          $skip_closing_order = TRUE;
        }
      }
      if (!$skip_closing_order) {
        $close_job = Job::create('commerce_recurring_order_close', [
          'order_id' => $order->id(),
        ]);
        $recurring_queue->enqueueJob($close_job);
      }
      // Recurring orders are renewed only if their subscription is active.
      if ($subscription->getState()->getId() == 'active') {
        $renew_job = Job::create('commerce_recurring_order_renew', [
          'order_id' => $order->id(),
        ]);
        $recurring_queue->enqueueJob($renew_job);
      }
    }
  }

  /**
   * Enqueues pending and trial subscriptions for activation.
   *
   * @param \Drupal\advancedqueue\Entity\QueueInterface $recurring_queue
   *   The recurring queue.
   */
  protected function enqueueSubscriptions(QueueInterface $recurring_queue) {
    $subscription_storage = $this->entityTypeManager->getStorage('commerce_subscription');
    $subscription_ids = $subscription_storage->getQuery()
      ->condition('state', ['pending', 'trial'], 'IN')
      ->condition('starts', $this->time->getRequestTime(), '<=')
      ->accessCheck(FALSE)
      ->execute();
    if (!$subscription_ids) {
      return;
    }

    foreach ($subscription_ids as $subscription_id) {
      $activate_job = Job::create('commerce_subscription_activate', [
        'subscription_id' => $subscription_id,
      ]);
      $recurring_queue->enqueueJob($activate_job);
    }
  }

}
