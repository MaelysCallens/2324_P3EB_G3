<?php

namespace Drupal\commerce_recurring\Plugin\Commerce\BillingSchedule;

use Drupal\commerce\Interval;
use Drupal\commerce_recurring\BillingPeriod;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base class for interval-based billing schedules.
 */
abstract class IntervalBase extends BillingScheduleBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'trial_interval' => [],
      'interval' => [
        'number' => 1,
        'unit' => 'month',
      ],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['#attached']['library'][] = 'commerce_recurring/admin';

    $form['trial_interval'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['interval'],
      ],
      '#open' => TRUE,
    ];
    $form['trial_interval']['allow_trials'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow free trials'),
      '#default_value' => !empty($this->configuration['trial_interval']),
    ];
    // Default the trial interval to the interval for easier configuration.
    if (empty($this->configuration['trial_interval'])) {
      $this->configuration['trial_interval'] = $this->configuration['interval'];
    }

    $form['trial_interval']['number'] = [
      '#type' => 'number',
      '#title' => $this->t('Trial interval'),
      '#default_value' => $this->configuration['trial_interval']['number'],
      '#min' => 1,
      '#states' => [
        'visible' => [
          ':input[name="configuration[' . $this->pluginId . '][trial_interval][allow_trials]"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];
    $form['trial_interval']['unit'] = [
      '#type' => 'select',
      '#title' => $this->t('Unit'),
      '#title_display' => 'invisible',
      '#options' => [
        'hour' => $this->t('Hour'),
        'day' => $this->t('Day'),
        'week' => $this->t('Week'),
        'month' => $this->t('Month'),
        'year' => $this->t('Year'),
      ],
      '#default_value' => $this->configuration['trial_interval']['unit'],
      '#states' => [
        'visible' => [
          ':input[name="configuration[' . $this->pluginId . '][trial_interval][allow_trials]"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    $form['interval'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['interval'],
      ],
      '#open' => TRUE,
    ];
    $form['interval']['number'] = [
      '#type' => 'number',
      '#title' => $this->t('Interval'),
      '#default_value' => $this->configuration['interval']['number'],
      '#min' => 1,
      '#required' => TRUE,
    ];
    $form['interval']['unit'] = [
      '#type' => 'select',
      '#title' => $this->t('Unit'),
      '#title_display' => 'invisible',
      '#options' => [
        'hour' => $this->t('Hour'),
        'day' => $this->t('Day'),
        'week' => $this->t('Week'),
        'month' => $this->t('Month'),
        'year' => $this->t('Year'),
      ],
      '#default_value' => $this->configuration['interval']['unit'],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);

      $this->configuration = [];
      $this->configuration['trial_interval'] = [];
      if (!empty($values['trial_interval']['allow_trials'])) {
        $this->configuration['trial_interval'] = [
          'number' => $values['trial_interval']['number'],
          'unit' => $values['trial_interval']['unit'],
        ];
      }
      $this->configuration['interval'] = $values['interval'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function allowTrials() {
    return !empty($this->configuration['trial_interval']);
  }

  /**
   * {@inheritdoc}
   */
  public function generateTrialPeriod(DrupalDateTime $start_date) {
    // Trial periods are always rolling (starting from the given start date).
    $interval = new Interval($this->configuration['trial_interval']['number'], $this->configuration['trial_interval']['unit']);
    return new BillingPeriod($start_date, $interval->add($start_date));
  }

  /**
   * Gets the current interval.
   *
   * @return \Drupal\commerce\Interval
   *   The interval.
   */
  protected function getInterval() {
    return new Interval($this->configuration['interval']['number'], $this->configuration['interval']['unit']);
  }

}
