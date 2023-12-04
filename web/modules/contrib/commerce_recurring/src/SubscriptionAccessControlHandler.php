<?php

namespace Drupal\commerce_recurring;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity\UncacheableEntityAccessControlHandler;

/**
 * Controls access based on the Subscription entity permissions.
 */
class SubscriptionAccessControlHandler extends UncacheableEntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $result = parent::checkAccess($entity, $operation, $account);

    // Canceling a subscription requires either the 'cancel' or 'update'
    // permissions.
    if ($result->isNeutral() && $operation === 'cancel') {
      $result = $this->checkEntityOwnerPermissions($entity, 'update', $account);
    }

    return $result;
  }

}
