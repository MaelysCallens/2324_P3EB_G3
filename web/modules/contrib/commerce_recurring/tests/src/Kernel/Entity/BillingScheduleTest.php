<?php

namespace Drupal\Tests\commerce_recurring\Kernel\Entity;

use Drupal\commerce_recurring\Entity\BillingSchedule;
use Drupal\commerce_recurring\Entity\BillingScheduleInterface;
use Drupal\commerce_recurring\Plugin\Commerce\BillingSchedule\Rolling;
use Drupal\commerce_recurring\Plugin\Commerce\Prorater\Proportional;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the billing schedule entity.
 *
 * @coversDefaultClass \Drupal\commerce_recurring\Entity\BillingSchedule
 *
 * @group commerce_recurring
 */
class BillingScheduleTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'commerce',
    'commerce_price',
    'commerce_order',
    'commerce_recurring',
    'profile',
  ];

  /**
   * @covers ::id
   * @covers ::label
   * @covers ::getDisplayLabel
   * @covers ::setDisplayLabel
   * @covers ::getBillingType
   * @covers ::setBillingType
   * @covers ::getRetrySchedule
   * @covers ::setRetrySchedule
   * @covers ::getUnpaidSubscriptionState
   * @covers ::setUnpaidSubscriptionState
   * @covers ::getPlugin
   * @covers ::getPluginId
   * @covers ::getPluginConfiguration
   * @covers ::setPluginConfiguration
   * @covers ::getProrater
   * @covers ::getProraterId
   */
  public function testBillingSchedule() {
    BillingSchedule::create([
      'id' => 'test_id',
      'label' => 'Test label',
      'displayLabel' => 'Test customer label',
      'billingType' => BillingScheduleInterface::BILLING_TYPE_POSTPAID,
      'plugin' => 'rolling',
      'configuration' => [
        'interval' => [
          'number' => '1',
          'unit' => 'month',
        ],
      ],
      'prorater' => 'proportional',
      'proraterConfiguration' => [],
    ])->save();

    $billing_schedule = BillingSchedule::load('test_id');
    $this->assertEquals('test_id', $billing_schedule->id());
    $this->assertEquals('Test label', $billing_schedule->label());

    $this->assertEquals('Test customer label', $billing_schedule->getDisplayLabel());
    $billing_schedule->setDisplayLabel('Test customer label (Modified)');
    $this->assertEquals('Test customer label (Modified)', $billing_schedule->getDisplayLabel());

    $this->assertEquals(BillingScheduleInterface::BILLING_TYPE_POSTPAID, $billing_schedule->getBillingType());
    $billing_schedule->setBillingType(BillingScheduleInterface::BILLING_TYPE_PREPAID);
    $this->assertEquals(BillingScheduleInterface::BILLING_TYPE_PREPAID, $billing_schedule->getBillingType());

    $this->assertEquals([1, 3, 5], $billing_schedule->getRetrySchedule());
    $billing_schedule->setRetrySchedule([2, 4, 6]);
    $this->assertEquals([2, 4, 6], $billing_schedule->getRetrySchedule());

    $this->assertEquals('canceled', $billing_schedule->getUnpaidSubscriptionState());
    $billing_schedule->setUnpaidSubscriptionState('active');
    $this->assertEquals('active', $billing_schedule->getUnpaidSubscriptionState());

    $this->assertEquals('rolling', $billing_schedule->getPluginId());
    $this->assertEquals([
      'trial_interval' => [],
      'interval' => [
        'number' => '1',
        'unit' => 'month',
      ],
    ], $billing_schedule->getPluginConfiguration());
    $billing_schedule->setPluginConfiguration([
      'trial_interval' => [
        'number' => '14',
        'unit' => 'day',
      ],
      'interval' => [
        'number' => '2',
        'unit' => 'year',
      ],
    ]);
    $this->assertEquals([
      'trial_interval' => [
        'number' => '14',
        'unit' => 'day',
      ],
      'interval' => [
        'number' => '2',
        'unit' => 'year',
      ],
    ], $billing_schedule->getPluginConfiguration());
    $plugin = $billing_schedule->getPlugin();
    $this->assertInstanceOf(Rolling::class, $plugin);
    $this->assertEquals($billing_schedule->getPluginId(), $plugin->getPluginId());
    $this->assertEquals($billing_schedule->getPluginConfiguration(), $plugin->getConfiguration());

    $this->assertEquals('proportional', $billing_schedule->getProraterId());
    $prorater = $billing_schedule->getProrater();
    $this->assertInstanceOf(Proportional::class, $prorater);
    $this->assertEquals($billing_schedule->getProraterId(), $prorater->getPluginId());
  }

}
