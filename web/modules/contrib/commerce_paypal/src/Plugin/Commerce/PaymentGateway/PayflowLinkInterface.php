<?php

namespace Drupal\commerce_paypal\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsAuthorizationsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;
use Drupal\commerce_price\Price;

/**
 * Provides the interface for the PayflowLink payment gateway.
 */
interface PayflowLinkInterface extends OffsitePaymentGatewayInterface, SupportsAuthorizationsInterface, SupportsRefundsInterface {

  /**
   * Requests a secure token from Payflow for use in follow-up API requests.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order whose details should be submitted in the secure token request.
   *
   * @return string|null
   *   The secure Token if successfully retrieved, NULL on failure.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function createSecureToken(OrderInterface $order);

  /**
   * Gets the redirect url.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface|null $order
   *   (Optional) The order.
   *
   * @return string
   *   The URL according selected 'mode' and 'redirect_method' settings.
   */
  public function getRedirectUrl(OrderInterface $order = NULL);

  /**
   * Creates a reference payment.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment to reference.
   * @param \Drupal\commerce_price\Price|null $amount
   *   The amount to use in reference payment.
   *
   * @throws \Drupal\commerce_payment\Exception\PaymentGatewayException
   *   Thrown when the transaction fails for any reason.
   */
  public function referencePayment(PaymentInterface $payment, Price $amount = NULL);

  /**
   * Returns an iframe embedding the Payflow Link Hosted Checkout page.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return string
   *   The iframe HTML to use to embed Payflow's Hosted Checkout page on-site.
   */
  public function createHostedCheckoutIframe(OrderInterface $order);

}
