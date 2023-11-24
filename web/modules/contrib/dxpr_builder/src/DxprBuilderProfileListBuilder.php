<?php

namespace Drupal\dxpr_builder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a listing of dxpr builder profiles.
 */
class DxprBuilderProfileListBuilder extends EntityListBuilder {

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
    $header['roles'] = $this->t('Roles');
    $header['status'] = $this->t('Status');
    $header['dxpr_editor'] = $this->t('DXPR Editor');
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
    /** @var \Drupal\dxpr_builder\DxprBuilderProfileInterface $entity */
    $row['label'] = $entity->label();
    $row['id']['data']['#markup'] = implode(',', $entity->get('roles'));
    $row['status']['data']['#markup'] = $entity->status() ? $this->t('Enabled') : $this->t('Disabled');
    $row['dxpr_editor']['data']['#markup'] = $entity->get('dxpr_editor') ? $this->t('Always On') : $this->t('Always Off');
    return $row + parent::buildRow($entity);
  }

}
