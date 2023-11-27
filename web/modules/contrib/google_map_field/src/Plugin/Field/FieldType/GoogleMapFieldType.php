<?php

namespace Drupal\google_map_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'Google Map' field type.
 *
 * @FieldType(
 *   id = "google_map_field",
 *   label = @Translation("Google Map field"),
 *   description = @Translation("This field stores Google Map fields in the database."),
 *   default_widget = "google_map_field_default",
 *   default_formatter = "google_map_field_default"
 * )
 */
class GoogleMapFieldType extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field) {
    return [
      'columns' => [
        'name' => [
          'type' => 'varchar',
          'length' => 128,
          'not null' => FALSE,
        ],
        'lat' => [
          'type' => 'float',
          'size' => 'big',
          'default' => 0.0,
          'not null' => FALSE,
        ],
        'lon' => [
          'type' => 'float',
          'size' => 'big',
          'default' => 0.0,
          'not null' => FALSE,
        ],
        'zoom' => [
          'type' => 'int',
          'length' => 10,
          'not null' => FALSE,
        ],
        'type' => [
          'type' => 'varchar',
          'length' => 32,
          'not null' => FALSE,
        ],
        'width' => [
          'type' => 'varchar',
          'length' => 32,
          'not null' => FALSE,
        ],
        'height' => [
          'type' => 'varchar',
          'length' => 32,
          'not null' => FALSE,
        ],
        'marker' => [
          'type' => 'int',
          'length' => 10,
          'not null' => FALSE,
        ],
        'traffic' => [
          'type' => 'int',
          'length' => 10,
          'not null' => FALSE,
        ],
        'marker_icon' => [
          'type' => 'varchar',
          'length' => 512,
          'not null' => FALSE,
        ],
        'controls' => [
          'type' => 'int',
          'length' => 10,
          'not null' => FALSE,
        ],
        'infowindow' => [
          'type' => 'text',
          'size' => 'medium',
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('lat')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['name'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Map Name'));

    $properties['lat'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Latitude'));

    $properties['lon'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Longitude'));

    $properties['zoom'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Map Zoom'));

    $properties['type'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Map Type'));

    $properties['width'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Map Width'));

    $properties['height'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Map Height'));

    $properties['marker'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Show marker'));

    $properties['traffic'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Traffic Layer'));

    $properties['marker_icon'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Custom marker'));

    $properties['controls'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Show controls'));

    $properties['infowindow'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('InfoWindow message'));

    return $properties;
  }

}
