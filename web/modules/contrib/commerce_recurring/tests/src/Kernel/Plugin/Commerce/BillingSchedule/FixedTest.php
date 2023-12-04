<?php

namespace Drupal\Tests\commerce_recurring\Kernel\Plugin\Commerce\BillingSchedule;

use Drupal\commerce_recurring\Plugin\Commerce\BillingSchedule\Fixed;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the fixed billing schedule.
 *
 * @coversDefaultClass \Drupal\commerce_recurring\Plugin\Commerce\BillingSchedule\Fixed
 * @group commerce_recurring
 */
class FixedTest extends KernelTestBase {

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
    $plugin = new Fixed([
      'interval' => [
        'number' => '2',
        'unit' => 'hour',
      ],
    ], '', []);
    $start_date = new DrupalDateTime('2017-03-16 10:22:30');
    $billing_period = $plugin->generateFirstBillingPeriod($start_date);
    $this->assertEquals(new DrupalDateTime('2017-03-16 10:00:00'), $billing_period->getStartDate());
    $this->assertEquals(new DrupalDateTime('2017-03-16 12:00:00'), $billing_period->getEndDate());
    $next_billing_period = $plugin->generateNextBillingPeriod($start_date, $billing_period);
    $this->assertEquals(new DrupalDateTime('2017-03-16 12:00:00'), $next_billing_period->getStartDate());
    $this->assertEquals(new DrupalDateTime('2017-03-16 14:00:00'), $next_billing_period->getEndDate());

    $plugin = new Fixed([
      'interval' => [
        'number' => '1',
        'unit' => 'month',
      ],
    ], '', []);
    $start_date = new DrupalDateTime('2017-03-16 10:22:30');
    $billing_period = $plugin->generateFirstBillingPeriod($start_date);
    $this->assertEquals(new DrupalDateTime('2017-03-01 00:00:00'), $billing_period->getStartDate());
    $this->assertEquals(new DrupalDateTime('2017-04-01 00:00:00'), $billing_period->getEndDate());
    $next_billing_period = $plugin->generateNextBillingPeriod($start_date, $billing_period);
    $this->assertEquals(new DrupalDateTime('2017-04-01 00:00:00'), $next_billing_period->getStartDate());
    $this->assertEquals(new DrupalDateTime('2017-05-01 00:00:00'), $next_billing_period->getEndDate());

    $plugin = new Fixed([
      'interval' => [
        'number' => '1',
        'unit' => 'year',
      ],
    ], '', []);
    $start_date = new DrupalDateTime('2017-03-16 10:22:30');
    $billing_period = $plugin->generateFirstBillingPeriod($start_date);
    $this->assertEquals(new DrupalDateTime('2017-01-01 00:00:00'), $billing_period->getStartDate());
    $this->assertEquals(new DrupalDateTime('2018-01-01 00:00:00'), $billing_period->getEndDate());
    $next_billing_period = $plugin->generateNextBillingPeriod($start_date, $billing_period);
    $this->assertEquals(new DrupalDateTime('2018-01-01 00:00:00'), $next_billing_period->getStartDate());
    $this->assertEquals(new DrupalDateTime('2019-01-01 00:00:00'), $next_billing_period->getEndDate());
  }

  /**
   * @covers ::generateFirstBillingPeriod
   */
  public function testCustomStart() {
    // Hourly intervals should be unaffected.
    $plugin = new Fixed([
      'interval' => [
        'number' => '2',
        'unit' => 'hour',
      ],
      'start_month' => '2',
      'start_day' => '3',
    ], '', []);
    $start_date = new DrupalDateTime('2017-03-16 10:22:30');
    $billing_period = $plugin->generateFirstBillingPeriod($start_date);
    $this->assertEquals(new DrupalDateTime('2017-03-16 10:00:00'), $billing_period->getStartDate());
    $this->assertEquals(new DrupalDateTime('2017-03-16 12:00:00'), $billing_period->getEndDate());

    // Monthly intervals should ignore the start_month.
    $plugin = new Fixed([
      'interval' => [
        'number' => '1',
        'unit' => 'month',
      ],
      'start_month' => '2',
      'start_day' => '3',
    ], '', []);
    $start_date = new DrupalDateTime('2017-03-16 10:22:30');
    $billing_period = $plugin->generateFirstBillingPeriod($start_date);
    $this->assertEquals(new DrupalDateTime('2017-03-03 00:00:00'), $billing_period->getStartDate());
    $this->assertEquals(new DrupalDateTime('2017-04-03 00:00:00'), $billing_period->getEndDate());

    // Subscription started before start_day.
    $plugin = new Fixed([
      'interval' => [
        'number' => '1',
        'unit' => 'month',
      ],
      'start_month' => '2',
      'start_day' => '3',
    ], '', []);
    $start_date = new DrupalDateTime('2017-03-02 10:22:30');
    $billing_period = $plugin->generateFirstBillingPeriod($start_date);
    $this->assertEquals(new DrupalDateTime('2017-02-03 00:00:00'), $billing_period->getStartDate());
    $this->assertEquals(new DrupalDateTime('2017-03-03 00:00:00'), $billing_period->getEndDate());

    $plugin = new Fixed([
      'interval' => [
        'number' => '2',
        'unit' => 'year',
      ],
      'start_month' => '2',
      'start_day' => '3',
    ], '', []);
    $start_date = new DrupalDateTime('2017-03-16 10:22:30');
    $billing_period = $plugin->generateFirstBillingPeriod($start_date);
    $this->assertEquals(new DrupalDateTime('2017-02-03 00:00:00'), $billing_period->getStartDate());
    $this->assertEquals(new DrupalDateTime('2019-02-03 00:00:00'), $billing_period->getEndDate());

    // Subscription started before start_day/start_month.
    $plugin = new Fixed([
      'interval' => [
        'number' => '2',
        'unit' => 'year',
      ],
      'start_month' => '2',
      'start_day' => '3',
    ], '', []);
    $start_date = new DrupalDateTime('2017-01-16 10:22:30');
    $billing_period = $plugin->generateFirstBillingPeriod($start_date);
    $this->assertEquals(new DrupalDateTime('2015-02-03 00:00:00'), $billing_period->getStartDate());
    $this->assertEquals(new DrupalDateTime('2017-02-03 00:00:00'), $billing_period->getEndDate());
  }

}
