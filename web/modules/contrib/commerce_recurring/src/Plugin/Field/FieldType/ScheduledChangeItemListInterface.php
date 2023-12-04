<?php

namespace Drupal\commerce_recurring\Plugin\Field\FieldType;

use Drupal\commerce_recurring\ScheduledChange;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Represents a list of adjustment item field values.
 */
interface ScheduledChangeItemListInterface extends FieldItemListInterface {

  /**
   * Gets the scheduled change value objects from the field list.
   *
   * @return \Drupal\commerce_recurring\ScheduledChange[]
   *   An array of scheduled change value objects.
   */
  public function getScheduledChanges();

  /**
   * Removes the matching scheduled change value.
   *
   * @param \Drupal\commerce_recurring\ScheduledChange $scheduled_change
   *   The scheduled change.
   *
   * @return $this
   */
  public function removeScheduledChange(ScheduledChange $scheduled_change);

}
