<?php

namespace Drupal\google_map_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'google_map_field_default' widget.
 *
 * @FieldWidget(
 *   id = "google_map_field_default",
 *   label = @Translation("Google Map Field default"),
 *   field_types = {
 *     "google_map_field"
 *   }
 * )
 */
class GoogleMapFieldDefaultWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    static $unique = 0;
    $unique = $unique + 1;
    $instance_delta = $items->getName() . '-' . $delta . '-' . $unique;
    $element += [
      '#type' => 'fieldset',
      '#title' => $this->t('Map'),
    ];
    $element['#attached']['library'][] = 'google_map_field/google-map-field-widget-renderer';
    $element['#attached']['library'][] = 'google_map_field/google-map-apis';

    $element['preview'] = [
      '#type' => 'item',
      '#title' => $this->t('Preview'),
      '#markup' => '<div class="google-map-field-preview" data-delta="' . $instance_delta . '"></div>',
      '#prefix' => '<div class="google-map-field-widget right">',
      '#suffix' => '</div>',
    ];

    $element['intro'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Use the "Set Map" button for more options.'),
      '#prefix' => '<div class="google-map-field-widget left">',
    ];

    $element['name'] = [
      '#title' => $this->t('Map Name'),
      '#size' => 32,
      '#type' => 'textfield',
      '#default_value' => $items[$delta]->name ?? NULL,
      '#attributes' => [
        'data-name-delta' => $instance_delta,
      ],
    ];

    $element['lat'] = [
      '#title' => $this->t('Latitude'),
      '#type' => 'textfield',
      '#size' => 18,
      '#default_value' => $items[$delta]->lat ?? NULL,
      '#attributes' => [
        'data-lat-delta' => $instance_delta,
        'class' => [
          'google-map-field-watch-change',
        ],
      ],
    ];

    $element['lon'] = [
      '#title' => $this->t('Longitude'),
      '#type' => 'textfield',
      '#size' => 18,
      '#default_value' => $items[$delta]->lon ?? NULL,
      '#attributes' => [
        'data-lon-delta' => $instance_delta,
        'class' => [
          'google-map-field-watch-change',
        ],
      ],
      '#suffix' => '</div>',
    ];

    $element['zoom'] = [
      '#type' => 'hidden',
      '#default_value' => $items[$delta]->zoom ?? 9,
      '#attributes' => [
        'data-zoom-delta' => $instance_delta,
      ],
    ];

    $element['type'] = [
      '#type' => 'hidden',
      '#default_value' => $items[$delta]->type ?? 'roadmap',
      '#attributes' => [
        'data-type-delta' => $instance_delta,
      ],
    ];

    $element['width'] = [
      '#type' => 'hidden',
      '#default_value' => $items[$delta]->width ?? '100%',
      '#attributes' => [
        'data-width-delta' => $instance_delta,
      ],
    ];

    $element['height'] = [
      '#type' => 'hidden',
      '#default_value' => $items[$delta]->height ?? '450px',
      '#attributes' => [
        'data-height-delta' => $instance_delta,
      ],
    ];

    $element['marker'] = [
      '#type' => 'hidden',
      '#default_value' => $items[$delta]->marker ?? "1",
      '#attributes' => [
        'data-marker-delta' => $instance_delta,
      ],
    ];

    $element['traffic'] = [
      '#type' => 'hidden',
      '#default_value' => $items[$delta]->traffic ?? "0",
      '#attributes' => [
        'data-traffic-delta' => $instance_delta,
      ],
    ];

    $element['marker_icon'] = [
      '#type' => 'hidden',
      '#default_value' => $items[$delta]->marker_icon ?? "",
      '#attributes' => [
        'data-marker-icon-delta' => $instance_delta,
      ],
    ];

    $element['controls'] = [
      '#type' => 'hidden',
      '#default_value' => $items[$delta]->controls ?? "1",
      '#attributes' => [
        'data-controls-delta' => $instance_delta,
      ],
    ];

    $element['infowindow'] = [
      '#type' => 'hidden',
      '#default_value' => $items[$delta]->infowindow ?? "",
      '#attributes' => [
        'data-infowindow-delta' => $instance_delta,
      ],
    ];

    $element['actions'] = [
      '#type' => 'actions',
      '#attributes' => [
        'class' => ['field-map-actions'],
      ],
    ];

    $element['actions']['open_map'] = [
      '#type' => 'button',
      '#value' => $this->t('Set Map'),
      '#attributes' => [
        'data-delta' => $instance_delta,
        'id' => 'map_setter_' . $instance_delta,
      ],
    ];

    $element['actions']['clear_fields'] = [
      '#type' => 'button',
      '#value' => $this->t('Clear'),
      '#attributes' => [
        'data-delta' => $instance_delta,
        'id' => 'clear_fields_' . $instance_delta,
        'class' => [
          'google-map-field-clear',
        ],
      ],
    ];

    return $element;
  }

}
