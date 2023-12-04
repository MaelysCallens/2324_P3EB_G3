<?php

namespace Drupal\Tests\commerce_recurring\Kernel\Plugin\Commerce\SubscriptionType;

use Drupal\commerce_price\Price;
use Drupal\commerce_recurring\BillingPeriod;
use Drupal\commerce_recurring\Charge;
use Drupal\commerce_recurring\Entity\BillingSchedule;
use Drupal\commerce_recurring\Entity\Subscription;
use Drupal\Tests\commerce_recurring\Kernel\RecurringKernelTestBase;

/**
 * Tests the product variation subscription type.
 *
 * @coversDefaultClass \Drupal\commerce_recurring\Plugin\Commerce\SubscriptionType\ProductVariation
 * @group commerce_recurring
 */
class ProductVariationTest extends RecurringKernelTestBase {

  /**
   * @covers ::getLabel
   * @covers ::getPurchasableEntityTypeId
   */
  public function testGetters() {
    $plugin_manager = $this->container->get('plugin.manager.commerce_subscription_type');
    /** @var \Drupal\commerce_recurring\Plugin\Commerce\SubscriptionType\SubscriptionTypeInterface $plugin */
    $plugin = $plugin_manager->createInstance('product_variation');

    $this->assertEquals('Product variation', $plugin->getLabel());
    $this->assertEquals('commerce_product_variation', $plugin->getPurchasableEntityTypeId());
  }

  /**
   * @covers ::collectTrialCharges
   */
  public function testTrialCharges() {
    $subscription = Subscription::create([
      'type' => 'product_variation',
      'store_id' => $this->store->id(),
      'billing_schedule' => $this->billingSchedule,
      'uid' => $this->user,
      'purchased_entity' => $this->variation,
      'title' => 'My subscription',
      'quantity' => 2,
      'unit_price' => new Price('49.99', 'USD'),
      'state' => 'trial',
      'trial_starts' => strtotime('2019-02-24 17:30:00'),
    ]);
    $subscription->save();
    $trial_period = new BillingPeriod($subscription->getTrialStartDate(), $subscription->getTrialEndDate());
    $start_date = $subscription->getStartDate();
    $first_billing_period = $this->billingSchedule->getPlugin()->generateFirstBillingPeriod($start_date);
    // The billing schedule is fixed, the first period starts before the charge.
    $expected_billing_period = new BillingPeriod($subscription->getStartDate(), $first_billing_period->getEndDate());

    // Postpaid.
    $charges = $subscription->getType()->collectTrialCharges($subscription, $trial_period);
    $this->assertCount(1, $charges);
    $base_charge = reset($charges);
    $this->assertInstanceOf(Charge::class, $base_charge);
    $this->assertEquals($this->variation, $base_charge->getPurchasedEntity());
    $this->assertEquals($subscription->getTitle(), $base_charge->getTitle());
    $this->assertEquals($subscription->getQuantity(), $base_charge->getQuantity());
    $this->assertEquals(new Price('0', 'USD'), $base_charge->getUnitPrice());
    $this->assertEquals($trial_period, $base_charge->getBillingPeriod());

    // Prepaid.
    $this->billingSchedule->setBillingType(BillingSchedule::BILLING_TYPE_PREPAID);
    $this->billingSchedule->save();
    $charges = $subscription->getType()->collectTrialCharges($subscription, $trial_period);
    $this->assertCount(1, $charges);
    $base_charge = reset($charges);
    $this->assertInstanceOf(Charge::class, $base_charge);
    $this->assertEquals($this->variation, $base_charge->getPurchasedEntity());
    $this->assertEquals($subscription->getTitle(), $base_charge->getTitle());
    $this->assertEquals($subscription->getQuantity(), $base_charge->getQuantity());
    $this->assertEquals($subscription->getUnitPrice(), $base_charge->getUnitPrice());
    $this->assertEquals($expected_billing_period, $base_charge->getBillingPeriod());
  }

  /**
   * @covers ::collectCharges
   */
  public function testCharges() {
    $subscription = Subscription::create([
      'type' => 'product_variation',
      'store_id' => $this->store->id(),
      'billing_schedule' => $this->billingSchedule,
      'uid' => $this->user,
      'purchased_entity' => $this->variation,
      'title' => 'My subscription',
      'quantity' => 2,
      'unit_price' => new Price('49.99', 'USD'),
      'state' => 'active',
      'starts' => strtotime('2019-02-24 17:30:00'),
    ]);
    $subscription->save();
    $start_date = $subscription->getStartDate();
    $billing_period = $this->billingSchedule->getPlugin()->generateFirstBillingPeriod($start_date);
    $next_billing_period = $this->billingSchedule->getPlugin()->generateNextBillingPeriod($start_date, $billing_period);

    // Postpaid.
    $charges = $subscription->getType()->collectCharges($subscription, $billing_period);
    $this->assertCount(1, $charges);
    $base_charge = reset($charges);
    $this->assertInstanceOf(Charge::class, $base_charge);
    $this->assertEquals($this->variation, $base_charge->getPurchasedEntity());
    $this->assertEquals($subscription->getTitle(), $base_charge->getTitle());
    $this->assertEquals($subscription->getQuantity(), $base_charge->getQuantity());
    $this->assertEquals($subscription->getUnitPrice(), $base_charge->getUnitPrice());
    $this->assertEquals(new BillingPeriod($start_date, $billing_period->getEndDate()), $base_charge->getBillingPeriod());

    // Prepaid.
    $this->billingSchedule->setBillingType(BillingSchedule::BILLING_TYPE_PREPAID);
    $this->billingSchedule->save();
    $charges = $subscription->getType()->collectCharges($subscription, $billing_period);
    $this->assertCount(1, $charges);
    $base_charge = reset($charges);
    $this->assertInstanceOf(Charge::class, $base_charge);
    $this->assertEquals($this->variation, $base_charge->getPurchasedEntity());
    $this->assertEquals($subscription->getTitle(), $base_charge->getTitle());
    $this->assertEquals($subscription->getQuantity(), $base_charge->getQuantity());
    $this->assertEquals($subscription->getUnitPrice(), $base_charge->getUnitPrice());
    $this->assertEquals($next_billing_period, $base_charge->getBillingPeriod());
  }

  /**
   * @covers ::collectCharges
   */
  public function testCanceledCharges() {
    $subscription = Subscription::create([
      'type' => 'product_variation',
      'store_id' => $this->store->id(),
      'billing_schedule' => $this->billingSchedule,
      'uid' => $this->user,
      'purchased_entity' => $this->variation,
      'title' => 'My subscription',
      'quantity' => 2,
      'unit_price' => new Price('49.99', 'USD'),
      'starts' => strtotime('2019-02-24 17:30:00'),
      'ends' => strtotime('2019-02-24 17:45:00'),
    ]);
    $subscription->save();
    $start_date = $subscription->getStartDate();
    $end_date = $subscription->getEndDate();
    $billing_period = $this->billingSchedule->getPlugin()->generateFirstBillingPeriod($start_date);

    // Postpaid.
    $charges = $subscription->getType()->collectCharges($subscription, $billing_period);
    $this->assertCount(1, $charges);
    $base_charge = reset($charges);
    $this->assertInstanceOf(Charge::class, $base_charge);
    $this->assertEquals($this->variation, $base_charge->getPurchasedEntity());
    $this->assertEquals($subscription->getTitle(), $base_charge->getTitle());
    $this->assertEquals($subscription->getQuantity(), $base_charge->getQuantity());
    $this->assertEquals($subscription->getUnitPrice(), $base_charge->getUnitPrice());
    $this->assertEquals(new BillingPeriod($start_date, $end_date), $base_charge->getBillingPeriod());

    // Prepaid.
    $this->billingSchedule->setBillingType(BillingSchedule::BILLING_TYPE_PREPAID);
    $this->billingSchedule->save();
    $charges = $subscription->getType()->collectCharges($subscription, $billing_period);
    $this->assertCount(0, $charges);
  }

}
