<?php

namespace Drupal\commerce_paypal;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentGatewayInterface;
use Drupal\commerce_paypal\Plugin\Commerce\PaymentGateway\CheckoutInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;

/**
 * Provides a helper for building the Smart payment buttons.
 */
class SmartPaymentButtonsBuilder implements SmartPaymentButtonsBuilderInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new SmartPaymentButtonsBuilder object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function build(OrderInterface $order, PaymentGatewayInterface $payment_gateway, $commit) {
    $element = [];
    if (!$payment_gateway->getPlugin() instanceof CheckoutInterface) {
      return $element;
    }
    $config = $payment_gateway->getPlugin()->getConfiguration();
    $create_url = Url::fromRoute('commerce_paypal.checkout.create', [
      'commerce_order' => $order->id(),
      'commerce_payment_gateway' => $payment_gateway->id(),
    ]);
    // Note that we're not making use of the payment return route since it
    // cannot be called from the cart page because of the checkout step
    // validation.
    $return_url_options = [
      'query' => [
        'skip_payment_creation' => 1,
      ],
    ];
    $return_url = Url::fromRoute('commerce_paypal.checkout.approve', [
      'commerce_order' => $order->id(),
      'commerce_payment_gateway' => $payment_gateway->id(),
    ], $return_url_options);
    $options = [
      'query' => [
        'client-id' => $config['client_id'],
        'intent' => $config['intent'],
        'commit' => $commit ? 'true' : 'false',
        'currency' => $order->getTotalPrice()->getCurrencyCode(),
      ],
    ];
    // Include the "messages" component, only if credit messaging is configured.
    if ($this->configFactory->get('commerce_paypal.credit_messaging_settings')->get('client_id')) {
      $options['query']['components'] = 'buttons,messages';
    }
    // Enable Venmo funding if it is not disabled.
    if (($key = array_search('venmo', $config['disable_funding'])) === FALSE) {
      $options['query']['enable-funding'] = 'venmo';
      unset($config['disable_funding'][$key]);
    }
    if (!empty($config['disable_funding'])) {
      $options['query']['disable-funding'] = implode(',', $config['disable_funding']);
    }
    if (!empty($config['disable_card'])) {
      $options['query']['disable-card'] = implode(',', $config['disable_card']);
    }
    $element['#attached']['library'][] = 'commerce_paypal/paypal_checkout';
    $element_id = Html::getUniqueId('paypal-buttons-container');
    $element['#attached']['drupalSettings']['paypalCheckout'][$order->id()] = [
      'src' => Url::fromUri('https://www.paypal.com/sdk/js', $options)->toString(),
      'elementId' => $element_id,
      'onCreateUrl' => $create_url->toString(),
      'onApproveUrl' => $return_url->toString(),
      'flow' => $commit ? 'mark' : 'shortcut',
      'style' => $config['style'],
    ];
    $element += [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#weight' => 100,
      '#attributes' => [
        'class' => ['paypal-buttons-container'],
        'id' => $element_id,
      ],
    ];

    return $element;
  }

}
