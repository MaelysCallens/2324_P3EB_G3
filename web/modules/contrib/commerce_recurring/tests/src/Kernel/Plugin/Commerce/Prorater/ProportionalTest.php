<?php

namespace Drupal\Tests\commerce_recurring\Kernel\Plugin\Commerce\Prorater;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_price\Price;
use Drupal\commerce_recurring\BillingPeriod;
use Drupal\Tests\commerce_recurring\Kernel\RecurringKernelTestBase;

/**
 * Tests the proportional prorater.
 *
 * @coversDefaultClass \Drupal\commerce_recurring\Plugin\Commerce\Prorater\Proportional
 * @group commerce_recurring
 */
class ProportionalTest extends RecurringKernelTestBase {

  /**
   * The prorater manager.
   *
   * @var \Drupal\commerce_recurring\ProraterManager
   */
  protected $proraterManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->proraterManager = $this->container->get('plugin.manager.commerce_prorater');
  }

  /**
   * @covers ::prorateOrderItem
   * @dataProvider testProratingProvider
   */
  public function testProrating($expected_price, $billing_period_start_time) {
    /** @var \Drupal\commerce_recurring\Plugin\Commerce\Prorater\ProraterInterface $plugin */
    $plugin = $this->proraterManager->createInstance('proportional');

    $order_item = OrderItem::create([
      'type' => 'default',
      'title' => $this->variation->getOrderItemTitle(),
      'purchased_entity' => $this->variation->id(),
      'unit_price' => new Price('30', 'USD'),
    ]);
    $order_item->save();
    $billing_period = new BillingPeriod(
      new DrupalDateTime($billing_period_start_time),
      new DrupalDateTime('2019-06-01 18:00:00')
    );
    $full_billing_period = new BillingPeriod(
      new DrupalDateTime('2019-06-01 17:00:00'),
      new DrupalDateTime('2019-06-01 18:00:00')
    );
    $prorated_unit_price = $plugin->prorateOrderItem($order_item, $billing_period, $full_billing_period);
    $this->assertEquals($expected_price, $prorated_unit_price);
  }

  /**
   * Data provider for testProrating().
   */
  public function testProratingProvider() {
    return [
      'full hour, full price' => [
        new Price('30', 'USD'),
        '2019-06-01 17:00:00',
      ],
      'half hour, half price' => [
        new Price('15', 'USD'),
        '2019-06-01 17:30:00',
      ],
      'partial half-hour, rounded' => [
        new Price('12.11', 'USD'),
        '2019-06-01 17:35:47',
      ],
    ];
  }

}
