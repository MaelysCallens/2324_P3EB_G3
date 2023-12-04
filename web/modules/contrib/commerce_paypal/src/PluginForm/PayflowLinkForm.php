<?php

namespace Drupal\commerce_paypal\PluginForm;

use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\Core\Routing\TrustedRedirectResponse;

/**
 * Provides an offsite payment form for Payflow link.
 */
class PayflowLinkForm extends BasePaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $configuration = $this->plugin->getConfiguration();
    $redirect_mode = $configuration['redirect_mode'];

    // Return an error if the gateway's settings haven't been configured.
    foreach (['partner', 'vendor', 'user', 'password'] as $key) {
      if (empty($configuration[$key])) {
        throw new PaymentGatewayException($this->t('Payflow Link is not configured for use. Please contact an administrator to resolve this issue.'));
      }
    }

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    $order = $payment->getOrder();

    $mode = $configuration['mode'];

    $commerce_payflow_data = [
      'tokenid' => str_replace('.', '', uniqid('', TRUE)),
    ];
    $order->setData('commerce_payflow', $commerce_payflow_data);

    // Request a token from Payflow Link.
    $token = $this->plugin->createSecureToken($order);

    // If we got one back...
    if (!empty($token)) {
      // Set the Payflow Link data array and proceed to the redirect page.
      $commerce_payflow_data['token'] = $token;
      $order->setData('commerce_payflow', $commerce_payflow_data);
      $order->save();
      // Determine how to process the redirect based on the payment
      // gateway's settings.
      switch ($configuration['redirect_mode']) {
        // For GET, redirect to Payflow Link with the parameters in the URL.
        case 'get':
          $redirect_url = $this->plugin->getRedirectUrl($order);
          if (empty($redirect_url)) {
            throw new PaymentGatewayException($this->t('Communication with PayPal failed. Please try again or contact an administrator to resolve the issue.'));
          }
          $redirect = new TrustedRedirectResponse($redirect_url);
          $redirect->send();
          break;

        // For POST, render a form that submits to the Payflow Link server.
        case 'post':
          $redirect_url = $this->plugin->getRedirectUrl();
          // Set the form to submit to Payflow Link.
          $form['#action'] = $redirect_url;

          // Add the Secure Token and Secure Token ID from the order's data.
          $data = [
            'SECURETOKEN' => $order->getData('commerce_payflow')['token'],
            'SECURETOKENID' => $order->getData('commerce_payflow')['tokenid'],
          ];

          // If 'test' mode, add the appropriate parameter.
          if ($mode === 'test') {
            $data['MODE'] = 'TEST';
          }

          // Add the parameters as hidden form elements to the form array.
          foreach ($data as $name => $value) {
            if (!empty($value)) {
              $form[$name] = ['#type' => 'hidden', '#value' => $value];
            }
          }
          break;

        case 'iframe':
          $iframe = $this->plugin->createHostedCheckoutIframe($order);
          $form['iframe'] = [
            '#markup' => Markup::create($iframe),
          ];
          if ($configuration['cancel_link']) {
            $cancel_link = Link::createFromRoute($this->t('Cancel payment and go back'), 'commerce_payment.checkout.cancel', [
              'commerce_order' => $order->id(),
              'step' => 'payment',
            ])->toString();
            $form['iframe']['#suffix'] = '<div class="commerce-payflow-cancel">' . $cancel_link . '</div>';
          }
          return $form;
      }
    }
    else {
      // Clear the payment related information.
      $order->unsetData('commerce_payflow');
      $order->save();
      // Show an error message and remain on the current page.
      throw new PaymentGatewayException($this->t('Communication with PayPal failed. Please try again or contact an administrator to resolve the issue.'));
    }

    $form = $this->buildRedirectForm($form, $form_state, $redirect_url, $data, $redirect_mode);

    return $form;
  }

}
