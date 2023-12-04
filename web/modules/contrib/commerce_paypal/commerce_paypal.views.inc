<?php

/**
 * @file
 * Provide Views data for Commerce PayPal.
 */

/**
 * Implements hook_views_data().
 */
function commerce_paypal_views_data() {
  $data['views']['commerce_paypal_credit_messaging'] = [
    'title' => t('PayPal Credit messaging'),
    'help' => t('Displays order specific messaging for PayPal Credit; requires an Order ID argument.'),
    'area' => [
      'id' => 'commerce_paypal_credit_messaging',
    ],
  ];
  return $data;
}
