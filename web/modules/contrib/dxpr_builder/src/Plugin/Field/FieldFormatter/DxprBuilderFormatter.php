<?php

namespace Drupal\dxpr_builder\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Extension\InfoParser;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Url;
use Drupal\dxpr_builder\Entity\DxprBuilderProfile;
use Drupal\dxpr_builder\Service\DxprBuilderJWTDecoder;
use Drupal\dxpr_builder\Service\DxprBuilderLicenseServiceInterface;
use Drupal\dxpr_builder\Service\DxprBuilderServiceInterface;
use Drupal\dxpr_builder\Service\Handler\ProfileHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Plugin implementation of the 'dxpr_builder_text' formatter.
 *
 * @FieldFormatter(
 *    id = "dxpr_builder_text",
 *    label = @Translation("DXPR Builder"),
 *    field_types = {
 *       "text",
 *       "text_long",
 *       "text_with_summary"
 *    }
 * )
 */
class DxprBuilderFormatter extends FormatterBase {

  use StringTranslationTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current path stack.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPathStack;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The CSRF token generator service.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfToken;

  /**
   * The extension path resolver service.
   *
   * @var \Drupal\Core\Extension\ExtensionPathResolver
   */
  protected $extensionPathResolver;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The dxpr builder service.
   *
   * @var \Drupal\dxpr_builder\Service\DxprBuilderServiceInterface
   */
  protected $dxprBuilderService;

  /**
   * The dxpr builder license service.
   *
   * @var \Drupal\dxpr_builder\Service\DxprBuilderLicenseServiceInterface
   */
  private $dxprBuilderLicenseService;

  /**
   * The profile handler service.
   *
   * @var \Drupal\dxpr_builder\Service\Handler\ProfileHandler
   */
  private $profileHandler;

  /**
   * Parsing yaml file.
   *
   * @var \Drupal\Core\Extension\InfoParser
   */
  private $infoParser;

  /**
   * JWT service to manipulate the DXPR JSON token.
   *
   * @var \Drupal\dxpr_builder\Service\DxprBuilderJWTDecoder
   */
  protected $jwtDecoder;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Construct a DxprBuilderFormatter object.
   *
   * @param string $plugin_id
   *   The ID of the formatter.
   * @param string $plugin_definition
   *   The formatter definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field.
   * @param mixed[] $settings
   *   The settings of the formatter.
   * @param string $label
   *   The position of the lable when the field is rendered.
   * @param string $view_mode
   *   The current view mode.
   * @param mixed[] $third_party_settings
   *   Any third-party settings.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   * @param \Drupal\Core\Path\CurrentPathStack $currentPathStack
   *   The current path stack.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager service.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrfToken
   *   The CSRF token generator service.
   * @param \Drupal\Core\Extension\ExtensionPathResolver $extensionPathResolver
   *   The extension path resolver service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\dxpr_builder\Service\DxprBuilderServiceInterface $dxprBuilderService
   *   The dxpr builder service.
   * @param \Drupal\dxpr_builder\Service\DxprBuilderLicenseServiceInterface $dxpr_builder_license_service
   *   The dxpr builder license service.
   * @param \Drupal\dxpr_builder\Service\Handler\ProfileHandler $profile_handler
   *   The profile handler service.
   * @param \Drupal\Core\Extension\InfoParser $infoParser
   *   Parsing yaml file service.
   * @param \Drupal\dxpr_builder\Service\DxprBuilderJWTDecoder $jwtDecoder
   *   Parsing DXPR JWT token.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    AccountProxyInterface $currentUser,
    ConfigFactoryInterface $configFactory,
    CurrentPathStack $currentPathStack,
    RequestStack $requestStack,
    LanguageManagerInterface $languageManager,
    CsrfTokenGenerator $csrfToken,
    ExtensionPathResolver $extensionPathResolver,
    ModuleHandlerInterface $moduleHandler,
    RendererInterface $renderer,
    DxprBuilderServiceInterface $dxprBuilderService,
    DxprBuilderLicenseServiceInterface $dxpr_builder_license_service,
    ProfileHandler $profile_handler,
    InfoParser $infoParser,
    DxprBuilderJWTDecoder $jwtDecoder,
    ThemeHandlerInterface $theme_handler,
    ThemeManagerInterface $theme_manager,
    Messenger $messenger,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct(
      $plugin_id,
      $plugin_definition,
      $field_definition,
      $settings,
      $label,
      $view_mode,
      $third_party_settings
    );

    $this->currentUser = $currentUser;
    $this->configFactory = $configFactory;
    $this->currentPathStack = $currentPathStack;
    $this->requestStack = $requestStack;
    $this->languageManager = $languageManager;
    $this->csrfToken = $csrfToken;
    $this->extensionPathResolver = $extensionPathResolver;
    $this->moduleHandler = $moduleHandler;
    $this->renderer = $renderer;
    $this->dxprBuilderService = $dxprBuilderService;
    $this->dxprBuilderLicenseService = $dxpr_builder_license_service;
    $this->profileHandler = $profile_handler;
    $this->infoParser = $infoParser;
    $this->jwtDecoder = $jwtDecoder;
    $this->themeHandler = $theme_handler;
    $this->themeManager = $theme_manager;
    $this->messenger = $messenger;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<mixed> $configuration
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('config.factory'),
      $container->get('path.current'),
      $container->get('request_stack'),
      $container->get('language_manager'),
      $container->get('csrf_token'),
      $container->get('extension.path.resolver'),
      $container->get('module_handler'),
      $container->get('renderer'),
      $container->get('dxpr_builder.service'),
      $container->get('dxpr_builder.license_service'),
      $container->get('dxpr_builder.profile_handler'),
      $container->get('info_parser'),
      $container->get('dxpr_builder.jwt_decoder'),
      $container->get('theme_handler'),
      $container->get('theme.manager'),
      $container->get('messenger'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Description.
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = $this->t('The DXPR Builder drag and drop interface');

    return $summary;
  }

  /**
   * Builds a renderable array for a field value.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field values to be rendered.
   * @param mixed $langcode
   *   The language that should be used to render the field.
   *
   * @phpstan-param \Drupal\Core\Field\FieldItemListInterface<\Drupal\Core\Field\FieldItemInterface> $items
   *
   * @return array
   *   A renderable array for $items, as an array of child elements keyed by
   *   consecutive numeric indexes starting from 0.
   *
   * @phpstan-return array<string, mixed>
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $element = [];

    $config = $this->configFactory->get('dxpr_builder.settings');

    $entity_type = $this->fieldDefinition->getTargetEntityTypeId();
    $bundle = $this->fieldDefinition->getTargetBundle();
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $items->getEntity();
    $id = $entity->id();
    $vid = $entity->getRevisionId();
    $field_name = $this->fieldDefinition->getName();
    $container_name = $id . '|' . $vid . '|' . $field_name;
    $entity_label = $entity->label();
    $loadAsBlock = FALSE;
    if ($this->requestStack->getCurrentRequest()->getPathInfo() == '/dxpr_builder/ajax') {
      $loadAsBlock = TRUE;
    }
    $enable_editor = FALSE;
    $has_permission = $this->dxprBuilderLicenseService->isBillableUser()
      && $entity->access('update', $this->currentUser)
      && !$loadAsBlock
      && !$this->dxprBuilderLicenseService->isBlacklisted();
    $warning = NULL;
    $messages_active = [
      'free_items_limit' => FALSE,
      'free_users_limit' => FALSE,
      'insufficient_users' => FALSE,
    ];
    if ($has_permission) {
      $within_users_limit = $this->dxprBuilderLicenseService->withinUsersLimit($this->currentUser);

      // Allow user with UID 1 always to have access.
      if ((int) $this->currentUser->id() === 1) {
        $within_users_limit = TRUE;
      }
      $within_entities_limit = $this->dxprBuilderLicenseService->withinEntitiesLimit($entity);
      $enable_editor = $within_users_limit && $within_entities_limit;
      if (!$within_users_limit) {
        $license_info = $this->dxprBuilderLicenseService->getLicenseInfo();
        if ($license_info['tier'] == 'free') {
          $username = $this->entityTypeManager->getStorage('user')->load(1)->name->value;
          $warning = $this->t('Oops, the no-code editor is not loading here. Only one account can use DXPR Builder on the DXPR Free tier. This permission is automatically assigned to the user account with username "%username". Please <a href="@add_subscription" target="_blank">add a paid subscription at DXPR.com</a> to use DXPR Builder with more than one user.', [
            '%username' => $username,
            '@add_subscription' => 'https://app.dxpr.com/user/me/subscription',
          ]);
          $messages_active['free_users_limit'] = TRUE;
        }
        else {
          $add_users_url = 'https://app.dxpr.com/user/me/subscription/add-ons';
          $manage_people_url = Url::fromRoute('entity.user.collection')->toString();
          $dxpr_builder_user_licenses_url = Url::fromRoute('dxpr_builder.user_licenses')->toString();

          $warning = $this->t('Oops, the no-code editor is not loading here because there are insufficient User licenses included in your DXPR.com subscription. There currently are <a href=":dxpr_builder_user_licenses_url">@users accounts</a> connected to your product key to use DXPR Builder but there are only @users_limit Users available to your account. Please <a href=":add_users_url" target="_blank">add more Users to your subscription</a> or <a href=":manage_people_url">remove DXPR Builder editing privileges</a> from user accounts to resolve this.', [
            '@users' => $license_info['users_count'],
            '@users_limit' => $license_info['users_limit'],
            ':add_users_url' => $add_users_url,
            ':manage_people_url' => $manage_people_url,
            ':dxpr_builder_user_licenses_url' => $dxpr_builder_user_licenses_url,
          ]);
          $messages_active['insufficient_users'] = TRUE;
        }
      }
      if (!$within_entities_limit) {
        $license_info = $this->dxprBuilderLicenseService->getLicenseInfo();
        $tier = !empty($license_info['tier']) ? ucfirst($license_info['tier']) : $this->t('Free');
        $dxpr_builder_content_licenses_url = Url::fromRoute('dxpr_builder.licensed_content')->toString();
        $warning = $this->t('Sorry, you cannot author more than <a href=":dxpr_builder_content_licenses_url">@entities_limit content items</a> with DXPR Builder on the DXPR @tier tier. Please <a href="@add_subscription" target="_blank">upgrade your account at DXPR.com</a> to create more content with DXPR Builder.', [
          '@add_subscription' => 'https://app.dxpr.com/user/me/subscription/change',
          '@entities_limit' => $license_info['entities_limit'],
          '@tier' => $tier,
          ':dxpr_builder_content_licenses_url' => $dxpr_builder_content_licenses_url,
        ]);
        $messages_active['free_items_limit'] = TRUE;
      }
      if ($warning) {
        $this->messenger->addMessage($warning, 'warning');
      }
    }

    $element['#attached']['drupalSettings']['dxprBuilder']['messagesActive'] = $messages_active;

    foreach ($items as $delta => $item) {
      // Ignore initializing the builder at excluded pages and empty entities.
      if ($item->getEntity()->id() == NULL) {
        continue;
      }
      /* @phpstan-ignore-next-line */
      $value = $item->value;
      $element[$delta] = [];
      if ($item->getLangcode()) {
        $langcode = $item->getLangcode();
      }
      else {
        $langcode = $this->languageManager->getCurrentLanguage()->getId();
      }
      $human_readable = base64_encode(Html::escape($field_name . ' on ' . str_replace('node', 'page', $entity_type) . ' ' . $entity_label . ' '));
      $attrs = 'class="az-element az-container dxpr" data-az-type="' . $entity_type . '|' . $bundle . '" data-az-name="' . $container_name . '" data-az-human-readable="' . $human_readable . '" data-az-langcode="' . $langcode . '"';
      preg_match('/^\s*\<[\s\S]*\>\s*$/', $value, $html_format);

      // non-breaking space if the forced default value in DXPR Builder.
      // This prevents the field from not rendering at all.
      $clean_empty_value = str_replace('&nbsp;', '', $value);
      if (!$clean_empty_value && $enable_editor) {
        $output = '<div ' . $attrs . ' style="display:none"></div>';
        $mode = 'static';
      }
      else {
        if (!$html_format) {
          $value = '<p>' . $value . '</p>';
        }
        $response = $this->dxprBuilderService->updateHtml($value, $enable_editor);
        $output = $response['output'];
        $mode = $response['mode'];
        $libraries = $response['library'];
        $settings = $response['settings'];

        foreach ($libraries as $library) {
          $element[$delta]['#attached']['library'][] = $library;
        }

        // Adds html_head scripts.
        if (isset($settings['dxpr_html_head'])) {
          $element[$delta]['#attached']['html_head'] = $settings['dxpr_html_head'];
          unset($settings['dxpr_html_head']);
        }

        foreach ($settings as $key => $setting) {
          $element[$delta]['#attached']['drupalSettings'][$key] = $setting;
        }
        $output = '<div ' . $attrs . ' data-az-mode="' . $mode . '">' . $output . '</div>';

        // DXPR Builder 1.1.0 Experimental feature: Process Text Format
        // Filters for non-editors ~Jur 15/06/16
        // Don't run text format filters when editor is loaded because
        // the editor would save all filter output into the db.
        if (!$this->dxprBuilderLicenseService->isBillableUser()
          && $config->get('format_filters')) {
          $build = [
            '#type' => 'processed_text',
            '#text' => $output,
            '#format' => $item->__get('format'),
            '#  ' => [],
            '#langcode' => $langcode,
          ];

          $output = $this->renderer->renderPlain($build);
        }
      }

      $element[$delta]['#markup'] = Markup::create($output);
      $element[$delta]['#id'] = $id . '|' . $field_name;
      // Attach DXPR Builder assets.
      $this->attachAssets($container_name, $element[$delta], $value, $html_format, $enable_editor, $mode, $this->languageManager->getCurrentLanguage()->getId());
    }

    $element['#cache']['max-age'] = $warning ? 0 : DxprBuilderLicenseServiceInterface::LICENSE_NOT_AUTHORIZED_INTERVAL;
    $element['#cache']['contexts'] = ['url'];
    $element['#cache']['tags'] = $config->getCacheTags();
    $profile = DxprBuilderProfile::loadByRoles($this->currentUser->getRoles());
    if ($profile) {
      $profile_settings = $this->profileHandler->buildSettings($profile);
      $element['#attached']['drupalSettings']['dxprBuilder']['profile'] = $profile_settings;
      $element['#cache']['tags'] = Cache::mergeTags($element['#cache']['tags'], $profile->getCacheTags());
    }

    $this->moduleHandler->invokeAll('dxpr_builder_view_elements', [&$element]);

    return $element;
  }

  /**
   * Attaches CSS and JS assets to field render array.
   *
   * @param string $container_name
   *   Unique container identifier.
   * @param mixed[] $element
   *   A renderable array for the $items, as an array of child
   *   elements keyed by numeric indexes starting from 0.
   * @param string $content
   *   Raw field value.
   * @param mixed[] $html_format
   *   Valid HTML field value.
   * @param bool $enable_editor
   *   When FALSE only frontend rendering assets will be attached. When TRUE
   *   the full drag and drop editor will be attached.
   * @param string $mode
   *   The mode.
   * @param string $dxpr_lang
   *   Two letter language code.
   *
   * @phpstan-return array<string, mixed>
   *
   * @see https://api.drupal.org/api/drupal/modules!field!field.api.php/function/hook_field_formatter_view/7.x
   */
  public function attachAssets($container_name, array &$element, $content, $html_format, $enable_editor, $mode, $dxpr_lang): array {
    $config = $this->configFactory->get('dxpr_builder.settings');

    $settings = [];
    $settings['disallowContainers'] = [];
    $settings['currentPath'] = $this->currentPathStack->getPath();

    $settings['offsetSelector'] = $config->get('offset_selector') ?: '.dxpr-theme-header--sticky, .dxpr-theme-header--fixed';

    if ($enable_editor) {
      $settings['dxprEditor'] = TRUE;
    }

    if ($this->moduleHandler->moduleExists('dxpr_builder_e')) {
      $settings['enterprise'] = TRUE;
    }

    $url = Url::fromRoute('dxpr_builder.ajax_callback');
    $token = $this->csrfToken->get($url->getInternalPath());
    $dxprBuilderPath = $this->getPath('module', 'dxpr_builder');
    $url->setOptions(['query' => ['token' => $token]]);
    $settings['dxprAjaxUrl'] = $url->toSTring();

    $csrf_url = Url::fromRoute('dxpr_builder.csrf_refresh');
    $settings['dxprCsrfUrl'] = $csrf_url->toSTring();

    $settings['dxprLanguage'] = $dxpr_lang;

    $infoFile = $this->infoParser->parse($dxprBuilderPath . '/dxpr_builder.info.yml');
    if (!empty($infoFile['version'])) {
      $settings['dxprBuilderVersion'] = $infoFile['version'];
    }
    else {
      $settings['dxprBuilderVersion'] = 'dev';
    }

    $settings['dxprBaseUrl'] = base_path() . $dxprBuilderPath . '/dxpr_builder/';
    $settings['dxprBasePath'] = base_path();

    if ($config->get('json_web_token') != NULL) {
      $jwtPayloadData = $this->jwtDecoder->decodeJwt($config->get('json_web_token'));
      if ($jwtPayloadData['sub'] != NULL || $jwtPayloadData['scope'] != NULL) {
        $settings['dxprTokenInfo'] = $jwtPayloadData;
      }
    }

    $settings['dxprSubscriptionInfo'] = $this->dxprBuilderLicenseService->getLicenseInfo();
    $settings['dxprDrmLastContact'] = $config->get('drm_last_contact');
    $settings['serverTime'] = time();

    if ($this->currentUser->id() != 0) {
      // Check if DXPR User id exists in JWT.
      $dxpr_user_exists = isset($jwtPayloadData) ? array_key_exists('sub', $jwtPayloadData) : FALSE;
      // Get enabled modules.
      $module_list = $this->moduleHandler->getModuleList();

      $settings['dxprUserInfo'] = [
        'local_email' => $this->currentUser->getEmail(),
        'local_email_hashed' => $this->hashEmail($this->currentUser->getEmail(), 14),
        'local_uid' => $this->currentUser->id(),
        'local_username' => $this->currentUser->getDisplayName(),
        'default_theme' => $this->themeHandler->getDefault(),
        'active_theme' => $this->themeManager->getActiveTheme()->getName(),
        'drupal_version' => \Drupal::VERSION,
        'installed_modules' => array_keys($module_list),
        'dxpr_user_id' => $dxpr_user_exists ? $jwtPayloadData['sub'] : NULL,
      ];

      $settings['dxprSiteInfo'] = [
        'dxpr_builder_editors' => $this->dxprBuilderLicenseService->getUsersCount(),
        'dxpr_builder_items' => $this->dxprBuilderLicenseService->getValuesCount(NULL, NULL),
      ];
    }

    $element['#attached']['library'][] = 'dxpr_builder/core';
    if (!$enable_editor) {
      if ($mode == 'dynamic') {
        if ($config->get('editor_assets_source') == 2) {
          $element['#attached']['library'][] = 'dxpr_builder/editor.frontend_dev';
        }
        else {
          $element['#attached']['library'][] = 'dxpr_builder/editor.frontend';
        }
      }
    }

    if ($config->get('editor_assets_source') == 2) {
      $element['#attached']['library'][] = 'dxpr_builder/editor.dev';
      $settings['dxprDevelopment'] = TRUE;
    }

    if ($config->get('bootstrap') == 1) {
      $element['#attached']['library'][] = 'dxpr_builder/bootstrap_3';
    }
    elseif ($config->get('bootstrap') === 'bs4') {
      $element['#attached']['library'][] = 'dxpr_builder/bootstrap_4';
    }
    elseif ($config->get('bootstrap') === 'bs5') {
      $element['#attached']['library'][] = 'dxpr_builder/bootstrap_5';
    }

    if ($enable_editor) {
      $this->dxprBuilderService->editorAttach($element, $settings);
    }
    else {
      $settings['disallowContainers'][] = $container_name;
    }

    $settings['mediaBrowser'] = $config->get('media_browser') ?: '';
    if ($settings['mediaBrowser'] != '') {
      // Attach Entity Browser Configurations and libraries.
      $element['#attached']['drupalSettings']['entity_browser'] = [
        'dxprBuilderSingle' => [
          'cardinality' => 1,
          'selection_mode' => 'selection_append',
          'selector' => FALSE,
        ],
        'dxprBuilderMulti' => [
          'cardinality' => -1,
          'selection_mode' => 'selection_append',
          'selector' => FALSE,
        ],
      ];
      $element['#attached']['library'][] = 'entity_browser/common';
    }

    if ($this->moduleHandler->moduleExists('color')) {
      if ($palette = $this->colorGetPalette()) {
        $settings['palette'] = array_slice($palette, 0, 10);
      }
    }

    // Related to DXPR analytics service.
    $settings['recordAnalytics'] = FALSE;
    $recordAnalytics = ($config->get('record_analytics') === NULL) ? TRUE : $config->get('record_analytics');
    if ($recordAnalytics) {
      $settings['recordAnalytics'] = TRUE;
    }

    // Related to DXPR hiding reminders after 5 clicks.
    $settings['hideReminders'] = ($config->get('hide_reminders') === NULL)
        ? TRUE
        : $config->get('hide_reminders');

    // Related to DXPR notifications.
    $notifications = ($config->get('notifications') === NULL) ? TRUE : $config->get('notifications');
    if ($notifications) {
      $settings['notifications'] = $notifications;
    }

    $element['#attached']['drupalSettings']['dxprBuilder'] = $settings;

    return [];
  }

  /**
   * Wrapper for drupal_get_path()
   *
   * @param string $type
   *   The type of path to return, module or theme.
   * @param string $name
   *   The name of the theme/module to look up.
   *
   * @return string
   *   The path to the given module/theme
   *
   * @see drupal_get_path()
   */
  private function getPath($type, $name) {
    $paths = &drupal_static(__CLASS__ . '::' . __FUNCTION__, []);
    $key = $type . '::' . $name;
    if (!isset($paths[$key])) {
      $paths[$key] = $this->extensionPathResolver->getPath($type, $name);
    }

    return $paths[$key];
  }

  /**
   * Get the theme color pallette for the current theme.
   *
   * @return array|null
   *   Return color palette if possible.
   *
   * @phpstan-return array<string, mixed>
   */
  private function colorGetPalette(): ?array {
    $default_theme = $this->configFactory->get('system.theme')->get('default');

    /* @phpstan-ignore-next-line */
    $info = color_get_info($default_theme);
    if ($info && array_key_exists('colors', $info['schemes']['default'])) {
      /* @phpstan-ignore-next-line */
      return color_get_palette($default_theme);
    }

    return NULL;
  }

  /**
   * Partly hash an email address with sha256.
   *
   * @param string $email
   *   The email address.
   * @param int $length
   *   (optional) The length of the returned string.
   *
   * @return string
   *   The hashed email address.
   */
  private function hashEmail($email, $length = 0) {
    $parts = explode('@', $email);
    $hashed = hash('sha256', $parts[0] . "48dfhj2k9");
    $hashed = ($length > 0) ? substr($hashed, 0, $length) : $hashed;
    return $hashed . '@' . $parts[1];
  }

}
