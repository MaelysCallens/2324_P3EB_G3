<?php

namespace Drupal\commerce_recurring\Plugin\AdvancedQueue\JobType;

use Drupal\advancedqueue\JobResult;
use Drupal\advancedqueue\Plugin\AdvancedQueue\JobType\JobTypeBase;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Exception\DeclineException;
use Drupal\commerce_recurring\Event\PaymentDeclinedEvent;
use Drupal\commerce_recurring\Event\RecurringEvents;
use Drupal\commerce_recurring\RecurringOrderManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides a base class for recurring job types.
 */
abstract class RecurringJobTypeBase extends JobTypeBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The recurring order manager.
   *
   * @var \Drupal\commerce_recurring\RecurringOrderManagerInterface
   */
  protected $recurringOrderManager;

  /**
   * Constructs a new RecurringJobTypeBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\commerce_recurring\RecurringOrderManagerInterface $recurring_order_manager
   *   The recurring order manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $event_dispatcher, RecurringOrderManagerInterface $recurring_order_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->eventDispatcher = $event_dispatcher;
    $this->recurringOrderManager = $recurring_order_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('event_dispatcher'),
      $container->get('commerce_recurring.order_manager')
    );
  }

  /**
   * Handles a declined order payment.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param \Drupal\commerce_payment\Exception\DeclineException $exception
   *   The decline exception.
   * @param int $num_retries
   *   The number of times the job was retried so far.
   *
   * @return \Drupal\advancedqueue\JobResult
   *   The job result.
   */
  protected function handleDecline(OrderInterface $order, DeclineException $exception, $num_retries) {
    /** @var \Drupal\commerce_recurring\Entity\BillingScheduleInterface $billing_schedule */
    $billing_schedule = $order->get('billing_schedule')->entity;
    $schedule = $billing_schedule->getRetrySchedule();
    $max_retries = count($schedule);
    if ($num_retries < $max_retries) {
      $retry_days = $schedule[$num_retries];
      $result = JobResult::failure($exception->getMessage(), $max_retries, 86400 * $retry_days);
    }
    else {
      $retry_days = 0;
      $result = JobResult::success('Dunning complete, recurring order not paid.');

      $this->handleFailedOrder($order, FALSE);
    }
    // Subscribers can choose to send a dunning email.
    $event = new PaymentDeclinedEvent($order, $retry_days, $num_retries, $max_retries, $exception);
    $this->eventDispatcher->dispatch($event, RecurringEvents::PAYMENT_DECLINED);
    $order->save();

    return $result;
  }

  /**
   * Handles an order whose payment has definitively failed.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param bool $save_order
   *   Whether the order should be saved after the operation.
   */
  protected function handleFailedOrder(OrderInterface $order, $save_order = TRUE) {
    if ($order->getState()->isTransitionAllowed('mark_failed')) {
      $order->getState()->applyTransitionById('mark_failed');
    }

    /** @var \Drupal\commerce_recurring\Entity\BillingScheduleInterface $billing_schedule */
    $billing_schedule = $order->get('billing_schedule')->entity;
    $unpaid_subscription_state = $billing_schedule->getUnpaidSubscriptionState();
    if ($unpaid_subscription_state != 'active') {
      $this->updateSubscriptions($order, $unpaid_subscription_state);
    }
    if ($save_order) {
      $order->save();
    }
  }

  /**
   * Updates the recurring order's subscriptions to the new state.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The recurring order.
   * @param string $new_state_id
   *   The new state.
   */
  protected function updateSubscriptions(OrderInterface $order, $new_state_id) {
    $subscriptions = $this->recurringOrderManager->collectSubscriptions($order);
    foreach ($subscriptions as $subscription) {
      if ($subscription->getState()->getId() != 'active') {
        // The subscriptions are expected to be active, if one isn't, it
        // might have been canceled in the meantime.
        continue;
      }
      $subscription->setState($new_state_id);
      $subscription->save();
    }
  }

}
