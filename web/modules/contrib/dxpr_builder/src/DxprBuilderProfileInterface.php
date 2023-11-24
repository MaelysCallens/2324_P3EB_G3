<?php

namespace Drupal\dxpr_builder;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a dxpr builder profile entity type.
 */
interface DxprBuilderProfileInterface extends ConfigEntityInterface {

  /**
   * Loads the first profile available for specified roles.
   *
   * @param array $roles
   *   The roles.
   *
   * @phpstan-param array<string, mixed> $roles
   * @phpstan-return \Drupal\dxpr_builder\DxprBuilderProfileInterface|null
   *
   * @return \Drupal\dxpr_builder\DxprBuilderProfileInterface|null
   *   User profile or empty array.
   */
  public static function loadByRoles(array $roles): ?DxprBuilderProfileInterface;

}
