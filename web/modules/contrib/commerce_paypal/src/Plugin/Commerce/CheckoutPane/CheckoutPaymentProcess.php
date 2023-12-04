<?php

namespace Drupal\commerce_paypal\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_payment\Plugin\Commerce\CheckoutPane\PaymentProcess;
use Drupal\commerce_paypal\Plugin\Commerce\PaymentGateway\CheckoutInterface;

/**
 * Provides the PayPal Checkout payment process pane.
 *
 * This extends the default "payment_process" pane which cannot work if the
 * "payment_information" pane isn't visible. This is only required in the
 * "shortcut" flow which is used in combination with the "paypal_checkout"
 * Checkout flow this module provides.
 *
 * @CommerceCheckoutPane(
 *   id = "paypal_checkout_payment_process",
 *   label = @Translation("PayPal Checkout payment process"),
 *   default_step = "payment"
 * )
 */
class CheckoutPaymentProcess extends PaymentProcess {

  /**
   * {@inheritdoc}
   */
  public function isVisible() {
    if ($this->order->isPaid() ||
      !$this->order->getTotalPrice() ||
      $this->order->getTotalPrice()->isZero()) {
      // No payment is needed if the order is free or has already been paid.
      return FALSE;
    }
    if ($this->checkoutFlow->getPluginId() !== 'paypal_checkout' ||
      empty($this->order->getData('commerce_paypal_checkout')) ||
      $this->order->get('payment_gateway')->isEmpty()) {
      return FALSE;
    }
    $checkout_data = $this->order->getData('commerce_paypal_checkout');
    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
    $payment_gateway = $this->order->get('payment_gateway')->entity;
    return $checkout_data['flow'] === 'shortcut' && $payment_gateway->getPlugin() instanceof CheckoutInterface;
  }

  /**
   * Gets the step ID that the customer should be sent to on error.
   *
   * @return string
   *   The error step ID.
   */
  protected function getErrorStepId() {
    $visible_steps = $this->checkoutFlow->getVisibleSteps();
    return key($visible_steps);
  }

}
