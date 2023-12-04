<?php

namespace Drupal\commerce_paypal\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_paypal\Event\PayflowLinkRequestEvent;
use Drupal\commerce_paypal\Event\PayPalEvents;
use Drupal\commerce_price\Calculator;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use GuzzleHttp\Exception\BadResponseException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the PayPal Payflow Link payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "paypal_payflow_link",
 *   label = @Translation("PayPal - Payflow Link"),
 *   display_label = @Translation("PayPal"),
 *   payment_method_types = {"paypal"},
 *   credit_card_types = {
 *     "amex", "discover", "mastercard", "visa", "paypal",
 *   },
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_paypal\PluginForm\PayflowLinkForm",
 *   },
 *   payment_type = "paypal_checkout",
 * )
 */
class PayflowLink extends OffsitePaymentGatewayBase implements PayflowLinkInterface {

  /**
   * Used as value for 'buttonsource' parameter in requests to API.
   */
  const BUTTON_SOURCE = 'CommerceGuys_Cart_PFL';

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->logger = $container->get('logger.channel.commerce_paypal');
    $instance->httpClient = $container->get('http_client');
    $instance->moduleHandler = $container->get('module_handler');
    $instance->eventDispatcher = $container->get('event_dispatcher');
    $instance->messenger = $container->get('messenger');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'partner' => 'PayPal',
      'vendor' => '',
      'user' => '',
      'password' => '',
      'trxtype' => 'S',
      'redirect_mode' => 'iframe',
      'cancel_link' => TRUE,
      'reference_transactions' => FALSE,
      'emailcustomer' => FALSE,
      'log' => [
        'request' => 0,
        'response' => 0,
      ],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['service_description'] = [
      '#markup' => $this->t('Accept payment securely on your site via credit card, debit card, or PayPal using a merchant account of your choice. This payment gateway requires a PayPal Payflow Link account. <a href="@url">Sign up here</a> and edit these settings to start accepting payments.', ['@url' => 'https://www.paypal.com/webapps/mpp/referral/paypal-payflow-link?partner_id=VZ6B9QLQ8LZEE']),
      '#weight' => '-100',
    ];

    $form['partner'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Partner'),
      '#default_value' => $this->configuration['partner'],
      '#required' => TRUE,
      '#description' => $this->t('Either PayPal or the name of the reseller who registered your Payflow Link account.'),
    ];
    $form['vendor'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Vendor'),
      '#default_value' => $this->configuration['vendor'],
      '#required' => TRUE,
      '#description' => $this->t('The merchant login ID you chose when you created your Payflow Link account.'),
    ];
    $form['user'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User'),
      '#default_value' => $this->configuration['user'],
      '#required' => TRUE,
      '#description' => $this->t('The name of the user on the account you want to use to process transactions or the merchant login if you have not created users.'),
    ];
    $form['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#default_value' => $this->configuration['password'],
      '#required' => TRUE,
      '#description' => $this->t('The password created for the user specified in the previous textfield.'),
    ];
    $form['trxtype'] = [
      '#type' => 'select',
      '#title' => $this->t('Default transaction type'),
      '#default_value' => $this->configuration['trxtype'],
      '#required' => TRUE,
      '#options' => [
        'S' => $this->t('Sale - authorize and capture the funds at the time the payment is processed'),
        'A' => $this->t('Authorization - reserve funds on the card to be captured later through your PayPal account'),
      ],
    ];
    $form['redirect_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Checkout redirect mode'),
      '#description' => $this->t('Your payment processor and Payflow Link account settings may limit which of these payment options are actually available on the payment form.'),
      '#default_value' => $this->configuration['redirect_mode'],
      '#required' => TRUE,
      '#options' => [
        'iframe' => $this->t('Stay on this site using an iframe to embed the hosted checkout page'),
        'post' => $this->t('Redirect to the hosted checkout page via POST through an automatically submitted form'),
        'get' => $this->t('Redirect to the hosted checkout page immediately with a GET request'),
      ],
    ];
    $form['cancel_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display a cancel link beneath the iframe of an embedded hosted checkout page.'),
      '#default_value' => $this->configuration['cancel_link'],
      '#states' => [
        'visible' => [
          ':input[name="configuration[paypal_payflow_link][redirect_mode]"]' => ['value' => 'iframe'],
        ],
      ],
    ];
    $form['reference_transactions'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable reference transactions for payments captured through this Payflow Link account.'),
      '#description' => $this->t('Contact PayPal if you are unsure if this option is available to you.'),
      '#default_value' => $this->configuration['reference_transactions'],
    ];
    $form['emailcustomer'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Instruct PayPal to e-mail payment receipts to your customers upon payment.'),
      '#default_value' => $this->configuration['emailcustomer'],
    ];
    // Add the logging configuration form elements.
    $form['log'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Log the following messages for debugging'),
      '#options' => [
        'request' => $this->t('API request messages'),
        'response' => $this->t('API response messages'),
      ],
      '#default_value' => $this->configuration['log'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['partner'] = $values['partner'];
      $this->configuration['vendor'] = $values['vendor'];
      $this->configuration['user'] = $values['user'];
      $this->configuration['password'] = $values['password'];
      $this->configuration['trxtype'] = $values['trxtype'];
      $this->configuration['redirect_mode'] = $values['redirect_mode'];
      $this->configuration['reference_transactions'] = $values['reference_transactions'];
      $this->configuration['emailcustomer'] = $values['emailcustomer'];
      $this->configuration['log'] = $values['log'];
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

    // Prepare a name-value pair array to capture the requested amount.
    $nvp = [
      'TRXTYPE' => 'C',
      'ORIGID' => $payment->getRemoteId(),
      'AMT' => Calculator::trim($amount->getNumber()),
    ];
    $order = $payment->getOrder();

    // Submit the refund request to Payflow Pro.
    $response = $this->apiRequest('pro', $nvp, $order);

    // If the credit succeeded...
    if (intval($response['RESULT']) === 0) {
      // Check if the Refund is partial or full.
      $old_refunded_amount = $payment->getRefundedAmount();
      $new_refunded_amount = $old_refunded_amount->add($amount);
      if ($new_refunded_amount->lessThan($payment->getAmount())) {
        $payment->setState('partially_refunded');
      }
      else {
        $payment->setState('refunded');
      }
      $payment->setRemoteState('C');
      $payment->setRefundedAmount($new_refunded_amount);
      $payment->save();
    }
    else {
      throw new PaymentGatewayException($this->t('Refund failed: @reason', ['@reason' => $response['RESPMSG']]), $response['RESULT']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function canRefundPayment(PaymentInterface $payment) {
    // Return FALSE if the transaction isn't valid for credit transactions:
    // Sale or Delayed Capture.
    $valid_types = ['S', 'D', 'C'];

    if (!in_array($payment->getRemoteState(), $valid_types)) {
      return FALSE;
    }

    // Return FALSE if the transaction was not a success.
    if (!in_array($payment->getState()->getId(), [
      'completed',
      'partially_refunded',
    ])) {
      return FALSE;
    }

    // Return FALSE if it is more than 60 days since the original transaction.
    if ($payment->getCompletedTime() &&
      $payment->getCompletedTime() < strtotime('-60 days')) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function voidPayment(PaymentInterface $payment) {
    // Build a name-value pair array for this transaction.
    $nvp = [
      'TRXTYPE' => 'V',
      'ORIGID' => $payment->getRemoteId(),
    ];

    $order = $payment->getOrder();
    // Submit the request to Payflow Pro.
    $response = $this->apiRequest('pro', $nvp, $order);

    // Log the response if specified.
    if (!empty($this->getConfiguration()['log']['response'])) {
      $this->logger->debug('Payflow server response: @param', [
        '@param' => new FormattableMarkup('<pre>' . print_r($response, 1) . '</pre>', []),
      ]);
    }

    // If we got an approval response code...
    if (intval($response['RESULT']) === 0) {
      // Set the remote and local status accordingly.
      $payment->remote_id = $response['PNREF'];
      $payment->state = 'voided';
      $payment->remote_state = 'V';
    }
    else {
      throw new PaymentGatewayException('Prior authorization capture failed, so the payment will remain in a pending status.');
    }

    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function canVoidPayment(PaymentInterface $payment) {
    $valid_types = ['A', 'Pending'];
    // Return FALSE if payment isn't awaiting capture.
    if (!in_array($payment->getRemoteState(), $valid_types)) {
      return FALSE;
    }

    // Return FALSE if the payment is not pending.
    if ($payment->getState()->getId() !== 'pending') {
      return FALSE;
    }

    // Return FALSE if it is more than 29 days past the original authorization.
    if ($payment->getCompletedTime() &&
      $payment->getCompletedTime() < strtotime('-29 days')
    ) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function canCapturePayment(PaymentInterface $payment) {
    return $this->canVoidPayment($payment);
  }

  /**
   * {@inheritdoc}
   */
  public function capturePayment(PaymentInterface $payment, Price $amount = NULL) {
    // If not specified, capture the entire amount.
    $amount = $amount ?: $payment->getAmount();

    $order = $payment->getOrder();

    // Prepare a name-value pair array to capture the requested amount.
    $nvp = [
      'TRXTYPE' => 'D',
      'ORIGID' => $payment->getRemoteId(),
      'AMT' => Calculator::trim($amount->getNumber()),
      'CAPTURECOMPLETE' => 'Y',
    ];

    // Submit the capture request to Payflow Pro.
    $response = $this->apiRequest('pro', $nvp, $order);

    // Log the response if specified.
    if (!empty($this->getConfiguration['log']['response'])) {
      $this->logger->debug('Payflow server response: @param', [
        '@param' => new FormattableMarkup('<pre>' . print_r($response, 1) . '</pre>', []),
      ]);
    }

    switch (intval($response['RESULT'])) {
      case 0:
        $payment->amount = $amount;
        $payment->remote_id = $response['PNREF'];
        $payment->state = 'completed';
        $payment->remote_state = 'D';
        break;

      default:
        throw new PaymentGatewayException($this->t('Capture failed: @reason.', ['@reason' => $response['RESPMSG']]), $response['RESULT']);
    }

    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function onCancel(OrderInterface $order, Request $request) {
    // Display a message indicating the customer canceled payment.
    $this->messenger->addMessage($this->t('You have canceled payment at PayPal but may resume the checkout process here when you are ready.'));

    // Remove the payment information from the order data array.
    $order->unsetData('commerce_payflow');
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    /** @var \Symfony\Component\HttpFoundation\ParameterBag $parameter_bag */
    $parameter_bag = $request->request;
    $configuration = $this->getConfiguration();

    if ($configuration['redirect_mode'] === 'iframe') {
      $received_parameters = $order->getData('commerce_payflow')['received_parameters'];
    }
    else {
      $received_parameters = $parameter_bag->all();
    }
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');

    if (!empty($configuration['silent_post_logging']) &&
      $configuration['silent_post_logging'] == 'full_post') {
      $this->logger->notice('Customer returned from Payflow with the following POST data: !data', ['!data' => '<pre>' . Html::escape(print_r($received_parameters, TRUE)) . '</pre>']);
    }

    if (!empty($configuration['log']['response'])) {
      $this->logger->notice('Payflow server response: @response', [
        '@response' => new FormattableMarkup('<pre>' . print_r($received_parameters, 1) . '</pre>', []),
      ]);
    }

    if (isset($received_parameters['RESULT']) && !in_array(intval($received_parameters['RESULT']), [0, 126])) {
      $message = $this->resultMessage($received_parameters['RESULT']);
      throw new PaymentGatewayException($message);
    }

    // Determine the type of transaction.
    if (!empty($received_parameters['TRXTYPE'])) {
      $trxtype = $received_parameters['TRXTYPE'];
    }
    elseif (!empty($received_parameters['TYPE'])) {
      $trxtype = $received_parameters['TYPE'];
    }
    else {
      $trxtype = $configuration['trxtype'];
    }

    $state = '';

    // Set the transaction status based on the type of transaction this was.
    if (intval($received_parameters['RESULT']) == 0) {
      switch ($trxtype) {
        case 'S':
          $state = 'completed';
          break;

        case 'A':
        default:
          $state = 'pending';
          break;
      }
    }
    elseif (intval($received_parameters['RESULT']) == 126) {
      $state = 'pending';
    }

    $commerce_payment = $payment_storage->create([
      'state' => $state,
      'amount' => $order->getBalance(),
      'payment_gateway' => $this->parentEntity->id(),
      'order_id' => $order->id(),
      'remote_id' => $received_parameters['PNREF'],
      'remote_state' => $trxtype,
    ]);

    if (!empty($received_parameters['PENDINGREASON']) && $received_parameters['PENDINGREASON'] != 'completed') {
      // And ensure the local and remote status are pending.
      $commerce_payment->setState('pending');
    }

    $commerce_payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaymentOperations(PaymentInterface $payment) {
    $operations = parent::buildPaymentOperations($payment);
    $operations['reference'] = [
      'title' => $this->t('Reference'),
      'page_title' => $this->t('Refund payment'),
      'plugin_form' => 'reference-payment',
      'access' => $this->canReferencePayment($payment),
    ];

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultForms() {
    $default_forms = parent::getDefaultForms();
    $default_forms['reference-payment'] = 'Drupal\commerce_paypal\PluginForm\PaymentReferenceForm';

    return $default_forms;
  }

  /**
   * {@inheritdoc}
   */
  public function createSecureToken(OrderInterface $order) {
    $cancel_url = Url::fromRoute('commerce_payment.checkout.cancel', [
      'commerce_order' => $order->id(),
      'step' => 'payment',
    ], ['absolute' => TRUE])->toString();
    $return_route_name = $this->configuration['redirect_mode'] == 'iframe' ? 'commerce_paypal.checkout.payflowlink_iframe_return' : 'commerce_payment.checkout.return';
    $return_url = Url::fromRoute($return_route_name, [
      'commerce_order' => $order->id(),
      'step' => 'payment',
    ], ['absolute' => TRUE])->toString();

    // Build a name-value pair array for this transaction.
    $nvp = [
      // Request a secure token using our order's token ID.
      'CREATESECURETOKEN' => 'Y',
      'SECURETOKENID' => $order->getData('commerce_payflow')['tokenid'],

      // Indicate the type and amount of the transaction.
      'TRXTYPE' => $this->configuration['trxtype'],
      'AMT' => Calculator::trim($order->getTotalPrice()->getNumber()),
      'CURRENCY' => $order->getTotalPrice()->getCurrencyCode(),
      'INVNUM' => $order->id() . '-' . $this->time->getRequestTime(),

      // Add application specific parameters.
      'BUTTONSOURCE' => self::BUTTON_SOURCE,
      'ERRORURL' => $return_url,
      'RETURNURL' => $return_url,
      'CANCELURL' => $cancel_url,
      'DISABLERECEIPT' => 'TRUE',
      'TEMPLATE' => $this->configuration['redirect_mode'] == 'iframe' ? 'MINLAYOUT' : 'TEMPLATEA',
      'CSCREQUIRED' => 'TRUE',
      'CSCEDIT' => 'TRUE',
      'URLMETHOD' => 'POST',
    ];

    /** @var \Drupal\profile\Entity\ProfileInterface $billing_profile */
    $billing_profile = $order->getBillingProfile();
    // Prepare the billing address for use in the request.
    if ($billing_profile && !$billing_profile->get('address')->isEmpty()) {
      $billing_address = $billing_profile->get('address')->first()->getValue();
      if (is_array($billing_address)) {
        // Add the billing address.
        $nvp += [
          'BILLTOEMAIL' => mb_substr($order->getEmail(), 0, 60),
          'BILLTOFIRSTNAME' => mb_substr($billing_address['given_name'], 0, 45),
          'BILLTOLASTNAME' => mb_substr($billing_address['family_name'], 0, 45),
          'BILLTOSTREET' => mb_substr($billing_address['address_line1'], 0, 150),
          'BILLTOCITY' => mb_substr($billing_address['locality'], 0, 45),
          'BILLTOSTATE' => mb_substr($billing_address['administrative_area'], 0, 2),
          'BILLTOCOUNTRY' => mb_substr($billing_address['country_code'], 0, 2),
          'BILLTOZIP' => mb_substr($billing_address['postal_code'], 0, 10),
        ];
      }
    }

    // If enabled, email the customer a receipt from PayPal.
    if (!empty($this->configuration['emailcustomer'])) {
      $nvp['EMAILCUSTOMER'] = 'TRUE';
    }

    /** @var \Drupal\profile\Entity\ProfileInterface $shipping_profile */
    $shipping_profile = $order->getBillingProfile();
    // Prepare the billing address for use in the request.
    if ($shipping_profile && !$shipping_profile->get('address')->isEmpty()) {
      $shipping_address = $shipping_profile->get('address')
        ->first()
        ->getValue();
      if (is_array($shipping_address)) {
        // Add the shipping address parameters to the request.
        $nvp += [
          'SHIPTOFIRSTNAME' => mb_substr($shipping_address['given_name'], 0, 45),
          'SHIPTOLASTNAME' => mb_substr($shipping_address['family_name'], 0, 45),
          'SHIPTOSTREET' => mb_substr($shipping_address['address_line1'], 0, 150),
          'SHIPTOCITY' => mb_substr($shipping_address['locality'], 0, 45),
          'SHIPTOSTATE' => mb_substr($shipping_address['administrative_area'], 0, 2),
          'SHIPTOCOUNTRY' => mb_substr($shipping_address['country_code'], 0, 2),
          'SHIPTOZIP' => mb_substr($shipping_address['postal_code'], 0, 10),
        ];
      }
    }

    // Add the line item details to the array.
    $nvp += $this->itemizeOrder($order);

    // Submit the API request to Payflow.
    $response = $this->apiRequest('pro', $nvp, $order);

    // If the request is successful, return the token.
    if (isset($response['RESULT']) && $response['RESULT'] == '0') {
      return $response['SECURETOKEN'];
    }

    // Otherwise indicate failure by returning NULL.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectUrl(OrderInterface $order = NULL) {
    $configuration = $this->getConfiguration();
    $mode = $configuration['mode'];
    $redirect_mode = $configuration['redirect_mode'];
    $url = '';

    switch ($mode) {
      case 'test':
        $url = 'https://pilot-payflowlink.paypal.com/';
        break;

      case 'live':
        $url = 'https://payflowlink.paypal.com/';
        break;
    }

    if (in_array($redirect_mode, ['get', 'iframe']) && !empty($order)) {
      $commerce_payflow_data = $order->getData('commerce_payflow');
      if (empty($commerce_payflow_data['token']) || empty($commerce_payflow_data['tokenid'])) {
        return '';
      }

      // Build a query array using information from the order object.
      $query = [
        'SECURETOKEN' => $commerce_payflow_data['token'],
        'SECURETOKENID' => $commerce_payflow_data['tokenid'],
      ];

      // Set the MODE parameter if the URL is for the test server.
      if ($mode === 'test') {
        $query['MODE'] = 'TEST';
      }

      // Grab the base URL of the appropriate API server.
      $url = Url::fromUri($url, ['query' => $query])
        ->toString();
    }

    return $url;
  }

  /**
   * {@inheritdoc}
   */
  public function referencePayment(PaymentInterface $payment, Price $amount = NULL) {
    $amount = $amount ?: $payment->getAmount();

    $order = $payment->getOrder();

    // Prepare a name-value pair array to capture the requested amount.
    $nvp = [
      'BUTTONSOURCE' => self::BUTTON_SOURCE,
      'TRXTYPE' => 'S',
      'ORIGID' => $payment->getRemoteId(),
      'AMT' => Calculator::trim($amount->getNumber()),
      'TENDER' => 'C',
    ];

    // Submit the reference transaction request to Payflow Pro.
    $response = $this->apiRequest('pro', $nvp, $order);

    if (isset($response['RESULT']) && intval($response['RESULT']) === 0) {
      // Create a new transaction to represent the reference transaction.
      /** @var \Drupal\commerce_payment\Entity\PaymentInterface $new_payment */
      $new_payment = $this->entityTypeManager->getStorage('commerce_payment')
        ->create([
          'state' => 'completed',
          'amount' => $amount,
          'payment_gateway' => $payment->getPaymentGatewayId(),
          'order_id' => $order->id(),
          'remote_id' => $response['PNREF'] ?? '',
          'remote_state' => 'S',
        ]);
      $new_payment->save();
    }
    else {
      throw new PaymentGatewayException($this->t('Reference transaction failed: @reason.', ['@reason' => $response['RESPMSG']]), $response['RESULT']);
    }
  }

  /**
   * Returns an itemized order data array for use in a name-value pair array.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order whose line items should be converted into name-value pairs.
   *
   * @return array
   *   The name-value pair array representing the line items that should be
   *   added to the name-value pair array for an API request.
   */
  protected function itemizeOrder(OrderInterface $order) {
    $nvp = [];

    // Calculate the items total.
    $items_total = new Price('0', $order->getTotalPrice()->getCurrencyCode());

    // Loop over all the line items on the order.
    $i = 0;
    foreach ($order->getItems() as $item) {
      $item_amount = Calculator::trim($item->getUnitPrice()->getNumber());

      // Add the line item to the return array.
      $nvp += [
        'L_NAME' . $i => $item->getTitle(),
        'L_COST' . $i => $item_amount,
        'L_QTY' . $i => $item->getQuantity(),
      ];

      // Add the SKU.
      /** @var \Drupal\commerce\PurchasableEntityInterface $purchased_entity */
      $purchased_entity = $item->getPurchasedEntity();
      if ($purchased_entity instanceof ProductVariationInterface) {
        $sku = $purchased_entity->getSku();
      }
      else {
        $sku = $purchased_entity->getOrderItemTitle();
      }

      $nvp += [
        'L_SKU' . $i => $sku,
      ];

      $items_total = $items_total->add($item->getTotalPrice());
      $i++;
    }
    $tax_amount = new Price('0', $order->getTotalPrice()->getCurrencyCode());

    // Collect the adjustments.
    $adjustments = [];
    foreach ($order->collectAdjustments() as $adjustment) {
      // Skip included adjustments.
      if ($adjustment->isIncluded()) {
        continue;
      }
      if ($adjustment->getType() === 'tax') {
        $tax_amount = $tax_amount->add($adjustment->getAmount());
      }
      else {
        // Collect other adjustments.
        $type = $adjustment->getType();
        $source_id = $adjustment->getSourceId();
        if (empty($source_id)) {
          // Adjustments without a source ID are always shown standalone.
          $key = count($adjustments);
        }
        else {
          // Adjustments with the same type and source ID are combined.
          $key = $type . '_' . $source_id;
        }

        if (empty($adjustments[$key])) {
          $adjustments[$key] = [
            'type' => $type,
            'label' => (string) $adjustment->getLabel(),
            'total' => $adjustment->getAmount(),
          ];
        }
        else {
          $adjustments[$key]['total'] = $adjustments[$key]['total']->add($adjustment->getAmount());
        }
      }
    }

    $i = 0;
    foreach ($adjustments as $adjustment) {
      $adjustment_amount = Calculator::trim($adjustment['total']->getNumber());
      $nvp += [
        'L_NAME' . $i => $adjustment['label'],
        'L_COST' . $i => $adjustment_amount,
        'L_QTY' . $i => 1,
      ];
      // Add the adjustment to the items total.
      $items_total = $items_total->add($adjustment['total']);
      $i++;
    }
    // Send the items total.
    $nvp['ITEMAMT'] = Calculator::trim($items_total->getNumber());

    // Send the tax amount.
    if (!$tax_amount->isZero()) {
      $nvp['TAXAMT'] = Calculator::trim($tax_amount->getNumber());
    }

    return $nvp;
  }

  /**
   * Submits an API request to Payflow.
   *
   * @param string $api
   *   Either 'pro' or 'link' indicating which API server the request should be
   *   sent to.
   * @param array $nvp
   *   (optional) The set of name-value pairs describing the transaction
   *   to submit.
   * @param null|\Drupal\commerce_order\Entity\OrderInterface $order
   *   (optional) The order the payment request is being made for.
   *
   * @return array|\Psr\Http\Message\ResponseInterface
   *   The response array from PayPal if successful or FALSE on error.
   */
  protected function apiRequest($api, array $nvp = [], $order = NULL) {
    $configuration = $this->getConfiguration();
    $mode = $configuration['mode'];

    // Get the API endpoint URL for the payment method's transaction mode.
    if ($api === 'pro') {
      $url = $this->getProServerUrl();
    }
    else {
      $url = $this->getRedirectUrl();
    }

    // Add the default name-value pairs to the array.
    $nvp += [
      // API credentials.
      'PARTNER' => $configuration['partner'],
      'VENDOR' => $configuration['vendor'],
      'USER' => $configuration['user'],
      'PWD' => $configuration['password'],

      // Set the mode based on which server we're submitting to.
      'MODE' => $mode === 'test' ? 'TEST' : 'LIVE',
    ];

    // Allow modules to alter the NVP request.
    $event = new PayflowLinkRequestEvent($nvp, $order);
    $this->eventDispatcher->dispatch(PayPalEvents::PAYFLOW_LINK_REQUEST, $event);
    $nvp = $event->getNvpData();

    // Log the request if specified.
    if ($configuration['log']['request'] === 'request') {
      // Mask sensitive request data.
      $log_nvp = $nvp;
      $log_nvp['PWD'] = str_repeat('X', strlen($log_nvp['PWD']));

      if (!empty($log_nvp['ACCT'])) {
        $log_nvp['ACCT'] = str_repeat('X', strlen($log_nvp['ACCT']) - 4) . mb_substr($log_nvp['ACCT'], -4);
      }

      if (!empty($log_nvp['CVV2'])) {
        $log_nvp['CVV2'] = str_repeat('X', strlen($log_nvp['CVV2']));
      }
      $this->logger->debug('Payflow API request to @url: @param', [
        '@url' => $url,
        '@param' => new FormattableMarkup('<pre>' . print_r($log_nvp, 1) . '</pre>', []),
      ]);
    }

    // Prepare the name-value pair array to be sent as a string.
    $pairs = [];

    foreach ($nvp as $key => $value) {
      /* Since we aren't supposed to urlencode parameter values for PFL/PPA API
      requests, we strip out ampersands and equals signs to. */
      $pairs[] = $key . '=' . str_replace(['&', '=', '#'], [''], $value);
    }

    $body = implode('&', $pairs);
    try {
      $response = $this->httpClient->post($url, [
        'headers' => [
          'Content-Type' => 'text/namevalue',
          'Content-Length' => strlen($body),
        ],
        'body' => $body,
        'timeout' => 45,
      ]);
    }
    catch (BadResponseException $exception) {
      $this->logger->error($exception->getResponse()->getBody()->getContents());
      throw new PaymentGatewayException('Redirect to PayPal failed. Please try again or contact an administrator to resolve the issue.');
    }

    $result = $response->getBody()->getContents();

    // Make the response an array.
    $response = [];

    foreach (explode('&', $result) as $nvp) {
      [$key, $value] = explode('=', $nvp);
      $response[urldecode($key)] = urldecode($value);
    }

    // Log the response if specified.
    if (!empty($configuration['log']['response'])) {
      $this->logger->debug('Payflow server response: @param', [
        '@param' => new FormattableMarkup('<pre>' . print_r($response, 1) . '</pre>', []),
      ]);
    }

    return $response;
  }

  /**
   * Returns the URL to a Payflow Pro API server.
   *
   * @return string
   *   The request URL with a trailing slash.
   */
  private function getProServerUrl() {
    switch ($this->getConfiguration()['mode']) {
      case 'test':
        return 'https://pilot-payflowpro.paypal.com/';

      case 'live':
        return 'https://payflowpro.paypal.com/';
    }

    return '';
  }

  /**
   * Returns the message explaining the RESULT of a Payflow transaction.
   *
   * @param string $result
   *   The RESULT value from a Payflow transaction.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   An error or explanation message fit for display to a customer.
   */
  protected function resultMessage($result) {
    switch (intval($result)) {
      case 0:
        return $this->t('Transaction approved.');

      case 1:
        return $this->t('Account authentication error. Please contact an administrator to resolve this issue.');

      case 5:
      case 26:
        return $this->t('The Payflow hosted checkout page is not configured for use. Please contact an administrator to resolve this issue.');

      case 2:
      case 25:
        return $this->t('You have attempted to use an invalid payment method. Please check your payment information and try again.');

      case 3:
        return $this->t('The specified transaction type is not appropriate for this transaction.');

      case 4:
      case 6:
        return $this->t('The payment request specified an invalid amount format or currency code. Please contact an administrator to resolve this issue.');

      case 7:
      case 8:
      case 9:
      case 10:
      case 19:
      case 20:
        return $this->t('The payment request included invalid parameters. Please contact an administrator to resolve this issue.');

      case 11:
      case 115:

      case 160:
      case 161:
      case 162:
        return $this->t('The payment request timed out. Please try again or contact an administrator to resolve the issue.');

      case 12:
      case 13:
      case 22:
      case 23:
      case 24:
        return $this->t('Payment declined. Please check your payment information and try again.');

      case 27:
      case 28:
      case 29:
      case 30:
      case 31:
      case 32:
      case 33:
      case 34:
      case 35:
      case 36:
      case 37:
      case 52:
      case 99:
      case 100:
      case 101:
      case 102:
      case 103:
      case 104:
      case 105:
      case 106:
      case 107:
      case 108:
      case 109:
      case 110:
      case 111:
      case 113:
      case 116:
      case 118:
      case 120:
      case 121:
      case 122:
      case 132:
      case 133:
      case 150:
      case 151:
        return $this->t('The transaction failed at PayPal. Please contact an administrator to resolve this issue.');

      case 50:
      case 51:
        return $this->t('Payment was declined due to insufficient funds or transaction limits. Please check your payment information and try again.');

      case 112:
        return $this->t('Address and Zip code do not match. Please check your payment information and try again.');

      case 114:
        return $this->t('Card Security Code (CSC) does not match. Please check your payment information and try again.');

      case 117:
      case 125:
      case 127:
      case 128:
        return $this->t('Payment was declined due to merchant fraud settings. Please contact an administrator to resolve this issue.');

      case 126:
        return $this->t('Payment was flagged for review by the merchant. We will validate the payment and update your order as soon as possible.');
    }

    return $this->t('Unknown result code.');
  }

  /**
   * Checks whether the given payment can be referenced.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment to reference.
   *
   * @return bool
   *   Result.
   */
  private function canReferencePayment(PaymentInterface $payment) {
    // Return FALSE if the payment isn't valid for reference transactions:
    // Sale, Authorization, Delayed Capture, Void, or Credit. This list includes
    // both the Payflow Link codes and Express Checkout statuses.
    $supported_states = [
      'S',
      'A',
      'D',
      'V',
      'C',
      'Pending',
      'Completed',
      'Voided',
      'Refunded',
    ];

    if (!in_array($payment->getRemoteState(), $supported_states)) {
      return FALSE;
    }

    // Return FALSE if it is more than 365 days since the original transaction.
    if ($payment->getCompletedTime() &&
      $payment->getCompletedTime() < strtotime('-365 days')) {
      return FALSE;
    }

    // Return FALSE if the payment gateway's instance does not have reference
    // transaction support enabled.
    if (empty($this->getConfiguration()['reference_transactions'])) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function createHostedCheckoutIframe(OrderInterface $order) {
    return '<iframe src="' . $this->getRedirectUrl($order) . '" name="embedded-payflow-link" scrolling="no" frameborder="0" width="490px" height="565px"></iframe>';
  }

}
