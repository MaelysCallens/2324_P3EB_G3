<?php

namespace Drupal\dxpr_builder\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Description.
 */
interface DxprBuilderLicenseServiceInterface {

  /**
   * The url of the domain status endpoint.
   */
  const DOMAIN_STATUS_ENDPOINT = 'https://dxpr.com/api/user-license';

  /**
   * The url of the domain blacklist endpoint.
   */
  const DOMAIN_BLACKLIST_ENDPOINT = 'https://www.dxpr.com/api/domain-blacklist/';

  /**
   * The url of endpoint to collect information about DXPR Builder users.
   */
  const CENTRAL_USER_STORAGE_ENDPOINT = 'https://dxpr.com/api/central-user-storage/';

  /**
   * The url of the site users endpoint.
   */
  const USERS_ENDPOINT = 'https://dxpr.com/api/user-license/users';

  /**
   * Operation name for syncing DXPR Builder users.
   */
  const DXPR_USER_SYNC_OPERATION = 'sync';

  /**
   * Operation name for adding new users to Central Storage.
   */
  const DXPR_USER_ADD_OPERATION = 'add';

  /**
   * Operation name for removing users from Central Storage.
   */
  const DXPR_USER_REMOVE_OPERATION = 'remove';

  /**
   * License check interval in seconds.
   */
  const LICENSE_CHECK_INTERVAL = 86400;

  /**
   * License check interval when currently not authorized in seconds.
   */
  const LICENSE_NOT_AUTHORIZED_INTERVAL = 1800;

  /**
   * Blacklist check interval in seconds.
   */
  const BLACKLIST_CHECK_INTERVAL = 3600;

  /**
   * Blacklist check interval when currently blacklisted in seconds.
   */
  const BLACKLIST_BLOCKED_INTERVAL = 60;

  /**
   * Array of the users tiers.
   */
  const TIERS_ARRAY = [1, 3, 5, 10, 15, 20, 30, 40, 50, 75, 100];

  /**
   * Retrieves license information.
   *
   * @return mixed[]
   *   Array of license information or FALSE in case of fail.
   */
  public function getLicenseInfo();

  /**
   * Checks if the site is in the blacklist.
   *
   * @return bool
   *   Result of the checking.
   */
  public function isBlacklisted();

  /**
   * Check if the user given is allowed within the user limit.
   *
   * @return bool
   *   Indicates if access is allowed.
   */
  public function withinUsersLimit(AccountInterface $account);

  /**
   * Check if the entity given is allowed within the entity limit.
   *
   * @return bool
   *   Indicates if access is allowed.
   */
  public function withinEntitiesLimit(EntityInterface $entity);

  /**
   * Remove a single user from central storage.
   *
   * @param string $mail
   *   User mail address.
   */
  public function removeMailFromCentralStorage(string $mail): void;

  /**
   * Sends DXPR Users to central storage.
   *
   * @param mixed[] $users
   *   Users with a permission to edit with DXPR Builder.
   * @param string $operation
   *   Operation on user storage, add or remove.
   * @param string $json_web_token
   *   DXPR product key in JWT.
   */
  public function syncUsersWithCentralStorage(array $users, string $operation, string $json_web_token = ''): void;

  /**
   * Sends all DXPR editor users to central storage.
   *
   * @param string $operation
   *   Operation on user storage, add or remove.
   * @param string $json_web_token
   *   DXPR product key in JWT.
   */
  public function syncAllUsersWithCentralStorage(string $operation, string $json_web_token = '') :void;

  /**
   * Process a single item from the sync queue, if not empty.
   */
  public function processSyncQueue(): void;

  /**
   * Collects User License information.
   *
   * @return mixed[]
   *   Information to be displayed on people page.
   */
  public function getUserLicenseInfo();

  /**
   * Retrieve count of users with 'edit with dxpr builder' permission.
   *
   * @return int
   *   The count of users.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getUsersCount();

  /**
   * Build list of DXPR enabled view modes, keyed by entity type.
   *
   * @param string|null $entity_type_filter
   *   Only count entities with the given entity type.
   *
   * @return array<string, array<string, \Drupal\Core\Entity\Display\EntityViewDisplayInterface>>
   *   List of view modes keyed by entity type.
   */
  public function getDxprEnabledViewModes(?string $entity_type_filter = NULL);

  /**
   * Build a query to retreive DXPR content items.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   Database query with entity_type, entity_id, bundle, langcode, and label.
   */
  public function getLicensedContentQuery(?string $entity_type_filter = NULL, ?string $before_id = NULL);

  /**
   * Retrieve count of content items with 'text_dxpr_builder' field formatter.
   *
   * @param string|null $entity_type_filter
   *   Only count entities with the given entity type.
   * @param string|null $before_id
   *   Only count users with a user id lower than $before_id.
   *   This is used for checking the users limit and must be used in conjuction
   *   with the $entity_type_filter parameter.
   *
   * @return int
   *   The count of fields.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getValuesCount(?string $entity_type_filter = NULL, ?string $before_id = NULL);

  /**
   * List users for license across all sites.
   *
   * @return mixed[]
   *   List of users.
   */
  public function getLicenseUsers();

  /**
   * Determine if user is a dxpr editor.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Optional. The user account to check. If not specified, the currently
   *   logged in user will be used.
   *
   * @return bool
   *   Is billable.
   */
  public function isBillableUser(AccountInterface $account = NULL);

}
