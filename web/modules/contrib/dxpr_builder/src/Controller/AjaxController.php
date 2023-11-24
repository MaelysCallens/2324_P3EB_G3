<?php

namespace Drupal\dxpr_builder\Controller;

use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Asset\AssetCollectionRendererInterface;
use Drupal\Core\Asset\AssetResolverInterface;
use Drupal\Core\Asset\AttachedAssets;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\RevisionableStorageInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Drupal\dxpr_builder\Entity\DxprBuilderUserTemplate;
use Drupal\dxpr_builder\Service\ContentLock;
use Drupal\dxpr_builder\Service\DxprBuilderLicenseServiceInterface;
use Drupal\dxpr_builder\Service\DxprBuilderServiceInterface;
use Drupal\dxpr_builder\Service\UploadHandler;
use Drupal\views\Views;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description.
 */
class AjaxController extends ControllerBase implements AjaxControllerInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The list of available modules.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $extensionListModule;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The asset resolver service.
   *
   * @var \Drupal\Core\Asset\AssetResolverInterface
   */
  protected $assetResolver;

  /**
   * The CSS asset collection renderer.
   *
   * @var \Drupal\Core\Asset\AssetCollectionRendererInterface
   */
  protected $cssAssetCollectionRenderer;

  /**
   * The JS asset collection renderer.
   *
   * @var \Drupal\Core\Asset\AssetCollectionRendererInterface
   */
  protected $jsAssetCollectionRenderer;

  /**
   * The entity type bundle info manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfoManager;

  /**
   * The dxpr builder service.
   *
   * @var \Drupal\dxpr_builder\Service\DxprBuilderServiceInterface
   */
  protected $dxprBuilderService;

  /**
   * The dxpr license service.
   *
   * @var \Drupal\dxpr_builder\Service\DxprBuilderLicenseServiceInterface
   */
  protected $dxprLicenseService;

  /**
   * The CSRF token generator service.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfToken;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  private $state;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The content lock service.
   *
   * @var \Drupal\dxpr_builder\Service\ContentLock
   */
  private $contentLock;

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

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
   * Construct an AjaxController object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   * @param \Drupal\Core\Extension\ModuleExtensionList $extensionListModule
   *   The module listing service.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager service.
   * @param \Drupal\Core\Asset\AssetResolverInterface $assetResolver
   *   The asset resolver service.
   * @param \Drupal\Core\Asset\AssetCollectionRendererInterface $cssAssetCollectionRenderer
   *   The CSS asset collection renderer.
   * @param \Drupal\Core\Asset\AssetCollectionRendererInterface $jsAssetCollectionRenderer
   *   The JS asset collection renderer.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfoManager
   *   The entity type bundle info manager.
   * @param \Drupal\dxpr_builder\Service\DxprBuilderServiceInterface $dxprBuilderService
   *   The dxpr builder service.
   * @param \Drupal\dxpr_builder\Service\DxprBuilderLicenseServiceInterface $dxprLicenseService
   *   The dxpr license service.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrfToken
   *   The CSRF token generator service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\dxpr_builder\Service\ContentLock $contentLock
   *   The content lock service.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $fileUrlGenerator
   *   The file URL generator.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   */
  public function __construct(
    AccountProxyInterface $currentUser,
    ModuleHandlerInterface $moduleHandler,
    ModuleExtensionList $extensionListModule,
    Connection $database,
    RequestStack $requestStack,
    EntityTypeManagerInterface $entityTypeManager,
    EntityFieldManagerInterface $entityFieldManager,
    AssetResolverInterface $assetResolver,
    AssetCollectionRendererInterface $cssAssetCollectionRenderer,
    AssetCollectionRendererInterface $jsAssetCollectionRenderer,
    EntityTypeBundleInfoInterface $entityTypeBundleInfoManager,
    DxprBuilderServiceInterface $dxprBuilderService,
    DxprBuilderLicenseServiceInterface $dxprLicenseService,
    CsrfTokenGenerator $csrfToken,
    LanguageManagerInterface $languageManager,
    StateInterface $state,
    RendererInterface $renderer,
    ContentLock $contentLock,
    FileUrlGeneratorInterface $fileUrlGenerator,
    ClientInterface $http_client
  ) {
    $this->currentUser = $currentUser;
    $this->moduleHandler = $moduleHandler;
    $this->extensionListModule = $extensionListModule;
    $this->database = $database;
    $this->requestStack = $requestStack;
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
    $this->assetResolver = $assetResolver;
    $this->cssAssetCollectionRenderer = $cssAssetCollectionRenderer;
    $this->jsAssetCollectionRenderer = $jsAssetCollectionRenderer;
    $this->entityTypeBundleInfoManager = $entityTypeBundleInfoManager;
    $this->dxprBuilderService = $dxprBuilderService;
    $this->dxprLicenseService = $dxprLicenseService;
    $this->csrfToken = $csrfToken;
    $this->languageManager = $languageManager;
    $this->state = $state;
    $this->renderer = $renderer;
    $this->contentLock = $contentLock;
    $this->fileUrlGenerator = $fileUrlGenerator;
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-return mixed
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('module_handler'),
      $container->get('extension.list.module'),
      $container->get('database'),
      $container->get('request_stack'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('asset.resolver'),
      $container->get('asset.css.collection_renderer'),
      $container->get('asset.js.collection_renderer'),
      $container->get('entity_type.bundle.info'),
      $container->get('dxpr_builder.service'),
      $container->get('dxpr_builder.license_service'),
      $container->get('csrf_token'),
      $container->get('language_manager'),
      $container->get('state'),
      $container->get('renderer'),
      $container->get('dxpr_builder.content_lock'),
      $container->get('file_url_generator'),
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxRefresh(): JsonResponse {
    $url = Url::fromRoute('dxpr_builder.ajax_callback');

    // Check if request related to enterprise.
    $enterprise = FALSE;
    $enterprise_installed = $this->requestStack->getCurrentRequest()->query->get('enterprise');
    if (isset($enterprise_installed) && $enterprise_installed == 'true') {
      $enterprise = TRUE;
    }
    if ($enterprise && $this->moduleHandler->moduleExists('dxpr_builder_e')) {
      $url = Url::fromRoute('dxpr_builder_e.ajax_callback');
    }
    elseif ($enterprise && !$this->moduleHandler->moduleExists('dxpr_builder_e')) {
      throw new \Exception("The DXPR Builder Enterprise module doesn't exist or disabled.");
    }

    $token = $this->csrfToken->get($url->getInternalPath());
    $url->setOptions(['query' => ['token' => $token]]);

    return new JsonResponse($url->toSTring());
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxCallback(): Response {
    $post_action = $this->requestStack->getCurrentRequest()->request->get('action');
    $action = $post_action ? $post_action : FALSE;
    $response = new AjaxResponse('');
    $html = '';

    switch ($action) {
      // Determine if the current user has access to DXPR Builder.
      case 'dxpr_login':
        $response = $this->hasEditAccess();

        break;

      // Determine if the current user has access to DXPR Builder.
      case 'dxpr_builder_csrf':
        if ($this->dxprLicenseService->isBillableUser()) {
          $url = Url::fromRoute('dxpr_builder.ajax_callback');
          $token = $this->csrfToken->get($url->getInternalPath());
          $url->setOptions([
            'query' => ['token' => $token],
          ]
          );
          $response = new JsonResponse($url->toSTring());
        }

        break;

      // Get a list of dxpr builder container types.
      case 'dxpr_get_container_types':
        if ($this->dxprLicenseService->isBillableUser()) {
          $response = $this->getContainerTypes();
        }

        break;

      // Get a list of dxpr builder container names.
      case 'dxpr_get_container_names':
        $container_type = $this->requestStack->getCurrentRequest()->request->get('container_type');
        if ($this->dxprLicenseService->isBillableUser() && !empty($container_type)) {
          $type = explode('|', $container_type);
          $entity_type = $type[0];
          $bundle = $type[1];

          $response = $this->getContainerNames($entity_type, $bundle);
        }
        else {
          $response = new JsonResponse('');
        }

        break;

      // Save a dxpr builder container.
      case 'dxpr_save_container':
        if ($this->dxprLicenseService->isBillableUser()) {
          $type = explode('|', $this->requestStack->getCurrentRequest()->request->get('type'));
          $entity_type = $type[0];
          $bundle = $type[1];
          $name = explode('|', $this->requestStack->getCurrentRequest()->request->get('name'));
          if (count($name) > 2 && is_numeric($name[1])) {
            $entity_id = $name[0];
            $revision_id = $name[1];
            $field_name = $name[2];
          }
          else {
            $entity_id = $name[0];
            $field_name = $name[1];
            $revision_id = NULL;
          }
          $encoded_html = $this->requestStack->getCurrentRequest()->request->get('shortcode');
          $langcode = $this->requestStack->getCurrentRequest()->request->get('lang');
          if (!$langcode) {
            $langcode = $this->languageManager->getDefaultLanguage()->getId();
          }

          $response = $this->saveContainer($entity_type, $bundle, $entity_id, $revision_id, $field_name, $encoded_html, $langcode);

          $saves_count = $this->state->get('dxpr_builder.saves_count', 0) + 1;
          $this->state->set('dxpr_builder.saves_count', $saves_count);
        }

        break;

      // Load a dxpr builder container.
      case 'dxpr_load_container':
        $post_type = $this->requestStack->getCurrentRequest()->request->get('type');
        $post_name = $this->requestStack->getCurrentRequest()->request->get('name');
        if ($post_type != 'block') {
          if (empty($post_type) || empty($post_name)) {
            new JsonResponse('');
          }
          $type = explode('|', $post_type);
          $entity_type = $type[0];
          $bundle = $type[1];
          $name = explode('|', $post_name);
          $id = $name[0];
          $field_name = $name[1];

          $langcode = $this->languageManager->getDefaultLanguage()->getId();

          if (isset($name[2])) {
            $langcode = $name[2];
          }

          $response = new HtmlResponse($this->loadContainer($entity_type, $id, $field_name, $langcode));
        }

        break;

      // Get a list of block and view names.
      case 'dxpr_builder_get_cms_element_names':
        if ($this->dxprLicenseService->isBillableUser()) {
          $response = $this->getCmsElementNames();
        }

        break;

      // Get settings for various CMS elements.
      case 'dxpr_get_cms_element_settings':
        if ($this->dxprLicenseService->isBillableUser()) {
          $assets = $this->fetchPreRenderAssets();
          $post_name = $this->requestStack->getCurrentRequest()->request->get('name');
          $name = explode('-', $post_name);
          $type = $name[0];
          if ($name[0] === 'view') {
            $view_id = $name[1];
            $display_id = $name[2];
            $data = $this->getViewSettings($view_id, $display_id);
            $data['#printed'] = FALSE;
            unset($data['#markup']);
            $html = $this->renderer->render($data);
          }
          if ($name[0] === 'block') {
            // Name is for example "block-system_menu_block:secondary-menu".
            $plugin_id = substr($post_name, 6);
            $settings = $this->getBlockSettings($plugin_id);
            // Render the fields in a form tag as expected by the frontend.
            // @see CMSSettingsParamType()
            $settings['#printed'] = FALSE;
            unset($settings['#markup']);
            $html = strval($this->renderer->renderRoot($settings));
            $html = '<form>' . $html . '</form>';
            $assets = AttachedAssets::createFromRenderArray($settings);
          }
          $data = $this->fetchPostRenderAssets($assets);
          $data['data'] = $html;
          $response = new JsonResponse($data);
        }

        break;

      // Load a given CMS element.
      case 'dxpr_builder_load_cms_element':
        $request = $this->requestStack->getCurrentRequest()->request;
        $name = $request->get('name');
        $element_info = $this->dxprBuilderService->parseStringForCmsElementInfo($name);
        $settings = $request->get('settings');
        $data = $request->all()['data'];

        $assets = $this->fetchPreRenderAssets();
        $html = $this->loadCmsElement($element_info, $settings, $data, $assets);
        $response = $this->fetchPostRenderAssets($assets);
        $response['data'] = $html;
        $response = new JsonResponse($response);

        break;

      // Get the templates for a given page.
      case 'dxpr_get_page_templates':
        if ($this->dxprLicenseService->isBillableUser()) {
          $response = $this->getPageTemplates();
        }

        break;

      // Load a given template.
      case 'dxpr_load_page_template':
        if ($this->dxprLicenseService->isBillableUser()) {
          $uuid = $this->requestStack->getCurrentRequest()->request->get('uuid');

          $response = new HtmlResponse($this->loadPageTemplate($uuid));
        }

        break;

      // Get the templates for the current user.
      case 'dxpr_get_templates':
        if ($this->dxprLicenseService->isBillableUser()) {
          $response = $this->getUserTemplates();
        }

        break;

      // Load a template for the current user.
      case 'dxpr_load_template':
        if ($this->dxprLicenseService->isBillableUser()) {
          $uuid = $this->requestStack->getCurrentRequest()->request->get('uuid');

          $response = new HtmlResponse($this->loadUserTemplate($uuid));
        }

        break;

      // Save a template for the current user.
      case 'dxpr_save_template':
        if ($this->dxprLicenseService->isBillableUser()) {
          $name = $this->requestStack->getCurrentRequest()->request->get('name');
          $template = $this->requestStack->getCurrentRequest()->request->get('template');
          $global = (bool) $this->requestStack->getCurrentRequest()->request->get('global');

          $this->saveUserTemplate($name, $template, $global);
          $response = new JsonResponse('');
        }

        break;

      // Delete a template for the current user.
      case 'dxpr_delete_template':
        if ($this->dxprLicenseService->isBillableUser()) {
          $template_name = $this->requestStack->getCurrentRequest()->request->get('name');

          $response = $this->deleteUserTemplate($template_name);
        }

        break;

      // Accept an array of fids and return comma-separated image URLs.
      case 'dxpr_builder_get_image_urls':
        if ($this->dxprLicenseService->isBillableUser()) {
          $entityIds = $this->requestStack->getCurrentRequest()->request->all()['entityIDs'];
          $imageStyle = $this->requestStack->getCurrentRequest()->request->get('imageStyle');
          $entityType = $this->requestStack->getCurrentRequest()->request->get('entityType');
          if ($entityType === "media") {
            $mediaEntities = $this->entityTypeManager->getStorage('media')->loadMultiple($entityIds);
            if ($mediaEntities) {
              $entityIds = array_map(function ($item) {
                return $item->field_media_image->target_id;
              }, $mediaEntities);
            }
          }

          $response = new HtmlResponse($this->getImageUrl($entityIds, $imageStyle));
        }

        break;

      case 'dxpr_builder_get_image_style_url':
        if ($this->dxprLicenseService->isBillableUser()) {
          $imageStyle = $this->requestStack->getCurrentRequest()->request->get('imageStyle');
          $fileId = $this->requestStack->getCurrentRequest()->request->get('fileId');
          /** @var \Drupal\file\Entity\File $fileEntity */
          $fileEntity = $this->entityTypeManager->getStorage('file')->load($fileId);
          $fileUri = $fileEntity->getFileUri();

          $isSvg = strpos($fileUri, '.svg');
          if ($imageStyle !== 'original' && !$isSvg) {
            /** @var \Drupal\image\Entity\ImageStyle $image_style */
            $image_style = $this->entityTypeManager->getStorage('image_style')->load($imageStyle);
            $url = $image_style->buildUrl($fileUri);
          }
          else {
            $url = $this->fileUrlGenerator->generateAbsoluteString($fileUri);
          }

          $url_transform = $this->fileUrlGenerator->transformRelative($url);
          $query_glue = (strpos($url_transform, '?') === FALSE) ? '?' : '&';
          $url_full = $url_transform . $query_glue . 'fid=' . $fileId;

          $response = new Response($url_full);
        }

        break;

      // Content lock status.
      case 'dxpr_content_lock_status':
        $entity_id = $this->requestStack->getCurrentRequest()->request->get('entity_id');
        $revision_id = $this->requestStack->getCurrentRequest()->request->get('revision_id');
        $entity_type = $this->requestStack->getCurrentRequest()->request->get('entity_type');
        $langcode = $this->requestStack->getCurrentRequest()->request->get('langcode');
        $response = $this->contentLock->contentLockStatus($entity_id, $revision_id, $entity_type, $langcode);
        break;

      // Toggle content lock.
      case 'dxpr_toggle_content_lock':
        $entity_id = $this->requestStack->getCurrentRequest()->request->get('entity_id');
        $revision_id = $this->requestStack->getCurrentRequest()->request->get('revision_id');
        $entity_type = $this->requestStack->getCurrentRequest()->request->get('entity_type');
        $langcode = $this->requestStack->getCurrentRequest()->request->get('langcode');
        $toggle_action = $this->requestStack->getCurrentRequest()->request->get('toggle_action');
        $response = $this->contentLock->toggleContentLock($entity_id, $revision_id, $entity_type, $langcode, $toggle_action);
        break;
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Returns json response.
   */
  public function validateHelpLink() {
    $help_link = $this->requestStack->getCurrentRequest()->request->get('help_link');

    if ($help_link && strpos($help_link, 'dxpr.com/documentation/')) {

      try {
        /* @phpstan-ignore-next-line */
        $response = $this->httpClient->head($help_link);

        // Send TRUE if help link is valid and exists on dxpr.com.
        if ($response->getStatusCode() === 200) {
          return new JsonResponse([TRUE]);
        }
      }
      catch (GuzzleException $e) {
        return new JsonResponse([FALSE]);
      }
    }

    return new JsonResponse([]);
  }

  /**
   * {@inheritdoc}
   */
  public function fileUpload(): Response {
    $type = $this->requestStack->getCurrentRequest()->get('type');
    switch ($type) {
      case 'video':
        $accept_file_types = '/\.(webm|ogv|ogg|mp4)$/i';
        break;

      // By default use image extensions.
      default:
        $accept_file_types = '/\.(gif|jpe?g|png|svg)$/i';
    }

    new UploadHandler(['accept_file_types' => $accept_file_types], TRUE, $type);

    return new Response('');
  }

  /**
   * Get the base URL of the current request.
   *
   * @return string
   *   The path.
   */
  private function getBase() {
    $current_request = $this->requestStack->getCurrentRequest();
    return $current_request->getSchemeAndHttpHost() . $current_request->getBasePath();
  }

  /**
   * Determine if user has access to edit with the dxpr builder.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Description
   */
  private function hasEditAccess() {
    return new JsonResponse($this->dxprLicenseService->isBillableUser());
  }

  /**
   * Callback to get container types.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Description
   */
  private function getContainerTypes() {
    // Lists fields that use DXPR Builder as default field formatter,
    // used in DXPR Container element.
    $container_types = [];

    $entity_definitions = $this->entityTypeManager->getDefinitions();
    foreach (array_keys($entity_definitions) as $entity_type) {
      // Only act on fieldable entity types.
      if ($entity_definitions[$entity_type]->get('field_ui_base_route')) {
        $bundle_info = $this->entityTypeBundleInfoManager->getBundleInfo($entity_type);
        if ($bundle_info) {
          foreach (array_keys($bundle_info) as $bundle) {
            $fields = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);
            foreach ($fields as $field_id => $field_info) {
              /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface|null $view_display */
              $view_display = $this->entityTypeManager->getStorage('entity_view_display')
                ->load($entity_type . '.' . $bundle . '.default');
              if ($view_display) {
                /** @var \Drupal\Component\Plugin\DerivativeInspectionInterface|null $renderer */
                $renderer = $view_display->getRenderer($field_id);
                if ($renderer && $renderer->getBaseId() === 'dxpr_builder_text') {
                  $container_types[$entity_type . '|' . $bundle] = $entity_type . ' - ' . $bundle;
                }
              }
            }
          }
        }
      }
    }

    return new JsonResponse($container_types);
  }

  /**
   * Callback to get container types.
   *
   * @param string $entityType
   *   The type of entity to check.
   * @param string $bundle
   *   The bundle of the given entity.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Description
   */
  private function getContainerNames($entityType, $bundle) {
    // Lists field instances that use DXPR Builder as default field formatter,
    // used in DXPR Container element. Get the display for the bundle.
    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display */
    $display = $this->entityTypeManager
      ->getStorage('entity_view_display')
      ->load($entityType . '.' . $bundle . '.default');

    $fields = $this->entityFieldManager->getFieldDefinitions($entityType, $bundle);
    $dxpr_fields = [];
    // Loop through each of the fields.
    foreach ($fields as $field) {
      // Get the formatter for the field.
      /** @var \Drupal\Component\Plugin\DerivativeInspectionInterface|null $renderer */
      $renderer = $display->getRenderer($field->getName());
      if ($renderer) {
        // Check to see if the formatter is dxpr_builder_text.
        if ($renderer->getBaseId() === 'dxpr_builder_text') {
          $dxpr_fields[$field->getName()] = $field->getLabel();
        }
      }
    }

    $query = $this->entityTypeManager->getStorage($entityType)->getQuery()->accessCheck(TRUE);
    $entity_ids = $query->condition('type', $bundle)
      ->execute();
    $entities = $this->entityTypeManager->getStorage($entityType)->loadMultiple($entity_ids);
    $container_names = [];
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    foreach ($entities as $entity_id => $entity) {
      // Return the translated entites if exist.
      if ($entity->isTranslatable()) {
        foreach ($dxpr_fields as $field_name => $field_label) {
          $languages = $entity->getTranslationLanguages();
          foreach ($languages as $langcode => $language) {
            $translatedEntity = $entity->getTranslation($langcode);
            $container_names[$translatedEntity->id() . '|' . $field_name . '|' . $langcode] = $translatedEntity->label() . '|' . $field_label;
          }
        }
      }
      else {
        foreach ($dxpr_fields as $field_name => $field_label) {
          $container_names[$entity_id . '|' . $field_name] = $entity->label() . '|' . $field_label;
        }
      }
    }

    return new JsonResponse($container_names);
  }

  /**
   * Saves a new container.
   *
   * @param string $entityType
   *   The type of entity.
   * @param string $bundle
   *   The type of bundle.
   * @param string|int $entityId
   *   The entity ID.
   * @param string|int $revisionId
   *   The entity revision ID.
   * @param string $fieldName
   *   The field name.
   * @param string $encodedHtml
   *   The html to be decoded.
   * @param string $langcode
   *   The language of the entity to be saved.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Empty response.
   */
  private function saveContainer($entityType, $bundle, $entityId, $revisionId, $fieldName, $encodedHtml, $langcode) {
    // Saves DXPR Builder container instance to field, respecting
    // permissions, language and revisions if supported.
    /** @var \Drupal\Core\Entity\RevisionableStorageInterface $entity_storage */
    $entity_storage = $this->entityTypeManager->getStorage($entityType);

    $revisionableEntity = $entity_storage->getEntityType()->isRevisionable();
    if ($entity_storage instanceof RevisionableStorageInterface && $revisionableEntity && !empty($revisionId)) {
      /* @phpstan-ignore-next-line */
      $entity = $entity_storage->loadRevision($revisionId);
    }
    else {
      $entity = $this->entityTypeManager->getStorage($entityType)->load($entityId);
    }
    // Load translation if available.
    /** @var \Drupal\Core\Entity\ContentEntityInterface|null $entity */
    if ($entity && $entity->isTranslatable()) {
      $languages = $entity->getTranslationLanguages();
      if (isset($languages[$langcode])) {
        $entity = $entity->getTranslation($langcode);
      }
    }

    if ($entity && $entity->access('update', $this->currentUser)) {
      $decoded_short_code = rawurldecode($this->decodeData($encodedHtml));

      $field_values = $entity->get($fieldName)->getValue();
      $field_value = $field_values[0];
      $field_value['value'] = $decoded_short_code;

      $entity->get($fieldName)->set(0, $field_value);

      // Check if the entity type supports revisions.
      if ($revisionableEntity) {
        $entity->setNewRevision();
        $entity->isDefaultRevision(TRUE);
        if ($entity instanceof RevisionLogInterface) {
          // If a new revision is created, save the current user as
          // revision author.
          $entity->setRevisionUserId($this->currentUser->id());
          $entity->setRevisionLogMessage('Saved with DXPR builder');
          $entity->setRevisionCreationTime($this->getRequestTime());
        }
      }

      // Delete locked content.
      $this->contentLock->deleteLockedContent($entityId, $revisionId, $entityType, $langcode);

      // Save entity.
      $entity->save();
    }

    return new JsonResponse('');
  }

  /**
   * Loads DXPR Builder field content.
   *
   * @param string|int $entityType
   *   The type of entity.
   * @param string|int $entityId
   *   The ID of the entity to be saved.
   * @param string $fieldName
   *   The name of the field to return.
   * @param string $langcode
   *   The lang code.
   *
   * @return string
   *   Description
   */
  private function loadContainer($entityType, $entityId, $fieldName, $langcode) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->entityTypeManager->getStorage($entityType)->load($entityId);
    if ($entity->isTranslatable()) {
      $entity = $entity->getTranslation($langcode);
    }

    $field_data = '';
    if ($entity->hasField($fieldName)) {
      $field_data = $entity->{$fieldName}->value;
      $this->dxprBuilderService->replaceBaseTokens($field_data);
    }

    return $field_data;
  }

  /**
   * Get Drupal blocks and views displays in DXPR Builder elements modal.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Description
   */
  private function getCmsElementNames() {
    return new JsonResponse($this->dxprBuilderService->getCmsElementNames());
  }

  /**
   * Get the element settings for the given view.
   *
   * @param string $viewId
   *   The ID of the view.
   * @param string $displayId
   *   The ID of the display for the given view.
   *
   * @return mixed[]
   *   The settings for the given view
   */
  private function getViewSettings($viewId, $displayId) {
    // Fetches settings for views display element settings modal.
    $executable_view = Views::getView($viewId);
    $executable_view->setDisplay($displayId);
    $executable_view->initHandlers();
    $executable_view->build();

    return $executable_view->exposed_widgets;
  }

  /**
   * Get the settings form for the given block.
   *
   * @param string $plugin_id
   *   The ID of the block.
   *
   * @return array
   *   The settings for the given block
   *
   * @phpstan-return array<string, mixed>
   */
  private function getBlockSettings($plugin_id): array {
    $entity = $this->entityTypeManager()->getStorage('block')->create([
      'plugin' => $plugin_id,
      'theme' => NULL,
    ]);
    $form = $this->entityFormBuilder()->getForm($entity);

    // Custom block configuration fields are added directly in the block config
    // form, without using a separate container. Remove the default fields,
    // since they are duplicate with the config provided by DXPR.
    $settings = $form['settings'];

    foreach ([
      'provider',
      'admin_label',
      'label',
      'label_display',
      'context_mapping',
    ] as $key) {
      unset($settings[$key]);
    }

    $this->updateFormNames($settings);
    $this->expandFieldsets($settings);

    return $settings;
  }

  /**
   * Update name attributes for form elements to match output structure.
   *
   * The names used in the form HTML uses the complete array structure.
   * However, the expected output structure is flattened, except when
   * #tree is set to TRUE. This method updates the name attributes
   * so that we can extract the correct output client-side.
   *
   * @param mixed[] $form
   *   The form structure.
   * @param bool $in_tree
   *   Indicates if #tree was set.
   * @param string[] $prefix
   *   Prefix to use for name.
   */
  private function updateFormNames(array &$form, $in_tree = FALSE, array $prefix = []): void {
    foreach (Element::children($form) as $name) {
      if (!empty($form[$name]['#name'])) {
        $html_name = '';
        foreach (array_merge($prefix, [$name]) as $index => $part) {
          if ($index) {
            $html_name .= "[$part]";
          }
          else {
            $html_name .= $part;
          }
        }
        $form[$name]['#name'] = $html_name;
      }
      $new_in_tree = $in_tree || !empty($form[$name]['#tree']);
      $this->updateFormNames($form[$name], $new_in_tree, $in_tree ? array_merge($prefix, [$name]) : $prefix);
    }
  }

  /**
   * Expand all fieldsets in the form.
   *
   * Fieldsets do not expand/collapse in all admin themes (e.g. Bootstrap).
   * Expanding all fieldsets ensures that they remain usable in all themes.
   *
   * @param mixed[] $form
   *   The form structure.
   */
  private function expandFieldsets(array &$form): void {
    foreach (Element::children($form) as $name) {
      if (isset($form[$name]['#type']) && $form[$name]['#type'] == 'details') {
        $form[$name]['#open'] = TRUE;
      }
      $this->expandFieldsets($form[$name]);
    }
  }

  /**
   * Loads settings for a CMS element - often views.
   *
   * @param mixed[] $element_info
   *   An array of info regarding the element to be returned.
   * @param string $settings
   *   Settings for the elmeent to be loaded.
   * @param mixed[] $data
   *   Data on the element to be returned.
   * @param \Drupal\Core\Asset\AttachedAssets $assets
   *   Any assets for the found element will be attached to this element.
   */
  private function loadCmsElement(array $element_info, $settings, array $data, AttachedAssets $assets): string {
    // Loads Drupal block or views display.
    return $this->dxprBuilderService->loadCmsElement($element_info, $settings, $data, $assets);
  }

  /**
   * Get the list of page templates for the dxpr builder.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Description
   */
  private function getPageTemplates() {
    // This refers to the templates you see when you click
    // "CHOOSE A TEMPLATE" on an empty DXPR Builder container.
    /** @var \Drupal\dxpr_builder\Entity\DxprBuilderPageTemplate[] $page_templates */
    $page_templates = $this->entityTypeManager->getStorage('dxpr_builder_page_template')
      ->loadByProperties(['status' => 1]);

    $templates = [];
    foreach ($page_templates as $page_template) {
      if (!$image = $page_template->getImageData()) {
        $image = $this->getBase() . '/' . $this->extensionListModule->getPath('dxpr_builder') . '/' . 'images/dxpr_templates/not-found.png';
      }
      $templates[] = [
        'title' => $page_template->label(),
        'uuid' => $page_template->uuid(),
        'module' => $page_template->get('module'),
        'category' => $page_template->get('category'),
        'image' => $image,
      ];
    }

    return new JsonResponse($templates);
  }

  /**
   * Load a dxpr builder page template.
   *
   * @param string $uuid
   *   The unique identifier for the page template to be loaded.
   *
   * @return string
   *   The template for the page, with all tokens replaced with actual values
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function loadPageTemplate($uuid) {
    $template = '';
    /** @var \Drupal\dxpr_builder\Entity\DxprBuilderPageTemplate[] $templates */
    $templates = $this->entityTypeManager->getStorage('dxpr_builder_page_template')
      ->loadByProperties([
        'uuid' => $uuid,
        'status' => 1,
      ]);
    if ($templates) {
      $template = reset($templates);
      $template = $template->get('template');
      $this->dxprBuilderService->replaceBaseTokens($template);
    }
    return $template;
  }

  /**
   * Get the list of dxpr builder templates for the current user.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Description
   */
  private function getUserTemplates() {
    $entityType = 'dxpr_builder_user_template';
    $query = $this->entityTypeManager->getStorage($entityType)->getQuery();

    $group = $query->orConditionGroup()
      ->condition('uid', $this->currentUser->id())
      ->condition('global', TRUE);
    $query->condition($group);

    $entity_ids = $query->accessCheck(TRUE)->execute();
    $user_templates = DxprBuilderUserTemplate::loadMultiple($entity_ids);

    $templates = [];
    $i = 0;
    foreach ($user_templates as $template) {
      $author_id = $template->get('uid');
      /** @var \Drupal\user\UserInterface $author */
      $author = $this->entityTypeManager->getStorage('user')->load($author_id);
      $templates[$i]['id'] = $template->id();
      $templates[$i]['uuid'] = $template->uuid();
      $templates[$i]['name'] = $template->label();
      $templates[$i]['global'] = $template->get('global');
      // Check if the current user is the author of template.
      $current_user_is_author = FALSE;
      if ($this->currentUser->id() == $template->get('uid')) {
        $current_user_is_author = TRUE;
      }
      $templates[$i]['current_user_is_author'] = $current_user_is_author;
      $templates[$i]['author_id'] = $author_id;
      $templates[$i]['author_name'] = $author->getAccountName();
      $i++;
    }

    return new JsonResponse($templates);
  }

  /**
   * Load a dxpr builder user template.
   *
   * @param string $uuid
   *   The unique identifier for the user template to be loaded.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Description
   */
  private function loadUserTemplate($uuid) {
    $template = '';
    /** @var \Drupal\dxpr_builder\Entity\DxprBuilderUserTemplate[] $templates */
    $templates = $this->entityTypeManager->getStorage('dxpr_builder_user_template')
      ->loadByProperties([
        'uuid' => $uuid,
        'status' => 1,
      ]);
    if ($templates) {
      $template = reset($templates);
      $template = $template->get('template');
      $this->dxprBuilderService->replaceBaseTokens($template);
    }
    return $template;
  }

  /**
   * Create machine name for user template.
   *
   * @param string $template_name
   *   The name of the user template.
   *
   * @return string
   *   Description
   */
  private function createUserTemplateMachineName($template_name) {
    return preg_replace('@[^a-z0-9_]+@', '_', strtolower($template_name));
  }

  /**
   * Helper function to check if the template exists.
   *
   * @param string $machine_name
   *   The machine name of the user template.
   *
   * @return bool
   *   Checking result.
   */
  private function userTemplateIsExists($machine_name) {
    $user_template = DxprBuilderUserTemplate::load($machine_name);
    if ($user_template) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Save the given template for the current user.
   *
   * @param string $template_name
   *   The name of the template to be saved.
   * @param string $template_contents
   *   The contents of the template to be saved.
   * @param bool $global
   *   The type of template is global or private.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse|bool
   *   Description
   */
  public function saveUserTemplate($template_name, $template_contents, $global) {
    if (!$this->dxprLicenseService->isBillableUser()) {
      return FALSE;
    }

    $machine_name = $this->createUserTemplateMachineName($template_name);

    // Validate template name to make sure that is unique name.
    if ($this->userTemplateIsExists($machine_name)) {
      throw new \Exception('Template name must be unique');
    }

    $user_template = DxprBuilderUserTemplate::create([
      'id' => $this->createUserTemplateMachineName($template_name),
      'label' => $template_name,
      'template' => $this->dxprBuilderService->insertBaseTokens($template_contents),
      'uid' => $this->currentUser->id(),
      'global' => $global,
    ]);

    $user_template->save();

    return new JsonResponse('');
  }

  /**
   * Delete the given template for the current user.
   *
   * @param string $template_name
   *   The name of the template to be deleted.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Description
   */
  private function deleteUserTemplate($template_name) {
    $machine_name = $this->createUserTemplateMachineName($template_name);
    $user_template = DxprBuilderUserTemplate::load($machine_name);
    $user_template->delete();

    return new JsonResponse('');
  }

  /**
   * Creates image url from file ID.
   *
   * @param mixed[] $fileIds
   *   Array of files ids.
   * @param string $imageStyle
   *   The image style.
   *
   * @return string
   *   The image url.
   */
  private function getImageUrl(array $fileIds, $imageStyle) {
    $images = [];
    foreach ($fileIds as $fid) {
      /** @var \Drupal\file\FileInterface $file */
      $file = $this->entityTypeManager->getStorage('file')->load($fid);
      if ($imageStyle && $imageStyle !== 'original') {
        /** @var \Drupal\image\ImageStyleInterface $image_style */
        $image_style = $this->entityTypeManager->getStorage('image_style')
          ->load($imageStyle);
        $images[] = $this->fileUrlGenerator->transformRelative($image_style->buildUrl($file->getFileUri())) . '?fid=' . $fid;
      }
      else {
        $images[] = $this->fileUrlGenerator->transformRelative($this->fileUrlGenerator->generateAbsoluteString($file->getFileUri())) . '?fid=' . $fid;
      }
    }

    return implode(',', $images);
  }

  /**
   * Decodes the given data.
   *
   * @param string $encoded
   *   The encoded data.
   *
   * @return string
   *   The decoded data
   */
  private function decodeData($encoded) {
    $decoded = "";
    for ($i = 0; $i < strlen($encoded); $i++) {
      $b = ord($encoded[$i]);
      $a = $b ^ 7;
      $decoded .= chr($a);
    }

    return $decoded;
  }

  /**
   * Description.
   */
  private function getRequestTime(): int {
    return $this->requestStack->getCurrentRequest()->server->get('REQUEST_TIME');
  }

  /**
   * Fixes the element for rendering.
   *
   * @param mixed[] $arr
   *   Array for scanning.
   */
  protected function addTagHelper(array &$arr): void {
    foreach ($arr as &$v_arr) {
      if (is_array($v_arr) && !empty($v_arr)) {
        if (!empty($v_arr['#tag']) && $v_arr['#tag'] === 'script') {
          $v_arr['#type'] = 'html_tag';
        }
        else {
          $this->addTagHelper($v_arr);
        }
      }
    };
  }

  /**
   * Save page assets before rendering.
   *
   * @return \Drupal\Core\Asset\AttachedAssets
   *   Assets to pass to fetchPostRenderAssets().
   */
  private function fetchPreRenderAssets() {
    $request = $this->requestStack->getCurrentRequest()->request;
    $ajax_page_state = $request->all()['ajax_page_state'];

    $assets = new AttachedAssets();
    $assets->setAlreadyLoadedLibraries(isset($ajax_page_state['libraries']) ? explode(',', $ajax_page_state['libraries']) : []);

    return $assets;
  }

  /**
   * Collect assets after rendering.
   *
   * @param \Drupal\Core\Asset\AttachedAssets $assets
   *   Assets retrieved by fetchPreRenderAssets().
   *
   * @return mixed[]
   *   Array with keys css, js, and settings.
   */
  private function fetchPostRenderAssets(AttachedAssets $assets) {
    $css_assets = $this->assetResolver->getCssAssets($assets, TRUE);
    $css = $this->cssAssetCollectionRenderer->render($css_assets);

    $js_assets = $this->assetResolver->getJsAssets($assets, TRUE);
    $js = '';
    $settings = '';
    foreach ($js_assets as $js_asset) {
      // Adds html_head scripts.
      if (!empty($js_asset['drupalSettings']['data']['dxpr_html_head'])) {
        $this->addTagHelper($js_asset['drupalSettings']['data']['dxpr_html_head']);
        $js_header = $this->renderer->render($js_asset['drupalSettings']['data']['dxpr_html_head']);
        $js .= $js_header->__toString();
        // Removes html_head from settings because it isn't real settings.
        unset($js_asset['drupalSettings']['data']['dxpr_html_head']);
      }

      $render = $this->jsAssetCollectionRenderer->render($js_asset);
      if (count($render)) {
        foreach ($render as $script) {
          if (isset($script['#attributes']['type']) && $script['#attributes']['type'] == 'application/json') {
            $settings = json_decode($script['#value']);
            continue;
          }
          $rendered = $this->renderer->render($script);
          $js .= $rendered->__toString();
        }
      }
    }

    return [
      'css' => count($css) ? $this->renderer->render($css)->__toString() : '',
      'js' => $js,
      'settings' => $settings,
    ];
  }

}
