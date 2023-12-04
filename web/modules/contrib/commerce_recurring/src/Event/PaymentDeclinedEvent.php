<?php

namespace Drupal\commerce_recurring\Event;

use Drupal\commerce\EventBase;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Exception\DeclineException;

/**
 * Defines the payment declined event.
 *
 * @see \Drupal\commerce_recurring\Event\SubscriptionEvents
 */
class PaymentDeclinedEvent extends EventBase {

  /**
   * The order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * The number of days until the next retry.
   *
   * 0 if the maximum number of retries has been reached,
   * and the dunning cycle is terminating.
   *
   * @var int
   */
  protected $retryDays;

  /**
   * The number of times payment was retried.
   *
   * @var int
   */
  protected $numRetries;

  /**
   * The maximum number of retries.
   *
   * @var int
   */
  protected $maxRetries;

  /**
   * The payment decline exception.
   *
   * @var null|\Drupal\commerce_payment\Exception\DeclineException
   */
  protected $exception;

  /**
   * Constructs a new PaymentDeclinedEvent object.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param int $retry_days
   *   The number of days until the next retry.
   * @param int $num_retries
   *   The number of times payment was retried.
   * @param int $max_retries
   *   The maximum number of retries.
   * @param \Drupal\commerce_payment\Exception\DeclineException $exception
   *   (optional) The payment decline exception.
   */
  public function __construct(OrderInterface $order, $retry_days, $num_retries, $max_retries, DeclineException $exception = NULL) {
    $this->order = $order;
    $this->retryDays = $retry_days;
    $this->numRetries = $num_retries;
    $this->maxRetries = $max_retries;
    $this->exception = $exception;
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
   * Gets the number of days until the next retry.
   *
   * @return int
   *   The number of days until the next retry.
   */
  public function getRetryDays() {
    return $this->retryDays;
  }

  /**
   * Gets the number of retries.
   *
   * @return int
   *   The number of retries.
   */
  public function getNumRetries() {
    return $this->numRetries;
  }

  /**
   * Gets the maximum number of retries.
   *
   * @return int
   *   The maximum number of retries.
   */
  public function getMaxRetries() {
    return $this->maxRetries;
  }

  /**
   * Gets the payment decline exception.
   *
   * @return null|\Drupal\commerce_payment\Exception\DeclineException
   *   The payment decline exception.
   */
  public function getException() {
    return $this->exception;
  }

}
