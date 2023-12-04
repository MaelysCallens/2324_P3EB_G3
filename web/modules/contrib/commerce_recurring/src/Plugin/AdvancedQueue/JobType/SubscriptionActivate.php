<?php

namespace Drupal\commerce_recurring\Plugin\AdvancedQueue\JobType;

use Drupal\advancedqueue\Job;
use Drupal\advancedqueue\JobResult;

/**
 * Provides the job type for activating subscriptions.
 *
 * @AdvancedQueueJobType(
 *   id = "commerce_subscription_activate",
 *   label = @Translation("Activate subscription"),
 * )
 */
class SubscriptionActivate extends RecurringJobTypeBase {

  /**
   * {@inheritdoc}
   */
  public function process(Job $job) {
    $subscription_id = $job->getPayload()['subscription_id'];
    $subscription_storage = $this->entityTypeManager->getStorage('commerce_subscription');
    /** @var \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription */
    $subscription = $subscription_storage->load($subscription_id);
    if (!$subscription) {
      return JobResult::failure('Subscription not found.');
    }
    if (!in_array($subscription->getState()->getId(), ['pending', 'trial'], TRUE)) {
      return JobResult::failure(sprintf('Unsupported subscription status. Supported statuses: ("trial", "pending"), Actual: "%s").', $subscription->getState()->getId()));
    }
    $subscription->getState()->applyTransitionById('activate');
    $subscription->save();
    $this->recurringOrderManager->startRecurring($subscription);

    return JobResult::success();
  }

}
