<?php

namespace Drupal\commerce_recurring;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\entity\UncacheableEntityPermissionProvider;

/**
 * Provides additional permissions for subscriptions.
 */
class SubscriptionPermissionProvider extends UncacheableEntityPermissionProvider {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityTypePermissions(EntityTypeInterface $entity_type) {
    $permissions = parent::buildEntityTypePermissions($entity_type);

    $entity_type_id = $entity_type->id();
    $plural_label = $entity_type->getPluralLabel();

    $permissions["cancel any {$entity_type_id}"] = [
      'title' => $this->t('Cancel any @type', [
        '@type' => $plural_label,
      ]),
    ];
    $permissions["cancel own {$entity_type_id}"] = [
      'title' => $this->t('Cancel own @type', [
        '@type' => $plural_label,
      ]),
    ];

    return $permissions;
  }

}
