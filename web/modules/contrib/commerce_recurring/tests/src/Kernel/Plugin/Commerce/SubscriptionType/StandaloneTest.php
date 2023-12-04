<?php

namespace Drupal\Tests\commerce_recurring\Kernel\Plugin\Commerce\SubscriptionType;

use Drupal\commerce_price\Price;
use Drupal\commerce_recurring\BillingPeriod;
use Drupal\commerce_recurring\Charge;
use Drupal\commerce_recurring\Entity\Subscription;
use Drupal\Tests\commerce_recurring\Kernel\RecurringKernelTestBase;

/**
 * Tests the standalone subscription type.
 *
 * @coversDefaultClass \Drupal\commerce_recurring\Plugin\Commerce\SubscriptionType\Standalone
 * @group commerce_recurring
 */
class StandaloneTest extends RecurringKernelTestBase {

  /**
   * @covers ::getLabel
   * @covers ::getPurchasableEntityTypeId
   */
  public function testGetters() {
    $plugin_manager = $this->container->get('plugin.manager.commerce_subscription_type');
    /** @var \Drupal\commerce_recurring\Plugin\Commerce\SubscriptionType\SubscriptionTypeInterface $plugin */
    $plugin = $plugin_manager->createInstance('standalone');

    $this->assertEquals('Standalone', $plugin->getLabel());
    $this->assertNull($plugin->getPurchasableEntityTypeId());
  }

  /**
   * @covers ::collectCharges
   */
  public function testCharges() {
    // Confirms that the Standalone plugin outputs the correct charge.
    // The full prepaid/postpaid logic is tested in ProductVariationTest.
    $subscription = Subscription::create([
      'type' => 'standalone',
      'store_id' => $this->store->id(),
      'billing_schedule' => $this->billingSchedule,
      'uid' => $this->user,
      'title' => 'My donation',
      'quantity' => 1,
      'unit_price' => new Price('19', 'USD'),
      'starts' => strtotime('2019-02-24 17:00:00'),
    ]);
    $subscription->save();
    $start_date = $subscription->getStartDate();
    $billing_period = $this->billingSchedule->getPlugin()->generateFirstBillingPeriod($start_date);
    // The billing schedule is fixed, the first period starts before the charge.
    $expected_billing_period = new BillingPeriod($subscription->getStartDate(), $billing_period->getEndDate());

    $charges = $subscription->getType()->collectCharges($subscription, $billing_period);
    $this->assertCount(1, $charges);
    $base_charge = reset($charges);
    $this->assertInstanceOf(Charge::class, $base_charge);
    $this->assertNull($base_charge->getPurchasedEntity());
    $this->assertEquals($subscription->getTitle(), $base_charge->getTitle());
    $this->assertEquals($subscription->getQuantity(), $base_charge->getQuantity());
    $this->assertEquals($subscription->getUnitPrice(), $base_charge->getUnitPrice());
    $this->assertEquals($expected_billing_period, $base_charge->getBillingPeriod());
  }

}
