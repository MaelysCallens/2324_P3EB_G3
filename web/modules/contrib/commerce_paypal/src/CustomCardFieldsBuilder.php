<?php

namespace Drupal\commerce_paypal;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentGatewayInterface;
use Drupal\commerce_paypal\Plugin\Commerce\PaymentGateway\CheckoutInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use GuzzleHttp\Exception\ClientException;
use Psr\Log\LoggerInterface;

/**
 * Provides a helper for building the PayPal custom card fields form.
 */
class CustomCardFieldsBuilder implements CustomCardFieldsBuilderInterface {

  /**
   * The PayPal Checkout SDK factory.
   *
   * @var \Drupal\commerce_paypal\CheckoutSdkFactoryInterface
   */
  protected $checkoutSdkFactory;

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a new CustomCardFieldsBuilder object.
   *
   * @param \Drupal\commerce_paypal\CheckoutSdkFactoryInterface $checkout_sdk_factory
   *   The PayPal Checkout SDK factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel.
   */
  public function __construct(CheckoutSdkFactoryInterface $checkout_sdk_factory, LoggerInterface $logger) {
    $this->checkoutSdkFactory = $checkout_sdk_factory;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function build(OrderInterface $order, PaymentGatewayInterface $payment_gateway) {
    $element = [];
    if (!$payment_gateway->getPlugin() instanceof CheckoutInterface) {
      return $element;
    }
    $config = $payment_gateway->getPlugin()->getConfiguration();
    $sdk = $this->checkoutSdkFactory->get($config);
    try {
      $response = $sdk->getClientToken();
      $body = Json::decode($response->getBody()->getContents());
      $client_token = $body['client_token'];
    }
    catch (ClientException $exception) {
      $this->logger->error($exception->getMessage());
      return $element;
    }
    $create_url = Url::fromRoute('commerce_paypal.checkout.create', [
      'commerce_payment_gateway' => $payment_gateway->id(),
      'commerce_order' => $order->id(),
    ]);
    $options = [
      'query' => [
        'components' => 'hosted-fields',
        'client-id' => $config['client_id'],
        'intent' => $config['intent'],
        'currency' => $order->getTotalPrice()->getCurrencyCode(),
      ],
    ];
    $element['#attached']['library'][] = 'commerce_paypal/paypal_checkout_custom_card_fields';
    $element['#attached']['drupalSettings']['paypalCheckout'] = [
      'src' => Url::fromUri('https://www.paypal.com/sdk/js', $options)->toString(),
      'onCreateUrl' => $create_url->toString(),
      'clientToken' => $client_token,
      'cardFieldsSelector' => '#commerce-paypal-checkout-custom-card-fields',
    ];

    // Display credit card logos in checkout form.
    if ($config['enable_credit_card_icons'] && $config['payment_solution'] === 'custom_card_fields') {
      $element['#attached']['library'][] = 'commerce_paypal/credit_card_icons';
      $element['#attached']['library'][] = 'commerce_payment/payment_method_icons';

      $supported_credit_cards = [];
      foreach ($payment_gateway->getPlugin()->getCreditCardTypes() as $credit_card) {
        $supported_credit_cards[] = $credit_card->getId();
      }

      $element += [
        'credit_card_logos' => [
          '#theme' => 'commerce_paypal_credit_card_logos',
          '#credit_cards' => $supported_credit_cards,
        ],
      ];
    }

    $element += [
      'card_fields_form' => [
        '#theme' => 'commerce_paypal_checkout_custom_card_fields',
        '#weight' => 0,
        '#intent' => $config['intent'],
      ],
      'paypal_remote_id' => [
        '#type' => 'hidden',
      ],
    ];
    return $element;
  }

}
