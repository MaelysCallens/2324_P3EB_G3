<?php

namespace Drupal\dxpr_builder\Plugin\Block;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Retrieves block plugin definitions for all snippet blocks.
 */
class WebformBlockDeriver extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler to invoke the alter hook.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a Snippet object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @param mixed $base_plugin_definition
   *   The definition array of the base plugin.
   *
   * @phpstan-param mixed $base_plugin_definition
   *
   * @return array
   *   An array of full derivative definitions keyed on derivative id.
   *
   * @phpstan-return array<string, mixed>
   */
  public function getDerivativeDefinitions($base_plugin_definition): array {
    if ($this->moduleHandler->moduleExists('webform')) {

      /** @var \Drupal\webform\WebformEntityStorageInterface $webform_storage */
      $webform_storage = $this->entityTypeManager->getStorage('webform');
      /** @var \Drupal\webform\Entity\Webform[] $webforms */
      $webforms = $webform_storage->loadMultiple();

      foreach ($webforms as $webform) {
        if ($webform->isOpen()) {
          $delta = $webform->id();
          $this->derivatives[$delta] = $base_plugin_definition;
          $this->derivatives[$delta]['admin_label'] = $this->t('Webform: @webformlabel', ['@webformlabel' => $webform->label()]);
        }
      }

    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
