<?php

namespace Drupal\dxpr_builder\Plugin\views\field;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\dxpr_builder\Service\DxprBuilderLicenseServiceInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to indicate if the user is a DXPR Builder user.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("dxpr_builder_user_field")
 */
class DxprBuilderUserField extends FieldPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The DXPR Builder License Service.
   *
   * @var \Drupal\dxpr_builder\Service\DxprBuilderLicenseServiceInterface
   */
  protected $licenseService;

  /**
   * Constructs a new DxprBuilderUserField object.
   *
   * @param mixed[] $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\dxpr_builder\Service\DxprBuilderLicenseServiceInterface $license_service
   *   The DXPR Builder License Service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DxprBuilderLicenseServiceInterface $license_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->licenseService = $license_service;
  }

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param mixed[] $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('dxpr_builder.license_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query(): void {
    // No query needed for this field.
  }

  /**
   * Renders dxpr user field.
   *
   * @param \Drupal\views\ResultRow $values
   *   The values.
   *
   * @return mixed[]|string|\Drupal\Component\Render\MarkupInterface
   *   Rertuns the markup
   */
  public function render(ResultRow $values) {
    /** @var \Drupal\user\UserInterface $user */
    $user = $this->getEntity($values);
    if ($this->licenseService->isBillableUser($user)) {
      return ['#markup' => $this->t('Yes')];
    }
    return ['#markup' => $this->t('No')];
  }

}
