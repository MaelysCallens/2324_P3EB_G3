<?php

namespace Drupal\commerce_paypal\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsAuthorizationsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsCreatingPaymentMethodsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;

/**
 * Provides the interface for the Checkout payment gateway.
 *
 * The PayPal Checkout payment gateway supports 2 different flows:
 * 1) The "shortcut" flow:
 *   - Customer initiates the payment from the cart page through the Smart
 *     payment buttons.
 *   - Once the payment is approved on PayPal, the customer is redirected to
 *     checkout and the checkout flow is set to "PayPal Checkout" (which is
 *     provided by the module and can be customized).
 *   - The payment is authorized/captured by the CheckoutPaymentProcess pane
 *     which calls createPayment().
 * 2) The "mark" flow:
 *   - This flow requires the presence of the "review" checkout step. In case
 *     no "review" step is configured, the Smart payment buttons can be shown
 *     using the "commerce_paypal.smart_payment_buttons_builder" service.
 *   - Customer initiates the payment from the checkout "review" step.
 *   - Once the payment is approved on PayPal, the payment is created on
 *     in onReturn() and the customer is redirected to the next checkout step
 *     (usually "payment" which is skipped because the order is already paid).
 */
interface CheckoutInterface extends OffsitePaymentGatewayInterface, SupportsAuthorizationsInterface, SupportsRefundsInterface, SupportsCreatingPaymentMethodsInterface {

  /**
   * Returns the payment solution (e.g "smart_payment_buttons").
   *
   * @return string
   *   The payment solution.
   */
  public function getPaymentSolution();

}
