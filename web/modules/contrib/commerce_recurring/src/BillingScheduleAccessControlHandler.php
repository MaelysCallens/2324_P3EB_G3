<?php

namespace Drupal\commerce_recurring;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity\EntityAccessControlHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controls access for the Billing Schedule entity type.
 */
class BillingScheduleAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * {@inheritdoc}
   */
  protected $viewLabelOperation = TRUE;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new BillingScheduleAccessControlHandler object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($entity_type);

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // Allow users with access to the 'commerce_subscription' entity type to
    // view the label of 'commerce_billing_schedule' entities.
    if ($operation === 'view label') {
      $permissions = [
        'administer commerce_billing_schedule',
        'administer commerce_subscription',
        'update any commerce_subscription',
        'view any commerce_subscription',
        'view own commerce_subscription',
      ];

      return AccessResult::allowedIfHasPermissions($account, $permissions, 'OR');
    }
    elseif ($operation === 'delete') {
      // Deny the "delete" operation if the billing schedule is referenced by
      // subscriptions.
      $is_referenced = (boolean) $this->entityTypeManager
        ->getStorage('commerce_subscription')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('billing_schedule', $entity->id())
        ->count()
        ->execute();

      if ($is_referenced) {
        return AccessResult::forbidden();
      }
    }

    return parent::checkAccess($entity, $operation, $account);
  }

}
