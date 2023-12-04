<?php

namespace Drupal\commerce_paypal;

use Drupal\address\AddressInterface;
use Drupal\commerce_order\Entity\OrderInterface;

interface CheckoutSdkInterface {

  /**
   * Gets an access token.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The HTTP response.
   */
  public function getAccessToken();

  /**
   * Gets a client token.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The HTTP response.
   */
  public function getClientToken();

  /**
   * Creates an order in PayPal.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param \Drupal\address\AddressInterface $billing_address
   *   (optional) A billing address to pass to PayPal as the payer information.
   *   This is used in checkout to pass the entered address that is not yet
   *   submitted and associated to the order.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The HTTP response.
   */
  public function createOrder(OrderInterface $order, AddressInterface $billing_address = NULL);

  /**
   * Get an existing order from PayPal.
   *
   * @param string $remote_id
   *   The PayPal order ID.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The HTTP response.
   */
  public function getOrder($remote_id);

  /**
   * Updates an existing PayPal order.
   *
   * @param string $remote_id
   *   The PayPal order ID.
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The HTTP response.
   */
  public function updateOrder($remote_id, OrderInterface $order);

  /**
   * Authorize payment for order.
   *
   * @param string $remote_id
   *   The PayPal order ID.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The HTTP response.
   */
  public function authorizeOrder($remote_id);

  /**
   * Capture payment for order.
   *
   * @param string $remote_id
   *   The PayPal order ID.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The HTTP response.
   */
  public function captureOrder($remote_id);

  /**
   * Captures an authorized payment, by ID.
   *
   * @param string $authorization_id
   *   The PayPal-generated ID for the authorized payment to capture.
   * @param array $parameters
   *   (optional An array of parameters to pass as the request body.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The HTTP response.
   */
  public function capturePayment($authorization_id, array $parameters = []);

  /**
   * Reauthorizes an authorized PayPal account payment, by ID.
   *
   * @param string $authorization_id
   *   The PayPal-generated ID of the authorized payment to reauthorize.
   * @param array $parameters
   *   (optional An array of parameters to pass as the request body.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The HTTP response.
   */
  public function reAuthorizePayment($authorization_id, array $parameters = []);

  /**
   * Refunds a captured payment, by ID.
   *
   * @param string $capture_id
   *   The PayPal-generated ID for the captured payment to refund.
   * @param array $parameters
   *   (optional An array of parameters to pass as the request body.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The HTTP response.
   */
  public function refundPayment($capture_id, array $parameters = []);

  /**
   * Voids, or cancels, an authorized payment, by ID.
   *
   * @param string $authorization_id
   *   The PayPal-generated ID of the authorized payment to void.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The HTTP response.
   */
  public function voidPayment($authorization_id);

  /**
   * Verifies a webhook signature.
   *
   * @param array $parameters
   *   An array of parameters to pass as the request body.
   *
   * @see https://developer.paypal.com/docs/api/webhooks/v1/#verify-webhook-signature_post
   *
   * @throws \InvalidArgumentException
   *   Thrown when one of the required parameter isn't passed.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The HTTP response.
   */
  public function verifyWebhookSignature(array $parameters);

}
