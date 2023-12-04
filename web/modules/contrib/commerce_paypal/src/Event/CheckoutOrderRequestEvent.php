<?php

namespace Drupal\commerce_paypal\Event;

use Drupal\commerce\EventBase;
use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Defines the Checkout order request event.
 *
 * @see \Drupal\commerce_paypal\Event\CommercePaypalEvents
 */
class CheckoutOrderRequestEvent extends EventBase {

  /**
   * The order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * The API request body.
   *
   * @var array
   */
  protected $requestBody;

  /**
   * Constructs a new CheckoutOrderRequestEvent object.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param array $request_body
   *   The API request body.
   */
  public function __construct(OrderInterface $order, array $request_body) {
    $this->order = $order;
    $this->requestBody = $request_body;
  }

  /**
   * Gets the order.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   The order.
   */
  public function getOrder() {
    return $this->order;
  }

  /**
   * Gets the API request body.
   *
   * @return array
   *   The API request body.
   */
  public function getRequestBody() {
    return $this->requestBody;
  }

  /**
   * Sets the API request body.
   *
   * @param array $request_body
   *   The API request body.
   *
   * @return $this
   */
  public function setRequestBody(array $request_body) {
    $this->requestBody = $request_body;
    return $this;
  }

}
