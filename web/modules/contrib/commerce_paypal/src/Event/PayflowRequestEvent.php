<?php

namespace Drupal\commerce_paypal\Event;

use Drupal\commerce\EventBase;
use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Defines the Payflow request event.
 *
 * @see \Drupal\commerce_paypal\Event\CommercePaypalEvents
 */
class PayflowRequestEvent extends EventBase {

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
  protected $params;

  /**
   * Constructs a new PayflowRequestEvent object.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param array $params
   *   The API request body.
   */
  public function __construct(OrderInterface $order, array $params) {
    $this->order = $order;
    $this->params = $params;
  }

  /**
   * Gets the order.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   The order.
   */
  public function getOrder(): OrderInterface {
    return $this->order;
  }

  /**
   * Gets the request parameters.
   *
   * @return array
   *   The request parameters.
   */
  public function getParams(): array {
    return $this->params;
  }

  /**
   * Sets the request parameters.
   *
   * @param array $params
   *   The request parameters.
   *
   * @return $this
   *   Returns the PayflowRequestEvent with the provided parameters set.
   */
  public function setParams(array $params): PayflowRequestEvent {
    $this->params = $params;
    return $this;
  }

}
