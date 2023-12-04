<?php

namespace Drupal\commerce_recurring\Mail;

use Drupal\commerce_order\Entity\OrderInterface;

interface PaymentDeclinedMailInterface {

  /**
   * Sends a payment declined email.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The recurring order.
   * @param int $retry_days
   *   Days until next retry.
   * @param int $num_retries
   *   Number of past attempts.
   * @param int $max_retries
   *   Maximum number of retries allowed.
   *
   * @return bool
   *   TRUE if the email was sent successfully, FALSE otherwise.
   */
  public function send(OrderInterface $order, $retry_days, $num_retries, $max_retries);

}
