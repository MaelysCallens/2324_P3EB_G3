<?php

namespace Drupal\Tests\commerce_recurring\Kernel\Plugin\AdvancedQueue\JobType;

use Drupal\advancedqueue\Job;
use Drupal\commerce_price\Price;
use Drupal\commerce_recurring\Entity\Subscription;
use Drupal\Tests\commerce_recurring\Kernel\RecurringKernelTestBase;

/**
 * @coversDefaultClass \Drupal\commerce_recurring\Plugin\AdvancedQueue\JobType\SubscriptionActivate
 * @group commerce_recurring
 */
class SubscriptionActivateTest extends RecurringKernelTestBase {

  /**
   * The recurring order manager.
   *
   * @var \Drupal\commerce_recurring\RecurringOrderManagerInterface
   */
  protected $recurringOrderManager;

  /**
   * The used queue.
   *
   * @var \Drupal\advancedqueue\Entity\QueueInterface
   */
  protected $queue;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->recurringOrderManager = $this->container->get('commerce_recurring.order_manager');
    /** @var \Drupal\Core\Entity\EntityStorageInterface $queue_storage */
    $queue_storage = $this->container->get('entity_type.manager')->getStorage('advancedqueue_queue');
    $this->queue = $queue_storage->load('commerce_recurring');
  }

  /**
   * @covers ::process
   */
  public function testActivate() {
    /** @var \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription */
    $subscription = Subscription::create([
      'type' => 'product_variation',
      'store_id' => $this->store->id(),
      'billing_schedule' => $this->billingSchedule,
      'uid' => $this->user,
      'purchased_entity' => $this->variation,
      'title' => $this->variation->getOrderItemTitle(),
      'unit_price' => new Price('2', 'USD'),
      'state' => 'active',
      'starts' => strtotime('2019-02-24 17:00'),
    ]);
    $subscription->save();

    // Confirm that it is not possible to active an already-active subscription.
    $job = Job::create('commerce_subscription_activate', [
      'subscription_id' => $subscription->id(),
    ]);
    $this->queue->enqueueJob($job);

    $job = $this->queue->getBackend()->claimJob();
    /** @var \Drupal\advancedqueue\ProcessorInterface $processor */
    $processor = \Drupal::service('advancedqueue.processor');
    $result = $processor->processJob($job, $this->queue);
    $this->assertEquals(Job::STATE_FAILURE, $result->getState());

    /** @var \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription */
    $subscription = Subscription::create([
      'type' => 'product_variation',
      'store_id' => $this->store->id(),
      'billing_schedule' => $this->billingSchedule,
      'uid' => $this->user,
      'purchased_entity' => $this->variation,
      'title' => $this->variation->getOrderItemTitle(),
      'unit_price' => new Price('2', 'USD'),
      'state' => 'pending',
      'starts' => strtotime('2019-02-01 00:00'),
    ]);
    $subscription->save();

    // Confirm that it is possible to activate a pending subscription.
    $job = Job::create('commerce_subscription_activate', [
      'subscription_id' => $subscription->id(),
    ]);
    $this->queue->enqueueJob($job);
    $job = $this->queue->getBackend()->claimJob();

    $result = $processor->processJob($job, $this->queue);
    $this->assertEquals(Job::STATE_SUCCESS, $result->getState());
    $subscription = $this->reloadEntity($subscription);
    $this->assertEquals('active', $subscription->getState()->getId());
    $this->assertCount(1, $subscription->getOrders());
    $order = $subscription->getOrders()[0];
    $this->assertEquals(new Price('2', 'USD'), $order->getTotalPrice());

    /** @var \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription */
    $subscription = Subscription::create([
      'type' => 'product_variation',
      'store_id' => $this->store->id(),
      'billing_schedule' => $this->billingSchedule,
      'uid' => $this->user,
      'purchased_entity' => $this->variation,
      'title' => $this->variation->getOrderItemTitle(),
      'unit_price' => new Price('3', 'USD'),
      'state' => 'trial',
      'trial_starts' => strtotime('2019-02-01 00:00'),
      'trial_ends' => strtotime('2019-02-10 00:00'),
      'starts' => strtotime('2019-02-10 00:00'),
    ]);
    $subscription->save();
    $this->recurringOrderManager->startTrial($subscription);

    // Confirm that it is possible to activate a trial subscription.
    $job = Job::create('commerce_subscription_activate', [
      'subscription_id' => $subscription->id(),
    ]);
    $this->queue->enqueueJob($job);
    $job = $this->queue->getBackend()->claimJob();

    /** @var \Drupal\advancedqueue\ProcessorInterface $processor */
    $result = $processor->processJob($job, $this->queue);
    $this->assertEquals(Job::STATE_SUCCESS, $result->getState());
    $subscription = $this->reloadEntity($subscription);
    $this->assertEquals('active', $subscription->getState()->getId());
    // One order for the trial period, one for the first billing period.
    $this->assertCount(2, $subscription->getOrders());
    $order = $subscription->getOrders()[1];
    // Prorated price for the Feb 10th - Mar 1st period.
    $this->assertEquals(new Price('2.04', 'USD'), $order->getTotalPrice());
  }

}
