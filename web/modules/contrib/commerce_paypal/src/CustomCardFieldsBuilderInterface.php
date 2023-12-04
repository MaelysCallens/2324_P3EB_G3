<?php

namespace Drupal\commerce_paypal;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentGatewayInterface;

/**
 * Provides an interface for the hosted fields builder.
 */
interface CustomCardFieldsBuilderInterface {

  /**
   * Build the PayPal Checkout card form.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway
   *   The payment gateway.
   *
   * @return array
   *   A renderable array representing the card form.
   */
  public function build(OrderInterface $order, PaymentGatewayInterface $payment_gateway);

}
