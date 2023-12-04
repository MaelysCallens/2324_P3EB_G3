<?php

namespace Drupal\commerce_paypal;

use Drupal\address\AddressInterface;
use Drupal\commerce_order\AdjustmentTransformerInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_paypal\Event\CheckoutOrderRequestEvent;
use Drupal\commerce_paypal\Event\PayPalEvents;
use Drupal\commerce_price\Calculator;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides a replacement of the PayPal SDK.
 */
class CheckoutSdk implements CheckoutSdkInterface {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * The adjustment transformer.
   *
   * @var \Drupal\commerce_order\AdjustmentTransformerInterface
   */
  protected $adjustmentTransformer;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The payment gateway plugin configuration.
   *
   * @var array
   */
  protected $config;

  /**
   * Constructs a new CheckoutSdk object.
   *
   * @param \GuzzleHttp\ClientInterface $client
   *   The client.
   * @param \Drupal\commerce_order\AdjustmentTransformerInterface $adjustment_transformer
   *   The adjustment transformer.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time.
   * @param array $config
   *   The payment gateway plugin configuration array.
   */
  public function __construct(ClientInterface $client, AdjustmentTransformerInterface $adjustment_transformer, EventDispatcherInterface $event_dispatcher, ModuleHandlerInterface $module_handler, TimeInterface $time, array $config) {
    $this->client = $client;
    $this->adjustmentTransformer = $adjustment_transformer;
    $this->eventDispatcher = $event_dispatcher;
    $this->moduleHandler = $module_handler;
    $this->time = $time;
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessToken() {
    return $this->client->post('/v1/oauth2/token', [
      'auth' => [$this->config['client_id'], $this->config['secret']],
      'form_params' => [
        'grant_type' => 'client_credentials',
      ],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getClientToken() {
    return $this->client->post('/v1/identity/generate-token', [
      'headers' => [
        'Content-Type' => 'application/json',
      ],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function createOrder(OrderInterface $order, AddressInterface $billing_address = NULL) {
    $params = $this->prepareOrderRequest($order, $billing_address);
    $event = new CheckoutOrderRequestEvent($order, $params);
    $this->eventDispatcher->dispatch($event, PayPalEvents::CHECKOUT_CREATE_ORDER_REQUEST);
    return $this->client->post('/v2/checkout/orders', ['json' => $event->getRequestBody()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getOrder($remote_id) {
    return $this->client->get(sprintf('/v2/checkout/orders/%s', $remote_id));
  }

  /**
   * {@inheritdoc}
   */
  public function updateOrder($remote_id, OrderInterface $order) {
    $params = $this->prepareOrderRequest($order);
    $update_params = [
      [
        'op' => 'replace',
        'path' => "/purchase_units/@reference_id=='default'",
        'value' => $params['purchase_units'][0],
      ],
    ];
    $event = new CheckoutOrderRequestEvent($order, $update_params);
    $this->eventDispatcher->dispatch($event, PayPalEvents::CHECKOUT_UPDATE_ORDER_REQUEST);
    return $this->client->patch(sprintf('/v2/checkout/orders/%s', $remote_id), ['json' => $event->getRequestBody()]);
  }

  /**
   * {@inheritdoc}
   */
  public function authorizeOrder($remote_id) {
    $headers = [
      'Content-Type' => 'application/json',
    ];
    return $this->client->post(sprintf('/v2/checkout/orders/%s/authorize', $remote_id), ['headers' => $headers]);
  }

  /**
   * {@inheritdoc}
   */
  public function captureOrder($remote_id) {
    $headers = [
      'Content-Type' => 'application/json',
    ];
    return $this->client->post(sprintf('/v2/checkout/orders/%s/capture', $remote_id), ['headers' => $headers]);
  }

  /**
   * {@inheritdoc}
   */
  public function capturePayment($authorization_id, array $parameters = []) {
    $options = [
      'headers' => [
        'Content-Type' => 'application/json',
      ],
    ];
    if ($parameters) {
      $options['json'] = $parameters;
    }
    return $this->client->post(sprintf('/v2/payments/authorizations/%s/capture', $authorization_id), $options);
  }

  /**
   * {@inheritdoc}
   */
  public function reAuthorizePayment($authorization_id, array $parameters = []) {
    $options = [
      'headers' => [
        'Content-Type' => 'application/json',
      ],
    ];
    if ($parameters) {
      $options['json'] = $parameters;
    }
    return $this->client->post(sprintf('/v2/payments/authorizations/%s/reauthorize', $authorization_id), $options);
  }

  /**
   * {@inheritdoc}
   */
  public function refundPayment($capture_id, array $parameters = []) {
    $options = [
      'headers' => [
        'Content-Type' => 'application/json',
      ],
    ];
    if ($parameters) {
      $options['json'] = $parameters;
    }
    return $this->client->post(sprintf('/v2/payments/captures/%s/refund', $capture_id), $options);
  }

  /**
   * {@inheritdoc}
   */
  public function voidPayment($authorization_id, array $parameters = []) {
    $options = [
      'headers' => [
        'Content-Type' => 'application/json',
      ],
    ];
    return $this->client->post(sprintf('/v2/payments/authorizations/%s/void', $authorization_id), $options);
  }

  /**
   * {@inheritdoc}
   */
  public function verifyWebhookSignature(array $parameters) {
    $required_keys = [
      'auth_algo',
      'cert_url',
      'transmission_id',
      'transmission_sig',
      'transmission_time',
      'webhook_id',
      'webhook_event',
    ];
    foreach ($required_keys as $required_key) {
      if (empty($parameters[$required_key])) {
        throw new \InvalidArgumentException(sprintf('Missing required parameter key "%s".', $required_key));
      }
    }
    return $this->client->post('/v1/notifications/verify-webhook-signature', ['json' => $parameters]);
  }

  /**
   * Prepare the order request parameters.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param \Drupal\address\AddressInterface $billing_address
   *   (optional) A billing address to pass to PayPal as the payer information.
   *   This is used in checkout to pass the entered address that is not yet
   *   submitted and associated to the order.
   *
   * @return array
   *   An array suitable for use in the create|update order API calls.
   */
  protected function prepareOrderRequest(OrderInterface $order, AddressInterface $billing_address = NULL) {
    $items = [];
    $item_total = NULL;
    foreach ($order->getItems() as $order_item) {
      $item_total = $item_total ? $item_total->add($order_item->getTotalPrice()) : $order_item->getTotalPrice();
      $item = [
        'name' => mb_substr($order_item->getTitle(), 0, 127),
        'unit_amount' => [
          'currency_code' => $order_item->getUnitPrice()->getCurrencyCode(),
          'value' => Calculator::trim($order_item->getUnitPrice()->getNumber()),
        ],
        'quantity' => intval($order_item->getQuantity()),
      ];

      $purchased_entity = $order_item->getPurchasedEntity();
      if ($purchased_entity instanceof ProductVariationInterface) {
        $item['sku'] = mb_substr($purchased_entity->getSku(), 0, 127);
      }
      $items[] = $item;
    }

    $skipped_adjustment_types = [
      'tax',
      'shipping',
      'promotion',
      'commerce_giftcard',
      'shipping_promotion',
    ];
    // Now, pass adjustments that are not "supported" by PayPal such as fees
    // and "custom" adjustments.
    // We could pass fees under "handling", but we can't make that assumption.
    $adjustments = $order->collectAdjustments();
    $adjustments = $this->adjustmentTransformer->processAdjustments($adjustments);
    foreach ($adjustments as $adjustment) {
      // Skip included adjustments and the adjustment types we're handling
      // below such as "shipping" and "tax".
      if ($adjustment->isIncluded() ||
        in_array($adjustment->getType(), $skipped_adjustment_types, TRUE)) {
        continue;
      }
      $item_total = $item_total ? $item_total->add($adjustment->getAmount()) : $adjustment->getAmount();
      $items[] = [
        'name' => mb_substr($adjustment->getLabel(), 0, 127),
        'unit_amount' => [
          'currency_code' => $adjustment->getAmount()->getCurrencyCode(),
          'value' => Calculator::trim($adjustment->getAmount()->getNumber()),
        ],
        'quantity' => 1,
      ];
    }

    $breakdown = [
      'item_total' => [
        'currency_code' => $item_total->getCurrencyCode(),
        'value' => Calculator::trim($item_total->getNumber()),
      ],
    ];

    $tax_total = $this->getAdjustmentsTotal($adjustments, ['tax']);
    if (!empty($tax_total)) {
      $breakdown['tax_total'] = [
        'currency_code' => $tax_total->getCurrencyCode(),
        'value' => Calculator::trim($tax_total->getNumber()),
      ];
    }

    $shipping_total = $this->getAdjustmentsTotal($adjustments, ['shipping']);
    if (!empty($shipping_total)) {
      $breakdown['shipping'] = [
        'currency_code' => $shipping_total->getCurrencyCode(),
        'value' => Calculator::trim($shipping_total->getNumber()),
      ];
    }

    $promotion_total = $this->getAdjustmentsTotal($adjustments, ['promotion', 'commerce_giftcard', 'shipping_promotion']);
    if (!empty($promotion_total)) {
      $breakdown['discount'] = [
        'currency_code' => $promotion_total->getCurrencyCode(),
        'value' => Calculator::trim($promotion_total->multiply(-1)->getNumber()),
      ];
    }

    // If an order was partially paid, add paid amount as discount.
    if ($order->getTotalPrice()->greaterThan($order->getBalance())) {
      $discount_total = $order->getTotalPrice()->subtract($order->getBalance());
      if (!empty($promotion_total)) {
        $discount_total = $discount_total->add($promotion_total->multiply(-1));
      }
      $breakdown['discount'] = [
        'currency_code' => $discount_total->getCurrencyCode(),
        'value' => Calculator::trim($discount_total->getNumber()),
      ];
    }

    $payer = [];

    if (!empty($order->getEmail())) {
      $payer['email_address'] = $order->getEmail();
    }

    $profiles = $order->collectProfiles();
    if (!empty($billing_address)) {
      $payer += static::formatAddress($billing_address);
    }
    elseif (isset($profiles['billing'])) {
      /** @var \Drupal\address\AddressInterface $address */
      $address = $profiles['billing']->address->first();
      if (!empty($address)) {
        $payer += static::formatAddress($address);
      }
    }
    $params = [
      'intent' => strtoupper($this->config['intent']),
      'purchase_units' => [
        [
          'reference_id' => 'default',
          'custom_id' => $order->id(),
          'invoice_id' => $order->id() . '-' . $this->time->getRequestTime(),
          'amount' => [
            'currency_code' => $order->getBalance()->getCurrencyCode(),
            'value' => Calculator::trim($order->getBalance()->getNumber()),
            'breakdown' => $breakdown,
          ],
          'items' => $items,
        ],
      ],
      'application_context' => [
        'brand_name' => mb_substr($order->getStore()->label() ?? '', 0, 127),
      ],
    ];

    $shipping_address = [];
    if (isset($profiles['shipping'])) {
      /** @var \Drupal\address\AddressInterface $address */
      $address = $profiles['shipping']->address->first();
      if (!empty($address)) {
        $shipping_address = static::formatAddress($address, 'shipping');
      }
    }
    $shipping_preference = $this->config['shipping_preference'];

    // The shipping module isn't enabled, override the shipping preference
    // configured.
    if (!$this->moduleHandler->moduleExists('commerce_shipping')) {
      $shipping_preference = 'no_shipping';
    }
    else {
      // If no shipping address was already collected, override the shipping
      // preference to "GET_FROM_FILE" so that the shipping address is collected
      // on the PayPal site.
      if ($shipping_preference == 'set_provided_address' && !$shipping_address) {
        $shipping_preference = 'get_from_file';
      }
    }

    // No need to pass a shipping_address if the shipping address collection
    // is configured to "no_shipping".
    if ($shipping_address && $shipping_preference !== 'no_shipping') {
      $params['purchase_units'][0]['shipping'] = $shipping_address;
    }
    $params['application_context']['shipping_preference'] = strtoupper($shipping_preference);

    if ($payer) {
      $params['payer'] = $payer;
    }

    return $params;
  }

  /**
   * Get the total for the given adjustments.
   *
   * @param \Drupal\commerce_order\Adjustment[] $adjustments
   *   The adjustments.
   * @param string[] $adjustment_types
   *   The adjustment types to include in the calculation.
   *   Examples: fee, promotion, tax. Defaults to all adjustment types.
   *
   * @return \Drupal\commerce_price\Price|null
   *   The adjustments total, or NULL if no matching adjustments were found.
   */
  protected function getAdjustmentsTotal(array $adjustments, array $adjustment_types = []) {
    $adjustments_total = NULL;
    $matching_adjustments = [];

    foreach ($adjustments as $adjustment) {
      if ($adjustment_types && !in_array($adjustment->getType(), $adjustment_types)) {
        continue;
      }
      if ($adjustment->isIncluded()) {
        continue;
      }
      $matching_adjustments[] = $adjustment;
    }
    if ($matching_adjustments) {
      $matching_adjustments = $this->adjustmentTransformer->processAdjustments($matching_adjustments);
      foreach ($matching_adjustments as $adjustment) {
        $adjustments_total = $adjustments_total ? $adjustments_total->add($adjustment->getAmount()) : $adjustment->getAmount();
      }
    }

    return $adjustments_total;
  }

  /**
   * Formats the given address into a format expected by PayPal.
   *
   * @param \Drupal\address\AddressInterface $address
   *   The address to format.
   * @param string $type
   *   The address type ("billing"|"shipping").
   *
   * @return array
   *   The formatted address.
   */
  public static function formatAddress(AddressInterface $address, $type = 'billing') {
    $return = [
      'address' => [
        'address_line_1' => $address->getAddressLine1(),
        'address_line_2' => $address->getAddressLine2(),
        'admin_area_2' => mb_substr($address->getLocality() ?? '', 0, 120),
        'admin_area_1' => $address->getAdministrativeArea(),
        'postal_code' => mb_substr($address->getPostalCode() ?? '', 0, 60),
        'country_code' => $address->getCountryCode(),
      ],
    ];
    if ($type == 'billing') {
      $return['name'] = [
        'given_name' => $address->getGivenName(),
        'surname' => $address->getFamilyName(),
      ];
    }
    elseif ($type == 'shipping') {
      $return['name'] = [
        'full_name' => mb_substr($address->getGivenName() . ' ' . $address->getFamilyName(), 0, 300),
      ];
    }
    return $return;
  }

}
