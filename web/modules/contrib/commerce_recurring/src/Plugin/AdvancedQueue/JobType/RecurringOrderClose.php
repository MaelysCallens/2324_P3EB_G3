<?php

namespace Drupal\commerce_recurring\Plugin\AdvancedQueue\JobType;

use Drupal\advancedqueue\Job;
use Drupal\advancedqueue\JobResult;
use Drupal\commerce_payment\Exception\DeclineException;

/**
 * Provides the job type for closing recurring orders.
 *
 * @AdvancedQueueJobType(
 *   id = "commerce_recurring_order_close",
 *   label = @Translation("Close recurring order"),
 * )
 */
class RecurringOrderClose extends RecurringJobTypeBase {

  /**
   * {@inheritdoc}
   */
  public function process(Job $job) {
    $order_id = $job->getPayload()['order_id'];
    $order_storage = $this->entityTypeManager->getStorage('commerce_order');
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $order_storage->load($order_id);
    if (!$order) {
      return JobResult::failure('Order not found.');
    }

    if ($order->getState()->getId() == 'canceled') {
      return JobResult::failure('Order has been canceled.');
    }

    try {
      $this->recurringOrderManager->closeOrder($order);
    }
    catch (DeclineException $exception) {
      // Both hard and soft declines need to be retried.
      // In case of a soft decline, the retry might succeed in charging the
      // same payment method. In case of a hard decline, the customer
      // might have changed their payment method since the last attempt.
      return $this->handleDecline($order, $exception, $job->getNumRetries());
    }
    catch (\Exception $exception) {
      // If something more general goes wrong, we assume it's not possible
      // or desirable to retry.
      $this->handleFailedOrder($order);

      // Rethrow the exception so that the queue processor can log the job's
      // failure with the exception's message.
      throw $exception;
    }

    return JobResult::success();
  }

}
