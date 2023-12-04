<?php

namespace Drupal\Tests\commerce_recurring\Kernel;

use Drupal\advancedqueue\Entity\Queue;
use Drupal\advancedqueue\Job;
use Drupal\commerce_price\Price;
use Drupal\commerce_recurring\Entity\BillingScheduleInterface;
use Drupal\commerce_recurring\Entity\Subscription;

/**
 * @coversDefaultClass \Drupal\commerce_recurring\Cron
 * @group commerce_recurring
 */
class CronTest extends RecurringKernelTestBase {

  /**
   * The recurring order manager.
   *
   * @var \Drupal\commerce_recurring\RecurringOrderManagerInterface
   */
  protected $recurringOrderManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->recurringOrderManager = $this->container->get('commerce_recurring.order_manager');
  }

  /**
   * Tests handling trial subscriptions and their orders.
   */
  public function testTrial() {
    $subscription = Subscription::create([
      'type' => 'product_variation',
      'store_id' => $this->store->id(),
      'billing_schedule' => $this->billingSchedule,
      'uid' => $this->user,
      'payment_method' => $this->paymentMethod,
      'purchased_entity' => $this->variation,
      'title' => $this->variation->getOrderItemTitle(),
      'unit_price' => new Price('2', 'USD'),
      'state' => 'trial',
      'trial_starts' => strtotime('2019-02-05 17:00'),
      'trial_ends' => strtotime('2019-02-15 17:00'),
      'starts' => strtotime('2019-02-15 17:00'),
    ]);
    $subscription->save();
    $order = $this->recurringOrderManager->startTrial($subscription);

    // Confirm that no jobs were scheduled while the trial was still ongoing.
    $this->rewindTime(strtotime('2019-02-10 17:00'));
    $this->container->get('commerce_recurring.cron')->run();
    /** @var \Drupal\advancedqueue\Entity\QueueInterface $queue */
    $queue = Queue::load('commerce_recurring');
    $counts = array_filter($queue->getBackend()->countJobs());
    $this->assertEmpty($counts);

    // Confirm that the trial order was scheduled for closing (but not renewal),
    // and that the subscription was scheduled for activation.
    $this->rewindTime(strtotime('2019-02-15 17:00'));
    $this->container->get('commerce_recurring.cron')->run();
    $this->container->get('entity_type.manager')->getStorage('advancedqueue_queue')->resetCache();
    /** @var \Drupal\advancedqueue\Entity\QueueInterface $queue */
    $queue = Queue::load('commerce_recurring');
    $counts = array_filter($queue->getBackend()->countJobs());
    $this->assertEquals([Job::STATE_QUEUED => 2], $counts);
    $first_job = $queue->getBackend()->claimJob();
    $this->assertSame(['order_id' => $order->id()], $first_job->getPayload());
    $this->assertEquals('commerce_recurring_order_close', $first_job->getType());
    $second_job = $queue->getBackend()->claimJob();
    $this->assertSame(['subscription_id' => $subscription->id()], $second_job->getPayload());
    $this->assertEquals('commerce_subscription_activate', $second_job->getType());
  }

  /**
   * Tests handling canceled trial subscriptions and their orders.
   */
  public function testTrialCanceled() {
    $postpaid_subscription = Subscription::create([
      'type' => 'product_variation',
      'store_id' => $this->store->id(),
      'billing_schedule' => $this->billingSchedule,
      'uid' => $this->user,
      'payment_method' => $this->paymentMethod,
      'purchased_entity' => $this->variation,
      'title' => $this->variation->getOrderItemTitle(),
      'unit_price' => new Price('2', 'USD'),
      'state' => 'trial',
      'trial_starts' => strtotime('2019-02-05 17:00'),
      'trial_ends' => strtotime('2019-02-15 17:00'),
      'starts' => strtotime('2019-02-15 17:00'),
    ]);
    $postpaid_subscription->save();
    $postpaid_order = $this->recurringOrderManager->startTrial($postpaid_subscription);
    // Cancel the subscription half-way through the trial.
    $this->rewindTime(strtotime('2019-02-10 17:00'));
    $postpaid_subscription->cancel(FALSE);
    $postpaid_subscription->save();

    // Confirm that the trial order is scheduling for closing.
    $this->rewindTime(strtotime('2019-02-15 17:00'));
    $this->container->get('commerce_recurring.cron')->run();
    /** @var \Drupal\advancedqueue\Entity\QueueInterface $queue */
    $queue = Queue::load('commerce_recurring');
    $counts = array_filter($queue->getBackend()->countJobs());
    $this->assertEquals([Job::STATE_QUEUED => 1], $counts);
    $job = $queue->getBackend()->claimJob();
    $this->assertSame(['order_id' => $postpaid_order->id()], $job->getPayload());
    $this->assertEquals('commerce_recurring_order_close', $job->getType());

    $postpaid_subscription->delete();
    $postpaid_order->delete();
    $queue->getBackend()->deleteQueue();
    $this->billingSchedule->setBillingType(BillingScheduleInterface::BILLING_TYPE_PREPAID);
    $this->billingSchedule->save();

    $prepaid_subscription = Subscription::create([
      'type' => 'product_variation',
      'store_id' => $this->store->id(),
      'billing_schedule' => $this->billingSchedule,
      'uid' => $this->user,
      'payment_method' => $this->paymentMethod,
      'purchased_entity' => $this->variation,
      'title' => $this->variation->getOrderItemTitle(),
      'unit_price' => new Price('2', 'USD'),
      'state' => 'trial',
      'trial_starts' => strtotime('2019-02-05 17:00'),
      'trial_ends' => strtotime('2019-02-15 17:00'),
      'starts' => strtotime('2019-02-15 17:00'),
    ]);
    $prepaid_subscription->save();
    $prepaid_order = $this->recurringOrderManager->startTrial($prepaid_subscription);
    // Schedule the subscription for cancellation.
    $prepaid_subscription->cancel();
    $prepaid_subscription->save();

    // Confirm that both the subscription and its order have been canceled.
    $this->container->get('commerce_recurring.cron')->run();
    $prepaid_subscription = $this->reloadEntity($prepaid_subscription);
    $this->assertEquals('canceled', $prepaid_subscription->getState()->getId());
    $prepaid_order = $this->reloadEntity($prepaid_order);
    $this->assertEquals('canceled', $prepaid_order->getState()->getId());
    $counts = array_filter($queue->getBackend()->countJobs());
    $this->assertEmpty($counts);
  }

  /**
   * Tests handling active subscriptions and their orders.
   */
  public function testActive() {
    $subscription = Subscription::create([
      'type' => 'product_variation',
      'store_id' => $this->store->id(),
      'billing_schedule' => $this->billingSchedule,
      'uid' => $this->user,
      'payment_method' => $this->paymentMethod,
      'purchased_entity' => $this->variation,
      'title' => $this->variation->getOrderItemTitle(),
      'unit_price' => new Price('2', 'USD'),
      'state' => 'active',
      'starts' => strtotime('2019-02-15 00:00'),
    ]);
    $subscription->save();
    $order = $this->recurringOrderManager->startRecurring($subscription);

    // Confirm that no jobs were scheduled before the end of the billing period.
    $this->rewindTime(strtotime('2019-02-24 17:00'));
    $this->container->get('commerce_recurring.cron')->run();
    /** @var \Drupal\advancedqueue\Entity\QueueInterface $queue */
    $queue = Queue::load('commerce_recurring');
    $counts = array_filter($queue->getBackend()->countJobs());
    $this->assertEmpty($counts);

    // Confirm that the order was scheduled for closing and renewal.
    $this->rewindTime(strtotime('2019-03-01 00:00'));
    $this->container->get('commerce_recurring.cron')->run();
    $this->container->get('entity_type.manager')->getStorage('advancedqueue_queue')->resetCache();
    /** @var \Drupal\advancedqueue\Entity\QueueInterface $queue */
    $queue = Queue::load('commerce_recurring');
    $counts = array_filter($queue->getBackend()->countJobs());
    $this->assertEquals([Job::STATE_QUEUED => 2], $counts);
    $first_job = $queue->getBackend()->claimJob();
    $this->assertSame(['order_id' => $order->id()], $first_job->getPayload());
    $this->assertEquals('commerce_recurring_order_close', $first_job->getType());
    $second_job = $queue->getBackend()->claimJob();
    $this->assertSame(['order_id' => $order->id()], $second_job->getPayload());
    $this->assertEquals('commerce_recurring_order_renew', $second_job->getType());
  }

  /**
   * Tests handling canceled subscriptions and their orders.
   */
  public function testCanceled() {
    $postpaid_subscription = Subscription::create([
      'type' => 'product_variation',
      'store_id' => $this->store->id(),
      'billing_schedule' => $this->billingSchedule,
      'uid' => $this->user,
      'payment_method' => $this->paymentMethod,
      'purchased_entity' => $this->variation,
      'title' => $this->variation->getOrderItemTitle(),
      'unit_price' => new Price('2', 'USD'),
      'state' => 'active',
      'starts' => strtotime('2019-02-15 00:00'),
    ]);
    $postpaid_subscription->save();
    $postpaid_order = $this->recurringOrderManager->startRecurring($postpaid_subscription);
    // Cancel the subscription half-way through.
    $this->rewindTime(strtotime('2019-02-21 00:00'));
    $postpaid_subscription->cancel(FALSE);
    $postpaid_subscription->save();

    // Confirm that the order is scheduling for closing, but not renewal.
    $this->rewindTime(strtotime('2019-03-01 00:00'));
    $this->container->get('commerce_recurring.cron')->run();
    /** @var \Drupal\advancedqueue\Entity\QueueInterface $queue */
    $queue = Queue::load('commerce_recurring');
    $counts = array_filter($queue->getBackend()->countJobs());
    $this->assertEquals([Job::STATE_QUEUED => 1], $counts);
    $job = $queue->getBackend()->claimJob();
    $this->assertSame(['order_id' => $postpaid_order->id()], $job->getPayload());
    $this->assertEquals('commerce_recurring_order_close', $job->getType());

    $postpaid_subscription->delete();
    $postpaid_order->delete();
    $queue->getBackend()->deleteQueue();
    $this->billingSchedule->setBillingType(BillingScheduleInterface::BILLING_TYPE_PREPAID);
    $this->billingSchedule->save();

    $prepaid_subscription = Subscription::create([
      'type' => 'product_variation',
      'store_id' => $this->store->id(),
      'billing_schedule' => $this->billingSchedule,
      'uid' => $this->user,
      'payment_method' => $this->paymentMethod,
      'purchased_entity' => $this->variation,
      'title' => $this->variation->getOrderItemTitle(),
      'unit_price' => new Price('2', 'USD'),
      'state' => 'active',
      'starts' => strtotime('2019-02-15 00:00'),
    ]);
    $prepaid_subscription->save();
    $prepaid_order = $this->recurringOrderManager->startRecurring($prepaid_subscription);
    // Schedule the subscription for cancellation.
    $prepaid_subscription->cancel();
    $prepaid_subscription->save();

    // Confirm that both the subscription and its order have been canceled.
    $this->container->get('commerce_recurring.cron')->run();
    $prepaid_subscription = $this->reloadEntity($prepaid_subscription);
    $this->assertEquals('canceled', $prepaid_subscription->getState()->getId());
    $prepaid_order = $this->reloadEntity($prepaid_order);
    $this->assertEquals('canceled', $prepaid_order->getState()->getId());
    $counts = array_filter($queue->getBackend()->countJobs());
    $this->assertEmpty($counts);
  }

  /**
   * Tests activating pending subscriptions.
   *
   * @covers ::run
   */
  public function testPending() {
    $subscription = Subscription::create([
      'type' => 'product_variation',
      'store_id' => $this->store->id(),
      'billing_schedule' => $this->billingSchedule,
      'uid' => $this->user,
      'payment_method' => $this->paymentMethod,
      'purchased_entity' => $this->variation,
      'title' => $this->variation->getOrderItemTitle(),
      'unit_price' => new Price('2', 'USD'),
      'state' => 'pending',
      'starts' => strtotime('2019-02-15 17:00'),
    ]);
    $subscription->save();

    $this->rewindTime(strtotime('2019-02-15 19:00'));
    $this->container->get('commerce_recurring.cron')->run();
    /** @var \Drupal\advancedqueue\Entity\QueueInterface $queue */
    $queue = Queue::load('commerce_recurring');
    $counts = array_filter($queue->getBackend()->countJobs());
    $this->assertEquals([Job::STATE_QUEUED => 1], $counts);
    $job = $queue->getBackend()->claimJob();
    $this->assertSame(['subscription_id' => $subscription->id()], $job->getPayload());
  }

  /**
   * {@inheritdoc}
   */
  protected function rewindTime($new_time) {
    parent::rewindTime($new_time);

    // Reload the cron service so that it gets the updated service.
    $this->container->set('commerce_recurring.cron', NULL);
    // Reload the queues so that their backends get the updated service.
    /** @var \Drupal\Core\Entity\EntityStorageInterface $queue_storage */
    $queue_storage = $this->container->get('entity_type.manager')->getStorage('advancedqueue_queue');
    $queue_storage->resetCache(['commerce_recurring']);
    $this->queue = $queue_storage->load('commerce_recurring');
  }

}
