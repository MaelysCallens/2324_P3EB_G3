<?php

namespace Drupal\commerce_paypal\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a PayPal credit messaging block.
 *
 * @Block(
 *   id = "commerce_paypal",
 *   admin_label = @Translation("PayPal Credit messaging"),
 *   category = @Translation("Commerce")
 * )
 */
class CreditMessaging extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'placement' => 'home',
      'style' => 'flex',
      'color' => 'blue',
      'ratio' => '1x1',
      'logo_type' => 'primary',
      'logo_position' => 'left',
      'text_size' => '12',
      'text_color' => 'black',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['placement'] = [
      '#type' => 'radios',
      '#title' => $this->t('Placement type'),
      '#default_value' => $this->configuration['placement'],
      '#options' => [
        'home' => $this->t('Home'),
        'category' => $this->t('Category'),
      ],
    ];

    $form['style'] = [
      '#type' => 'radios',
      '#title' => $this->t('Style'),
      '#default_value' => $this->configuration['style'],
      '#options' => [
        'flex' => $this->t('Banner'),
        'text' => $this->t('Text'),
      ],
    ];

    $states = [
      'visible' => [
        ':input[name="settings[style]"]' => ['value' => 'flex'],
      ],
    ];

    $form['color'] = [
      '#type' => 'select',
      '#title' => $this->t('Color'),
      '#default_value' => $this->configuration['color'],
      '#options' => [
        'blue' => $this->t('Blue'),
        'black' => $this->t('Black'),
        'white' => $this->t('White'),
        'white-no-border' => $this->t('White with no border'),
        'gray' => $this->t('Gray'),
        'monochrome' => $this->t('Monochrome'),
        'grayscale' => $this->t('Grayscale'),
      ],
      '#states' => $states,
    ];

    $form['ratio'] = [
      '#type' => 'select',
      '#title' => $this->t('Ratio'),
      '#default_value' => $this->configuration['ratio'],
      '#options' => [
        '1x1' => $this->t('Square (1x1)'),
        '1x4' => $this->t('Tall (1x4)'),
        '8x1' => $this->t('Wide (8x1)'),
        '20x1' => $this->t('Wide and narrow (20x1)'),
      ],
      '#states' => $states,
    ];

    $states = [
      'visible' => [
        ':input[name="settings[style]"]' => ['value' => 'text'],
      ],
    ];

    $form['logo_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Logo type'),
      '#description' => $this->t('See examples of these options in the <a href="https://developer.paypal.com/docs/limited-release/sdk-credit-messaging/reference/reference-tables/#logo-type" target="_blank">PayPal documentation</a>.'),
      '#default_value' => $this->configuration['logo_type'],
      '#options' => [
        'primary' => $this->t('Stacked PayPal Credit logo'),
        'alternative' => $this->t('Single line PayPal Credit logo'),
        'inline' => $this->t('Single line PayPal Credit logo without monogram'),
        'none' => $this->t('No logo, only text'),
      ],
      '#states' => $states,
    ];

    $form['logo_position'] = [
      '#type' => 'select',
      '#title' => $this->t('Logo position'),
      '#default_value' => $this->configuration['logo_position'],
      '#options' => [
        'left' => $this->t('Left'),
        'right' => $this->t('Right'),
        'top' => $this->t('Top'),
      ],
      '#states' => $states,
    ];

    $form['text_size'] = [
      '#type' => 'select',
      '#title' => $this->t('Text size'),
      '#default_value' => $this->configuration['text_size'],
      '#options' => [
        '10' => $this->t('10'),
        '11' => $this->t('11'),
        '12' => $this->t('12'),
        '13' => $this->t('13'),
        '14' => $this->t('14'),
        '15' => $this->t('15'),
        '16' => $this->t('16'),
      ],
      '#states' => $states,
    ];

    $form['text_color'] = [
      '#type' => 'select',
      '#title' => $this->t('Text color'),
      '#default_value' => $this->configuration['text_color'],
      '#options' => [
        'black' => $this->t('Black'),
        'white' => $this->t('White'),
        'monochrome' => $this->t('Monochrome'),
        'grayscale' => $this->t('Grayscale'),
      ],
      '#states' => $states,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['placement'] = $form_state->getValue('placement');
    $this->configuration['style'] = $form_state->getValue('style');
    $this->configuration['color'] = $form_state->getValue('color');
    $this->configuration['ratio'] = $form_state->getValue('ratio');
    $this->configuration['logo_type'] = $form_state->getValue('logo_type');
    $this->configuration['logo_position'] = $form_state->getValue('logo_position');
    $this->configuration['text_size'] = $form_state->getValue('text_size');
    $this->configuration['text_color'] = $form_state->getValue('text_color');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $element = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'data-pp-message' => '',
        'data-pp-placement' => $this->configuration['placement'],
        'data-pp-style-layout' => $this->configuration['style'],
      ],
      '#attached' => ['library' => ['commerce_paypal/credit_messaging']],
    ];

    if ($this->configuration['style'] == 'flex') {
      $element['#attributes'] += [
        'data-pp-style-color' => $this->configuration['color'],
        'data-pp-style-ratio' => $this->configuration['ratio'],
      ];
    }
    else {
      $element['#attributes'] += [
        'data-pp-style-logo-type' => $this->configuration['logo_type'],
        'data-pp-style-logo-position' => $this->configuration['logo_position'],
        'data-pp-style-text-size' => $this->configuration['text_size'],
        'data-pp-style-text-color' => $this->configuration['text_color'],
      ];
    }

    return $element;
  }

}
