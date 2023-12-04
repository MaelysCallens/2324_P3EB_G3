<?php

namespace Drupal\Tests\commerce_recurring\Kernel;

use Drupal\commerce_recurring\BillingPeriod;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\commerce_recurring\BillingPeriod
 * @group commerce_recurring
 */
class BillingPeriodTest extends KernelTestBase {

  /**
   * @covers ::__construct
   * @covers ::getStartDate
   * @covers ::getEndDate
   * @covers ::getDuration
   * @covers ::contains
   */
  public function testBillingPeriod() {
    $start_date = new DrupalDateTime('2019-01-01 00:00:00');
    $end_date = new DrupalDateTime('2019-01-02 00:00:00');
    $billing_period = new BillingPeriod($start_date, $end_date);

    $this->assertEquals($start_date, $billing_period->getStartDate());
    $this->assertEquals($end_date, $billing_period->getEndDate());
    $this->assertEquals(86400, $billing_period->getDuration());

    $contained_date = new DrupalDateTime('2019-01-01 11:00:00');
    $not_contained_date = new DrupalDateTime('2019-01-03 00:00:00');
    $this->assertTrue($billing_period->contains($contained_date));
    $this->assertFalse($billing_period->contains($not_contained_date));
  }

}
