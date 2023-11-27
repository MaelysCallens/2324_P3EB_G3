<?php

namespace Drupal\google_map_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'google_map_field' OpenLayers formatter.
 *
 * @FieldFormatter(
 *   id = "google_map_field_open_layers",
 *   label = @Translation("Google Map Field OpenLayers"),
 *   field_types = {
 *     "google_map_field"
 *   }
 * )
 */
class GoogleMapFieldOpenLayersFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $element = [
        '#theme' => 'olmap_field',
        '#name' => $item->name,
        '#lat' => $item->lat,
        '#lon' => $item->lon,
        '#zoom' => $item->zoom,
        '#show_marker' => $item->marker === "1" ? "true" : "false",
        '#show_controls' => $item->controls === "1" ? "true" : "false",
        '#width' => $item->width ? $item->width : '320px',
        '#height' => $item->height ? $item->height : '200px',
        '#default_marker' => '/' . \Drupal::service('extension.list.module')->getPath('google_map_field') . '/images/pin.png',
      ];

      // Handle markup for InfoWindow popup.
      if (!empty($item->infowindow)) {
        $element['#infowindow'] = [
          '#markup' => $item->infowindow,
          '#allowed_tags' => $this->allowedTags(),
        ];
      }

      $element['#attached']['library'][] = 'google_map_field/olmap-field-renderer';
      $element['#attached']['library'][] = 'google_map_field/olmap-apis';

      $elements[$delta] = $element;
    }

    return $elements;
  }

  /**
   * Retrieves list of allowed tags for infowindow popup.
   *
   * @return array
   *   List of allowed tags to use on infowindow popup.
   */
  private function allowedTags() {
    return [
      'div',
      'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
      'p', 'span', 'br', 'em', 'strong',
      'a', 'img', 'video',
      'ul', 'ol', 'li',
    ];
  }

}
