<?php

namespace Drupal\dxpr_builder_page;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleUninstallValidatorInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Prevents dxpr_builder_page module from being uninstalled.
 */
class DXPRBuilderPageUninstallValidator implements ModuleUninstallValidatorInterface {

  use StringTranslationTrait;


  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new DXPRBuilderPageUninstallValidator.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation) {
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($module) {
    $reasons = [];
    if ($module == 'dxpr_builder_page') {
      if ($this->hasUsedBundles()) {
        $reasons[] = $this->t('To uninstall DXPR Builder Drag and Drop Node Type module, delete all content that has the Drag and Drop Page content type');
      }
    }
    return $reasons;
  }

  /**
   * Determines if there is any drag_and_drop_page nodes or not.
   *
   * @return bool
   *   TRUE if there are drag_and_drop_page nodes, FALSE otherwise.
   */
  protected function hasUsedBundles() {
    $nodes = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'drag_and_drop_page')
      ->accessCheck(FALSE)
      ->range(0, 1)
      ->execute();
    return !empty($nodes);
  }

}
