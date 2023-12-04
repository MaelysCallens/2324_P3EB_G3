<?php

namespace Drupal\commerce_paypal;

/**
 * PayPal checkout SDK factory interface.
 */
interface CheckoutSdkFactoryInterface {

  /**
   * Retrieves the PayPal Checkout SDK for the given config.
   *
   * @param array $configuration
   *   An associative array, containing at least these three keys:
   *   - mode: The API mode (e.g "test" or "live").
   *   - client_id: The client ID.
   *   - secret: The client secret.
   *
   * @return \Drupal\commerce_paypal\CheckoutSdk
   *   The PayPal Checkout SDK.
   */
  public function get(array $configuration);

}
