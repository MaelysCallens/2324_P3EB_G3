<?php

namespace Drupal\dxpr_builder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\dxpr_builder\Entity\DxprBuilderPageTemplate;
use Drupal\dxpr_builder\Service\DxprBuilderLicenseServiceInterface;
use Drupal\dxpr_builder\Service\DxprBuilderServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Description.
 */
class PageController extends ControllerBase implements PageControllerInterface {

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The DXPR license service.
   *
   * @var \Drupal\dxpr_builder\Service\DxprBuilderLicenseServiceInterface
   */
  protected $license;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The dxpr builder service.
   *
   * @var \Drupal\dxpr_builder\Service\DxprBuilderServiceInterface
   */
  protected $dxprBuilderService;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a DxprBuilderService object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The Drupal file system
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service
   * @param \Drupal\Core\Extension\ModuleExtensionList $extensionListModule
   *   The module listing service
   * @param \Drupal\dxpr_builder\Service\Handler\BlockHandlerInterface $dxprBlockHandler
   *   The dxpr builder block handler service
   * @param \Drupal\dxpr_builder\Service\Handler\ViewHandlerInterface $dxprViewHandler
   *   The dxpr builder view handler service
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   The cache service;
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   The entity manager service
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   *   The theme handler service
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager service
   * @param \Drupal\Core\Block\BlockManagerInterface $blockManager
   *   The block manager service
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrfToken
   *   The CSRF token generator service
   */

  /**
   * Construct a PageController object.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The form builder service.
   * @param \Drupal\dxpr_builder\Service\DxprBuilderLicenseServiceInterface $license
   *   The DXPR license service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\dxpr_builder\Service\DxprBuilderServiceInterface $dxprBuilderService
   *   The dxpr builder service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager service.
   */
  public function __construct(
    FormBuilderInterface $formBuilder,
    DxprBuilderLicenseServiceInterface $license,
    RequestStack $requestStack,
    Connection $connection,
    EntityTypeManagerInterface $entityTypeManager,
    MessengerInterface $messenger,
    DxprBuilderServiceInterface $dxprBuilderService,
    LanguageManagerInterface $languageManager
  ) {
    $this->formBuilder = $formBuilder;
    $this->license = $license;
    $this->requestStack = $requestStack;
    $this->connection = $connection;
    $this->entityTypeManager = $entityTypeManager;
    $this->messenger = $messenger;
    $this->dxprBuilderService = $dxprBuilderService;
    $this->languageManager = $languageManager;
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-return mixed
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder'),
      $container->get('dxpr_builder.license_service'),
      $container->get('request_stack'),
      $container->get('database'),
      $container->get('entity_type.manager'),
      $container->get('messenger'),
      $container->get('dxpr_builder.service'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function configPage() {
    return [
      '#prefix' => '<div id="dxpr_builder_configuration_page">',
      '#suffix' => '</div>',
      'form' => $this->formBuilder->getForm('Drupal\dxpr_builder\Form\ConfigForm'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function pathsPage() {
    return [
      '#prefix' => '<div id="dxpr_builder_pathsPage_page">',
      '#suffix' => '</div>',
      'form' => $this->formBuilder->getForm('Drupal\dxpr_builder\Form\PathsForm'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function userLicensesPage() {
    $rows = [];
    $users = $this->license->getLicenseUsers();
    foreach ($users as $mail => $info) {
      $user = NULL;
      $info['domains'] = isset($info['domains']) ? (array) $info['domains'] : [];
      $count = count($info['domains']);
      $uid = $this->connection->select('users_field_data', 'u')
        ->fields('u', ['uid'])
        ->condition('u.mail', $mail)
        ->execute()
        ->fetchField();
      if (!empty($uid)) {
        /** @var \Drupal\user\UserInterface $user */
        $user = $this->entityTypeManager()->getStorage('user')->load($uid);
      }

      if (empty($uid)) {
        // User not found.
        $operationsColumnValue = [
          'data' => [
            '#type' => 'link',
            '#title' => $this->t('Delete license data'),
            '#url' => Url::fromRoute('dxpr_builder.delete_stale_user_confirm', [], [
              'query' => [
                'email' => $mail,
              ],
            ]),
            '#attributes' => [
              'title' => $this->t('Remove stale user data. This will delete all license info for this email.'),
            ],
          ],
        ];
      }
      elseif ($user->get('dxpr_user_is_disavowed')->value) {
        // User found and is excluded.
        $operationsColumnValue = $this->t('Editing disabled');
      }
      else {
        // User is not excluded.
        $operationsColumnValue = [
          'data' => [
            '#type' => 'link',
            '#title' => $this->t('Disable editing permission'),
            '#url' => Url::fromRoute('dxpr_builder.disavow_user_confirm', [], [
              'query' => [
                'uid' => $uid,
              ],
            ]),
            '#attributes' => [
              'title' => $this->t('Revoke DXPR Builder editing permissions for this user on this site.'),
            ],
          ],
        ];
      }

      $rows[] = [
        $uid ? [
          'data' => [
            '#type' => 'link',
            '#title' => $mail,
            '#url' => Url::fromRoute('entity.user.canonical', [
              'user' => $uid,
            ]),
          ],
        ] : $mail,
        [
          'data' => [
            '#theme' => 'item_list',
            '#items' => array_unique($info['roles']),
          ],
        ],
        [
          'data' => [
            '#type' => 'link',
            '#title' => $this->formatPlural($count, '1 site', '@count sites'),
            '#url' => Url::fromRoute('dxpr_builder.user_licenses.sites', [
              'mail' => $mail,
            ]),
            '#attributes' => [
              'class' => 'use-ajax',
              'data-dialog-type' => 'modal',
            ],
          ],
        ],
        $operationsColumnValue,
      ];
    }

    return [
      '#cache' => [
        'max-age' => 0,
      ],
      '#theme' => 'table',
      '#header' => [
        $this->t('User'),
        $this->t('Roles'),
        $this->t('Sites'),
        $this->t('Operations'),
      ],
      '#empty' => $this->t('There are no billable users recorded yet.'),
      '#rows' => $rows,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function userLicensesSitesPage() {
    $mail = $this->requestStack->getCurrentRequest()->get('mail');
    $users = $this->license->getLicenseUsers();
    $domains = isset($users[$mail]) ? $users[$mail]['domains'] : [];
    return [
      '#cache' => [
        '#max-age' => 0,
      ],
      '#theme' => 'item_list',
      '#items' => $domains,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function licensedContentPage() {
    $count = $this->license->getValuesCount();
    $licensed_content = $this->license->getLicensedContentQuery();

    $view_modes = $this->license->getDxprEnabledViewModes();

    // Create a pager query.
    // @phpstan-ignore-next-line
    $pager_query = $licensed_content->extend(PagerSelectExtender::class)->limit(20);

    $result = $pager_query->execute();

    // Render results in a render array using '#theme' => 'table'.
    $header = [
      ['data' => $this->t('Title')],
      ['data' => $this->t('Language')],
      ['data' => $this->t('Entity type')],
      ['data' => $this->t('Entity bundle')],
      ['data' => $this->t('View modes')],
    ];
    $rows = [];
    foreach ($result as $record) {
      $entity_type = $this->entityTypeManager->getDefinition($record->entity_type);

      // Get name for language.
      $language = $this->languageManager->getLanguage($record->langcode)->getName();

      // Create a link for nodes.
      $link = $record->entity_type == 'node' ? [
        '#type' => 'link',
        '#title' => $record->label,
        '#url' => Url::fromRoute('entity.node.canonical', ["node" => $record->entity_id]),
      ] : $record->label;

      // Get readable name for $record->entity_bundle.
      if ($record->bundle && ($bundle_entity_type = $entity_type->get('bundle_entity_type'))) {
        $bundle = $this->entityTypeManager->getStorage($bundle_entity_type)->load($record->bundle);
        $bundle_label = $bundle->label();
      }
      else {
        $bundle_label = $record->bundle;
      }

      // Get view modes for the bundle.
      $bundle_view_modes = array_filter($view_modes[$record->entity_type], function ($view_mode) use ($record) {
        return $view_mode->getTargetBundle() == $record->bundle;
      });
      $view_mode_names = array_map(function ($view_mode) {
        // View modes don't have labels but uses the id instead.
        $parts = explode('.', $view_mode->id());
        return array_pop($parts);
      }, $bundle_view_modes);

      $rows[] = [
        'link' => ['data' => $link],
        'language' => $language,
        'label' => $entity_type->get('label'),
        'bundle' => $bundle_label,
        'view_modes' => implode(', ', $view_mode_names),
      ];
    }

    $info = $this->license->getLicenseInfo();

    return [
      '#cache' => [
        'max-age' => 0,
      ],
      'info' => [
        '#theme' => 'dxpr-license-info',
        '#total_count' => $count,
        // Used is the number of entities maximized by the limit, if any.
        '#used_count' => $info['entities_limit'] ? min(intval($count), intval($info['entities_limit'])) : $count,
        '#limit' => $info['entities_limit'],
        '#block_label' => $this->t('DXPR Content items'),
        '#total_label' => $this->t('DXPR  Builder content items'),
        '#used_label' => $this->t('Max items used'),
        '#more_info_link' => NULL,
        '#attached' => [
          'library' => ['dxpr_builder/user-licenses'],
        ],
      ],
      'explainer' => [
        '#markup' => $this->t('The content items limit is limiting how many pages, blocks, etc. you can edit with DXPR Builder. You can increase or eliminate the limit by <a href="https://app.dxpr.com/user/me/subscription/change">upgrading to a higher subscription tier</a>.'),
      ],
      'table' => [
        '#theme' => 'table',
        '#header' => $header,
        '#rows' => $rows,
      ],
      'pager' => [
        '#type' => 'pager',
      ],
    ];
  }

  /**
   * Create page template from user template.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|string
   *   User template url string
   */
  public function createPageTemplate() {
    // Get the query parameter ''uuid from URL.
    $uuid = $this->requestStack->getCurrentRequest()->get('uuid');
    $template = '';
    /** @var \Drupal\dxpr_builder\Entity\DxprBuilderUserTemplate[] $templates */
    $templates = $this->entityTypeManager->getStorage('dxpr_builder_user_template')
      ->loadByProperties([
        'uuid' => $uuid,
        'status' => 1,
      ]);
    if ($templates) {
      $template = reset($templates);
      if ($this->pageTemplateIsExists($template->getOriginalId())) {
        $this->messenger->addError($this->t('Page template creation failed because a page template with the same name already exists'));
      }
      else {
        $label = $template->label();
        $userTemplate = $template->get('template');
        $this->dxprBuilderService->replaceBaseTokens($userTemplate);

        $pageStorage = $this->entityTypeManager->getStorage('dxpr_builder_page_template');
        // Create page template with user template values.
        $page = $pageStorage->create([
          'id' => $template->getOriginalId(),
          'label' => $label,
          'template' => $userTemplate,
          'category' => 'DXPR',
        ]);
        $page->save();

        $this->messenger->addStatus($this->t('Page template <strong>@label</strong> was created: <a href="@url">View page templates dashboard</a>', [
          '@label' => $label,
          '@url' => Url::fromRoute('entity.dxpr_builder_page_template.collection')->toString(),
        ]));
      }
    }
    return new RedirectResponse(Url::fromRoute('entity.dxpr_builder_user_template.collection')->toString());
  }

  /**
   * Helper function to check if the template exists.
   *
   * @param string $machine_name
   *   The machine name of the page template.
   *
   * @return bool
   *   Checking result.
   */
  private function pageTemplateIsExists($machine_name) {
    $page_template = DxprBuilderPageTemplate::load($machine_name);
    if ($page_template) {
      return TRUE;
    }
    return FALSE;
  }

}
