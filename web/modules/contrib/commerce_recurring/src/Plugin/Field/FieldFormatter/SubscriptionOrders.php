<?php

namespace Drupal\commerce_recurring\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'subscription_orders' formatter.
 *
 * @FieldFormatter(
 *   id = "subscription_orders",
 *   label = @Translation("Subscription orders"),
 *   field_types = {
 *     "entity_reference",
 *   },
 * )
 */
class SubscriptionOrders extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ViewsSelection object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    /** @var \Drupal\views\Entity\View $view */
    $view = $this->entityTypeManager
      ->getStorage('view')
      ->load($this->getSetting('view'));

    return [
      $this->t('Using view %view.', [
        '%view' => $view->label(),
      ]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'view' => 'commerce_subscription_orders_customer',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $view_storage */
    $view_storage = $this->entityTypeManager->getStorage('view');

    /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
    $entity_type = $this->entityTypeManager->getDefinition('commerce_order');
    $eligible_views = array_filter($view_storage->loadMultiple(), static function ($view) use ($entity_type) {
      return in_array($view->get('base_table'), [$entity_type->getBaseTable(), $entity_type->getDataTable()]);
    });

    $elements = [];
    $options = array_map(static function ($view) {
      return $view->label();
    }, $eligible_views);
    $elements['view'] = [
      '#type' => 'select',
      '#title' => $this->t('View used to display the orders'),
      '#required' => TRUE,
      '#options' => $options,
      '#default_value' => $this->getSetting('view'),
      '#description' => '<p>' . $this->t('Choose the view to use to render the recurring order list for this display. The default display will be used.') . '</p>',
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $subscription */
    $subscription = $items->getEntity();
    $elements = [];
    $elements[0] = [
      '#type' => 'view',
      '#name' => $this->getSetting('view'),
      '#arguments' => [$subscription->id()],
      '#embed' => TRUE,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $entity_type = $field_definition->getTargetEntityTypeId();
    $field_name = $field_definition->getName();
    return $entity_type == 'commerce_subscription' && ($field_name === 'initial_order' || $field_name === 'orders');
  }

}
