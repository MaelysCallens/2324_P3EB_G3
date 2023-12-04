<?php

namespace Drupal\commerce_recurring\Plugin\Commerce\BillingSchedule;

use Drupal\commerce_recurring\BillingPeriod;
use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Provides the interface for billing schedules.
 *
 * Responsible for generating billing periods, used to determine when the
 * customer should be charged.
 *
 * @see \Drupal\commerce_recurring\BillingPeriod
 */
interface BillingScheduleInterface extends ConfigurableInterface, DependentPluginInterface, PluginFormInterface, PluginInspectionInterface {

  /**
   * Gets the billing schedule label.
   *
   * @return string
   *   The billing schedule label.
   */
  public function getLabel();

  /**
   * Checks whether the billing schedule allows trials.
   *
   * @return bool
   *   TRUE if the billing schedule allows trials, FALSE otherwise.
   */
  public function allowTrials();

  /**
   * Generates the trial period.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $start_date
   *   The trial start date/time.
   *
   * @return \Drupal\commerce_recurring\BillingPeriod
   *   The trial period.
   */
  public function generateTrialPeriod(DrupalDateTime $start_date);

  /**
   * Generates the first billing period.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $start_date
   *   The billing start date/time.
   *
   * @return \Drupal\commerce_recurring\BillingPeriod
   *   The billing period.
   */
  public function generateFirstBillingPeriod(DrupalDateTime $start_date);

  /**
   * Generates the next billing period.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $start_date
   *   The billing start date/time.
   * @param \Drupal\commerce_recurring\BillingPeriod $billing_period
   *   The current billing period.
   *
   * @return \Drupal\commerce_recurring\BillingPeriod
   *   The billing period.
   */
  public function generateNextBillingPeriod(DrupalDateTime $start_date, BillingPeriod $billing_period);

}
