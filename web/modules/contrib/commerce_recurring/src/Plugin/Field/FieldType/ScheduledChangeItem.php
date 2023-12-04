<?php

namespace Drupal\commerce_recurring\Plugin\Field\FieldType;

use Drupal\commerce_recurring\ScheduledChange;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'commerce_scheduled_change' field type.
 *
 * @FieldType(
 *   id = "commerce_scheduled_change",
 *   label = @Translation("Scheduled change"),
 *   description = @Translation("Stores a a scheduled change"),
 *   category = @Translation("Commerce"),
 *   list_class = "\Drupal\commerce_recurring\Plugin\Field\FieldType\ScheduledChangeItemList",
 *   default_formatter = "commerce_scheduled_change_default",
 * )
 */
class ScheduledChangeItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['field_name'] = DataDefinition::create('string')
      ->setLabel(t('Field name'))
      ->setRequired(TRUE);
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Value'))
      ->setRequired(TRUE);
    $properties['created'] = DataDefinition::create('timestamp')
      ->setLabel(t('Created'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'field_name' => [
          'type' => 'varchar',
          'length' => 64,
        ],
        'value' => [
          'type' => 'varchar',
          'length' => 255,
        ],
        'created' => [
          'type' => 'int',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return empty($this->field_name) || empty($this->value);
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    // Allow callers to pass a ScheduledChange value object.
    if ($values instanceof ScheduledChange) {
      $values = [
        'field_name' => $values->getFieldName(),
        'value' => $values->getValue(),
        'created' => $values->getCreatedTime(),
      ];
    }

    parent::setValue($values, $notify);
  }

  /**
   * Gets the scheduled change value object for the current field item.
   *
   * @return \Drupal\commerce_recurring\ScheduledChange
   *   The scheduled change object.
   */
  public function toScheduledChange() {
    return new ScheduledChange($this->field_name, $this->value, $this->created);
  }

}
