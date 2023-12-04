<?php

namespace Drupal\Tests\commerce_recurring\Kernel;

use Drupal\commerce_recurring\ScheduledChange;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\commerce_recurring\ScheduledChange
 * @group commerce_recurring
 */
class ScheduledChangeTest extends KernelTestBase {

  /**
   * @covers ::__construct
   * @covers ::getFieldName
   * @covers ::getValue
   * @covers ::getCreatedTime
   */
  public function testScheduledChange() {
    $created = time();
    $scheduled_change = new ScheduledChange('state', 'canceled', $created);

    $this->assertEquals('state', $scheduled_change->getFieldName());
    $this->assertEquals('canceled', $scheduled_change->getValue());
    $this->assertEquals($created, $scheduled_change->getCreatedTime());
  }

}
