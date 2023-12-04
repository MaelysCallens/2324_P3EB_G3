<?php

namespace Drupal\commerce_paypal\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a configuration form for PayPal credit messaging.
 */
class CreditMessagingForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'commerce_paypal.credit_messaging_settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_paypal_credit_messaging_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('commerce_paypal.credit_messaging_settings');

    $form['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PayPal Client ID'),
      '#description' => $this->t('You must supply a PayPal client ID for messaging to appear where you have enabled it.'),
      '#default_value' => $config->get('client_id'),
      '#required' => FALSE,
    ];

    $form['add_to_cart'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable PayPal Credit messaging on Add to Cart forms.'),
      '#default_value' => $config->get('add_to_cart'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('commerce_paypal.credit_messaging_settings')
      ->set('client_id', $form_state->getValue('client_id'))
      ->set('add_to_cart', $form_state->getValue('add_to_cart'))
      ->save();
  }

}
