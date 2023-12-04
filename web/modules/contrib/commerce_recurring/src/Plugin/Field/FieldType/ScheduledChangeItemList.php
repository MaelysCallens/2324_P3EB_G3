<?php

namespace Drupal\commerce_recurring\Plugin\Field\FieldType;

use Drupal\commerce_recurring\ScheduledChange;
use Drupal\Core\Field\FieldItemList;

/**
 * Represents a list of scheduled change item field values.
 */
class ScheduledChangeItemList extends FieldItemList implements ScheduledChangeItemListInterface {

  /**
   * {@inheritdoc}
   */
  public function getScheduledChanges() {
    $scheduled_changes = [];
    /** @var \Drupal\commerce_recurring\Plugin\Field\FieldType\ScheduledChangeItem $field_item */
    foreach ($this->list as $key => $field_item) {
      if (!$field_item->isEmpty()) {
        $scheduled_changes[$key] = $field_item->toScheduledChange();
      }
    }

    return $scheduled_changes;
  }

  /**
   * {@inheritdoc}
   */
  public function removeScheduledChange(ScheduledChange $scheduled_change) {
    /** @var \Drupal\commerce_recurring\Plugin\Field\FieldType\ScheduledChangeItem $field_item */
    foreach ($this->list as $key => $field_item) {
      if ($field_item->toScheduledChange() == $scheduled_change) {
        $this->removeItem($key);
      }
    }
  }

}
