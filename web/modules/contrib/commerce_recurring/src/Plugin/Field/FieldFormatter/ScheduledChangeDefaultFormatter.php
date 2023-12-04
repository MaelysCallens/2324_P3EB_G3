<?php

namespace Drupal\commerce_recurring\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Provides the default scheduled change formatter.
 *
 * @FieldFormatter(
 *   id = "commerce_scheduled_change_default",
 *   module = "commerce_recurring",
 *   label = @Translation("Scheduled change"),
 *   field_types = {
 *     "commerce_scheduled_change"
 *   },
 *   quickedit = {
 *     "editor" = "disabled"
 *   }
 * )
 */
class ScheduledChangeDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $build = [];
    /** @var \Drupal\commerce_recurring\Plugin\Field\FieldType\ScheduledChangeItem $item */
    foreach ($items as $delta => $item) {
      $scheduled_change = $item->toScheduledChange();

      $build[$delta] = [
        '#plain_text' => $scheduled_change->getFieldName() . ' - ' . $scheduled_change->getValue(),
      ];
    }
    return $build;
  }

}
