<?php

namespace Drupal\commerce_paypal;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentGatewayInterface;

/**
 * Provides an interface for the Smart payment buttons builder.
 */
interface SmartPaymentButtonsBuilderInterface {

  /**
   * Builds the Smart payment buttons.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway
   *   The payment gateway.
   * @param bool $commit
   *   Set to TRUE if the transaction is Pay Now, or FALSE if the amount
   *   captured changes after the buyer returns to your site.
   *
   * @return array
   *   A renderable array representing the Smart payment buttons.
   */
  public function build(OrderInterface $order, PaymentGatewayInterface $payment_gateway, $commit);

}
