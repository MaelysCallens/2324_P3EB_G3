<?php

namespace Drupal\commerce_recurring;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines the list builder for billing schedules.
 */
class BillingScheduleListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Billing schedule');
    $header['type'] = $this->t('Type');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\commerce_recurring\Entity\BillingScheduleInterface $entity */
    $row['label'] = $entity->label();
    if (!$entity->status()) {
      $row['label'] .= ' (' . $this->t('Disabled') . ')';
    }
    $row['type'] = $entity->getPlugin()->getLabel();
    return $row + parent::buildRow($entity);
  }

}
