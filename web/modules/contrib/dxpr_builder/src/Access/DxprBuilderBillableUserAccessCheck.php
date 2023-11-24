<?php

namespace Drupal\dxpr_builder\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\dxpr_builder\Service\DxprBuilderLicenseServiceInterface;

/**
 * Determines access for billable users.
 */
class DxprBuilderBillableUserAccessCheck implements AccessInterface {

  /**
   * The module handler service.
   *
   * @var \Drupal\dxpr_builder\Service\DxprBuilderLicenseServiceInterface
   */
  private $dxprBuilderLicenseService;

  /**
   * Constructs an EntityCreateAccessCheck object.
   *
   * @param \Drupal\dxpr_builder\Service\DxprBuilderLicenseServiceInterface $dxprBuilderLicenseService
   *   The dxpr builder license service.
   */
  public function __construct(DxprBuilderLicenseServiceInterface $dxprBuilderLicenseService) {
    $this->dxprBuilderLicenseService = $dxprBuilderLicenseService;
  }

  /**
   * Checks access for the billable users.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account) {
    return AccessResult::allowedIf(
      $this->dxprBuilderLicenseService->isBillableUser($account)
    );
  }

}
