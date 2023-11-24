<?php

namespace Drupal\dxpr_builder\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\user\UserInterface;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Description.
 */
class DxprBuilderLicenseService implements DxprBuilderLicenseServiceInterface, EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $requestStack;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private $database;

  /**
   * The http client.
   *
   * @var \GuzzleHttp\Client
   */
  private $client;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * The module extension list service.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  private $moduleExtensionList;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  private $state;

  /**
   * The cache.default cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  private $cache;

  /**
   * Queue for site user updates.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  private $queue;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * DxprBuilderLicenseService constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \GuzzleHttp\Client $client
   *   The http client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_extension_list
   *   The module extension list service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   Queue for site user updates.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    RequestStack $requestStack,
    EntityTypeManagerInterface $entity_type_manager,
    Connection $database,
    Client $client,
    ConfigFactoryInterface $config_factory,
    ModuleExtensionList $module_extension_list,
    StateInterface $state,
    CacheBackendInterface $cache,
    QueueFactory $queue_factory,
    AccountProxyInterface $currentUser,
    MessengerInterface $messenger,
    TimeInterface $time,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->requestStack = $requestStack;
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
    $this->client = $client;
    $this->configFactory = $config_factory;
    $this->moduleExtensionList = $module_extension_list;
    $this->state = $state;
    $this->cache = $cache;
    $this->queue = $queue_factory->get('dxpr_builder_site_user');
    $this->currentUser = $currentUser;
    $this->messenger = $messenger;
    $this->time = $time;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::TERMINATE][] = ['processSyncQueue'];
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  public function getLicenseInfo() {
    $domain = $this->requestStack->getCurrentRequest()->getHost();
    $base_url = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
    $jwt = $this->configFactory->get('dxpr_builder.settings')->get('json_web_token');

    $free_tier = [
      'users_limit' => 1,
      'entities_limit' => 10,
    ];

    if (!$jwt) {
      return ['status' => 'not found'] + $free_tier;
    }

    $result = $this->cache->get('dxpr_builder_license_info');
    if ($result !== FALSE) {
      return $result->data;
    }

    $site = Crypt::hmacBase64(Settings::getHashSalt(), '3TUoWRDSEFn77KMT');
    $domain = Html::escape($domain);
    $module_info = $this->moduleExtensionList->getExtensionInfo('dxpr_builder');

    $users_count = $this->getUsersCount();
    $users_tier = $this->getUsersTier($users_count);
    $this->state->set('dxpr_builder.users_tier_users_count', $users_tier . ' ' . $users_count);

    $query = UrlHelper::buildQuery([
      'gba' => $users_count,
      'users_tier' => $users_tier,
      'project' => 'dxpr_builder',
      'site_base_url' => $base_url,
      'site' => $site,
      'values_count' => $this->getValuesCount(),
      'site_mail' => $this->configFactory->get('system.site')->get('mail'),
      'version' => $module_info['version'] ?? NULL,
    ]);
    $end_point = self::DOMAIN_STATUS_ENDPOINT . '?' . $query;
    $request_options = [
      RequestOptions::HEADERS => [
        'Authorization' => 'Bearer ' . $jwt,
      ],
    ];
    try {
      $result = $this->client->request('GET', $end_point, $request_options);
    }
    catch (\Exception $e) {
      // Continue without throwing error if `json_web_token` has wrong value.
      // because this case is handled by editor_validation.js.
      if ($e->getCode() !== 403) {
        $this->messenger->addMessage($this->t('DXPR Subscription lookup failed due to an error.'), 'warning');
        $this->loggerFactory->get('dxpr_builder')
          ->error($e->getMessage());
      }
      $result = FALSE;
    }

    if ($result instanceof ResponseInterface && $result->getStatusCode() === 200) {
      $result = Json::decode($result->getBody());
      $result['users_count'] = $users_count;
      $result['status'] = 'authorized';
    }
    else {
      $result = [
        'status' => 'not found',
        'users_limit' => NULL,
        'entities_limit' => NULL,
      ];
    }

    $authorized = !isset($result['status']) || $result['status'] === 'authorized';
    $interval = $authorized ? self::LICENSE_CHECK_INTERVAL : self::LICENSE_NOT_AUTHORIZED_INTERVAL;
    $now = $this->time->getRequestTime();
    $this->cache->set('dxpr_builder_license_info', $result, $now + $interval);

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function isBlacklisted() {
    $domain = $this->requestStack->getCurrentRequest()->getHost();

    $blacklisted = $this->cache->get('dxpr_builder_blacklisted');
    if ($blacklisted !== FALSE) {
      return $blacklisted->data;
    }

    $end_point = self::DOMAIN_BLACKLIST_ENDPOINT . $domain;

    try {
      $result = $this->client->request('GET', $end_point);
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('dxpr_builder')
        ->error($e->getMessage());
      $result = FALSE;
    }

    if ($result instanceof ResponseInterface && $result->getStatusCode() === 200) {
      $result = Json::decode($result->getBody());
    }

    $blacklisted = isset($result['status']) && $result['status'] === 'blacklisted';
    $interval = $blacklisted ? self::BLACKLIST_BLOCKED_INTERVAL : self::BLACKLIST_CHECK_INTERVAL;
    $now = $this->time->getRequestTime();
    $this->cache->set('dxpr_builder_blacklisted', $blacklisted, $now + $interval);

    $blacklisted_state = $this->state->get('dxpr_builder.blacklisted', FALSE);
    if ($blacklisted_state !== $blacklisted) {
      $this->state->set('dxpr_builder.blacklisted', $blacklisted);
      drupal_flush_all_caches();
    }

    return $blacklisted;
  }

  /**
   * {@inheritdoc}
   */
  public function removeMailFromCentralStorage(string $mail): void {
    $this->queue->createItem([
      'users_data' => [
        [
          'mail' => $mail,
          'roles' => [],
        ],
      ],
      'operation' => DxprBuilderLicenseServiceInterface::DXPR_USER_REMOVE_OPERATION,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function syncUsersWithCentralStorage(array $user_ids, string $operation, string $json_web_token = ''): void {
    // Build a list of users.
    $users_data = [];
    foreach ($user_ids as $user_id) {
      $user = $this->entityTypeManager->getStorage('user')->load($user_id);
      if ($user !== NULL && strpos($user->getEmail(), 'placeholder-for-uid') === FALSE) {
        $users_data[] = [
          'mail' => $user->getEmail(),
          'roles' => $user->getRoles(),
        ];
      }
    }

    // We will only execute the first call directly and queue subsequent calls.
    // Always queue when a hostname is not available (when running from Drush).
    // Run directly when JWT is provided (when changing license in settings).
    static $called = TRUE;
    if (empty($json_web_token) && ($called || !$this->hasHostname())) {
      $this->queue->createItem([
        'users_data' => $users_data,
        'operation' => $operation,
      ]);
      return;
    }
    else {
      $called = TRUE;
      $this->sendToCentralStorage($users_data, $operation, $json_web_token);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function syncAllUsersWithCentralStorage(string $operation, string $json_web_token = ''): void {
    $dxpr_editors = $this->getEditors();
    $this->syncUsersWithCentralStorage($dxpr_editors, $operation, $json_web_token);
  }

  /**
   * {@inheritdoc}
   */
  public function isBillableUser(AccountInterface $account = NULL) {
    if (empty($account)) {
      $account = $this->currentUser->getAccount();
    }

    if (!$account instanceof UserInterface) {
      /** @var \Drupal\user\UserInterface $account */
      $account = $this->entityTypeManager->getStorage('user')->load($account->id());
    }

    if ($account->isBlocked()) {
      return FALSE;
    }
    if ($account->hasField('dxpr_user_is_disavowed') &&
      !empty($account->get('dxpr_user_is_disavowed')->value)
    ) {
      return FALSE;
    }
    if ($account->id() == 1 || $account->hasPermission('edit with dxpr builder')) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Get user ids for DXPR editors.
   *
   * @return array
   *   User ID's.
   *
   * @phpstan-return array<int, string>
   */
  private function getEditors(): array {
    $dxpr_builder_role_ids = [];
    foreach ($this->entityTypeManager->getStorage('user_role')->loadMultiple() as $user_role) {
      if ($user_role->hasPermission('edit with dxpr builder')) {
        $dxpr_builder_role_ids[] = $user_role->id();
      }
    }
    if ($dxpr_builder_role_ids) {
      $result = $this->entityTypeManager->getStorage('user')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('roles', $dxpr_builder_role_ids, 'IN')
        ->condition('dxpr_user_is_disavowed', 0)
        ->execute();
      $dxpr_editors = array_values($result);
    }
    else {
      $dxpr_editors = [];
    }
    if (!in_array('1', $dxpr_editors)) {
      $dxpr_editors[] = '1';
    }
    return $dxpr_editors;
  }

  /**
   * Check if a hostname is available.
   *
   * @return bool
   *   Returns boolean value
   */
  private function hasHostname() {
    return $this->requestStack->getCurrentRequest()->getHost() !== 'default';
  }

  /**
   * Send to license storage API.
   *
   * @param mixed[] $users_data
   *   The user data.
   * @param string $operation
   *   The opertion.
   * @param string $json_web_token
   *   DXPR product key in JWT.
   */
  private function sendToCentralStorage(array $users_data, string $operation, string $json_web_token = ''): void {
    $hostname = $this->requestStack->getCurrentRequest()->getHost();
    $config = $this->configFactory->get('dxpr_builder.settings');
    if (empty($json_web_token)) {
      $json_web_token = $config->get('json_web_token');
    }
    if (!empty($users_data) && $json_web_token !== NULL) {
      $endpoint = $config->get('license_endpoint') ?? self::CENTRAL_USER_STORAGE_ENDPOINT;
      $sent = FALSE;
      try {
        if ($operation == self::DXPR_USER_REMOVE_OPERATION && count($users_data) == 1 && !empty($users_data[0]['mail'])) {
          $license_users = $this->getLicenseUsers();
          $license_user = $license_users[$users_data[0]['mail']];
          if (!in_array($hostname, $license_user['domains'])) {
            /*
             * The user is considered 'stale' i.e. they show up on the License
             * Dashboard but are not associated with the current domain.
             * The only way to remove them from the list is to remove them
             * from all the domains associated with them.
             */
            foreach ($license_user['domains'] as $domain) {
              $this->sendRequestToCentralStorage($json_web_token, $domain, $users_data, $endpoint, $operation);
            }
            $sent = TRUE;
          }
        }

        if (!$sent) {
          $this->sendRequestToCentralStorage($json_web_token, $hostname, $users_data, $endpoint, $operation);
        }

        // Clear cache to immediately reflect changes on admin pages.
        $this->cache->invalidate('dxpr_builder_license_info');
        $this->cache->invalidate('dxpr_builder_license_users');
      }
      catch (\Exception $e) {
        $this->loggerFactory->get('dxpr_builder')
          ->error($e->getMessage());
        // Add item to queue to try again later.
        $this->queue->createItem([
          'users_data' => $users_data,
          'operation' => $operation,
        ]);
      }
    }
  }

  /**
   * Sends a request to central strorage.
   *
   * @param string $json_web_token
   *   DXPR product key in JWT.
   * @param string $hostname
   *   The hostname.
   * @param mixed[] $users_data
   *   The user data.
   * @param string $endpoint
   *   The endpoint.
   * @param string $operation
   *   The operation.
   *
   * @return void
   *   Returns void.
   */
  private function sendRequestToCentralStorage($json_web_token, $hostname, $users_data, $endpoint, $operation) {
    $request_options = [
      RequestOptions::HEADERS => [
        'Authorization' => 'Bearer ' . $json_web_token,
      ],
      RequestOptions::JSON => [
        'domain' => $hostname,
        'users' => $users_data,
      ],
    ];
    $this->client->request('POST', $endpoint . $operation, $request_options);
  }

  /**
   * {@inheritdoc}
   */
  public function processSyncQueue(): void {
    $account = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
    if (!$account->hasPermission('edit with dxpr builder')) {
      // Only execute process for editors to minimize performance impact.
      return;
    }
    if (!$this->hasHostname()) {
      // Do not run in Drush calls.
      return;
    }

    // Group users per operation. Each operation is a separate API call.
    $data = [];
    $limit = 100;
    while (($item = $this->queue->claimItem()) && --$limit) {
      /** @var object|null $item */
      if (!isset($data[$item->data['operation']])) {
        $data[$item->data['operation']] = [];
      }
      $data[$item->data['operation']] = array_merge($data[$item->data['operation']], $item->data['users_data']);
      $this->queue->deleteItem($item);
    }

    // Send users to API.
    foreach (array_keys($data) as $operation) {
      try {
        $this->sendToCentralStorage($data[$operation], $operation);
      }
      catch (\Exception $e) {
        $this->loggerFactory->get('dxpr_builder')
          ->error($e->getMessage());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getUserLicenseInfo() {
    $license_info = $this->state->get('dxpr_user_license_info');

    if (empty($license_info)) {
      $license_info = [
        'dxpr_users' => $this->getUsersCount(),
        'allocated_seats' => 0,
      ];

      $this->state->set('dxpr_user_license_info', $license_info);
    }

    return $license_info;
  }

  /**
   * {@inheritdoc}
   */
  public function getUsersCount() {
    $users = $this->getLicenseUsers();
    return count(array_keys($users));
  }

  /**
   * Get users tier.
   *
   * @param int $count
   *   Count for which needs to get tier.
   *
   * @return int
   *   Tier value.
   */
  protected function getUsersTier($count) {
    $tier = 0;
    if ($count) {
      foreach (self::TIERS_ARRAY as $preset) {
        if ($count < $preset) {
          break;
        }
        $tier = $preset;
      }
    }
    return $tier;
  }

  /**
   * {@inheritdoc}
   */
  public function getValuesCount($entity_type_filter = NULL, $before_id = NULL) {
    return $this
      ->getLicensedContentQuery($entity_type_filter, $before_id)
      ->countQuery()
      ->execute()
      ->fetchField();
  }

  /**
   * Get view modes that contain DXPR fields.
   *
   * @param string $entity_type_filter
   *   Only count entities with the given entity type.
   *
   * @return array<string, \Drupal\Core\Entity\Display\EntityViewDisplayInterface[]>
   *   Array of view modes, keyed by entity type.
   */
  public function getDxprEnabledViewModes($entity_type_filter = NULL) {
    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface[] $entity_view_displays */
    $entity_view_displays = $this->entityTypeManager->getStorage('entity_view_display')
      ->loadMultiple();

    $entity_types = [];
    foreach ($entity_view_displays as $entity_view_display) {
      foreach ($entity_view_display->getComponents() as $component) {
        if (isset($component['type']) && $component['type'] === 'dxpr_builder_text') {
          $entity_type = $entity_view_display->getTargetEntityTypeId();
          if (!$entity_type_filter || $entity_type_filter === $entity_type) {
            $entity_types[$entity_type][] = $entity_view_display;
          }
          break;
        }
      }
    }

    return $entity_types;
  }

  /**
   * Build a query to retreive DXPR content items.
   *
   * @param string|null $entity_type_filter
   *   Only count entities with the given entity type.
   * @param string|null $before_id
   *   Only count users with a user id lower than $before_id.
   *   This is used for checking the users limit and must be used in conjuction
   *   with the $entity_type_filter parameter.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   The query.
   */
  public function getLicensedContentQuery($entity_type_filter = NULL, $before_id = NULL) {
    $entity_types = $this->getDxprEnabledViewModes($entity_type_filter);

    // Build a query for each entity type.
    $queries = [];
    foreach ($entity_types as $entity_type_id => $view_displays) {
      try {
        if ($this->entityTypeManager->hasHandler($entity_type_id, 'storage')) {
          $entity_type = $this->entityTypeManager->getStorage($entity_type_id)
            ->getEntityType();
          $id_key = $entity_type->getKey('id');
          $bundle_key = $entity_type->getKey('bundle');
          $langcode_key = $entity_type->getKey('langcode');
          $label_key = $entity_type->getKey('label');
          $data_table = $entity_type->getDataTable();

          if ($data_table && $entity_type->isTranslatable()) {
            $query = $this->database->select($data_table);
          }
          else {
            $query = $this->database->select($entity_type->getBaseTable());
          }

          if ($bundle_key) {
            $entity_bundles = array_map(function ($display) {
              return $display->getTargetBundle();
            }, $view_displays);
            $query->condition($bundle_key, $entity_bundles, 'IN');
          }

          if ($before_id) {
            $query->condition($id_key, strval($before_id), '<');
          }

          $query->addExpression("'$entity_type_id'", 'entity_type');
          $query->addExpression($id_key, 'entity_id');
          $query->addExpression($bundle_key ? $bundle_key : "''", 'bundle');
          $query->addExpression($langcode_key ? $langcode_key : "''", 'langcode');
          $query->addExpression($label_key ? $label_key : "''", 'label');

          $queries[] = $query;
        }
      }
      catch (\Exception $exception) {
        $this->loggerFactory->get('dxpr_builder')
          ->error($exception->getMessage());
      }
    }

    // Union all queries.
    $query = array_reduce($queries, function ($a, $b) {
      if (is_null($a)) {
        return $b;
      }
      return $a->union($b);
    });

    // Build a query with the union query in the FROM clause.
    // This is required for count queries and sorting.
    // @see https://www.drupal.org/docs/8/api/database-api/dynamic-queries/count-queries
    return $this->database
      ->select($query, 'base')
      ->fields('base', [
        'entity_type',
        'entity_id',
        'bundle',
        'langcode',
        'label',
      ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getLicenseUsers() {
    $result = '';

    $cache_result = $this->cache->get('dxpr_builder_license_users');
    if ($cache_result !== FALSE && isset($cache_result->valid) && isset($cache_result->data) && $cache_result->valid) {
      return $cache_result->data;
    }

    try {
      $endpoint = self::USERS_ENDPOINT;
      $config = $this->configFactory->getEditable('dxpr_builder.settings');

      $jwt = $config->get('json_web_token');
      if (!$jwt) {
        // No license set. Do not request users from central storage,
        // but make sure to list billable users from the current site.
        $users_data = [];
        foreach ($this->getEditors() as $uid) {
          $user = $this->entityTypeManager->getStorage('user')->load($uid);
          $users_data[$user->getEmail()] = [
            'id' => intval($user->id()),
            'domains' => 1,
            'roles' => $user->getRoles(),
          ];
        }
        return $users_data;
      }

      $request_options = [
        RequestOptions::HEADERS => [
          'Authorization' => 'Bearer ' . $jwt,
        ],
      ];
      $result = $this->client->request('GET', $endpoint, $request_options);
      if ($result instanceof ResponseInterface && $result->getStatusCode() === 200) {
        $result = Json::decode($result->getBody());
        $interval = self::LICENSE_CHECK_INTERVAL;
        $now = $this->time->getRequestTime();
        $this->cache->set('dxpr_builder_license_users', $result['site_users'], $now + $interval);
        $config->set('drm_last_contact', $now);
        $config->save();
      }
    }
    catch (\Exception $e) {
      // Continue without throwing error if `json_web_token` has wrong value.
      // because this case is handled by editor_validation.js.
      if ($e->getCode() !== 403) {
        $this->loggerFactory->get('dxpr_builder')
          ->error($e->getMessage());
        $this->messenger->addError($this->t('We are having trouble connecting to the DXPR servers, the data will refresh when the network is working again.'));
      }
      return [];
    }
    return $result['site_users'];
  }

  /**
   * {@inheritdoc}
   */
  public function withinUsersLimit(AccountInterface $account) {
    $license_info = $this->getLicenseInfo();
    if (empty($license_info['users_limit'])) {
      // Number of users is not limited for this licenses.
      return TRUE;
    }

    // Count users registered before the logged in user.
    $users_before_count = 0;
    $users = $this->getLicenseUsers();
    if (isset($users[$account->getEmail()])) {
      $account_id = $users[$account->getEmail()]['id'];
      foreach ($users as $user) {
        if ($user['id'] < $account_id) {
          ++$users_before_count;
        }
      }
    }
    else {
      $users_before_count = count(array_keys($users));
    }

    return $users_before_count < $license_info['users_limit'];
  }

  /**
   * {@inheritdoc}
   */
  public function withinEntitiesLimit(EntityInterface $entity) {
    $license_info = $this->getLicenseInfo();
    if (empty($license_info['entities_limit'])) {
      // Number of entities is not limited for this licenses.
      return TRUE;
    }
    $entities_before_count = $this->getValuesCount(
      NULL,
      $entity->id()
    );
    return $entities_before_count < $license_info['entities_limit'];
  }

}
