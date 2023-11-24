<?php

namespace Drupal\dxpr_builder\Service;

use Drupal\Component\Utility\Environment;
use Drupal\Component\Utility\Html;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Asset\AttachedAssets;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\dxpr_builder\Service\Handler\BlockHandlerInterface;
use Drupal\dxpr_builder\Service\Handler\ViewHandlerInterface;
use Drupal\views\Views;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Description.
 */
class DxprBuilderService implements DxprBuilderServiceInterface {

  use StringTranslationTrait;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The dxpr builder configuration.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Drupal file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The file url generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

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
   * The dxpr builder block handler service.
   *
   * @var \Drupal\dxpr_builder\Service\Handler\BlockHandlerInterface
   */
  protected $dxprBlockHandler;

  /**
   * The dxpr builder view handler service.
   *
   * @var \Drupal\dxpr_builder\Service\Handler\ViewHandlerInterface
   */
  protected $dxprViewHandler;

  /**
   * The cache service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The theme handler service.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The block manager service.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * The CSRF token generator service.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfToken;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * The theme extension list.
   *
   * @var \Drupal\Core\Extension\ThemeExtensionList
   */
  protected $themeExtensionList;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs a DxprBuilderService object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The Drupal file system.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $fileUrlGenerator
   *   The file url generator.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   * @param \Drupal\dxpr_builder\Service\Handler\BlockHandlerInterface $dxprBlockHandler
   *   The dxpr builder block handler service.
   * @param \Drupal\dxpr_builder\Service\Handler\ViewHandlerInterface $dxprViewHandler
   *   The dxpr builder view handler service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   The cache service;.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity manager service.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   *   The theme handler service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager service.
   * @param \Drupal\Core\Block\BlockManagerInterface $blockManager
   *   The block manager service.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrfToken
   *   The CSRF token generator service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList
   *   The module extension list service.
   * @param \Drupal\Core\Extension\ThemeExtensionList $themeExtensionList
   *   The theme extension list service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
      RequestStack $requestStack,
      ConfigFactoryInterface $configFactory,
      FileSystemInterface $fileSystem,
      FileUrlGeneratorInterface $fileUrlGenerator,
      AccountProxyInterface $currentUser,
      ModuleHandlerInterface $moduleHandler,
      BlockHandlerInterface $dxprBlockHandler,
      ViewHandlerInterface $dxprViewHandler,
      CacheBackendInterface $cacheBackend,
      EntityFieldManagerInterface $entityFieldManager,
      EntityTypeManagerInterface $entityTypeManager,
      ThemeHandlerInterface $themeHandler,
      LanguageManagerInterface $languageManager,
      BlockManagerInterface $blockManager,
      CsrfTokenGenerator $csrfToken,
      MessengerInterface $messenger,
      ModuleExtensionList $moduleExtensionList,
      ThemeExtensionList $themeExtensionList,
      LoggerChannelFactoryInterface $logger_factory
    ) {
    $this->requestStack = $requestStack;
    $this->configFactory = $configFactory;
    $this->fileSystem = $fileSystem;
    $this->currentUser = $currentUser;
    $this->moduleHandler = $moduleHandler;
    $this->dxprBlockHandler = $dxprBlockHandler;
    $this->dxprViewHandler = $dxprViewHandler;
    $this->cacheBackend = $cacheBackend;
    $this->entityFieldManager = $entityFieldManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->themeHandler = $themeHandler;
    $this->languageManager = $languageManager;
    $this->blockManager = $blockManager;
    $this->csrfToken = $csrfToken;
    $this->fileUrlGenerator = $fileUrlGenerator;
    $this->messenger = $messenger;
    $this->moduleExtensionList = $moduleExtensionList;
    $this->themeExtensionList = $themeExtensionList;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function insertBaseTokens($content) {
    // Get url-safe path, replace backslashes from windows paths.
    $filesDirectoryPath = str_replace('\\', '/', $this->getFilesDirectoryPath('public'));
    $filesPrivateDirectoryPath = str_replace('\\', '/', $this->getFilesDirectoryPath('private'));
    $modulePath = str_replace('\\', '/', $this->getModulePath());
    $replacements = [
      $this->getBasePath() => '-base-url-',
      $filesDirectoryPath => '-files-directory-',
      $filesPrivateDirectoryPath => '-files-private-directory-',
      $modulePath => '-module-directory-',
    ];

    return str_replace(array_keys($replacements), array_values($replacements), $content);
  }

  /**
   * {@inheritdoc}
   */
  public function replaceBaseTokens(&$content): void {
    // Get url-safe path, replace backslashes from windows paths.
    $filesDirectoryPath = str_replace('\\', '/', $this->getFilesDirectoryPath('public'));
    $filesPrivateDirectoryPath = str_replace('\\', '/', $this->getFilesDirectoryPath('private'));
    $modulePath = str_replace('\\', '/', $this->getModulePath());
    $replacements = [
      '-base-url-' => $this->getBasePath(),
      '-files-directory-' => $filesDirectoryPath,
      '-files-private-directory-' => $filesPrivateDirectoryPath,
      '-module-directory-' => $modulePath,
      $this->getBaseUrl() . $filesDirectoryPath => $this->getBasePath() . '/' . $filesDirectoryPath,
      $this->getBaseUrl() . $modulePath => $this->getBasePath() . '/' . $modulePath,
      '="' . $filesDirectoryPath => '="' . $this->getBasePath() . '/' . $filesDirectoryPath,
      '="' . $filesDirectoryPath => '="' . $this->getBasePath() . '/' . $filesDirectoryPath,
      '="' . $filesPrivateDirectoryPath => '="' . $this->getBasePath() . '/' . $filesPrivateDirectoryPath,
      $this->getBaseUrl() => $this->getBasePath(),
    ];

    if ($content !== NULL) {
      $content = str_replace(array_keys($replacements), array_values($replacements), $content);
    }

  }

  /**
   * {@inheritdoc}
   */
  public function replaceDeprecatedStrings(&$content): void {
    $replacements = [
      'glazed_builder' => 'dxpr_builder',
      'glazed-builder' => 'dxpr-builder',
      'glazed_frontend' => 'dxpr_frontend',
      'glazedBuilder' => 'dxprBuilder',
      'glazed-util' => 'dxpr-theme-util',
      'panel-glazed' => 'panel-dxpr',
      'glazed.css' => 'dxpr.css',
      'files/dxpr-builder' => 'files/glazed-builder',
      'dxpr_builder_images' => 'glazed_builder_images',
    ];

    $content = str_replace(array_keys($replacements), array_values($replacements), $content);
  }

  /**
   * {@inheritdoc}
   */
  public function updateHtml($dataString, $enable_editor) {
    $response = [
      'output' => $dataString,
      'library' => [],
      'settings' => [],
      'mode' => 'static',
    ];

    $this->replaceBaseTokens($response['output']);
    $this->parseContentForScripts($response);
    $this->parseForContent($response, $enable_editor);

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function editorAttach(array &$element, array &$settings): void {

    $config = $this->configFactory->get('dxpr_builder.settings');

    // Add settings required to load assets from cloud.
    if ($config->get('editor_assets_source') == 0) {
      if (empty($config->get('json_web_token'))) {
        $this->messenger->addError($this->t(
          'DXPR Builder needs a product key to work, please install it in the <a href=":link">DXPR Builder settings form</a>.', [
            ':link' => Url::fromRoute('dxpr_builder.settings')->toString(),
          ]
        ));
        // Disable cache to keep showing message on every page.
        $element['#cache']['max-age'] = 0;
        $settings['dxprJwtValue'] = NULL;
      }
      $version = $settings['dxprBuilderVersion'] == 'dev' ? 'latest' : $settings['dxprBuilderVersion'];
      $url = $config->get('cloud_url') ?? 'https://cdn.dxpr.com/VERSION/';
      $settings['dxprAssetsUrl'] = str_replace('VERSION', $version, $url);
      $jwt = $config->get('json_web_token');
      $settings['dxprAssetsParams'] = "jwt=$jwt";
    }
    else {
      $dxprBuilderPath = $this->getPath('module', 'dxpr_builder');
      $settings['dxprAssetsUrl'] = base_path() . $dxprBuilderPath . '/dxpr_builder/';
      $settings['dxprAssetsParams'] = '';
    }

    $getCmsElementNames = $this->getCmsElementNames();
    $settings['cmsElementNames'] = $getCmsElementNames['list'];
    $settings['cmsDisallowedElements'] = $getCmsElementNames['disallowed'];

    // Creating a list of views with additional settings.
    $settings['cmsElementViewsSettings'] = $this->getCmsElementSettings();

    // Creating a list of views tags.
    $settings['viewsTags'] = $this->getCmsViewsTags();

    // Creating a list of buttons style.
    $settings['buttonStyles'] = $this->getButtonStyles();

    // Set the current language.
    $settings['language'] = $this->languageManager->getCurrentLanguage()->getId();

    // Set AJAX file upload callback URL.
    $url = Url::fromRoute('dxpr_builder.ajax_file_upload_callback');
    $token = $this->csrfToken->get($url->getInternalPath());
    $url->setOptions(['query' => ['token' => $token]]);
    $settings['fileUploadUrl'] = $url->toString();

    $default_scheme = $this->configFactory->get('system.file')->get('default_scheme');
    if ($default_scheme == 'public') {
      $settings['publicFilesFolder'] = $this->fileUrlGenerator->generateString($default_scheme . '://');
    }
    else {
      $settings['publicFilesFolder'] = $this->fileUrlGenerator->generateString('system/files/');
    }
    $settings['fileUploadFolder'] = $this->fileUrlGenerator->generateString($default_scheme . '://dxpr_builder_images');

    // Set max upload size per file.
    $settings['fileUploadMaxSize'] = Environment::getUploadMaxSize();

    if ($cke_stylesset = $config->get('cke_stylesset')) {
      $settings['cke_stylesset'] = $this->ckeParseStyles($cke_stylesset);
    }

    $element['#attached']['library'][] = 'core/jquery.ui';
    $element['#attached']['library'][] = 'core/jquery.ui.tabs';
    $element['#attached']['library'][] = 'core/jquery.ui.sortable';
    $element['#attached']['library'][] = 'core/jquery.ui.droppable';
    $element['#attached']['library'][] = 'core/jquery.ui.draggable';
    $element['#attached']['library'][] = 'core/jquery.ui.accordian';
    $element['#attached']['library'][] = 'core/jquery.ui.selectable';
    $element['#attached']['library'][] = 'core/jquery.ui.resizable';
    $element['#attached']['library'][] = 'core/jquery.ui.slider';
    $element['#attached']['library'][] = 'core/drupalSettings';

    $themes = $this->themeHandler->listInfo();
    $dxpr_builder_classes = [];
    foreach ($themes as $theme => $theme_info) {
      /* @phpstan-ignore-next-line */
      if ($theme_info->status == 1 && isset($theme_info->info['dxpr_builder_classes'])) {
        $optgroup = 'optgroup-' . $theme;
        $dxpr_builder_classes[$optgroup] = $theme_info->info['name'];
        $dxpr_builder_classes = array_merge($dxpr_builder_classes, $theme_info->info['dxpr_builder_classes']);
      }
    }

    $this->moduleHandler->alter('dxpr_builder_classes', $dxpr_builder_classes);
    $settings['dxprClasses'] = $dxpr_builder_classes;

    $styles = $this->entityTypeManager->getStorage('image_style')->loadMultiple();
    $styles_list = ['original' => $this->t('Original image (No resizing)')];
    foreach ($styles as $style) {
      $styles_list[$style->id()] = $style->label();
    }

    $settings['imageStyles'] = $styles_list;

    // Load assets media module.
    if ($this->moduleHandler->moduleExists('media')) {
      $element['#attached']['library'][] = 'media/view';
    }

    $element['#attached']['library'][] = 'dxpr_builder/twig.js';
    $element['#attached']['library'][] = 'dxpr_builder/editor.builder';

    $element['#cache']['tags'] = $config->getCacheTags();
  }

  /**
   * Cke css classes parsing styles.
   *
   * @param string $css_classes
   *   The css classes.
   *
   * @return mixed[]|false
   *   The css classes.
   */
  public function ckeParseStyles($css_classes) {
    $set = [];
    $input = trim($css_classes);
    if (empty($input)) {
      return $set;
    }
    // Handle both Unix and Windows line-endings.
    foreach (explode("\n", str_replace("\r", '', $input)) as $line) {
      $line = trim($line);
      // [label]=[element].[class][.[class]][...] pattern expected.
      if (!preg_match('@^.+= *[a-zA-Z0-9]+(\.[a-zA-Z0-9_ -]+)*$@', $line)) {
        return FALSE;
      }
      [$label, $selector] = explode('=', $line, 2);
      $classes = explode('.', $selector);
      $element = array_shift($classes);

      $style = [];
      $style['name'] = trim($label);
      $style['element'] = trim($element);
      if (!empty($classes)) {
        $style['attributes']['class'] = implode(' ', array_map('trim', $classes));
      }
      $set[] = $style;
    }
    return $set;

  }

  /**
   * {@inheritdoc}
   */
  public function getCmsElementNames() {
    $cms_elements = &drupal_static(__CLASS__ . '::' . __FUNCTION__);
    if (!isset($cms_elements)) {
      $cms_elements = [
        'list' => [],
        'disallowed' => [],
      ];
      $block_elements = [];
      if ($this->moduleHandler->moduleExists('block_content')) {
        if (($cache = $this->cacheBackend->get('dxpr_builder:cms_elements_blocks'))
          && ($cache2 = $this->cacheBackend->get('dxpr_builder:cms_elements_blocks' . $this->currentUser->id()))) {
          $block_elements = $cache->data;
          $cms_elements['disallowed'] = $cache2->data;
        }
        else {
          $blacklist = [
            // These two blocks can only be configured in display
            // variant plugin.
            // @see \Drupal\block\Plugin\DisplayVariant\BlockPageVariant
            'page_title_block',
            'system_main_block',
            'license_info',
            'local_tasks_block',
            // Remove entity blocks that makes no sense to use in a block
            // or can be added directly.
            'entity_block:block',
            'entity_block:block_content',
            'entity_block:contact_message',
            'entity_block:content_moderation_state',
            'entity_block:crop',
            'entity_block:file',
            'entity_block:menu_link_content',
            'entity_block:path_alias',
            'entity_block:redirect',
            'entity_block:shortcut',
            'entity_block:webform',
            'entity_block:webform_submission',
            // Fallback plugin makes no sense here.
            'broken',
          ];
          $block_definitions = $this->blockManager->getDefinitions();
          foreach ($block_definitions as $block_id => $definition) {
            $blacklisted = in_array($block_id, $blacklist);
            $is_view = ($definition['provider'] == 'views');
            $is_ctools = ($definition['provider'] == 'ctools');
            if ($blacklisted || $is_view || $is_ctools) {
              continue;
            }
            if (strpos($block_id, 'entity_block') !== FALSE) {
              // Its block access depends solely on the access to the entity
              // set in the configuration. It's empty here, so checking
              // blockAccess will always result in FALSE. However, we still
              // want these blocks listed in the admin.
              $access = TRUE;
            }
            else {
              $access = $this->dxprBlockHandler->blockAccess($block_id, $definition);
            }
            if (!$access) {
              $cms_elements['disallowed']['az_block-' . $block_id] = 'az_block-' . $block_id;
            }
            $block_elements['block-' . $block_id] = $this->t('Block: @block_name', ['@block_name' => ucfirst($definition['category']) . ': ' . $definition['admin_label']])->render();
          }
          unset($cms_elements['disallowed']['az_block-user_login_block']);
          unset($cms_elements['disallowed']['az_block-dxpr_theme_helper_user_registersdf']);
          asort($block_elements);
          $this->cacheBackend->set('dxpr_builder:cms_elements_blocks', $block_elements);
          $this->cacheBackend->set('dxpr_builder:cms_disallowed_elements' . $this->currentUser->id(), $cms_elements['disallowed']);
        }
      }

      $views_elements = [];
      if ($this->moduleHandler->moduleExists('views')) {
        if ($cache = $this->cacheBackend->get('dxpr_builder:cms_elements_views')) {
          $views_elements = $cache->data;
        }
        else {
          $views = Views::getAllViews();
          foreach ($views as $view) {
            if (!$view->status()) {
              continue;
            }
            $executable_view = Views::getView($view->id());
            $executable_view->initDisplay();
            foreach ($executable_view->displayHandlers as $id => $display) {
              $key = 'view-' . $executable_view->id() . '-' . $id;
              $views_elements[$key] = $this->t('View: @view_name', ['@view_name' => $view->label() . ' (' . $display->display['display_title'] . ')'])->render();
            }
          }
          asort($views_elements);
          $this->cacheBackend->set('dxpr_builder:cms_elements_views', $views_elements);
        }
      }

      $cms_elements['list'] = $block_elements + $views_elements;
    }

    return $cms_elements;
  }

  /**
   * {@inheritdoc}
   */
  public function getFilesDirectoryPath($default_scheme = NULL): string {
    if (!$default_scheme) {
      $default_scheme = $this->configFactory->get('system.file')->get('default_scheme');
    }
    if ($default_scheme == 'public') {
      $files_folder = $this->fileUrlGenerator->generateAbsoluteString($default_scheme . '://');
    }
    else {
      $files_folder = $this->fileUrlGenerator->generateAbsoluteString('system/files/');
    }
    return trim(str_replace($this->getBaseUrl(), '', $files_folder), '/');
  }

  /**
   * {@inheritdoc}
   */
  public function loadCmsElement(array $element_info, string $settings, array $data = [], AttachedAssets $assets = NULL) {
    if ($element_info['type'] === 'block') {
      $output = $this->dxprBlockHandler->getBlock($element_info, $settings, $assets, $data);
    }
    else {
      $output = FALSE;
      if ($element_info['type'] === 'view') {
        $output = $this->dxprViewHandler->getView($element_info['view_id'], $settings, $element_info['display_id'], $data, $assets);
      }
    }

    if (!$output) {
      $output = '<div class="empty-cms-block-placeholder"></div>';
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function getDxprElementsFolders() {
    $dxpr_elements_folders = [[
      'folder' => realpath($this->getModulePath()) . DIRECTORY_SEPARATOR . 'dxpr_elements',
      'folder_url' => '/' . $this->getModulePath() . '/' . 'dxpr_elements',
    ],
    ];

    $themes = $this->themeHandler->listInfo();
    foreach ($themes as $theme_key => $theme_info) {
      if ($this->themeHandler->themeExists($theme_key)
        && ($folder = $this->fileSystem->realpath($this->getPath('theme', $theme_key) . DIRECTORY_SEPARATOR . 'elements'))) {
        $dxpr_elements_folders[] = [
          'folder' => $folder,
          'folder_url' => '/' . $this->getPath('theme', $theme_key) . '/' . 'elements',
        ];
      }
    }
    $this->moduleHandler->alter('dxpr_builder_elements_folders', $dxpr_elements_folders);

    return $dxpr_elements_folders;
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseUrl() {
    $current_request = $this->requestStack->getCurrentRequest();
    return $current_request->getSchemeAndHttpHost() . $current_request->getBasePath();
  }

  /**
   * {@inheritdoc}
   */
  public function getBasePath() {
    $current_request = $this->requestStack->getCurrentRequest();
    return $current_request->getBasePath();
  }

  /**
   * {@inheritdoc}
   */
  public function parseStringForCmsElementInfo($string) {
    $element_info = [];
    if (strpos($string, 'block-') === 0) {
      preg_match('/^block-(.+):(.+)$/', $string, $matches);
      if (count($matches)) {
        if ($matches[1] == 'block_content') {
          $element_info = [
            'type' => 'block',
            'provider' => $matches[1],
            'uuid' => $matches[2],
          ];
        }
        else {
          array_shift($matches);
          $element_info = [
            'type' => 'block',
            'provider' => 'plugin',
            'id' => implode(':', $matches),
          ];
        }
      }
      else {
        $parts = explode('-', $string);
        array_shift($parts);
        $element_info = [
          'type' => 'block',
          'provider' => 'plugin',
          'id' => implode('-', $parts),
        ];
      }
    }
    elseif (strpos($string, 'view-') === 0) {
      $parts = explode('-', $string);
      $element_info = [
        'type' => array_shift($parts),
        'display_id' => array_pop($parts),
        'view_id' => implode('-', $parts),
      ];
    }
    return $element_info;
  }

  /**
   * {@inheritdoc}
   */
  public function setEmptyStringToDxprFieldsOnEntity(ContentEntityInterface $entity): void {
    $entity_type = $entity->getEntityType()->id();
    $bundle = $entity->bundle();

    // Get the display for the current bundle.
    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface|null $display */
    $display = $this->entityTypeManager
      ->getStorage('entity_view_display')
      ->load($entity_type . '.' . $bundle . '.default');

    if (!$display) {
      return;
    }

    // Get all fields on the current bundle.
    $fields = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);

    // Loop through each of the fields.
    foreach ($fields as $field) {
      // Get the formatter for the field.
      /** @var \Drupal\Component\Plugin\DerivativeInspectionInterface|null $renderer */
      $renderer = $display->getRenderer($field->getName());
      if ($renderer) {
        // Check to see if the formatter is dxpr_builder_text.
        if ($renderer->getBaseId() === 'dxpr_builder_text') {
          // If the field is empty, set an empty space to the
          // field to force it to render.
          if ($entity->get($field->getName())->isEmpty()) {
            $entity->get($field->getName())->set(0, '&nbsp;');
          }
        }
      }
    }
  }

  /**
   * Retrieves and caches list of views displays and their settings and fields.
   *
   * @return mixed[]
   *   Array of views displayscontaining all metadata that the DXPR Builder
   *   interface uses for modifying the display using various settings. Keyed by
   *   an identifier with the view and display name.
   */
  protected function getCmsElementSettings() {
    $cms_view_elements_settings = &drupal_static(__FUNCTION__);
    if (!isset($cms_view_elements_settings)) {
      if ($cache = $this->cacheBackend->get('dxpr_builder:cms_view_elements_settings')) {
        $cms_view_elements_settings = $cache->data;
      }
      else {
        $cms_view_elements_settings = [];
        foreach (Views::getEnabledViews() as $view) {
          try {
            $executable_view = Views::getView($view->id());
            $executable_view->initDisplay();
            foreach ($executable_view->displayHandlers as $id => $display) {
              $key = 'az_view-' . $executable_view->id() . '-' . $id;
              $executable_view->setDisplay($display->display['id']);
              $title = $executable_view->getTitle();
              $storage = $executable_view->storage;
              $defaultDisplay = &$storage->getDisplay('default');

              $hasExposed = 0;
              $executable_view->initHandlers();
              $executable_view->build();

              if (isset($defaultDisplay['display_options']['filters'])) {
                foreach ($defaultDisplay['display_options']['filters'] as $filter) {
                  if (isset($filter['exposed']) && $filter['exposed'] === TRUE) {
                    $hasExposed = 1;
                    break;
                  }
                }
              }

              if (isset($display->options['filters'])) {
                foreach ($display->options['filters'] as $filter) {
                  if (isset($filter['exposed'])) {
                    if ($filter['exposed'] === FALSE) {
                      $hasExposed = 0;
                    }
                    elseif ($filter['exposed'] === TRUE) {
                      $hasExposed = 1;
                      break;
                    }
                  }
                }
              }

              $ajaxEnabled = 0;

              if (isset($defaultDisplay['display_options']['use_ajax']) && ($defaultDisplay['display_options']['use_ajax'])) {
                $ajaxEnabled = 1;
              }

              if (isset($display->options['use_ajax']) && !empty($display->options['use_ajax'])) {
                $ajaxEnabled = (int) $display->options['use_ajax'];
              }

              if (isset($display->options['defaults']['arguments']) && $display->options['defaults']['arguments'] === FALSE) {
                $hasContextualFilters = !empty($display->options['arguments']);
              }
              else {
                $hasContextualFilters = !empty($defaultDisplay['display_options']['arguments']);
              }

              $cms_view_elements_settings[$key] = [
                'view_display_type' => $display->getType(),
                'title' => !empty($title) ? 1 : 0,
                'contextual_filter' => $hasContextualFilters,
                'exposed_filter' => $hasExposed,
                'ajax_enabled' => $ajaxEnabled,
              ];

              $fields = $display->display['display_options']['fields'] ?? [];
              // Copy field list form default display when possible.
              if (count($fields) == 0 && $display->usesFields()) {
                $fields = $defaultDisplay['display_options']['fields'];
              }
              $relationships = [];
              foreach ($fields as $k => $field) {
                $handler = $executable_view->display_handler->getHandler('field', $field['id']);
                if (empty($handler)) {
                  $field_name = $this->t(
                    'Broken/missing handler: @table > @field',
                    [
                      '@table' => $field['table'],
                      '@field' => $field['field'],
                    ]
                  );
                }
                else {
                  $field_name = Html::escape($handler->adminLabel(TRUE));
                }

                /* @phpstan-ignore-next-line */
                if (!empty($field['relationship']) && !empty($relationships[$field['relationship']])) {
                  $field_name = '(' . $relationships[$field['relationship']] . ') ' . $field_name;
                }
                $fields[$k] = $field_name;
              }
              $cms_view_elements_settings[$key]['use_fields'] = (count($fields) > 1) ? 1 : 0;
              $cms_view_elements_settings[$key]['field_list'] = $fields;

              if (!empty($display->display['display_options']['pager'])) {
                $pager = $display->display['display_options']['pager'];
                $cms_view_elements_settings[$key]['pager'] = [
                  'items_per_page' => !empty($pager['options']['items_per_page']) ? $pager['options']['items_per_page'] : NULL,
                  'offset' => !empty($pager['options']['offset']) ? $pager['options']['offset'] : NULL,
                ];
              }
              elseif (!empty($cms_view_elements_settings['az_view-' . $view->id() . '-default']['pager'])) {
                $cms_view_elements_settings[$key]['pager'] = $cms_view_elements_settings['az_view-' . $executable_view->id() . '-default']['pager'];
              }
              else {
                $cms_view_elements_settings[$key] = [
                  'items_per_page' => NULL,
                  'offset' => NULL,
                ];
              }
            }
          }
          catch (\Exception $exception) {
            $this->loggerFactory->get('dxpr_builder')
              ->error($exception->getMessage());
          }
        }

        $this->cacheBackend->set('dxpr_builder:cms_view_elements_settings', $cms_view_elements_settings);
      }
    }

    return $cms_view_elements_settings;
  }

  /**
   * Retrieves and caches list of Views tags to help organize and filter the.
   *
   * Interface where you can select views displays in the DXPR Builder modal.
   *
   * @return mixed[]
   *   Array of views tags keyed by an identifier with the view & display name.
   */
  protected function getCmsViewsTags() {
    $cms_views_tags = &drupal_static(__FUNCTION__);
    if (!isset($cms_views_tags)) {
      if ($cache = $this->cacheBackend->get('dxpr_builder:cms_views_tags')) {
        $cms_views_tags = $cache->data;
      }
      else {
        $cms_views_tags = [];

        if ($this->moduleHandler->moduleExists('views')) {
          $views = Views::getAllViews();
          foreach ($views as $view) {
            if (!$view->status()) {
              continue;
            }
            $executable_view = Views::getView($view->id());
            $executable_view->initDisplay();
            foreach ($executable_view->displayHandlers as $id => $display) {
              $cms_views_tags['az_view-' . $executable_view->id() . '-' . $id] = $executable_view->id();
            }
          }
        }

        $this->cacheBackend->set('dxpr_builder:cms_views_tags', $cms_views_tags);
      }
    }

    return $cms_views_tags;
  }

  /**
   * Discovers CSS classes used for (bootstrap) buttons.
   *
   * Checks for button classes in dxpr_elements/Buttons and
   * in modules implementing hook_dxpr_builder_element_buttons_folders.
   * These classes are used in the button modal element settings.
   *
   * @return mixed[]
   *   Array of button style classes, keyed by an identifier for button style.
   */
  protected function getButtonStyles() {
    $button_styles = &drupal_static(__FUNCTION__);
    if (!isset($button_styles)) {
      if ($cache = $this->cacheBackend->get('dxpr_builder:button_styles')) {
        $button_styles = $cache->data;
      }
      else {
        $button_styles = [];

        $dxpr_element_buttons_folders = [$this->getModulePath() . DIRECTORY_SEPARATOR . 'dxpr_elements/Buttons'];
        $this->moduleHandler->alter('dxpr_builder_element_buttons_folders', $dxpr_element_buttons_folders);

        $elements = [];
        foreach ($dxpr_element_buttons_folders as $src) {
          if (is_dir($src)) {
            $files = $this->scanDirectory($src, '/\.html/');
            foreach ($files as $path => $file) {
              $path = realpath($path);
              $info = pathinfo($path);
              if ($info['extension'] == 'html') {
                $elements[$info['filename']] = file_get_contents($path);
              }
            }
          }
        }
        foreach ($elements as &$element) {
          preg_match('/class="(.*?)"/', $element, $match);
          $classes = preg_replace('/(btn\s)|(btn-\w+\s)|(\saz-\w+$)/', '', $match[1]);
          if (!empty($classes)) {
            $element = $classes;
          }
          else {
            unset($element);
          }
        }
        $button_styles = $elements;
        $this->cacheBackend->set('dxpr_builder:button_styles', $button_styles);
      }
    }
    return $button_styles;
  }

  /**
   * Get the path to this module.
   *
   * @return string
   *   The path of the module
   */
  private function getModulePath() {
    return $this->moduleExtensionList->getPath('dxpr_builder');
  }

  /**
   * Get the path to a theme more module.
   *
   * @param string $type
   *   The type of path to get - module or theme.
   * @param string $key
   *   The module/theme for which the path should be returned.
   *
   * @return string
   *   The path, relative to the webroot, of the module/theme
   */
  private function getPath($type, $key) {
    if ($type === 'module') {
      return $this->moduleExtensionList->getPath($key);
    }
    else {
      return $this->themeExtensionList->getPath($key);
    }
  }

  /**
   * Provides an OOP wrapper for file_scan_directory()
   *
   * @param string $dir
   *   The base directory or URI to scan, without trailing slash.
   * @param string $mask
   *   The preg_match() regular expression for files to be included.
   * @param mixed[] $options
   *   An associative array of additional options, with the following elements:
   *   - 'nomask': The preg_match() regular expression for files to be excluded.
   *     Defaults to the 'file_scan_ignore_directories' setting.
   *   - 'callback': The callback function to call for each match. There is no
   *     default callback.
   *   - 'recurse': When TRUE, the directory scan will recurse the entire tree
   *     starting at the provided directory. Defaults to TRUE.
   *   - 'key': The key to be used for the returned associative array of files.
   *     Possible values are 'uri', for the file's URI; 'filename', for the
   *     basename of the file; and 'name' for the name of the file without the
   *     extension. Defaults to 'uri'.
   *   - 'min_depth': Minimum depth of directories to return files from.
   *     Defaults to 0.
   *
   * @return mixed[]
   *   An associative array (keyed on the chosen key) of objects with 'uri',
   *   'filename', and 'name' properties corresponding to the matched files.
   *
   * @see file_scan_directory
   */
  private function scanDirectory($dir, $mask, array $options = []) {
    return $this->fileSystem->scanDirectory($dir, $mask, $options);
  }

  /**
   * Parse the content to determine if there are any scripts.
   *
   * As well as to determine the mode (static or dynamic).
   *
   * @param mixed[] $response
   *   An array containing the following keys:
   *   - output: the value to be altered by this function
   *   - library: an array of libraries to be included
   *   - settings: an array of drupalSettings to be included
   *   - mode: the mode of the response.
   */
  private function parseContentForScripts(array &$response): void {
    if (
      (strpos($response['output'], 'dxpr_frontend.min.js') !== FALSE)
      || strpos($response['output'], 'dxpr_frontend.js') !== FALSE
    ) {
      // Dynamic mode means we add dxpr_frontend.js for processing of elements
      // and styles that depend on JS. For example circle counter, parallax
      // backgrounds video backgrounds, etc.
      $response['mode'] = 'dynamic';
    }
  }

  /**
   * Parse the given value for content.
   *
   * @param mixed[] $response
   *   An array containing the following keys:
   *   - output: the value to be altered by this function
   *   - library: an array of libraries to be included
   *   - settings: an array of drupalSettings to be included
   *   - mode: the mode of the response.
   * @param bool $enable_editor
   *   Check if editor mode is enabled.
   */
  private function parseForContent(array &$response, bool $enable_editor): void {
    $doc = $this->createDocument($response['output']);
    $this->stripScriptsAndStylesheetsFromContent($doc, $response);
    $this->parseDocumentForTemplateLibrary($doc, $response);
    $this->parseDocumentForCmsElements($doc, $response);
    if (!$enable_editor) {
      $this->parseDocumentForCleanup($doc);
    }
    $this->getValueFromDoc($doc, $response);
  }

  /**
   * Create a DOMDocument from the given data.
   *
   * To be used to parse the data for content.
   *
   * @param string $data
   *   The data that is to be parsed into a DOMDocument.
   *
   * @return \DOMDocument
   *   An object containing the data, ready to be parsed for content
   */
  private function createDocument($data) {
    // We convert html string to DOM object so that we can
    // process individual elements.
    $doc = new \DOMDocument("1.0", "UTF-8");
    $doc->resolveExternals = FALSE;
    $doc->substituteEntities = FALSE;
    $doc->strictErrorChecking = FALSE;
    libxml_use_internal_errors(TRUE);
    $html = '<?xml encoding="UTF-8"><!DOCTYPE html><html><head></head><body>' . $data . '</body></html>';
    @$doc->loadHTML($html);
    libxml_clear_errors();

    return $doc;
  }

  /**
   * Strip scripts & stylesheets from the content.
   *
   * As they are added in libraries.
   *
   * @param \DOMDocument $doc
   *   The documentcontaining the parseable data.
   * @param mixed[] $response
   *   An array containing the following keys:
   *   - output: the value to be altered by this function
   *   - library: an array of libraries to be included
   *   - settings: an array of drupalSettings to be included
   *   - mode: the mode of the response.
   */
  private function stripScriptsAndStylesheetsFromContent(\DOMDocument $doc, array &$response): void {
    // Strip script tags.
    $scripts = $doc->getElementsByTagName('script');
    // Looping backwards due to DOM changing and DomNodeList
    // quirks: http://php.net/manual/en/class.domnodelist.php#83390
    for ($i = $scripts->length; --$i >= 0;) {
      $script = $scripts->item($i);
      if ($script->hasAttribute('src')) {
        /** @var \DOMElement $parent */
        $parent = $script->parentNode;
        $parent_classes = $parent->getAttribute('class');
        if (strpos($parent_classes, 'az-html') !== FALSE) {
          // Skip over tags in HTML elements.
          return;
        }
        $script->parentNode->removeChild($script);
      }
    }

    // Strip stylesheets.
    $stylesheets = $doc->getElementsByTagName('link');
    for ($i = $stylesheets->length; --$i >= 0;) {
      $stylesheet = $stylesheets->item($i);
      if ($stylesheet->hasAttribute('rel') && $stylesheet->getAttribute('rel') == 'stylesheet') {
        /** @var \DOMElement $parent */
        $parent = $stylesheet->parentNode;
        $parent_classes = $parent->getAttribute('class');
        if (strpos($parent_classes, 'az-html') !== FALSE) {
          // Skip over tags in HTML elements.
          return;
        }
        $stylesheet->parentNode->removeChild($stylesheet);
      }
    }
  }

  /**
   * Parse the given DOMDocument for libraries to be included in the response.
   *
   * Any found libraries should be added to the $response['libraries'] array.
   *
   * @param \DOMDocument $doc
   *   The documentcontaining the parseable data.
   * @param mixed[] $response
   *   An array containing the following keys:
   *   - output: the value to be altered by this function
   *   - library: an array of libraries to be included
   *   - settings: an array of drupalSettings to be included
   *   - mode: the mode of the response.
   */
  private function parseDocumentForTemplateLibrary(\DOMDocument $doc, array &$response): void {
    $xpath = new \DOMXpath($doc);
    // We aggregate all element css and remove the link tags.
    $result = $xpath->query('//*[@data-dxpr-builder-libraries]');

    $nodes = [];
    foreach ($result as $node) {
      $nodes[] = $node;
    }

    foreach ($nodes as $node) {
      $library_keys = $node->getAttribute('data-dxpr-builder-libraries');
      $keys = explode(' ', $library_keys);
      foreach ($keys as $key) {
        if ($key == 'font_awesome_5_pro') {
          $key = 'font_awesome_5_free';
        }
        $response['library'][] = 'dxpr_builder/elements.' . $key;
      }
    }
  }

  /**
   * Parse the given DOMDocument for Drupal elements (blocks, views etc).
   *
   * To be returned as the response.
   *
   * @param \DOMDocument $doc
   *   The documentcontaining the parseable data.
   * @param mixed[] $response
   *   An array containing the following keys:
   *   - output: the value to be altered by this function
   *   - library: an array of libraries to be included
   *   - settings: an array of drupalSettings to be included
   *   - mode: the mode of the response.
   */
  private function parseDocumentForCmsElements(\DOMDocument $doc, array &$response): void {
    // Drupal blocks and views are represented as empty tags, here we replace
    // empty tags with the actual block or view content.
    $xpath = new \DOMXpath($doc);
    $result = $xpath->query('//div[contains(@class,"az-cms-element")]');

    $nodes = [];
    foreach ($result as $node) {
      $nodes[] = $node;
    }

    foreach ($nodes as $node) {
      while ($node->hasChildNodes()) {
        $node->removeChild($node->firstChild);
      }
      $base = $node->getAttribute('data-azb');
      $settings = $node->getAttribute('data-azat-settings');

      // Additional settings for cms views.
      $data = [
        'display_title' => $node->getAttribute('data-azat-display_title'),
        'display_exposed_filters' => $node->getAttribute('data-azat-display_exposed_filters'),
        'override_pager' => $node->getAttribute('data-azat-override_pager'),
        'items' => $node->getAttribute('data-azat-items'),
        'offset' => $node->getAttribute('data-azat-offset'),
        'contextual_filter' => $node->getAttribute('data-azat-contextual_filter'),
        'toggle_fields' => $node->getAttribute('data-azat-toggle_fields'),
      ];

      $element_info = $this->parseStringForCmsElementInfo(substr($base, 3));
      $assets = new AttachedAssets();

      $html = $this->loadCmsElement($element_info, $settings, $data, $assets);
      if ($html) {
        $this->documentAppendHtml($node, $html);
        $response['library'] = array_merge($response['library'], $assets->getLibraries());
        $response['settings'] = array_merge_recursive($response['settings'], $assets->getSettings());
      }
    }
  }

  /**
   * Parse the given DOMDocument.
   *
   * Remove editor attributes if editor is not enabled on the container.
   *
   * @param \DOMDocument $doc
   *   The documentcontaining the parseable data.
   *
   * @return \DOMDocument
   *   An object containing the data, ready to be parsed for content
   */
  private function parseDocumentForCleanup(\DOMDocument $doc) {
    $xpath = new \DOMXpath($doc);
    // Cleanup builder attributes.
    $dynamic_els = [
      'accordion',
      'carousel',
      'container',
      'layers',
      'section',
      'tabs',
      'circle_counter',
      'countdown',
      'counter',
      'images_carousel',
      'video',
    ];
    $passthrough_attrs = [
      // General.
      'b',
      'id',
      'pid',
      'cnt',
      // Animation.
      'an_start',
      'an_in',
      'an_out',
      'an_hidden',
      'an_infinite',
      'an_offset',
      'an_duration',
      'an_in_delay',
      'an_out_delay',
      'an_parent',
      'an_name',
      // Core Elements.
      // Section.
      'effect',
      'parallax_speed',
      'parallax_mode',
      'parallax_mobile_disable',
      'video_options',
      'video_youtube',
      'video_start',
      'video_stop',
      // Tab.
      'title',
      // Tabs.
      'az_dirrection',
      'responsive',

      // DO NOT PASTE GENERATED LIST ABOVE THIS LINE.
      // DXPR Elements Generated list.
      // Carousel.
      // Collapsibles.
      'collapsed',
      // Carousel.
      'items',
      'autoplay',
      'pagination',
      'pagination_orientation',
      'pagination_shape',
      'pagination_transform',
      'pagination_color',
      'pagination_active_color',
      'navigation',
      'navigation_orientation',
      'navigation_shape',
      'navigation_icon_color',
      'navigation_icon_hover_color',
      'navigation_background_color',
      'navigation_background_hover_color',
      'navigation_thickness',
      'navigation_position',
      'interval',
      'transition',
      'stoponhover',
      'options',
      // Circle counter.
      'fgcolor',
      'bgcolor',
      'fill',
      'percent',
      'dimension',
      'text',
      'fontsize',
      'info',
      'bordersize',
      'width',
      'border',
      'type',
      'icon',
      'icon_size',
      'icon_color',
      // Countdown Timer.
      'countdown_style',
      'counter_scope',
      'date',
      'date_time',
      'time',
      'reset_hours',
      'reset_minutes',
      'reset_seconds',
      'referrer',
      'restart',
      'saved',
      'display',
      // Number Counter.
      'start',
      'end',
      'speed',
      'seperator',
      'prefix',
      'postfix',
      // Image carousel.
      'interval',
      // Positioned Layers.
      'width',
      'height',
      'responsive',
    ];

    $dxprDataAttrsElements = $xpath->query('//*[contains(@class,"az-element") or contains(@class,"az-ctnr") or contains(@data-dxpr-builder-libraries,"")]');
    // Loop through elements.
    foreach ($dxprDataAttrsElements as $element) {
      $data_attrs = [];
      $attributes = $element->attributes;
      $el_name = str_replace('az_', '', $element->getAttribute('data-azb'));
      $el_anim = $element->getAttribute('data-azat-an_start');
      for ($i = 0; $i < $attributes->length; $i++) {
        $item = $attributes->item($i);
        $attr = $item->nodeName;
        // Collect data attributes at the element.
        if (preg_match('#^data-(az|dxpr)(.*)$#i', $attr) && !in_array($item->nodeName, $data_attrs)) {
          $data_attrs[] = $item->nodeName;
        }
      }

      // Loop through data attributes.
      foreach ($data_attrs as $attr) {
        if ($element->hasAttribute($attr)) {
          // Regular attribute params.
          $clean_attr = str_replace('data-azat-', '', $attr);
          // data-az-id.
          $clean_attr = str_replace('data-az-', '', $clean_attr);
          // data-azb, data-azcnt.
          $clean_attr = str_replace('data-az', '', $clean_attr);
          if ((!($el_name) or in_array($el_name, $dynamic_els) or $el_anim)
            && in_array($clean_attr, $passthrough_attrs)) {
            continue;
          }
          $element->removeAttribute($attr);
        }
      }
      // Remove empty attributes.
      foreach ($attributes as $attr) {
        $attrName = $attr->nodeName;
        $value = $attr->nodeValue;
        // @see https://www.php.net/manual/en/language.types.boolean.php.
        // For unknown reason circle counter icon breaks when empty
        // data-text is removed. Maybe we accidentally access the text node.
        if (!$value && $value !== "0"
          && $value !== 0
          && $value !== 0.0
          && ($attrName !== 'data-text')) {
          $element->removeAttribute($attrName);
        }
      }
    }

    return $doc;
  }

  /**
   * Appends HTML to DOMDocument object.
   *
   * Used to add Blocks/Views to DOM tree while processing raw Builder fields.
   *
   * @param \DOMNode $parent
   *   The DOM object to which a new node will be added.
   * @param string $source
   *   HTML code to be added on to DOM object.
   */
  private function documentAppendHtml(\DOMNode $parent, $source): void {
    $doc = new \DOMDocument("1.0", "UTF-8");
    $doc->resolveExternals = FALSE;
    $doc->substituteEntities = FALSE;
    $doc->strictErrorChecking = FALSE;
    libxml_use_internal_errors(TRUE);
    $html = '<?xml encoding="UTF-8"><!DOCTYPE html><html><head></head><body>' . $source . '</body></html>';
    @$doc->loadHTML($html);
    libxml_clear_errors();

    foreach ($doc->getElementsByTagName('head')->item(0)->childNodes as $node) {
      $imported_node = $parent->ownerDocument->importNode($node, TRUE);
      $parent->appendChild($imported_node);
    }

    foreach ($doc->getElementsByTagName('body')->item(0)->childNodes as $node) {
      $imported_node = $parent->ownerDocument->importNode($node, TRUE);
      $parent->appendChild($imported_node);
    }
  }

  /**
   * Retrieve the value from the now fully parsed document.
   *
   * Set it to $response['output'].
   *
   * @param \DOMDocument $doc
   *   The documentcontaining the parseable data.
   * @param mixed[] $response
   *   An array containing the following keys:
   *   - output: the value to be altered by this function
   *   - library: an array of libraries to be included
   *   - settings: an array of drupalSettings to be included
   *   - mode: the mode of the response.
   */
  private function getValueFromDoc(\DOMDocument $doc, array &$response): void {
    $response['output'] = preg_replace('/^<!DOCTYPE.+?>/', '', str_replace([
      '<?xml encoding="UTF-8">',
      '<html>',
      '</html>',
      '<head>',
      '</head>',
      '<body>',
      '</body>',
    ], ['', '', '', '', '', '', ''], $doc->saveHTML()));
  }

}
