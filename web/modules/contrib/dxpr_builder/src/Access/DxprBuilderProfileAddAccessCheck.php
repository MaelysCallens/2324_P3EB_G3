<?php

namespace Drupal\dxpr_builder\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Determines access to for dxpr_builder_profile add pages.
 */
class DxprBuilderProfileAddAccessCheck implements AccessInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private $moduleHandler;

  /**
   * Constructs an EntityCreateAccessCheck object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Checks access to the node add page for the node type.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account) {
    $admin_permission = $this->entityTypeManager->getDefinition('dxpr_builder_profile')
      ->getAdminPermission();
    if (!$account->hasPermission($admin_permission)) {
      return AccessResult::forbidden();
    }
    if (!$this->moduleHandler->moduleExists('dxpr_builder_e')) {
      $entities = $this->entityTypeManager->getStorage('dxpr_builder_profile')->loadMultiple();
      if (count($entities)) {
        return AccessResult::forbidden('Provisioning multiple editor profiles to respective user roles is only supported on the DXPR Enterprise subscription tier.');
      }
    }
    return AccessResult::allowed();
  }

}
