<?php

/**
 * @file
 * Post update functions for Commerce PayPal.
 */

use Drupal\commerce_payment\Entity\PaymentGatewayInterface;
use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Import the PayPal Checkout flow config.
 */
function commerce_paypal_post_update_1() {
  /** @var \Drupal\commerce\Config\ConfigUpdaterInterface $config_updater */
  $config_updater = \Drupal::service('commerce.config_updater');
  $message = '';

  $config_names = [
    'commerce_checkout.commerce_checkout_flow.paypal_checkout',
  ];
  $result = $config_updater->import($config_names);

  $success_results = $result->getSucceeded();
  $failure_results = $result->getFailed();
  if ($success_results) {
    $message = t('Succeeded:') . '<br>';
    foreach ($success_results as $success_message) {
      $message .= $success_message . '<br>';
    }
    $message .= '<br>';
  }
  if ($failure_results) {
    $message .= t('Failed:') . '<br>';
    foreach ($failure_results as $failure_message) {
      $message .= $failure_message . '<br>';
    }
  }

  return $message;
}

/**
 * Delete the PayPal Checkout payment methods.
 */
function commerce_paypal_post_update_2(&$sandbox = NULL) {
  $payment_method_storage = \Drupal::entityTypeManager()->getStorage('commerce_payment_method');
  if (!isset($sandbox['current_count'])) {
    $query = $payment_method_storage->getQuery();
    $query->condition('type', 'paypal_checkout');
    $sandbox['total_count'] = $query->count()->execute();
    $sandbox['current_count'] = 0;

    if (empty($sandbox['total_count'])) {
      $sandbox['#finished'] = 1;
      return;
    }
  }
  $query = $payment_method_storage->getQuery();
  $query->condition('type', 'paypal_checkout');
  $query->range(0, 20);
  $result = $query->execute();
  if (empty($result)) {
    $sandbox['#finished'] = 1;
    return;
  }
  /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface[] $payment_methods */
  $payment_methods = $payment_method_storage->loadMultiple($result);
  $order_storage = \Drupal::entityTypeManager()->getStorage('commerce_order');
  foreach ($payment_methods as $payment_method) {
    $query = $order_storage->getQuery();
    $query->condition('payment_method', $payment_method->id());
    $query->accessCheck(FALSE);
    $result = $query->execute();
    if (!$result) {
      continue;
    }
    $orders = $order_storage->loadMultiple($result);
    /** @var \Drupal\commerce_order\Entity\OrderInterface[] $orders */
    foreach ($orders as $order) {
      // Remove the reference to the payment method we're about to delete and
      // migrate the Payment method data to the order's data.
      $order->set('payment_method', NULL);
      $order->setRefreshState(OrderInterface::REFRESH_SKIP);
      if (!empty($payment_method->getRemoteId())) {
        $paypal_checkout_data = [
          'remote_id' => $payment_method->getRemoteId(),
        ];
        if ($payment_method->hasField('flow') && !$payment_method->get('flow')->isEmpty()) {
          $paypal_checkout_data['flow'] = $payment_method->get('flow')->value;
        }
        $order->setData('commerce_paypal_checkout', $paypal_checkout_data);
      }
      $order->save();
    }
  }
  $sandbox['current_count'] += count($payment_methods);
  $payment_method_storage->delete($payment_methods);
  if ($sandbox['current_count'] >= $sandbox['total_count']) {
    $sandbox['#finished'] = 1;
  }
  else {
    $sandbox['#finished'] = ($sandbox['total_count'] - $sandbox['current_count']) / $sandbox['total_count'];
  }
}

/**
 * Empty post update function.
 */
function commerce_paypal_post_update_3() {
  // This used to contain logic to uninstall the "paypal_checkout" payment
  // method type which is no longer relevant and could cause issues when
  // upgrading from previous beta versions.
}

/**
 * Uninstall the flow field.
 */
function commerce_paypal_post_update_4() {
  $original_storage_definitions = \Drupal::service('entity.last_installed_schema.repository')->getLastInstalledFieldStorageDefinitions('commerce_payment_method');
  if (isset($original_storage_definitions['flow'])) {
    \Drupal::service('field_definition.listener')->onFieldDefinitionDelete($original_storage_definitions['flow']);
    \Drupal::service('field_storage_definition.listener')->onFieldStorageDefinitionDelete($original_storage_definitions['flow']);
  }
}

/**
 * Set the "payment_method_types" to "paypal_checkout".
 */
function commerce_paypal_post_update_5(&$sandbox) {
  $entity_type_manager = \Drupal::entityTypeManager();
  $payment_gateway_storage = $entity_type_manager->getStorage('commerce_payment_gateway');
  /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface[] $gateways */
  $payment_gateways = array_filter($payment_gateway_storage->loadMultiple(), function (PaymentGatewayInterface $gateway) {
    return $gateway->getPluginId() === 'paypal_checkout';
  });

  /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
  foreach ($payment_gateways as $payment_gateway) {
    $configuration = $payment_gateway->getPluginConfiguration();
    $configuration['payment_method_types'] = ['paypal_checkout'];
    $payment_gateway->setPluginConfiguration($configuration);
    $payment_gateway->save();
  }
}
