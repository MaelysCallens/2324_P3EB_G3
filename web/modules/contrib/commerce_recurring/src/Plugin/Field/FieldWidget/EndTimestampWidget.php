<?php

namespace Drupal\commerce_recurring\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\Plugin\Field\FieldWidget\TimestampDatetimeWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'commerce_recurring_end_timestamp' widget.
 *
 * @FieldWidget(
 *   id = "commerce_recurring_end_timestamp",
 *   label = @Translation("End timestamp"),
 *   field_types = {
 *     "timestamp"
 *   }
 * )
 */
class EndTimestampWidget extends TimestampDatetimeWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['has_value'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Provide an end date'),
      '#default_value' => !empty($element['value']['#default_value']),
      '#access' => empty($element['value']['#default_value']),
    ];
    $element['value']['#weight'] = 10;
    $element['value']['#description'] = '';
    // Workaround for #2419131.
    $field_name = $this->fieldDefinition->getName();
    $element['container']['#type'] = 'container';
    $element['container'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="' . $field_name . '[' . $delta . '][has_value]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $element['container']['value'] = $element['value'];
    unset($element['value']);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$item) {
      $item['value'] = $item['container']['value'];
      // This is required, otherwise the field isn't considered as empty by
      // TimestampItem preventing empty values to be saved.
      unset($item['has_value']);
      unset($item['container']);
      if (empty($item['value'])) {
        continue;
      }
      if ($item['value'] instanceof DrupalDateTime) {
        $item['value'] = $item['value']->getTimestamp();
      }
      elseif (isset($item['value']['object']) && $item['value']['object'] instanceof DrupalDateTime) {
        $item['value'] = $item['value']['object']->getTimestamp();
      }
    }
    return $values;
  }

}
