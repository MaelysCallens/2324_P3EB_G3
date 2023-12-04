<?php

namespace Drupal\commerce_recurring;

/**
 * Represents a scheduled change.
 */
final class ScheduledChange {

  /**
   * The field name.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * The value.
   *
   * @var string
   */
  protected $value;

  /**
   * The created time.
   *
   * @var int
   */
  protected $created;

  /**
   * Constructs a new ScheduledChange object.
   *
   * @param string $field_name
   *   The field name.
   * @param string $value
   *   The value.
   * @param int $created
   *   The created timestamp.
   */
  public function __construct($field_name, $value, $created) {
    $this->fieldName = $field_name;
    $this->value = $value;
    $this->created = $created;
  }

  /**
   * Gets the field name.
   *
   * @return string
   *   The field name.
   */
  public function getFieldName() {
    return $this->fieldName;
  }

  /**
   * Gets the value.
   *
   * @return string
   *   The value.
   */
  public function getValue() {
    return $this->value;
  }

  /**
   * Gets the created time.
   *
   * @return string
   *   The created time.
   */
  public function getCreatedTime() {
    return $this->created;
  }

  /**
   * Gets the array representation of the scheduled change.
   *
   * @return array
   *   The array representation of the scheduled change.
   */
  public function toArray() {
    return [
      'field_name' => $this->fieldName,
      'value' => $this->value,
      'created' => $this->created,
    ];
  }

}
