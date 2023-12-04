<?php

namespace Drupal\commerce_recurring_test\Entity;

use Drupal\commerce_payment\Entity\PaymentMethod;

/**
 * Replacement commerce_payment_method entity class that throws an exception.
 *
 * We only need to override getPaymentGateway(), as it is the first call to the
 * entity after it is loaded in RecurringOrderManager::closeOrder().
 */
class ExceptionPaymentMethod extends PaymentMethod {

  /**
   * {@inheritdoc}
   */
  public function getPaymentGateway() {
    // Throw an exception if the test tells us to via the state. We need this
    // switch because startRecurring() causes this method to be called, at
    // which point we want things to behave normally.
    if (\Drupal::state()->get('commerce_recurring_test.payment_method_throw')) {
      throw new \Exception("This payment is failing dramatically!");
    }

    return parent::getPaymentGateway();
  }

}
