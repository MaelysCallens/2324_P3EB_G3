<?php

namespace Drupal\dxpr_builder;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of DxprBuilderUserTemplateListBuilder.
 */
class DxprBuilderUserTemplateListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'dxpr_builder_user_template';
  }

  /**
   * {@inheritdoc}
   *
   * @return array
   *   ListBuilder header.
   *
   * @phpstan-return array<string, mixed>
   */
  public function buildHeader(): array {
    $header['label'] = $this->t('Label');
    $header['id'] = $this->t('Machine name');
    $header['uid'] = $this->t('User ID');
    $header['global'] = $this->t('Global');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   *
   * @return array
   *   ListBuilder entity row.
   *
   * @phpstan-return array<string, mixed>
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $entity */
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['uid'] = $entity->get('uid');
    $row['global'] = $entity->get('global') == 1 ? $this->t('True') : $this->t('False');

    return $row + parent::buildRow($entity);
  }

}
