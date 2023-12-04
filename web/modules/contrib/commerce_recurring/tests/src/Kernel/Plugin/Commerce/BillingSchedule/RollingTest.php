<?php

namespace Drupal\Tests\commerce_recurring\Kernel\Plugin\Commerce\BillingSchedule;

use Drupal\commerce_recurring\Plugin\Commerce\BillingSchedule\Rolling;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the rolling billing schedule.
 *
 * @coversDefaultClass \Drupal\commerce_recurring\Plugin\Commerce\BillingSchedule\Rolling
 * @group commerce_recurring
 */
class RollingTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'commerce',
    'commerce_price',
    'commerce_order',
    'commerce_recurring',
  ];

  /**
   * @covers ::generateFirstBillingPeriod
   * @covers ::generateNextBillingPeriod
   */
  public function testGenerate() {
    $plugin = new Rolling([
      'interval' => [
        'number' => '2',
        'unit' => 'hour',
      ],
    ], '', []);
    $start_date = new DrupalDateTime('2017-03-16 10:22:30');
    $billing_period = $plugin->generateFirstBillingPeriod($start_date);
    $this->assertEquals(new DrupalDateTime('2017-03-16 10:22:30'), $billing_period->getStartDate());
    $this->assertEquals(new DrupalDateTime('2017-03-16 12:22:30'), $billing_period->getEndDate());
    $next_billing_period = $plugin->generateNextBillingPeriod($start_date, $billing_period);
    $this->assertEquals(new DrupalDateTime('2017-03-16 12:22:30'), $next_billing_period->getStartDate());
    $this->assertEquals(new DrupalDateTime('2017-03-16 14:22:30'), $next_billing_period->getEndDate());

    $plugin = new Rolling([
      'interval' => [
        'number' => '1',
        'unit' => 'week',
      ],
    ], '', []);
    $start_date = new DrupalDateTime('2017-03-16 10:22:30');
    $billing_period = $plugin->generateFirstBillingPeriod($start_date);
    $this->assertEquals(new DrupalDateTime('2017-03-16 10:22:30'), $billing_period->getStartDate());
    $this->assertEquals(new DrupalDateTime('2017-03-23 10:22:30'), $billing_period->getEndDate());
    $next_billing_period = $plugin->generateNextBillingPeriod($start_date, $billing_period);
    $this->assertEquals(new DrupalDateTime('2017-03-23 10:22:30'), $next_billing_period->getStartDate());
    $this->assertEquals(new DrupalDateTime('2017-03-30 10:22:30'), $next_billing_period->getEndDate());

    $plugin = new Rolling([
      'interval' => [
        'number' => '1',
        'unit' => 'month',
      ],
    ], '', []);
    $start_date = new DrupalDateTime('2017-01-30 10:22:30');
    $billing_period = $plugin->generateFirstBillingPeriod($start_date);
    $this->assertEquals(new DrupalDateTime('2017-01-30 10:22:30'), $billing_period->getStartDate());
    $this->assertEquals(new DrupalDateTime('2017-02-28 10:22:30'), $billing_period->getEndDate());
    $next_billing_period = $plugin->generateNextBillingPeriod($start_date, $billing_period);
    $this->assertEquals(new DrupalDateTime('2017-02-28 10:22:30'), $next_billing_period->getStartDate());
    $this->assertEquals(new DrupalDateTime('2017-03-30 10:22:30'), $next_billing_period->getEndDate());

    $plugin = new Rolling([
      'interval' => [
        'number' => '1',
        'unit' => 'year',
      ],
    ], '', []);
    $start_date = new DrupalDateTime('2017-03-16 10:22:30');
    $billing_period = $plugin->generateFirstBillingPeriod($start_date);
    $this->assertEquals(new DrupalDateTime('2017-03-16 10:22:30'), $billing_period->getStartDate());
    $this->assertEquals(new DrupalDateTime('2018-03-16 10:22:30'), $billing_period->getEndDate());
    $next_billing_period = $plugin->generateNextBillingPeriod($start_date, $billing_period);
    $this->assertEquals(new DrupalDateTime('2018-03-16 10:22:30'), $next_billing_period->getStartDate());
    $this->assertEquals(new DrupalDateTime('2019-03-16 10:22:30'), $next_billing_period->getEndDate());
  }

}
