<?php

namespace Drupal\collapsible_fields;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Manages widget and formatter settings.
 */
class SettingsManager {

  use StringTranslationTrait;

  /**
   * Generates the element for widget/formatter settings form.
   *
   * @param \Drupal\Core\Field\WidgetInterface|\Drupal\Core\Field\FormatterInterface $plugin
   *   The plugin.
   *
   * @return array
   *   The form element.
   */
  public function getSettingsFromElement($plugin) {
    $element = [];
    $element['collapsible_fields_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Make Collapsible'),
      '#description' => $this->t('Check this option if you want make this field collapsible. It will make the label to show always.'),
      '#default_value' => $plugin->getThirdPartySetting('collapsible_fields', 'collapsible_fields_enabled'),
    ];
    $element['collapsible_fields_open'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Open'),
      '#description' => $this->t('Use this option if the field should start open.'),
      '#default_value' => $plugin->getThirdPartySetting('collapsible_fields', 'collapsible_fields_open'),
    ];
    return $element;
  }

  /**
   * Generates the summary for widget/formatter settings form.
   *
   * @param mixed $summary
   *   The summary.
   * @param mixed $context
   *   The context.
   */
  public function getSettingsFromSummary(&$summary, $context) {
    $context_plugin = isset($context['widget']) ? 'widget' : 'formatter';
    if ($context[$context_plugin]->getThirdPartySetting('collapsible_fields', 'collapsible_fields_enabled')) {
      $collapsible_fields_summary = $this->t('Collapsible');
      if ($context[$context_plugin]->getThirdPartySetting('collapsible_fields', 'collapsible_fields_open')) {
        $collapsible_fields_summary .= ' - ' . $this->t('Starts open');
      }
      $summary[] = $collapsible_fields_summary;
    }
  }

}
