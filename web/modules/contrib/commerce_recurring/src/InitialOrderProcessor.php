<?php

namespace Drupal\commerce_recurring;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderProcessorInterface;
use Drupal\commerce_recurring\Entity\BillingScheduleInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Modifies the price of order items which start subscriptions.
 */
class InitialOrderProcessor implements OrderProcessorInterface {

  /**
   * The time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new InitialOrderProcessor object.
   *
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time.
   */
  public function __construct(TimeInterface $time) {
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public function process(OrderInterface $order) {
    if ($order->bundle() == 'recurring') {
      return;
    }

    $start_date = DrupalDateTime::createFromTimestamp($this->time->getRequestTime());
    foreach ($order->getItems() as $order_item) {
      $purchased_entity = $order_item->getPurchasedEntity();
      if (!$purchased_entity || !$purchased_entity->hasField('billing_schedule')) {
        continue;
      }
      /** @var \Drupal\commerce_recurring\Entity\BillingScheduleInterface $billing_schedule */
      $billing_schedule = $purchased_entity->get('billing_schedule')->entity;
      if (!$billing_schedule) {
        continue;
      }
      $allow_trials = $billing_schedule->getPlugin()->allowTrials();

      // Price differences are added as adjustments, to preserve the original
      // price, for both display purposes and for being able to transfer the
      // unit price to the subscription when the order is placed.
      // It's assumed that the customer won't see the actual adjustment,
      // because the cart/order summary was hidden or restyled.
      if ($billing_schedule->getBillingType() == BillingScheduleInterface::BILLING_TYPE_PREPAID) {
        if ($allow_trials) {
          $order_item->addAdjustment(new Adjustment([
            'type' => 'subscription',
            'label' => t('Free trial'),
            'amount' => $order_item->getTotalPrice()->multiply('-1'),
            'source_id' => $billing_schedule->id(),
          ]));
        }
        else {
          // Prepaid subscriptions need to be prorated so that the customer
          // pays only for the portion of the period that they'll get.
          $unit_price = $order_item->getUnitPrice();
          $billing_period = $billing_schedule->getPlugin()->generateFirstBillingPeriod($start_date);
          $partial_billing_period = new BillingPeriod($start_date, $billing_period->getEndDate());
          $prorater = $billing_schedule->getProrater();
          $prorated_unit_price = $prorater->prorateOrderItem($order_item, $partial_billing_period, $billing_period);
          if (!$prorated_unit_price->equals($unit_price)) {
            $adjustment_amount = $unit_price->subtract($prorated_unit_price);
            $adjustment_amount = $adjustment_amount->multiply($order_item->getQuantity());

            $order_item->addAdjustment(new Adjustment([
              'type' => 'subscription',
              'label' => t('Proration'),
              'amount' => $adjustment_amount->multiply('-1'),
              'source_id' => $billing_schedule->id(),
            ]));
          }
        }
      }
      else {
        $label = $allow_trials ? t('Free trial') : t('Pay later');
        // A postpaid purchased entity is free in the initial order.
        $order_item->addAdjustment(new Adjustment([
          'type' => 'subscription',
          'label' => $label,
          'amount' => $order_item->getTotalPrice()->multiply('-1'),
          'source_id' => $billing_schedule->id(),
        ]));
      }
    }
  }

}
