<?php

namespace Drupal\commerce_paypal\Plugin\Commerce\PaymentType;

use Drupal\commerce_payment\Plugin\Commerce\PaymentType\PaymentTypeBase;

/**
 * Provides the payment type for PayPal Checkout.
 *
 * @CommercePaymentType(
 *   id = "paypal_checkout",
 *   label = @Translation("PayPal Checkout"),
 *   workflow = "payment_paypal_checkout"
 * )
 */
class PayPalCheckout extends PaymentTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    return [];
  }

}
