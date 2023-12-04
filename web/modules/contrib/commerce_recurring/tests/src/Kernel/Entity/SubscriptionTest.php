<?php

namespace Drupal\Tests\commerce_recurring\Kernel\Entity;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_price\Price;
use Drupal\commerce_recurring\BillingPeriod;
use Drupal\commerce_recurring\Entity\Subscription;
use Drupal\commerce_recurring\Plugin\Commerce\SubscriptionType\SubscriptionTypeInterface;
use Drupal\commerce_recurring\ScheduledChange;
use Drupal\Tests\commerce_recurring\Kernel\RecurringKernelTestBase;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Tests the subscription entity.
 *
 * @coversDefaultClass \Drupal\commerce_recurring\Entity\Subscription
 *
 * @group commerce_recurring
 */
class SubscriptionTest extends RecurringKernelTestBase {

  /**
   * @covers ::getType
   * @covers ::getStore
   * @covers ::getStoreId
   * @covers ::getBillingSchedule
   * @covers ::setBillingSchedule
   * @covers ::getCustomer
   * @covers ::setCustomer
   * @covers ::getCustomerId
   * @covers ::setCustomerId
   * @covers ::getPaymentMethod
   * @covers ::setPaymentMethod
   * @covers ::getPaymentMethodId
   * @covers ::getTitle
   * @covers ::setTitle
   * @covers ::getQuantity
   * @covers ::setQuantity
   * @covers ::getUnitPrice
   * @covers ::setUnitPrice
   * @covers ::getState
   * @covers ::setState
   * @covers ::getInitialOrder
   * @covers ::setInitialOrder
   * @covers ::getInitialOrderId
   * @covers ::getCurrentOrder
   * @covers ::getOrderIds
   * @covers ::getOrders
   * @covers ::setOrders
   * @covers ::addOrder
   * @covers ::removeOrder
   * @covers ::hasOrder
   * @covers ::getCreatedTime
   * @covers ::setCreatedTime
   * @covers ::getNextRenewalTime
   * @covers ::setNextRenewalTime
   * @covers ::getRenewedTime
   * @covers ::setRenewedTime
   * @covers ::getTrialStartTime
   * @covers ::setTrialStartTime
   * @covers ::getTrialEndTime
   * @covers ::setTrialEndTime
   * @covers ::getStartTime
   * @covers ::setStartTime
   * @covers ::getEndTime
   * @covers ::setEndTime
   * @covers ::getCurrentBillingPeriod
   * @covers ::getScheduledChanges
   * @covers ::setScheduledChanges
   * @covers ::hasScheduledChanges
   * @covers ::addScheduledChange
   * @covers ::removeScheduledChanges
   * @covers ::hasScheduledChange
   * @covers ::applyScheduledChanges
   * @covers ::cancel
   */
  public function testSubscription() {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $initial_order */
    $initial_order = Order::create([
      'type' => 'default',
      'store_id' => $this->store,
    ]);
    $initial_order->save();
    $initial_order = $this->reloadEntity($initial_order);

    $subscription = Subscription::create([
      'type' => 'product_variation',
      'store_id' => $this->store->id(),
      'billing_schedule' => $this->billingSchedule,
      'uid' => 0,
      'payment_method' => $this->paymentMethod,
      'title' => 'My subscription',
      'purchased_entity' => $this->variation,
      'quantity' => 2,
      'unit_price' => new Price('2', 'USD'),
      'state' => 'pending',
      'created' => 1550250000,
      'trial_starts' => 1550250000 + 10,
      'trial_ends' => 1550250000 + 50,
      'starts' => 1550250000 + 10,
      'ends' => 1550250000 + 50,
    ]);
    $subscription->save();

    $subscription = Subscription::load($subscription->id());
    $this->assertInstanceOf(SubscriptionTypeInterface::class, $subscription->getType());
    $this->assertEquals('product_variation', $subscription->getType()->getPluginId());
    $this->assertEquals($this->store, $subscription->getStore());
    $this->assertEquals($this->store->id(), $subscription->getStoreId());

    $this->assertEquals($this->billingSchedule, $subscription->getBillingSchedule());

    $this->assertEquals($this->paymentMethod, $subscription->getPaymentMethod());
    $this->assertEquals($this->paymentMethod->id(), $subscription->getPaymentMethodId());

    $this->assertTrue($subscription->hasPurchasedEntity());
    $this->assertEquals($this->variation, $subscription->getPurchasedEntity());
    $this->assertEquals($this->variation->id(), $subscription->getPurchasedEntityId());

    $this->assertEquals('My subscription', $subscription->getTitle());
    $subscription->setTitle('My premium subscription');
    $this->assertEquals('My premium subscription', $subscription->getTitle());

    $this->assertEquals('2', $subscription->getQuantity());
    $subscription->setQuantity('3');
    $this->assertEquals('3', $subscription->getQuantity());

    $this->assertEquals(new Price('2', 'USD'), $subscription->getUnitPrice());
    $subscription->setUnitPrice(new Price('3', 'USD'));
    $this->assertEquals(new Price('3', 'USD'), $subscription->getUnitPrice());

    $this->assertEquals('pending', $subscription->getState()->getId());
    $subscription->setState('expired');
    $this->assertEquals('expired', $subscription->getState()->getId());

    $this->assertNull($subscription->getInitialOrder());
    $subscription->setInitialOrder($initial_order);
    $this->assertEquals($initial_order, $subscription->getInitialOrder());
    $this->assertEquals($initial_order->id(), $subscription->getInitialOrderId());

    $start_date = new DrupalDateTime('2019-10-19 15:07:12');
    $end_date = new DrupalDateTime('2019-11-19 15:07:12');
    /** @var \Drupal\commerce_recurring\BillingPeriod $billing_period */
    $billing_period = new BillingPeriod($start_date, $end_date);

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = Order::create([
      'type' => 'recurring',
      'store_id' => $this->store,
      'state' => 'draft',
      'billing_period' => $billing_period,
    ]);
    $order->save();
    $order = $this->reloadEntity($order);

    $this->assertEquals([], $subscription->getOrderIds());
    $this->assertEquals([], $subscription->getOrders());
    $this->assertEquals(NULL, $subscription->getCurrentOrder());
    $this->assertEquals(NULL, $subscription->getCurrentBillingPeriod());
    $subscription->setOrders([$order]);
    $this->assertEquals([$order->id()], $subscription->getOrderIds());
    $this->assertEquals([$order], $subscription->getOrders());
    $this->assertTrue($subscription->hasOrder($order));
    $this->assertEquals($order, $subscription->getCurrentOrder());
    $this->assertEquals($billing_period, $subscription->getCurrentBillingPeriod());
    $subscription->removeOrder($order);
    $this->assertEquals([], $subscription->getOrderIds());
    $this->assertEquals([], $subscription->getOrders());
    $this->assertFalse($subscription->hasOrder($order));
    $this->assertEquals(NULL, $subscription->getCurrentOrder());
    $this->assertEquals(NULL, $subscription->getCurrentBillingPeriod());
    $subscription->addOrder($order);
    $this->assertEquals([$order->id()], $subscription->getOrderIds());
    $this->assertEquals([$order], $subscription->getOrders());
    $this->assertTrue($subscription->hasOrder($order));
    $this->assertEquals($order, $subscription->getCurrentOrder());
    $this->assertEquals($billing_period, $subscription->getCurrentBillingPeriod());

    $new_end_date = new DrupalDateTime('2019-12-19 15:07:12');
    /** @var \Drupal\commerce_recurring\BillingPeriod $new_billing_period */
    $new_billing_period = new BillingPeriod($end_date, $new_end_date);

    /** @var \Drupal\commerce_order\Entity\OrderInterface $second_order */
    $second_order = Order::create([
      'type' => 'recurring',
      'store_id' => $this->store,
      'billing_period' => $new_billing_period,
    ]);
    $second_order->save();
    $second_order = $this->reloadEntity($second_order);
    $subscription->addOrder($second_order);
    $this->assertTrue($subscription->hasOrder($order));
    $this->assertTrue($subscription->hasOrder($second_order));
    $this->assertEquals($second_order, $subscription->getCurrentOrder());
    $this->assertEquals($new_billing_period, $subscription->getCurrentBillingPeriod());

    $this->assertEquals(1550250000, $subscription->getCreatedTime());
    $subscription->setCreatedTime(1508002101);
    $this->assertEquals(1508002101, $subscription->getCreatedTime());

    $this->assertEquals(0, $subscription->getNextRenewalTime());
    $subscription->setNextRenewalTime(1508002101);
    $this->assertEquals(1508002101, $subscription->getNextRenewalTime());
    $this->assertEquals(DrupalDateTime::createFromTimestamp($subscription->getNextRenewalTime()), $subscription->getNextRenewalDate());

    $this->assertEquals(0, $subscription->getRenewedTime());
    $subscription->setRenewedTime(123456);
    $this->assertEquals(123456, $subscription->getRenewedTime());

    $this->assertEquals(1550250000 + 10, $subscription->getTrialStartTime());
    $subscription->setTrialStartTime(1508002120);
    $this->assertEquals(1508002120, $subscription->getTrialStartTime());

    $this->assertEquals(1550250000 + 50, $subscription->getTrialEndTime());
    $subscription->setTrialEndTime(1508002920);
    $this->assertEquals(1508002920, $subscription->getTrialEndTime());

    $this->assertEquals(1550250000 + 10, $subscription->getStartTime());
    $subscription->setStartTime(1508002120);
    $this->assertEquals(1508002120, $subscription->getStartTime());

    $this->assertEquals(1550250000 + 50, $subscription->getEndTime());
    $subscription->setEndTime(1508002920);
    $this->assertEquals(1508002920, $subscription->getEndTime());

    $scheduled_changes = [new ScheduledChange('state', 'canceled', time())];
    $subscription->setScheduledChanges($scheduled_changes);
    $this->assertTrue($subscription->hasScheduledChanges());
    $this->assertTrue($subscription->hasScheduledChange('state', 'canceled'));
    $this->assertEquals($scheduled_changes, $subscription->getScheduledChanges());
    $subscription->removeScheduledChanges('state');
    $this->assertFalse($subscription->hasScheduledChanges());
    $subscription->addScheduledChange('state', 'canceled');
    $this->assertTrue($subscription->hasScheduledChange('state', 'canceled'));
    $subscription->removeScheduledChanges();
    $this->assertFalse($subscription->hasScheduledChanges());

    $subscription->setScheduledChanges($scheduled_changes);
    $this->assertNotEquals('canceled', $subscription->getState()->getId());
    $subscription->applyScheduledChanges();
    $this->assertEquals('canceled', $subscription->getState()->getId());
    $this->assertFalse($subscription->hasScheduledChanges());
    $subscription->save();

    // Manually updating the state should clear the scheduled changes.
    $subscription->addScheduledChange('state', 'pending');
    $this->assertCount(1, $subscription->getScheduledChanges());
    $subscription->setState('active');
    $subscription->save();
    $this->assertFalse($subscription->hasScheduledChanges());

    // Cancelling the subscription should result in a scheduled change.
    $subscription->cancel()->save();
    $this->assertTrue($subscription->hasScheduledChanges());
    $this->assertTrue($subscription->hasScheduledChange('state', 'canceled'));

    $subscription->setEndTime(NULL);
    $subscription->cancel(FALSE)->save();
    $this->assertEquals('canceled', $subscription->getState()->getId());
    // Assert that canceling the subscription sets the end time.
    $this->assertNotNull($subscription->getEndTime());
    $this->assertFalse($subscription->hasScheduledChanges());
  }

  /**
   * Tests the automatic timestamp generation for trials.
   *
   * @covers ::preSave
   */
  public function testTrialTimestamps() {
    $configuration = $this->billingSchedule->getPluginConfiguration();
    $configuration['trial_interval'] = [
      'number' => '1',
      'unit' => 'hour',
    ];
    $this->billingSchedule->setPluginConfiguration($configuration);
    $this->billingSchedule->save();

    $trial_subscription = Subscription::create([
      'type' => 'product_variation',
      'store_id' => $this->store->id(),
      'billing_schedule' => $this->billingSchedule,
      'uid' => 0,
      'payment_method' => $this->paymentMethod,
      'title' => 'My subscription',
      'purchased_entity' => $this->variation,
      'quantity' => 2,
      'unit_price' => new Price('2', 'USD'),
      'state' => 'trial',
      'trial_starts' => 1550250000,
    ]);
    $trial_subscription->save();

    $this->assertEquals(1550250000, $trial_subscription->getTrialStartTime());
    $this->assertEquals(1550250000 + 3600, $trial_subscription->getTrialEndTime());
    $this->assertEquals(1550250000 + 3600, $trial_subscription->getStartTime());
  }

}
