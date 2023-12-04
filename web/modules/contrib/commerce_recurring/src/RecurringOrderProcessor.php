<?php

namespace Drupal\commerce_recurring;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderProcessorInterface;

/**
 * Refreshes draft recurring orders.
 */
class RecurringOrderProcessor implements OrderProcessorInterface {

  /**
   * The recurring order manager.
   *
   * @var \Drupal\commerce_recurring\RecurringOrderManagerInterface
   */
  protected $recurringOrderManager;

  /**
   * Constructs a new RecurringOrderProcessor object.
   *
   * @param \Drupal\commerce_recurring\RecurringOrderManagerInterface $recurring_order_manager
   *   The recurring order manager.
   */
  public function __construct(RecurringOrderManagerInterface $recurring_order_manager) {
    $this->recurringOrderManager = $recurring_order_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function process(OrderInterface $order) {
    if ($order->bundle() == 'recurring') {
      $this->recurringOrderManager->refreshOrder($order);
    }
  }

}
