<?php

namespace Drupal\commerce_paypal\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\CreditCard;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Exception\HardDeclineException;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PaymentMethodStorageInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_price\Calculator;
use Drupal\commerce_price\Price;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\profile\Entity\ProfileInterface;
use GuzzleHttp\Exception\BadResponseException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides the PayPal Checkout payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "paypal_checkout",
 *   label = @Translation("PayPal Checkout (Preferred)"),
 *   display_label = @Translation("PayPal"),
 *   modes = {
 *     "test" = @Translation("Sandbox"),
 *     "live" = @Translation("Live"),
 *   },
 *   forms = {
 *     "add-payment-method" = "Drupal\commerce_paypal\PluginForm\Checkout\PaymentMethodAddForm",
 *     "offsite-payment" = "Drupal\commerce_paypal\PluginForm\Checkout\PaymentOffsiteForm",
 *   },
 *   credit_card_types = {
 *     "amex", "dinersclub", "discover", "jcb", "maestro", "mastercard", "visa", "unionpay"
 *   },
 *   payment_method_types = {"paypal_checkout"},
 *   payment_type = "paypal_checkout",
 *   requires_billing_information = FALSE,
 * )
 *
 * @see https://developer.paypal.com/docs/business/checkout/
 */
class Checkout extends OffsitePaymentGatewayBase implements CheckoutInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The PayPal Checkout SDK factory.
   *
   * @var \Drupal\commerce_paypal\CheckoutSdkFactoryInterface
   */
  protected $checkoutSdkFactory;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->moduleHandler = $container->get('module_handler');
    $instance->checkoutSdkFactory = $container->get('commerce_paypal.checkout_sdk_factory');
    $instance->logger = $container->get('logger.channel.commerce_paypal');
    $instance->state = $container->get('state');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'payment_solution' => 'smart_payment_buttons',
      'client_id' => '',
      'secret' => '',
      'intent' => 'capture',
      'disable_funding' => [],
      'disable_card' => [],
      'shipping_preference' => 'get_from_file',
      'update_billing_profile' => TRUE,
      'update_shipping_profile' => TRUE,
      'style' => [],
      'enable_on_cart' => TRUE,
      'collect_billing_information' => FALSE,
      'webhook_id' => '',
      'enable_credit_card_icons' => TRUE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $documentation_url = Url::fromUri('https://www.drupal.org/node/3042053')->toString();
    $form['mode']['#weight'] = 0;
    $form['payment_solution'] = [
      '#type' => 'select',
      '#title' => $this->t('PayPal Commerce Platform features'),
      '#options' => [
        'smart_payment_buttons' => $this->t('Accept PayPal with Smart Payment Buttons'),
        'custom_card_fields' => $this->t('Accept credit cards'),
      ],
      '#default_value' => $this->configuration['payment_solution'],
      '#weight' => 0,
    ];
    // Some settings are visible only when the "Smart Payment Buttons" payment
    // solution is selected.
    $spb_states = [
      'visible' => [
        ':input[name="configuration[' . $this->pluginId . '][payment_solution]"]' => ['value' => 'smart_payment_buttons'],
      ],
    ];
    $form['credentials'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('API Credentials'),
      '#weight' => 0,
    ];
    $form['credentials']['help'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['form-item'],
      ],
      '#value' => $this->t('Refer to the <a href=":url" target="_blank">module documentation</a> to find your API credentials.', [':url' => $documentation_url]),
    ];
    $form['credentials']['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client ID'),
      '#default_value' => $this->configuration['client_id'],
      '#maxlength' => 255,
      '#required' => TRUE,
      '#parents' => array_merge($form['#parents'], ['client_id']),
    ];
    $form['credentials']['secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret'),
      '#maxlength' => 255,
      '#default_value' => $this->configuration['secret'],
      '#required' => TRUE,
      '#parents' => array_merge($form['#parents'], ['secret']),
    ];
    $form['collect_billing_information']['#field_suffix'] = $this->t('Collect billing information');
    $form['collect_billing_information']['#description'] = $this->t('When disabled, PayPal will collect the billing information instead, in the opened modal.');
    $form['collect_billing_information']['#states'] = $spb_states;
    $form['collect_billing_information']['#title_display'] = 'before';
    $form['collect_billing_information']['#title'] = $this->t('General');
    $form['enable_on_cart'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Smart Payment Buttons on the cart page.'),
      '#default_value' => $this->configuration['enable_on_cart'],
      '#states' => $spb_states,
    ];
    $form['webhook_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Webhook ID'),
      '#description' => $this->t('Required value when using Webhooks, used to verify the webhook signature.'),
      '#default_value' => $this->configuration['webhook_id'],
    ];
    $form['intent'] = [
      '#type' => 'radios',
      '#title' => $this->t('Transaction type'),
      '#options' => [
        'capture' => $this->t("Capture (capture payment immediately after customer's approval)"),
        'authorize' => $this->t('Authorize (requires manual or automated capture after checkout)'),
      ],
      '#description' => $this->t('For more information on capturing a prior authorization, please refer to <a href=":url" target="_blank">Capture an authorization</a>.', [':url' => 'https://docs.drupalcommerce.org/commerce2/user-guide/payments/capture']),
      '#default_value' => $this->configuration['intent'],
    ];
    $form['disable_funding'] = [
      '#title' => $this->t('Disable funding sources'),
      '#description' => $this->t('The disabled funding sources for the transaction. Any funding sources passed do not display with Smart Payment Buttons. By default, funding source eligibility is smartly decided based on a variety of factors.'),
      '#type' => 'checkboxes',
      '#options' => commerce_paypal_get_funding_sources(),
      '#default_value' => $this->configuration['disable_funding'],
      '#states' => $spb_states,
    ];
    $form['disable_card'] = [
      '#title' => $this->t('Disable card types'),
      '#description' => $this->t('The disabled cards for the transaction. Any cards passed do not display with Smart Payment Buttons. By default, card eligibility is smartly decided based on a variety of factors.'),
      '#type' => 'checkboxes',
      '#options' => [
        'visa' => $this->t('Visa'),
        'mastercard' => $this->t('Mastercard'),
        'amex' => $this->t('American Express'),
        'discover' => $this->t('Discover'),
        'jcb' => $this->t('JCB'),
        'elo' => $this->t('Elo'),
        'hiper' => $this->t('Hiper'),
      ],
      '#default_value' => $this->configuration['disable_card'],
    ];
    $shipping_enabled = $this->moduleHandler->moduleExists('commerce_shipping');
    $form['shipping_preference'] = [
      '#type' => 'radios',
      '#title' => $this->t('Shipping address collection'),
      '#options' => [
        'no_shipping' => $this->t('Do not ask for a shipping address at PayPal.'),
        'get_from_file' => $this->t('Ask for a shipping address at PayPal even if the order already has one.'),
        'set_provided_address' => $this->t('Ask for a shipping address at PayPal if the order does not have one yet.'),
      ],
      '#default_value' => $this->configuration['shipping_preference'],
      '#access' => $shipping_enabled,
      '#states' => $spb_states,
    ];
    $form['update_billing_profile'] = [
      '#type' => 'checkbox',
      '#title' => t('Update the billing customer profile with address information the customer enters at PayPal.'),
      '#default_value' => $this->configuration['update_billing_profile'],
      '#states' => $spb_states,
    ];
    $form['update_shipping_profile'] = [
      '#type' => 'checkbox',
      '#title' => t('Update shipping customer profiles with address information the customer enters at PayPal.'),
      '#default_value' => $this->configuration['update_shipping_profile'],
      '#access' => $shipping_enabled,
      '#states' => $spb_states,
    ];
    $form['enable_credit_card_icons'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Credit Card Icons'),
      '#description' => $this->t('Enabling this setting will display credit card icons in the payment section during checkout.'),
      '#default_value' => $this->configuration['enable_credit_card_icons'],
    ];
    $form['customize_buttons'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Smart Payment Buttons style'),
      '#default_value' => !empty($this->configuration['style']),
      '#title_display' => 'before',
      '#field_suffix' => $this->t('Customize view'),
      '#description_display' => 'before',
      '#states' => $spb_states,
    ];
    $form['style'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Settings'),
      '#description' => $this->t('For more information, please visit <a href=":url" target="_blank">customize the PayPal buttons</a>.', [':url' => 'https://developer.paypal.com/docs/checkout/integration-features/customize-button/#layout']),
      '#states' => array_merge_recursive($spb_states, [
        'visible' => [
          ':input[name="configuration[' . $this->pluginId . '][customize_buttons]"]' => ['checked' => TRUE],
        ],
      ]),
    ];
    // Define some default values for the style configuration.
    $this->configuration['style'] += [
      'layout' => 'vertical',
      'color' => 'gold',
      'shape' => 'rect',
      'label' => 'paypal',
      'tagline' => FALSE,
    ];
    $form['style']['layout'] = [
      '#type' => 'select',
      '#title' => $this->t('Layout'),
      '#default_value' => $this->configuration['style']['layout'],
      '#options' => [
        'vertical' => $this->t('Vertical (Recommended)'),
        'horizontal' => $this->t('Horizontal'),
      ],
    ];
    $form['style']['color'] = [
      '#type' => 'select',
      '#title' => $this->t('Color'),
      '#options' => [
        'gold' => $this->t('Gold (Recommended)'),
        'blue' => $this->t('Blue'),
        'silver' => $this->t('Silver'),
      ],
      '#default_value' => $this->configuration['style']['color'],
    ];
    $form['style']['shape'] = [
      '#type' => 'select',
      '#title' => $this->t('Shape'),
      '#options' => [
        'rect' => $this->t('Rect (Default)'),
        'pill' => $this->t('Pill'),
      ],
      '#default_value' => $this->configuration['style']['shape'],
    ];
    $form['style']['label'] = [
      '#type' => 'select',
      '#title' => $this->t('Label'),
      '#options' => [
        'paypal' => $this->t('Displays the PayPal logo (Default)'),
        'checkout' => $this->t('Displays the PayPal Checkout button'),
        'buynow' => $this->t('Displays the PayPal Buy Now button'),
        'pay' => $this->t('Displays the Pay With PayPal button'),
      ],
      '#default_value' => $this->configuration['style']['label'],
    ];
    $form['style']['tagline'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display tagline'),
      '#default_value' => $this->configuration['style']['tagline'],
      '#states' => array_merge_recursive($spb_states, [
        'visible' => [
          ':input[name="configuration[' . $this->pluginId . '][style][layout]"]' => ['value' => 'horizontal'],
        ],
      ]),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    if ($form_state->getErrors()) {
      return;
    }
    $values = $form_state->getValue($form['#parents']);
    if (empty($values['client_id']) || empty($values['secret'])) {
      return;
    }
    $sdk = $this->checkoutSdkFactory->get($values);
    // Make sure we query for a fresh access token.
    $this->state->delete('commerce_paypal.oauth2_token');
    try {
      $sdk->getAccessToken();
      $this->messenger()->addMessage($this->t('Connectivity to PayPal successfully verified.'));
    }
    catch (BadResponseException $exception) {
      $this->messenger()->addError($this->t('Invalid client_id or secret specified.'));
      $form_state->setError($form['credentials']['client_id']);
      $form_state->setError($form['credentials']['secret']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if ($form_state->getErrors()) {
      return;
    }
    $values = $form_state->getValue($form['#parents']);
    $values['disable_funding'] = array_filter($values['disable_funding']);
    $values['disable_card'] = array_filter($values['disable_card']);
    $keys = [
      'payment_solution',
      'client_id',
      'secret',
      'intent',
      'disable_funding',
      'disable_card',
      'shipping_preference',
      'update_billing_profile',
      'update_shipping_profile',
      'enable_on_cart',
      'webhook_id',
      'enable_credit_card_icons',
    ];

    // Only save the style settings if the customize buttons checkbox is checked.
    if (!empty($values['customize_buttons'])) {
      $keys[] = 'style';

      // Can't display the tagline if the layout configured is "vertical".
      if ($values['style']['layout'] === 'vertical') {
        $values['style']['tagline'] = FALSE;
      }
    }

    // When the "card" funding source is disabled, the "disable_card" setting
    // cannot be specified.
    if (isset($values['disable_funding']['card'])) {
      $values['disable_card'] = [];
    }

    foreach ($keys as $key) {
      if (!isset($values[$key])) {
        continue;
      }
      $this->configuration[$key] = $values[$key];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function collectsBillingInformation() {
    // Collecting a billing profile is required when selecting the
    // "PayPal custom card fields" payment solution.
    if ($this->getPaymentSolution() == 'custom_card_fields') {
      return TRUE;
    }
    return parent::collectsBillingInformation();
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentSolution() {
    return $this->configuration['payment_solution'];
  }

  /**
   * {@inheritdoc}
   */
  public function createPayment(PaymentInterface $payment, $capture = TRUE) {
    $payment_method = $payment->getPaymentMethod();
    if (!$payment_method || empty($payment_method->getRemoteId())) {
      throw new PaymentGatewayException('Cannot create the payment without the PayPal order ID.');
    }
    $sdk = $this->checkoutSdkFactory->get($this->configuration);
    $order = $payment->getOrder();
    $checkout_data = $order->getData('commerce_paypal_checkout', [
      'flow' => '',
    ]);
    $remote_id = $payment_method->getRemoteId();

    try {
      // Ensure the PayPal order is up to date and in sync with Drupal.
      $sdk->updateOrder($remote_id, $order);
      $request = $sdk->getOrder($remote_id);
      $paypal_order = Json::decode($request->getBody());
    }
    catch (BadResponseException $exception) {
      throw new PaymentGatewayException($exception->getMessage());
    }
    // When in the "shortcut" flow, the PayPal order status is expected to be
    // "approved".
    if ($checkout_data['flow'] === 'shortcut' && !in_array($paypal_order['status'], ['APPROVED', 'SAVED'])) {
      throw new PaymentGatewayException(sprintf('Wrong remote order status. Expected: "approved"|"saved", Actual: %s.', $paypal_order['status']));
    }
    $intent = $checkout_data['intent'] ?? $this->configuration['intent'];
    try {
      if ($intent == 'capture') {
        $response = $sdk->captureOrder($remote_id);
        $paypal_order = Json::decode($response->getBody()->getContents());
        $remote_payment = $paypal_order['purchase_units'][0]['payments']['captures'][0];
        $payment->setRemoteId($remote_payment['id']);
      }
      else {
        $response = $sdk->authorizeOrder($remote_id);
        $paypal_order = Json::decode($response->getBody()->getContents());
        $remote_payment = $paypal_order['purchase_units'][0]['payments']['authorizations'][0];

        if (isset($remote_payment['expiration_time'])) {
          $expiration = new \DateTime($remote_payment['expiration_time']);
          $payment->setExpiresTime($expiration->getTimestamp());
        }
      }
    }
    catch (BadResponseException $exception) {
      throw new PaymentGatewayException($exception->getMessage());
    }
    $remote_state = strtolower($remote_payment['status']);

    if (in_array($remote_state, ['denied', 'expired', 'declined'])) {
      throw new HardDeclineException(sprintf('Could not %s the payment for order %s. Remote payment state: %s', $intent, $order->id(), $remote_state));
    }
    $state = $this->mapPaymentState($intent, $remote_state);

    // If we couldn't find a state to map to, stop here.
    if (!$state) {
      $this->logger->debug('PayPal remote payment debug: <pre>@remote_payment</pre>', ['@remote_payment' => print_r($remote_payment, TRUE)]);
      throw new PaymentGatewayException(sprintf('The PayPal payment is in a state we cannot handle. Remote state: %s.', $remote_state));
    }

    // Special handling of the "pending" state, if the order is "pending review"
    // we allow the order to go "through" to give a chance to the merchant
    // to accept the payment, in case manual review is needed.
    if ($state === 'pending' && $remote_state === 'pending') {
      $reason = $remote_payment['status_details']['reason'];
      if ($reason === 'PENDING_REVIEW') {
        $state = 'authorization';
      }
      else {
        throw new PaymentGatewayException(sprintf('The PayPal payment is pending. Reason: %s.', $reason));
      }
    }

    $payment_amount = Price::fromArray([
      'number' => $remote_payment['amount']['value'],
      'currency_code' => $remote_payment['amount']['currency_code'],
    ]);
    $payment->setAmount($payment_amount);
    $payment->setState($state);
    $payment->setRemoteId($remote_payment['id']);
    $payment->setRemoteState($remote_state);
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function capturePayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->assertPaymentState($payment, ['authorization']);
    // If not specified, capture the entire amount.
    $amount = $amount ?: $payment->getAmount();
    $remote_id = $payment->getRemoteId();
    $params = [
      'amount' => [
        'value' => Calculator::trim($amount->getNumber()),
        'currency_code' => $amount->getCurrencyCode(),
      ],
    ];

    if ($amount->equals($payment->getAmount())) {
      $params['final_capture'] = TRUE;
    }

    try {
      $sdk = $this->checkoutSdkFactory->get($this->configuration);

      // If the payment was authorized more than 3 days ago, attempt to
      // reauthorize it.
      if (($this->time->getRequestTime() >= ($payment->getAuthorizedTime() + (86400 * 3))) && !$payment->isExpired()) {
        $sdk->reAuthorizePayment($remote_id, ['amount' => $params['amount']]);
      }

      $response = $sdk->capturePayment($remote_id, $params);
      $response = Json::decode($response->getBody()->getContents());
    }
    catch (BadResponseException $exception) {
      $this->logger->error($exception->getResponse()->getBody()->getContents());
      throw new PaymentGatewayException('An error occurred while capturing the authorized payment.');
    }
    $remote_state = strtolower($response['status']);
    $state = $this->mapPaymentState('capture', $remote_state);

    if (!$state) {
      throw new PaymentGatewayException('Unhandled payment state.');
    }
    $payment->setState('completed');
    $payment->setAmount($amount);
    $payment->setRemoteId($response['id']);
    $payment->setRemoteState($remote_state);
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function voidPayment(PaymentInterface $payment) {
    $this->assertPaymentState($payment, ['authorization']);
    try {
      $sdk = $this->checkoutSdkFactory->get($this->configuration);
      $response = $sdk->voidPayment($payment->getRemoteId());
    }
    catch (BadResponseException $exception) {
      $this->logger->error($exception->getResponse()->getBody()->getContents());
      throw new PaymentGatewayException('An error occurred while voiding the payment.');
    }
    if ($response->getStatusCode() == Response::HTTP_NO_CONTENT) {
      $payment->setState('authorization_voided');
      $payment->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function refundPayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->assertPaymentState($payment, ['completed', 'partially_refunded']);
    // If not specified, refund the entire amount.
    $amount = $amount ?: $payment->getAmount();
    $this->assertRefundAmount($payment, $amount);

    $old_refunded_amount = $payment->getRefundedAmount();
    $new_refunded_amount = $old_refunded_amount->add($amount);
    $params = [
      'amount' => [
        'value' => Calculator::trim($amount->getNumber()),
        'currency_code' => $amount->getCurrencyCode(),
      ],
    ];
    if ($new_refunded_amount->lessThan($payment->getAmount())) {
      $payment->setState('partially_refunded');
    }
    else {
      $payment->setState('refunded');
    }
    try {
      $sdk = $this->checkoutSdkFactory->get($this->configuration);
      $response = $sdk->refundPayment($payment->getRemoteId(), $params);
      $response = Json::decode($response->getBody()->getContents());
    }
    catch (BadResponseException $exception) {
      $this->logger->error($exception->getResponse()->getBody()->getContents());
      throw new PaymentGatewayException('An error occurred while refunding the payment.');
    }

    if (strtolower($response['status']) !== 'completed') {
      throw new PaymentGatewayException(sprintf('Invalid state returned by PayPal. Expected: ("%s"), Actual: ("%s").', 'COMPLETED', $response['status']));
    }
    $payment->setRemoteState($response['status']);
    $payment->setRefundedAmount($new_refunded_amount);
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function onNotify(Request $request) {
    // Reacts to Webhook events.
    $request_body = Json::decode($request->getContent());
    $this->logger->debug('Incoming webhook request: <pre>@data</pre>', [
      '@data' => print_r($request_body, TRUE),
    ]);
    $supported_events = [
      'PAYMENT.AUTHORIZATION.VOIDED',
      'PAYMENT.CAPTURE.COMPLETED',
      'PAYMENT.CAPTURE.REFUNDED',
      'PAYMENT.CAPTURE.PENDING',
      'PAYMENT.CAPTURE.DENIED',
    ];

    // Ignore unsupported events.
    if (!isset($request_body['event_type']) ||
      !in_array($request_body['event_type'], $supported_events)) {
      return;
    }

    try {
      $sdk = $this->checkoutSdkFactory->get($this->configuration);
      $parameters = [
        'auth_algo' => $request->headers->get('PAYPAL-AUTH-ALGO'),
        'cert_url' => $request->headers->get('PAYPAL-CERT-URL'),
        'transmission_id' => $request->headers->get('PAYPAL-TRANSMISSION-ID'),
        'transmission_sig' => $request->headers->get('PAYPAL-TRANSMISSION-SIG'),
        'transmission_time' => $request->headers->get('PAYPAL-TRANSMISSION-TIME'),
        'webhook_id' => $this->configuration['webhook_id'],
        'webhook_event' => $request_body,
      ];
      $signature_request = $sdk->verifyWebhookSignature($parameters);
      $response = Json::decode($signature_request->getBody());

      // If the webhook signature could not be successfully verified, stop here.
      if (strtolower($response['verification_status']) !== 'success') {
        $this->logger->error('An error occurred while trying to verify the webhook signature: <pre>@response</pre>', [
          '@response' => print_r($response, TRUE),
        ]);
        return;
      }
      // Unfortunately, we need to use the "custom_id" (i.e the order_id) for
      // retrieving the payment associated to this webhook event since the
      // resource id might differ from our "remote_id".
      $order_id = $request_body['resource']['custom_id'];
      /** @var \Drupal\commerce_payment\PaymentStorageInterface $payment_storage */
      $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
      // Note that we don't use the loadMultipleByOrder() method on the payment
      // storage since we don't actually need to load the order.
      // This assumes the last payment is the right one.
      $payment_ids = $payment_storage->getQuery()
        ->condition('order_id', $order_id)
        ->accessCheck(FALSE)
        ->sort('payment_id', 'DESC')
        ->range(0, 1)
        ->execute();

      if (!$payment_ids) {
        $this->logger->error('Could not find a payment transaction in Drupal for the order ID @order_id.', [
          '@order_id' => $order_id,
        ]);
        return;
      }
      $payment = $payment_storage->load(reset($payment_ids));
      $amount = Price::fromArray([
        'number' => $request_body['resource']['amount']['value'],
        'currency_code' => $request_body['resource']['amount']['currency_code'],
      ]);
      // Synchronize the remote ID and remote state.
      $payment->setRemoteId($request_body['resource']['id']);
      $payment->setRemoteState($request_body['resource']['status']);

      switch ($request_body['event_type']) {
        case 'PAYMENT.AUTHORIZATION.VOIDED':
          if ($payment->getState()->getId() !== 'authorization_voided') {
            $payment->setState('authorization_voided');
            $payment->save();
          }
          break;

        case 'PAYMENT.CAPTURE.DENIED':
          if ($payment->getState()->getId() !== 'authorization_voided') {
            $payment->setState('capture_denied');
            $payment->save();
          }
          break;

        case 'PAYMENT.CAPTURE.COMPLETED':
          // Ignore completed payments.
          if ($payment->getState()->getId() !== 'completed' ||
            $amount->lessThan($payment->getAmount())) {
            $payment->setAmount($amount);
            $payment->setState('completed');
            $payment->save();
          }
          break;

        case 'PAYMENT.CAPTURE.REFUNDED':
          if ($amount->lessThan($payment->getAmount())) {
            $payment->setState('partially_refunded');
          }
          else {
            $payment->setState('refunded');
          }
          if (!$payment->getRefundedAmount() ||
            !$payment->getRefundedAmount()->equals($amount)) {
            $payment->setRefundedAmount($amount);
            $payment->save();
          }
          break;

        case 'PAYMENT.CAPTURE.PENDING':
          if ($payment->getState()->getId() !== 'pending') {
            $payment->setAmount($amount);
            $payment->setState('pending');
            $payment->save();
          }
          break;
      }
    }
    catch (BadResponseException $exception) {
      $this->logger->error('An error occurred while trying to verify the webhook signature: @error', [
        '@error' => $exception->getMessage(),
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    try {
      $sdk = $this->checkoutSdkFactory->get($this->configuration);
      $paypal_request = $sdk->getOrder($order->getData('paypal_order_id'));
      $paypal_order = Json::decode($paypal_request->getBody());
    }
    catch (BadResponseException $exception) {
      throw new PaymentGatewayException('Could not load the order from PayPal.');
    }
    $paypal_amount = $paypal_order['purchase_units'][0]['amount'];
    $paypal_total = Price::fromArray(['number' => $paypal_amount['value'], 'currency_code' => $paypal_amount['currency_code']]);

    // Make sure the order balance matches the total we get from PayPal.
    if (!$paypal_total->equals($order->getBalance())) {
      throw new PaymentGatewayException('The PayPal order total does not match the order balance.');
    }
    if (!in_array($paypal_order['status'], ['APPROVED', 'SAVED'])) {
      throw new PaymentGatewayException(sprintf('Unexpected PayPal order status %s.', $paypal_order['status']));
    }
    $flow = $order->getData('paypal_checkout_flow');
    $order->setData('commerce_paypal_checkout', [
      'remote_id' => $paypal_order['id'],
      'flow' => $flow,
      'intent' => strtolower($paypal_order['intent']),
      // It's safe to assume the last funding source set in the cookie was for
      // this order and note it in the data array for later use.
      'funding_source' => $request->cookies->get('lastFundingSource', NULL),
    ]);

    if (empty($order->getEmail())) {
      $order->setEmail($paypal_order['payer']['email_address']);
    }

    if ($this->configuration['update_billing_profile']) {
      $this->updateProfile($order, 'billing', $paypal_order);
    }
    if (!empty($this->configuration['update_shipping_profile']) && $order->hasField('shipments')) {
      $this->updateProfile($order, 'shipping', $paypal_order);
    }

    $payment_method = NULL;
    // If a payment method is already referenced by the order, no need to create
    // a new one.
    if (!$order->get('payment_method')->isEmpty()) {
      /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
      $payment_method = $order->get('payment_method')->entity;
    }
    // If the order doesn't reference a payment method yet, or if the payment
    // method doesn't reference the right gateway, create a new one.
    if (!$payment_method || $payment_method->getPaymentGatewayId() !== $this->parentEntity->id()) {
      // Create a payment method.
      $payment_method_storage = $this->entityTypeManager->getStorage('commerce_payment_method');
      assert($payment_method_storage instanceof PaymentMethodStorageInterface);
      $payment_method = $payment_method_storage->createForCustomer(
        'paypal_checkout',
        $this->parentEntity->id(),
        $order->getCustomerId(),
        $order->getBillingProfile()
      );
    }
    $payment_method->setRemoteId($paypal_order['id']);
    $payment_method->setReusable(FALSE);
    $payment_method->save();
    $order->set('payment_method', $payment_method);

    if ($flow === 'shortcut' && $order->hasField('checkout_flow')) {
      // Force the checkout flow to PayPal checkout which is the flow the module
      // defines for the "shortcut" flow.
      $order->set('checkout_flow', 'paypal_checkout');
      $order->set('checkout_step', NULL);
    }
    // For the "mark" flow, create the payment right away (if not configured
    // to be skipped).
    if ($flow === 'mark' && !$request->query->has('skip_payment_creation')) {
      $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
      /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
      $payment = $payment_storage->create([
        'state' => 'new',
        'amount' => $order->getBalance(),
        'payment_gateway' => $this->parentEntity->id(),
        'payment_method' => $payment_method->id(),
        'order_id' => $order->id(),
      ]);
      $this->createPayment($payment);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createPaymentMethod(PaymentMethodInterface $payment_method, array $payment_details) {
    if (empty($payment_details['paypal_remote_id'])) {
      throw new PaymentGatewayException('Cannot create the payment method without the PayPal order ID.');
    }
    try {
      $sdk = $this->checkoutSdkFactory->get($this->configuration);
      $request = $sdk->getOrder($payment_details['paypal_remote_id']);
      $paypal_order = Json::decode($request->getBody());
    }
    catch (BadResponseException $exception) {
      throw new PaymentGatewayException($exception->getResponse()->getBody()->getContents());
    }
    // Check if we have information about the card used.
    if (isset($paypal_order['payment_source']['card'])) {
      $payment_source = $paypal_order['payment_source']['card'];

      // Remove any character that isn't A-Z, a-z or 0-9.
      $payment_source['brand'] = strtolower(preg_replace("/[^A-Za-z0-9]/", '', $payment_source['brand']));

      // We should in theory map the credit card type we get from PayPal to one
      // expected by us, but the credit card types are not correctly documented.
      // For example, ("Mastercard" is sent as "MASTER_CARD" but documented
      // as "MASTERCARD").
      $card_types = CreditCard::getTypes();
      if (!isset($card_types[$payment_source['brand']])) {
        throw new HardDeclineException(sprintf('Unsupported credit card type "%s".', $paypal_order['payment_source']['card']));
      }

      $payment_method->set('card_type', $payment_source['brand']);
      $payment_method->set('card_number', $payment_source['last_digits']);
    }
    $payment_method->setRemoteId($paypal_order['id']);
    $payment_method->setReusable(FALSE);
    $payment_method->save();
  }

  /**
   * {@inheritdoc}
   */
  public function deletePaymentMethod(PaymentMethodInterface $payment_method) {
    $payment_method->delete();
  }

  /**
   * Map a PayPal payment state to a local one.
   *
   * @param string $type
   *   The payment type. One of "authorize" or "capture".
   * @param string $remote_state
   *   The PayPal remote payment state.
   *
   * @return string
   *   The corresponding local payment state.
   */
  protected function mapPaymentState($type, $remote_state) {
    $mapping = [
      'authorize' => [
        'created' => 'authorization',
        'pending' => 'pending',
        'voided' => 'authorization_voided',
        'expired' => 'authorization_expired',
      ],
      'capture' => [
        'completed' => 'completed',
        'pending' => 'pending',
        'partially_refunded' => 'partially_refunded',
      ],
    ];
    return $mapping[$type][$remote_state] ?? '';
  }

  /**
   * Updates the profile of the given type using the response returned by PayPal.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param string $type
   *   The type (billing|profile).
   * @param array $paypal_order
   *   The PayPal order.
   */
  protected function updateProfile(OrderInterface $order, $type, array $paypal_order) {
    if ($type == 'billing') {
      /** @var \Drupal\profile\Entity\ProfileInterface $profile */
      $profile = $order->getBillingProfile() ?: $this->buildCustomerProfile($order);
      $profile->address->given_name = $paypal_order['payer']['name']['given_name'];
      $profile->address->family_name = $paypal_order['payer']['name']['surname'];
      if (isset($paypal_order['payer']['address'])) {
        $this->populateProfile($profile, $paypal_order['payer']['address']);
      }
      $profile->save();
      $order->setBillingProfile($profile);
    }
    elseif ($type == 'shipping' && !empty($paypal_order['purchase_units'][0]['shipping'])) {
      $shipping_info = $paypal_order['purchase_units'][0]['shipping'];
      $shipments = $order->shipments->referencedEntities();
      if (!$shipments) {
        /** @var \Drupal\commerce_shipping\PackerManagerInterface $packer_manager */
        $packer_manager = \Drupal::service('commerce_shipping.packer_manager');
        [$shipments] = $packer_manager->packToShipments($order, $this->buildCustomerProfile($order), $shipments);
      }
      // Can't proceed without shipments.
      if (!$shipments) {
        return;
      }
      /** @var \Drupal\commerce_shipping\Entity\ShipmentInterface $first_shipment */
      $first_shipment = $shipments[0];
      /** @var \Drupal\profile\Entity\ProfileInterface $profile */
      $profile = $first_shipment->getShippingProfile() ?: $this->buildCustomerProfile($order);

      // This is a hack but shipments with empty amounts is crashing other
      // contrib modules.
      // Ideally, we shouldn't have to pack the shipments ourselves...
      if (!$first_shipment->getAmount()) {
        $shipment_amount = Price::fromArray([
          'number' => 0,
          'currency_code' => $order->getTotalPrice()->getCurrencyCode(),
        ]);
        $first_shipment->setAmount($shipment_amount);
      }

      // We only get the full name from PayPal, so we need to "guess" the given
      // name and the family name.
      $names = explode(' ', $shipping_info['name']['full_name']);
      $given_name = array_shift($names);
      $family_name = implode(' ', $names);
      $profile->address->given_name = $given_name;
      $profile->address->family_name = $family_name;
      if (!empty($shipping_info['address'])) {
        $this->populateProfile($profile, $shipping_info['address']);
      }
      $profile->save();
      $first_shipment->setShippingProfile($profile);
      $first_shipment->save();
      $order->set('shipments', $shipments);
    }
  }

  /**
   * Builds a customer profile, assigned to the order's owner.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return \Drupal\profile\Entity\ProfileInterface
   *   The customer profile.
   */
  protected function buildCustomerProfile(OrderInterface $order) {
    return $this->entityTypeManager->getStorage('profile')->create([
      'uid' => $order->getCustomerId(),
      'type' => 'customer',
    ]);
  }

  /**
   * Populate the given profile with the given PayPal address.
   *
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   *   The profile to populate.
   * @param array $address
   *   The PayPal address.
   */
  protected function populateProfile(ProfileInterface $profile, array $address) {
    // Map PayPal address keys to keys expected by AddressItem.
    $mapping = [
      'address_line_1' => 'address_line1',
      'address_line_2' => 'address_line2',
      'admin_area_1' => 'administrative_area',
      'admin_area_2' => 'locality',
      'postal_code' => 'postal_code',
      'country_code' => 'country_code',
    ];
    foreach ($address as $key => $value) {
      if (!isset($mapping[$key])) {
        continue;
      }
      // PayPal address fields have a higher maximum length than ours.
      $value = $key == 'country_code' ? $value : mb_substr($value, 0, 255);
      $profile->address->{$mapping[$key]} = $value;
    }
  }

}
