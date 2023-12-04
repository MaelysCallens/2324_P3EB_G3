<?php

namespace Drupal\commerce_paypal\Event;

/**
 * Defines events for the Commerce PayPal module.
 */
final class PayPalEvents {

  /**
   * Name of the event fired when performing the Express Checkout requests.
   *
   * @Event
   *
   * @see \Drupal\commerce_paypal\Event\ExpressCheckoutRequestEvent.php
   */
  const EXPRESS_CHECKOUT_REQUEST = 'commerce_paypal.express_checkout_request';

  /**
   * Name of the event fired when calling the PayPal API for creating an order.
   *
   * @Event
   *
   * @see \Drupal\commerce_paypal\Event\CheckoutOrderRequestEvent.php
   */
  const CHECKOUT_CREATE_ORDER_REQUEST = 'commerce_paypal.checkout_create_order_request';

  /**
   * Name of the event fired when calling the PayPal API for creating Payflow payments.
   *
   * @Event
   *
   * @see \Drupal\commerce_paypal\Event\PayflowRequestEvent.php
   */
  const PAYFLOW_CREATE_PAYMENT = 'commerce_paypal.payflow_create_payment';

  /**
   * Name of the event fired when calling the PayPal API for updating an order.
   *
   * @Event
   *
   * @see \Drupal\commerce_paypal\Event\CheckoutOrderRequestEvent.php
   */
  const CHECKOUT_UPDATE_ORDER_REQUEST = 'commerce_paypal.checkout_update_order_request';

  /**
   * Name of the event fired when performing the Payflow Link requests.
   *
   * @Event
   *
   * @see \Drupal\commerce_paypal\Event\PayflowLinkRequestEvent.php
   */
  const PAYFLOW_LINK_REQUEST = 'commerce_paypal.payflow_link_request';

}
