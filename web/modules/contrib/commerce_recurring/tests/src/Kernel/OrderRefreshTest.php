<?php

namespace Drupal\Tests\commerce_recurring\Kernel;

use Drupal\commerce_price\Price;
use Drupal\commerce_recurring\Entity\BillingScheduleInterface;
use Drupal\commerce_recurring\Entity\Subscription;

/**
 * @coversDefaultClass \Drupal\commerce_recurring\RecurringOrderProcessor
 * @group commerce_recurring
 */
class OrderRefreshTest extends RecurringKernelTestBase {

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
   * @covers ::process
   */
  public function testRefresh() {
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
      'starts' => strtotime('2019-02-01 00:00'),
    ]);
    $subscription->save();
    $order = $this->recurringOrderManager->startRecurring($subscription);
    $this->assertEquals(new Price('2', 'USD'), $order->getTotalPrice());

    $subscription->setUnitPrice(new Price('3', 'USD'));
    $subscription->save();
    // Save the order to refresh it.
    $order->save();

    $this->assertEquals(new Price('3', 'USD'), $order->getTotalPrice());

    // Confirm that the order is canceled on refresh if no charges remain.
    $this->billingSchedule->setBillingType(BillingScheduleInterface::BILLING_TYPE_PREPAID);
    $this->billingSchedule->save();
    $subscription->cancel()->save();
    $order->save();

    $this->assertEquals('canceled', $order->getState()->getId());
    $this->assertEmpty($order->getItems());
  }

}
