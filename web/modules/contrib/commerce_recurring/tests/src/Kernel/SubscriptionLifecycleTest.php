<?php

namespace Drupal\Tests\commerce_recurring\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderType;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_payment\Entity\PaymentMethod;
use Drupal\commerce_recurring\Entity\Subscription;

/**
 * Tests the subscription lifecycle.
 *
 * @group commerce_recurring
 */
class SubscriptionLifecycleTest extends RecurringKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $order_type = OrderType::load('default');
    $order_type->setWorkflowId('order_default_validation');
    $order_type->save();
  }

  /**
   * Tests the subscription lifecycle, without a free trial.
   *
   * Placing an initial order should create an active subscription.
   * Canceling the initial order should cancel the subscription.
   */
  public function testLifecycle() {
    $initial_order = $this->createInitialOrder();

    // Confirm that placing the initial order with no payment method doesn't
    // create the subscription.
    $initial_order->getState()->applyTransitionById('place');
    $initial_order->save();
    $subscriptions = Subscription::loadMultiple();
    $this->assertCount(0, $subscriptions);

    // Confirm that placing an order with a payment method creates an
    // active subscription.
    $initial_order->set('state', 'draft');
    $initial_order->set('payment_method', $this->paymentMethod);
    $initial_order->save();
    $initial_order->getState()->applyTransitionById('place');
    $initial_order->save();
    $subscriptions = Subscription::loadMultiple();
    $this->assertCount(1, $subscriptions);
    /** @var \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription */
    $subscription = reset($subscriptions);

    $this->assertEquals('active', $subscription->getState()->getId());
    $this->assertEquals($this->store->id(), $subscription->getStoreId());
    $this->assertEquals($this->billingSchedule->id(), $subscription->getBillingSchedule()->id());
    $this->assertEquals($this->user->id(), $subscription->getCustomerId());
    $this->assertEquals($this->paymentMethod->id(), $subscription->getPaymentMethod()->id());
    $this->assertEquals($this->variation->id(), $subscription->getPurchasedEntityId());
    $this->assertEquals($this->variation->getOrderItemTitle(), $subscription->getTitle());
    $this->assertEquals('3', $subscription->getQuantity());
    $this->assertEquals($this->variation->getPrice(), $subscription->getUnitPrice());
    $this->assertEquals($initial_order->id(), $subscription->getInitialOrderId());
    $orders = $subscription->getOrders();
    $this->assertCount(1, $orders);
    $order = reset($orders);
    $this->assertFalse($order->getTotalPrice()->isZero());
    $this->assertEquals('recurring', $order->bundle());
    // Confirm that the recurring order has an order item for the subscription.
    $order_items = $order->getItems();
    $this->assertCount(1, $order_items);
    $order_item = reset($order_items);
    $this->assertEquals($subscription->id(), $order_item->get('subscription')->target_id);

    // Test initial order cancellation.
    $initial_order->getState()->applyTransitionById('cancel');
    $initial_order->save();
    $subscription = $this->reloadEntity($subscription);
    $this->assertEquals('canceled', $subscription->getState()->getId());
  }

  /**
   * Tests the subscription lifecycle, with a free trial.
   *
   * Placing an initial order should create a trial subscription.
   * Canceling the initial order should cancel the trial.
   */
  public function testLifecycleWithTrial() {
    // Rewind the time so that the trial duration is not affected by daylight
    // savings.
    // If the daylight savings occur during the trial, then the trial duration
    // could be 1hour less/more than expected, so rewinding the time helps us
    // ensuring the trial is exactly 10 days.
    $this->rewindTime(strtotime('2021-01-01 00:00'));
    $initial_order = $this->createInitialOrder(TRUE);

    // Confirm that placing the initial order creates a trial subscription,
    // even without a payment method.
    $initial_order->getState()->applyTransitionById('place');
    $initial_order->save();
    $subscriptions = Subscription::loadMultiple();
    $this->assertCount(1, $subscriptions);
    /** @var \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription */
    $subscription = reset($subscriptions);

    $this->assertEquals('trial', $subscription->getState()->getId());
    $this->assertEquals($this->store->id(), $subscription->getStoreId());
    $this->assertEquals($this->billingSchedule->id(), $subscription->getBillingSchedule()->id());
    $this->assertEquals($this->user->id(), $subscription->getCustomerId());
    $this->assertNull($subscription->getPaymentMethod());
    $this->assertEquals($this->variation->id(), $subscription->getPurchasedEntityId());
    $this->assertEquals($this->variation->getOrderItemTitle(), $subscription->getTitle());
    $this->assertEquals('3', $subscription->getQuantity());
    $this->assertEquals($this->variation->getPrice(), $subscription->getUnitPrice());
    $this->assertEquals($initial_order->id(), $subscription->getInitialOrderId());
    $this->assertNotEmpty($subscription->getTrialStartTime());
    $this->assertNotEmpty($subscription->getTrialEndTime());
    $this->assertEquals(864000, $subscription->getTrialEndTime() - $subscription->getTrialStartTime());
    $orders = $subscription->getOrders();
    $this->assertCount(1, $orders);
    $order = reset($orders);
    $this->assertEquals('recurring', $order->bundle());
    $this->assertTrue($order->getTotalPrice()->isZero());
    // Confirm that the recurring order has an order item for the subscription.
    $order_items = $order->getItems();
    $this->assertCount(1, $order_items);
    $order_item = reset($order_items);
    $this->assertEquals($subscription->id(), $order_item->get('subscription')->target_id);

    // Test initial order cancellation.
    $initial_order->getState()->applyTransitionById('cancel');
    $initial_order->save();
    $subscription = $this->reloadEntity($subscription);
    $this->assertEquals('canceled', $subscription->getState()->getId());
  }

  /**
   * Tests that updating an active subscription also updates the current order.
   */
  public function testSubscriptionUpdates() {
    $initial_order = $this->createInitialOrder();

    // Set a payment gateway and place the order so the subscription gets
    // created.
    $initial_order->set('payment_method', $this->paymentMethod);
    $initial_order->getState()->applyTransitionById('place');
    $initial_order->save();

    /** @var \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription */
    $subscription = Subscription::load(1);
    $this->assertEquals('active', $subscription->getState()->getId());

    /** @var \Drupal\commerce_order\Entity\OrderInterface $recurring_order */
    $recurring_order = Order::load(2);
    $this->assertEquals('recurring', $recurring_order->bundle());
    $this->assertEquals($recurring_order->id(), $subscription->getCurrentOrder()->id());

    // Check that updating the payment method of the subscription also updates
    // the current recurring order.
    $new_payment_gateway = PaymentGateway::create([
      'id' => 'example_2',
      'label' => 'Example 2',
      'plugin' => 'example_onsite',
    ]);
    $new_payment_gateway->save();
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $new_payment_method */
    $new_payment_method = PaymentMethod::create([
      'type' => 'credit_card',
      'payment_gateway' => $new_payment_gateway,
      'card_type' => 'visa',
      'uid' => $this->user->id(),
    ]);
    $new_payment_method->save();

    $subscription->setPaymentMethod($new_payment_method);
    $subscription->save();
    $recurring_order = $this->reloadEntity($recurring_order);
    $this->assertEquals($new_payment_method->id(), $subscription->getPaymentMethodId());
    $this->assertEquals($new_payment_method->id(), $recurring_order->get('payment_method')->target_id);
    $this->assertEquals($new_payment_gateway->id(), $recurring_order->get('payment_gateway')->target_id);
  }

  /**
   * Tests the subscription deletion.
   *
   * Deleting the subscription will also delete its recurring orders.
   */
  public function testSubscriptionDelete() {
    $initial_order = $this->createInitialOrder();

    // Confirm that placing the initial order with no payment method doesn't
    // create the subscription.
    $initial_order->getState()->applyTransitionById('place');
    $initial_order->save();
    $subscriptions = Subscription::loadMultiple();
    $this->assertCount(0, $subscriptions);

    // Confirm that placing an order with a payment method creates an
    // active subscription.
    $initial_order->set('state', 'draft');
    $initial_order->set('payment_method', $this->paymentMethod);
    $initial_order->save();
    $initial_order->getState()->applyTransitionById('place');
    $initial_order->save();
    $subscriptions = Subscription::loadMultiple();
    $this->assertCount(1, $subscriptions);
    /** @var \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription */
    $subscription = reset($subscriptions);

    // Confirm that a recurring order was created for the subscription.
    $orders = $subscription->getOrders();
    $this->assertCount(1, $orders);
    $order = reset($orders);
    $this->assertEquals('recurring', $order->bundle());

    // Confirm that the recurring order has an order item for the subscription.
    $order_items = $order->getItems();
    $this->assertCount(1, $order_items);
    $order_item = reset($order_items);
    $this->assertEquals($subscription->id(), $order_item->get('subscription')->target_id);

    // Test deleting the subscription.
    $subscription->delete();
    $subscription = $this->reloadEntity($subscription);
    $order_item = $this->reloadEntity($order_item);
    $order = $this->reloadEntity($order);
    $this->assertNull($subscription);
    $this->assertNull($order_item);
    $this->assertNull($order);
  }

}
