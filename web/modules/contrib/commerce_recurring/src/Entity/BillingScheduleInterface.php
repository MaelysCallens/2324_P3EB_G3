<?php

namespace Drupal\commerce_recurring\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;

/**
 * Defines the interface for billing schedules.
 *
 * This configuration entity stores configuration for billing schedule plugins.
 */
interface BillingScheduleInterface extends ConfigEntityInterface, EntityWithPluginCollectionInterface {

  /**
   * Available billing types.
   */
  const BILLING_TYPE_PREPAID = 'prepaid';
  const BILLING_TYPE_POSTPAID = 'postpaid';

  /**
   * Gets the display label.
   *
   * This label is customer-facing.
   *
   * @return string
   *   The display label.
   */
  public function getDisplayLabel();

  /**
   * Sets the display label.
   *
   * @param string $display_label
   *   The display label.
   *
   * @return $this
   */
  public function setDisplayLabel($display_label);

  /**
   * Gets the billing type.
   *
   * The billing type can be either:
   * - Prepaid: Subscription is paid at the beginning of the period.
   * - Postpaid: Subscription is paid at the end of the period.
   *
   * @return string
   *   The billing type, one of the BILLING_TYPE_ constants.
   */
  public function getBillingType();

  /**
   * Sets the billing type.
   *
   * @param string $billing_type
   *   The billing type.
   *
   * @return $this
   */
  public function setBillingType($billing_type);

  /**
   * Gets the retry schedule.
   *
   * Controls the dunning process that starts after a declined payment.
   * For example, [1, 3, 5] means that a recurring order's payment will be
   * retried 3 times, with 1, 3, and 5 days between retries.
   *
   * @return array
   *   The retry schedule.
   */
  public function getRetrySchedule();

  /**
   * Sets the retry schedule.
   *
   * @param array $schedule
   *   The retry schedule.
   *
   * @return $this
   */
  public function setRetrySchedule(array $schedule);

  /**
   * Gets the unpaid subscription state.
   *
   * This is the state that the subscription will transition to after the end
   * of the dunning cycle. Common values:
   * - active (indicating that the subscription should stay active)
   * - canceled (indicating that the subscription should be canceled).
   *
   * @return string
   *   The subscription state.
   */
  public function getUnpaidSubscriptionState();

  /**
   * Sets the unpaid subscription state.
   *
   * @param string $state
   *   The subscription state.
   *
   * @return $this
   */
  public function setUnpaidSubscriptionState($state);

  /**
   * Gets the billing schedule plugin ID.
   *
   * @return string
   *   The billing schedule plugin ID.
   */
  public function getPluginId();

  /**
   * Sets the billing schedule plugin ID.
   *
   * @param string $plugin_id
   *   The billing schedule plugin ID.
   *
   * @return $this
   */
  public function setPluginId($plugin_id);

  /**
   * Gets the billing schedule plugin configuration.
   *
   * @return array
   *   The billing schedule plugin configuration.
   */
  public function getPluginConfiguration();

  /**
   * Sets the billing schedule plugin configuration.
   *
   * @param array $configuration
   *   The billing schedule plugin configuration.
   *
   * @return $this
   */
  public function setPluginConfiguration(array $configuration);

  /**
   * Gets the billing schedule plugin.
   *
   * @return \Drupal\commerce_recurring\Plugin\Commerce\BillingSchedule\BillingScheduleInterface
   *   The billing schedule plugin.
   */
  public function getPlugin();

  /**
   * Gets the prorater plugin ID.
   *
   * @return string
   *   The prorater plugin ID.
   */
  public function getProraterId();

  /**
   * Sets the prorater plugin ID.
   *
   * @param string $prorater_id
   *   The prorater plugin ID.
   *
   * @return $this
   */
  public function setProraterId($prorater_id);

  /**
   * Gets the prorater plugin configuration.
   *
   * @return array
   *   The prorater plugin configuration.
   */
  public function getProraterConfiguration();

  /**
   * Sets the prorater plugin configuration.
   *
   * @param array $configuration
   *   The prorater plugin configuration.
   *
   * @return $this
   */
  public function setProraterConfiguration(array $configuration);

  /**
   * Gets the prorater plugin.
   *
   * @return \Drupal\commerce_recurring\Plugin\Commerce\Prorater\ProraterInterface
   *   The prorater plugin.
   */
  public function getProrater();

  /**
   * Checks whether the billing schedule allows combining subscriptions.
   *
   * @return bool
   *   TRUE if the billing schedule allows combining subscriptions into a single
   *   order sharing the same billing schedule/period, FALSE otherwise.
   */
  public function allowCombiningSubscriptions();

  /**
   * Sets whether the billing schedule allows combining subscriptions.
   *
   * @param bool $combine
   *   Whether the billing schedule allows combining subscriptions.
   *
   * @return $this
   */
  public function setCombineSubscriptions($combine);

}
