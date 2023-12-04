<?php

namespace Drupal\commerce_recurring;

/**
 * Provides the interface for the Recurring module's cron.
 *
 * Queues ended recurring orders for closing/renewal and pending/trial
 * subscriptions for activation.
 */
interface CronInterface {

  /**
   * Runs the cron.
   */
  public function run();

}
