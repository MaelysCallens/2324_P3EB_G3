<?php

namespace Drupal\dxpr_builder\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dxpr_builder\Service\DxprBuilderJWTDecoder;
use Drupal\dxpr_builder\Service\DxprBuilderLicenseServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for DXPR Builder Settings.
 */
class DxprBuilderSettingsForm extends FormBase {

  /**
   * Configuration Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * JWT service to manipulate the DXPR JSON token.
   *
   * @var \Drupal\dxpr_builder\Service\DxprBuilderJWTDecoder
   */
  protected $jwtDecoder;

  /**
   * DXPR license service.
   *
   * @var \Drupal\dxpr_builder\Service\DxprBuilderLicenseServiceInterface
   */
  protected $license;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\dxpr_builder\Service\DxprBuilderJWTDecoder $jwtDecoder
   *   Parsing DXPR JWT token.
   * @param \Drupal\dxpr_builder\Service\DxprBuilderLicenseServiceInterface $license
   *   DXPR license service.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    ModuleHandlerInterface $moduleHandler,
    EntityTypeManagerInterface $entityTypeManager,
    DxprBuilderJWTDecoder $jwtDecoder,
    DxprBuilderLicenseServiceInterface $license
  ) {
    $this->configFactory = $configFactory;
    $this->moduleHandler = $moduleHandler;
    $this->entityTypeManager = $entityTypeManager;
    $this->jwtDecoder = $jwtDecoder;
    $this->license = $license;
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-return mixed
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('entity_type.manager'),
      $container->get('dxpr_builder.jwt_decoder'),
      $container->get('dxpr_builder.license_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dxpr_builder_settings';
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $form
   * @phpstan-return array<string, mixed>
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    // $form = parent::buildForm($form, $form_state);
    $config = $this->configFactory->get('dxpr_builder.settings');

    $form['license_info'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('DXPR License'),
      '#description' => $this->t('Please enter your product key. Find your product key on our <a href=":uri" target="_blank">Getting Started page</a>', [
        ':uri' => 'https://app.dxpr.com/getting-started',
      ]),
    ];

    $form['license_info']['json_web_token'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Product key'),
      '#default_value' => $config->get('json_web_token'),
      '#required' => TRUE,
    ];

    $form['custom_overrides'] = [
      '#type' => 'details',
      '#title' => $this->t('DXPR Builder Overrides'),
      '#description' => $this->t('Customize DXPR Builder  behaviors.'),
    ];

    $form['custom_overrides']['offset_selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Smooth-scroll offset selector'),
      '#description' => $this->t('When using a #hash to link to content inside a DXPR Builder container, the user will smooth-scroll to this content. DXPR Builder will also open any tabs, collapsibles, or carousel slides that contain this content. DXPR Builder automatically corrects the amount of page it scrolls to account for the DXPR Theme fixed or sticky header. If you use a different theme you can use this setting to correct the smooth-scroll behavior for your fixed header by adding a CSS selector here.'),
      '#default_value' => $config->get('offset_selector') ?: '.dxpr-theme-header--sticky, .dxpr-theme-header--fixed',
    ];

    $form['ui_customization'] = [
      '#type' => 'details',
      '#title' => $this->t('Text Editor Overrides'),
      '#description' => $this->t('Extend DXPR Builder text editor style options and text editor font options.'),
    ];

    $form['ui_customization']['cke_stylesset'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Text Editor Formatting Styles'),
      '#description' => $this->t('Enter one class on each line in the format: @format. Example: @example.', [
        '@url' => 'https://ckeditor.com/docs/ckeditor4/latest/api/CKEDITOR_config.html#cfg-stylesSet',
        '@format' => '[label]=[element].[class]',
        '@example' => 'Sassy Title=h1.sassy-title',
      ]) . ' ' . $this->t('Uses the <a href="@url">@setting</a> setting internally.', [
        '@setting' => 'stylesSet',
        '@url' => 'http://docs.ckeditor.com/#!/api/CKEDITOR.config-cfg-stylesSet',
      ]
      ),
      '#default_value' => $config->get('cke_stylesset'),
      // '#element_validate' => array('form_validate_stylesset'),
    ];

    $form['bootstrap_details'] = [
      '#type' => 'details',
      '#title' => $this->t('Bootstrap Assets'),
    ];

    $form['bootstrap_details']['bootstrap'] = [
      '#type' => 'radios',
      '#title' => $this->t('Include Bootstrap Files'),
      '#options' => [
        0 => $this->t('No'),
        1 => $this->t('Load Bootstrap 3'),
        'bs4' => $this->t('Load Bootstrap 4'),
        'bs5' => $this->t('Load Bootstrap 5'),
      ],
      '#default_value' => $config->get('bootstrap'),
    ];

    $form['media_details'] = [
      '#type' => 'details',
      '#title' => $this->t('Media Browser'),
    ];
    $default = ['' => $this->t('None (Use basic file upload widget)')];
    if ($this->moduleHandler->moduleExists('entity_browser')) {
      /** @var array<mixed> $media_browsers */
      $media_browsers = $this->entityTypeManager->getStorage('entity_browser')
        ->getQuery()
        ->accessCheck(TRUE)
        ->execute();
      $media_browsers = $default + $media_browsers;
    }
    else {
      $media_browsers = $default;
    }
    $form['media_details']['media_browser'] = [
      '#type' => 'radios',
      '#title' => $this->t('Media Browser'),
      '#description' => $this->t('DXPR Builder supports media image reusability via the Entity Browser module. The Entity Browser selected here will be used by the editor. The Entity Browser has to be using the iFrame display plugin.'),
      '#options' => $media_browsers,
      '#default_value' => $config->get('media_browser') ?: '',
    ];

    $form['experimental'] = [
      '#type' => 'details',
      '#title' => $this->t('Text Format Filters'),
    ];

    $form['experimental']['format_filters'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Process Text Format Filters on Frontend Builder content'),
      '#description' => $this->t("Use with caution. If a field uses DXPR Builder as field formatter any filters that are set on the field's text format will be ignored. This is because when editing on the frontend, you are editing the raw field contents. With this setting enabled the DXPR editor still loads raw fields content, but users that don't have DXPR Builder editing permission will get a filtered field. Some filters will not work at all with DXPR Builder while others should work just fine."),
      '#default_value' => $config->get('format_filters'),
    ];

    // If a local build exists, provide the option to use it instead of the
    // cloud-hosted files.
    $frontend_asset_options = [
      0 => $this->t('Cloud'),
    ];
    if (file_exists(__DIR__ . '/../../dxpr_builder/dxpr_builder.min.js')) {
      $frontend_asset_options[1] = $this->t('Local files (minified)');
    }
    if (file_exists(__DIR__ . '/../../dxpr_builder/dxpr_builder.js')) {
      $frontend_asset_options[2] = $this->t('Local files (unminified)');
    }
    if (count($frontend_asset_options) > 1) {
      $form['editor_assets'] = [
        '#type' => 'details',
        '#title' => $this->t('Editor Assets'),
      ];
      $form['editor_assets']['editor_assets_source'] = [
        '#type' => 'radios',
        '#title' => $this->t('Source for Editor Assets'),
        '#description' => $this->t("Assets may be loaded from the cloud to provide an up-to-date version (recommended), or can be loaded from a local build."),
        '#options' => $frontend_asset_options,
        '#default_value' => $config->get('editor_assets_source'),
      ];
    }

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $form
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $jwtPayloadData = $this->jwtDecoder->decodeJwt($form_state->getValue('json_web_token'));
    if ($jwtPayloadData['sub'] === NULL || $jwtPayloadData['scope'] === NULL) {
      $form_state->setErrorByName('json_web_token', $this->t('Your DXPR Builder product key can’t be read, please make sure you copy the whole key without any trailing or leading spaces into the form.'));
      Cache::invalidateTags(['config:dxpr_builder.settings']);
    }
    elseif ($jwtPayloadData['dxpr_tier'] === NULL) {
      $form_state->setErrorByName('json_web_token', $this->t('Your product key (JWT) is outdated and not compatible with DXPR Builder version 2.0.0 and up. Please follow instructions <a href=":uri">here</a> to get a new product key.', [
        ':uri' => 'https://app.dxpr.com/download/all#token',
      ]));
      Cache::invalidateTags(['config:dxpr_builder.settings']);
    }
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $form
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->configFactory->getEditable('dxpr_builder.settings');

    $old_editor_assets_source = $config->get('editor_assets_source');
    $old_json_web_token = $config->get('json_web_token');
    $new_json_web_token = trim($form_state->getValue('json_web_token'));

    $config
      ->set('bootstrap', $form_state->getValue('bootstrap'))
      ->set('cke_stylesset', $form_state->getValue('cke_stylesset'))
      ->set('editor_assets_source', $form_state->getValue('editor_assets_source'))
      ->set('format_filters', $form_state->getValue('format_filters'))
      ->set('media_browser', $form_state->getValue('media_browser'))
      ->set('offset_selector', $form_state->getValue('offset_selector'))
      ->set('json_web_token', $new_json_web_token)
      ->save();

    $this->messenger()->addStatus($this->t('The configuration has been updated'));
    Cache::invalidateTags(['config:dxpr_builder.settings']);

    // Invalidate caches for the library declarations provided by
    // dxpr_builder_library_info_build().
    // There is no cache tag for this part.
    if ($old_editor_assets_source != $config->get('editor_assets_source') || $old_json_web_token != $config->get('json_web_token')) {
      drupal_flush_all_caches();
    }

    // Move users to new license when changing or setting a new license.
    if ($old_json_web_token != $new_json_web_token) {
      if ($old_json_web_token) {
        $this->license->syncAllUsersWithCentralStorage(DxprBuilderLicenseServiceInterface::DXPR_USER_REMOVE_OPERATION, $old_json_web_token);
      }
      $this->license->syncAllUsersWithCentralStorage(DxprBuilderLicenseServiceInterface::DXPR_USER_ADD_OPERATION, $new_json_web_token);
    }
  }

}
