<?php

namespace Drupal\commerce_recurring\Plugin\AdvancedQueue\JobType;

use Drupal\advancedqueue\Job;
use Drupal\advancedqueue\JobResult;

/**
 * Provides the job type for renewing recurring orders.
 *
 * @AdvancedQueueJobType(
 *   id = "commerce_recurring_order_renew",
 *   label = @Translation("Renew recurring order"),
 * )
 */
class RecurringOrderRenew extends RecurringJobTypeBase {

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
    $this->recurringOrderManager->renewOrder($order);

    return JobResult::success();
  }

}
