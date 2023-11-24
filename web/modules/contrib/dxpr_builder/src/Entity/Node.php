<?php

namespace Drupal\dxpr_builder\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\node\Entity\Node as NodeEntity;

/**
 * Description.
 */
class Node extends NodeEntity {

  /**
   * Add an empty string to any field that would otherwise be completely empty.
   *
   * Without this code, the frontend editor has nothing to attach to.
   *
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::presave($storage);

    \Drupal::service('dxpr_builder.service')->setEmptyStringToDxprFieldsOnEntity($this);
  }

}
