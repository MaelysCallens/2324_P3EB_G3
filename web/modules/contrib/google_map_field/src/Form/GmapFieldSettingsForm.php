<?php

namespace Drupal\google_map_field\Form;

/**
 * @file
 * Contains \Drupal\google_map_field\Form\GmapFieldSettingsForm.
 */
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

// Enumerate options for type of API authentication.
define('GOOGLE_MAP_FIELD_AUTH_KEY', 1);
define('GOOGLE_MAP_FIELD_AUTH_WORK', 2);

/**
 * Administration settings form.
 */
class GmapFieldSettingsForm extends ConfigFormBase {

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The construct method.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
    $container->get('config.factory')
    );
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormId() {
    return 'google_map_field_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'google_map_field.settings',
    ];
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('google_map_field.settings');
    $settings = $config->get();
    $api_key = '';
    $client_id = '';

    if (isset($settings['google_map_field_apikey']) && trim($settings['google_map_field_apikey']) != '') {
      $api_key = $settings['google_map_field_apikey'];
    }

    if (isset($settings['google_map_field_map_client_id']) && trim($settings['google_map_field_map_client_id']) != '') {
      $client_id = $settings['google_map_field_map_client_id'];
    }

    $form['google_map_field_auth_method'] = [
      '#type' => 'select',
      '#title' => $this->t('Google API Authentication Method'),
      '#default_value' => $settings['google_map_field_auth_method'] ?? GOOGLE_MAP_FIELD_AUTH_KEY,
      '#options' => [
        GOOGLE_MAP_FIELD_AUTH_KEY => $this->t('API Key'),
        GOOGLE_MAP_FIELD_AUTH_WORK => $this->t('Google Maps API for Work'),
      ],
    ];

    $form['google_map_field_apikey'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google Maps API Key'),
      '#description' => $this->t('Obtain a Google Maps Javascript API key at <a href="@link">@link</a>', [
        '@link' => 'https://developers.google.com/maps/documentation/javascript/get-api-key',
      ]),
      '#default_value' => $api_key,
      '#required' => FALSE,
      '#size' => 80,
      '#states' => [
        'visible' => [
          ':input[name="google_map_field_auth_method"]' => ['value' => GOOGLE_MAP_FIELD_AUTH_KEY],
        ],
      ],
    ];

    $form['google_map_field_map_client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google Maps API for Work: Client ID'),
      '#description' => $this->t('For more information, visit: <a href="@link">@link</a>', [
        '@link' => 'https://developers.google.com/maps/documentation/javascript/get-api-key#client-id',
      ]),
      '#default_value' => $client_id,
      '#required' => FALSE,
      '#size' => 80,
      '#states' => [
        'visible' => [
          ':input[name="google_map_field_auth_method"]' => ['value' => GOOGLE_MAP_FIELD_AUTH_WORK],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface:submitForm()
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('google_map_field.settings');
    $config->set('google_map_field_auth_method', $form_state->getValue('google_map_field_auth_method'))
      ->set('google_map_field_apikey', $form_state->getValue('google_map_field_apikey'))
      ->set('google_map_field_map_client_id', $form_state->getValue('google_map_field_map_client_id'))
      ->set('google_map_field_icon', $form_state->getValue('google_map_field_icon'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
