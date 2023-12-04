<?php

namespace Drupal\commerce_paypal\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_paypal\Event\ExpressCheckoutRequestEvent;
use Drupal\commerce_paypal\Event\PayPalEvents;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_price\Price;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Provides the Paypal Express Checkout payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "paypal_express_checkout",
 *   label = @Translation("PayPal - Express Checkout [LEGACY-DEPRECATED]"),
 *   display_label = @Translation("PayPal"),
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_paypal\PluginForm\ExpressCheckoutForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "dinersclub", "discover", "jcb", "maestro", "mastercard", "visa", "unionpay"
 *   },
 *   requires_billing_information = FALSE,
 * )
 */
class ExpressCheckout extends OffsitePaymentGatewayBase implements ExpressCheckoutInterface {

  // Shipping address collection options.
  const SHIPPING_ASK_ALWAYS = 'shipping_ask_always';
  const SHIPPING_ASK_NOT_PRESENT = 'shipping_ask_not_present';
  const SHIPPING_SKIP = 'shipping_skip';

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
   * The price rounder.
   *
   * @var \Drupal\commerce_price\RounderInterface
   */
  protected $rounder;

  /**
   * The time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The IPN handler.
   *
   * @var \Drupal\commerce_paypal\IPNHandlerInterface
   */
  protected $ipnHandler;

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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->logger = $container->get('logger.channel.commerce_paypal');
    $instance->httpClient = $container->get('http_client');
    $instance->rounder = $container->get('commerce_price.rounder');
    $instance->ipnHandler = $container->get('commerce_paypal.ipn_handler');
    $instance->moduleHandler = $container->get('module_handler');
    $instance->eventDispatcher = $container->get('event_dispatcher');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'api_username' => '',
      'api_password' => '',
      'shipping_prompt' => self::SHIPPING_SKIP,
      'signature' => '',
      'solution_type' => 'Mark',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['api_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Username'),
      '#default_value' => $this->configuration['api_username'],
      '#required' => TRUE,
    ];
    $form['api_password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Password'),
      '#default_value' => $this->configuration['api_password'],
      '#required' => TRUE,
    ];
    $form['signature'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Signature'),
      '#default_value' => $this->configuration['signature'],
      '#required' => TRUE,
    ];

    $form['solution_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Type of checkout flow'),
      '#description' => $this->t('Express Checkout Account Optional (ECAO) where PayPal accounts are not required for payment may not be available in all markets.'),
      '#options' => [
        'Mark' => $this->t('Require a PayPal account (this is the standard configuration).'),
        'SoleLogin' => $this->t('Allow PayPal AND credit card payments, defaulting to the PayPal form.'),
        'SoleBilling' => $this->t('Allow PayPal AND credit card payments, defaulting to the credit card form.'),
      ],
      '#default_value' => $this->configuration['solution_type'],
    ];

    $form['shipping_prompt'] = [
      '#type' => 'radios',
      '#title' => $this->t('Shipping address collection'),
      '#description' => $this->t('Express Checkout will only request a shipping address if the Shipping module is enabled to store the address in the order.'),
      '#options' => [
        self::SHIPPING_SKIP => $this->t('Do not ask for a shipping address at PayPal.'),
      ],
      '#default_value' => $this->configuration['shipping_prompt'],
    ];

    if ($this->moduleHandler->moduleExists('commerce_shipping')) {
      $form['shipping_prompt']['#options'] += [
        self::SHIPPING_ASK_NOT_PRESENT => $this->t('Ask for a shipping address at PayPal if the order does not have one yet.'),
        self::SHIPPING_ASK_ALWAYS => $this->t('Ask for a shipping address at PayPal even if the order already has one.'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    if (!$form_state->getErrors() && $form_state->isSubmitted()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['api_username'] = $values['api_username'];
      $this->configuration['api_password'] = $values['api_password'];
      $this->configuration['signature'] = $values['signature'];
      $this->configuration['solution_type'] = $values['solution_type'];
      $this->configuration['mode'] = $values['mode'];

      $response = $this->doRequest([
        'METHOD' => 'GetBalance',
      ]);

      if ($response['ACK'] != 'Success') {
        $form_state->setError($form, $this->t('Invalid API credentials.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['api_username'] = $values['api_username'];
      $this->configuration['api_password'] = $values['api_password'];
      $this->configuration['signature'] = $values['signature'];
      $this->configuration['solution_type'] = $values['solution_type'];
      $this->configuration['shipping_prompt'] = $values['shipping_prompt'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    $order_express_checkout_data = $order->getData('paypal_express_checkout');
    if (empty($order_express_checkout_data['token'])) {
      throw new PaymentGatewayException('Token data missing for this PayPal Express Checkout transaction.');
    }

    // GetExpressCheckoutDetails API Operation (NVP).
    // Shows information about an Express Checkout transaction.
    $paypal_response = $this->getExpressCheckoutDetails($order);

    // If the request failed, exit now with a failure message.
    if ($paypal_response['ACK'] == 'Failure') {
      throw new PaymentGatewayException($paypal_response['PAYMENTREQUESTINFO_0_LONGMESSAGE'], $paypal_response['PAYMENTREQUESTINFO_n_ERRORCODE']);
    }

    // Set the Payer ID used to finalize payment.
    $order_express_checkout_data['payerid'] = $paypal_response['PAYERID'];

    // Note: There is no need to save the order here, because it will be
    // saved by the Commerce PaymentController after onReturn() completes.
    $order->setData('paypal_express_checkout', $order_express_checkout_data);
    // If the user is anonymous, add their PayPal e-mail to the order.
    if (empty($order->mail)) {
      $order->setEmail($paypal_response['EMAIL']);
    }

    // DoExpressCheckoutPayment API Operation (NVP).
    // Completes an Express Checkout transaction.
    $paypal_response = $this->doExpressCheckoutDetails($order);

    // Nothing to do for failures for now - no payment saved.
    if (isset($paypal_response['PAYMENTINFO_0_PAYMENTSTATUS']) && $paypal_response['PAYMENTINFO_0_PAYMENTSTATUS'] == 'Failed') {
      throw new PaymentGatewayException($paypal_response['PAYMENTINFO_0_LONGMESSAGE'], $paypal_response['PAYMENTINFO_0_ERRORCODE']);
    }

    if ($paypal_response['ACK'] == 'Failure') {
      // When a buyer's funding source fails, the DoExpressCheckoutPayment and
      // DoAuthorization call, a 10486 error is returned.
      // @link https://developer.paypal.com/docs/classic/express-checkout/ht_ec_fundingfailure10486/
      if (isset($paypal_response['L_ERRORCODE0']) && $paypal_response['L_ERRORCODE0'] == "10486") {
        $message = $paypal_response['L_LONGMESSAGE0'];
        throw new PaymentGatewayException("$message Express Checkout payment failed due to a bad funding source; it is possible that the transaction exceeded the buyer's card limit.", $paypal_response['L_ERRORCODE0']);
      }

      throw new PaymentGatewayException($paypal_response['L_LONGMESSAGE0'], $paypal_response['L_ERRORCODE0']);
    }

    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    $payment = $payment_storage->create([
      'state' => 'authorization',
      'amount' => $order->getTotalPrice(),
      'payment_gateway' => $this->entityId,
      'order_id' => $order->id(),
      'remote_id' => $paypal_response['PAYMENTINFO_0_TRANSACTIONID'],
      'remote_state' => $paypal_response['PAYMENTINFO_0_PAYMENTSTATUS'],
    ]);
    $status_mapping = $this->getStatusMapping();

    if (isset($status_mapping[$paypal_response['PAYMENTINFO_0_PAYMENTSTATUS']])) {
      $payment->setState($status_mapping[$paypal_response['PAYMENTINFO_0_PAYMENTSTATUS']]);
    }

    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function capturePayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->assertPaymentState($payment, ['authorization']);
    // If not specified, capture the entire amount.
    $amount = $amount ?: $payment->getAmount();
    $amount = $this->rounder->round($amount);

    // GetExpressCheckoutDetails API Operation (NVP).
    // Shows information about an Express Checkout transaction.
    $paypal_response = $this->doCapture($payment, $amount->getNumber());

    if ($paypal_response['ACK'] == 'Failure') {
      $message = $paypal_response['L_LONGMESSAGE0'];
      throw new PaymentGatewayException($message, $paypal_response['L_ERRORCODE0']);
    }

    $payment->setState('completed');
    $payment->setAmount($amount);
    // Update the remote id for the captured transaction.
    $payment->setRemoteId($paypal_response['TRANSACTIONID']);
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function voidPayment(PaymentInterface $payment) {
    $this->assertPaymentState($payment, ['authorization']);

    // GetExpressCheckoutDetails API Operation (NVP).
    // Shows information about an Express Checkout transaction.
    $paypal_response = $this->doVoid($payment);
    if ($paypal_response['ACK'] == 'Failure') {
      $message = $paypal_response['L_LONGMESSAGE0'];
      throw new PaymentGatewayException($message, $paypal_response['L_ERRORCODE0']);
    }

    $payment->setState('authorization_voided');
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function refundPayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->assertPaymentState($payment, ['completed', 'partially_refunded']);
    // If not specified, refund the entire amount.
    $amount = $amount ?: $payment->getAmount();
    $this->assertRefundAmount($payment, $amount);
    $amount = $this->rounder->round($amount);

    $extra['amount'] = $amount->getNumber();
    // Check if the Refund is partial or full.
    $old_refunded_amount = $payment->getRefundedAmount();
    $new_refunded_amount = $old_refunded_amount->add($amount);
    if ($new_refunded_amount->lessThan($payment->getAmount())) {
      $payment->setState('partially_refunded');
      $extra['refund_type'] = 'Partial';
    }
    else {
      $payment->setState('refunded');
      if ($amount->lessThan($payment->getAmount())) {
        $extra['refund_type'] = 'Partial';
      }
      else {
        $extra['refund_type'] = 'Full';
      }
    }

    // RefundTransaction API Operation (NVP).
    // Refund (full or partial) an Express Checkout transaction.
    $paypal_response = $this->doRefundTransaction($payment, $extra);

    if ($paypal_response['ACK'] == 'Failure') {
      $message = $paypal_response['L_LONGMESSAGE0'];
      throw new PaymentGatewayException($message, $paypal_response['L_ERRORCODE0']);
    }

    $payment->setRefundedAmount($new_refunded_amount);
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function onNotify(Request $request) {
    // Get IPN request data and basic processing for the IPN request.
    $ipn_data = $this->ipnHandler->process($request);

    // Do not perform any processing on EC transactions here that do not have
    // transaction IDs, indicating they are non-payment IPNs such as those used
    // for subscription signup requests.
    if (empty($ipn_data['txn_id'])) {
      $this->logger->alert('The IPN request does not have a transaction id. Ignored.');
      return FALSE;
    }
    // Exit when we don't get a payment status we recognize.
    if (!in_array($ipn_data['payment_status'], ['Voided', 'Pending', 'Completed', 'Refunded'])) {
      throw new BadRequestHttpException('Invalid payment status');
    }
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    $amount = new Price($ipn_data['mc_gross'], $ipn_data['mc_currency']);

    // If this is a prior authorization capture IPN...
    if (in_array($ipn_data['payment_status'], ['Voided', 'Pending', 'Completed']) && !empty($ipn_data['auth_id'])) {
      // Ensure we can load the existing corresponding transaction.
      $payment = $payment_storage->loadByRemoteId($ipn_data['auth_id']);
      // If not, bail now because authorization transactions should be created
      // by the Express Checkout API request itself.
      if (!$payment) {
        $this->logger->warning('IPN for Order @order_number ignored: authorization transaction already created.', ['@order_number' => $ipn_data['invoice']]);
        return FALSE;
      }
      $payment->setAmount($amount);
      $payment->setState($this->getStatusMapping($ipn_data['payment_status']));
      // Update the remote id.
      $payment->setRemoteId($ipn_data['txn_id']);
    }
    elseif ($ipn_data['payment_status'] == 'Refunded') {
      // Get the corresponding parent transaction and refund it.
      $payment = $payment_storage->loadByRemoteId($ipn_data['txn_id']);
      if (!$payment) {
        $this->logger->warning('IPN for Order @order_number ignored: the transaction to be refunded does not exist.', ['@order_number' => $ipn_data['invoice']]);
        return FALSE;
      }
      elseif ($payment->getState() == 'refunded') {
        $this->logger->warning('IPN for Order @order_number ignored: the transaction is already refunded.', ['@order_number' => $ipn_data['invoice']]);
        return FALSE;
      }
      $amount = new Price((string) $ipn_data['mc_gross'], $ipn_data['mc_currency']);
      // Check if the Refund is partial or full.
      $old_refunded_amount = $payment->getRefundedAmount();
      $new_refunded_amount = $old_refunded_amount->add($amount);
      if ($new_refunded_amount->lessThan($payment->getAmount())) {
        $payment->setState('partially_refunded');
      }
      else {
        $payment->setState('refunded');
      }
      $payment->setRefundedAmount($new_refunded_amount);
    }

    if (isset($payment)) {
      $payment->setRemoteState($ipn_data['payment_status']);
      $payment->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectUrl() {
    if ($this->getMode() == 'test') {
      return 'https://www.sandbox.paypal.com/checkoutnow';
    }
    else {
      return 'https://www.paypal.com/checkoutnow';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getApiUrl() {
    if ($this->getMode() == 'test') {
      return 'https://api-3t.sandbox.paypal.com/nvp';
    }
    else {
      return 'https://api-3t.paypal.com/nvp';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setExpressCheckout(PaymentInterface $payment, array $extra) {
    $order = $payment->getOrder();
    $amount = $this->rounder->round($payment->getAmount());
    $configuration = $this->getConfiguration();

    if ($extra['capture']) {
      $payment_action = 'Sale';
    }
    else {
      $payment_action = 'Authorization';
    }

    // Build a name-value pair array for this transaction.
    $nvp_data = [
      'METHOD' => 'SetExpressCheckout',
      // Default the Express Checkout landing page to the Mark solution.
      'SOLUTIONTYPE' => 'Mark',
      'LANDINGPAGE' => 'Login',
      // Disable entering notes in PayPal, we don't have any way to accommodate
      // them right now.
      'ALLOWNOTE' => '0',
      'PAYMENTREQUEST_0_PAYMENTACTION' => $payment_action,
      'PAYMENTREQUEST_0_AMT' => $amount->getNumber(),
      'PAYMENTREQUEST_0_CURRENCYCODE' => $amount->getCurrencyCode(),
      'PAYMENTREQUEST_0_INVNUM' => $order->id() . '-' . $this->time->getCurrentTime(),
      // Set the return and cancel URLs.
      'RETURNURL' => $extra['return_url'],
      'CANCELURL' => $extra['cancel_url'],
    ];

    // Check if there is a reference transaction, and also see if a billing
    // agreement was supplied.
    if (!empty($configuration['reference_transactions']) && !empty($configuration['ba_desc'])) {
      $nvp_data['BILLINGTYPE'] = 'MerchantInitiatedBillingSingleAgreement';
      $nvp_data['L_BILLINGTYPE0'] = 'MerchantInitiatedBillingSingleAgreement';
      $nvp_data['L_BILLINGAGREEMENTDESCRIPTION0'] = $configuration['ba_desc'];
    }

    // If Express Checkout Account Optional is enabled...
    if ($configuration['solution_type'] != 'Mark') {
      // Update the solution type and landing page parameters accordingly.
      $nvp_data['SOLUTIONTYPE'] = 'Sole';

      if ($configuration['solution_type'] == 'SoleBilling') {
        $nvp_data['LANDINGPAGE'] = 'Billing';
      }
    }

    // Add itemized information to the API request.
    $nvp_data += $this->itemizeOrder($order, $amount->getCurrencyCode());

    // If the shipping module is not enabled, or if
    // "Shipping address collection" is configured to not send the address to
    // PayPal, set the NOSHIPPING parameter to 1.
    if ($configuration['shipping_prompt'] == self::SHIPPING_SKIP || !$this->moduleHandler->moduleExists('commerce_shipping')) {
      $nvp_data['NOSHIPPING'] = '1';
    }
    else {
      // Check if the order references shipments.
      if ($order->hasField('shipments') && !$order->get('shipments')->isEmpty()) {
        // Gather the shipping profiles and only send shipping information if
        // there's only one shipping profile referenced by the shipments.
        $shipping_profiles = [];

        // Loop over the shipments to collect shipping profiles.
        foreach ($order->get('shipments')->referencedEntities() as $shipment) {
          if ($shipment->get('shipping_profile')->isEmpty()) {
            continue;
          }
          $shipping_profile = $shipment->getShippingProfile();
          $shipping_profiles[$shipping_profile->id()] = $shipping_profile;
        }

        // Don't send the shipping profile if we found more than one.
        if ($shipping_profiles && count($shipping_profiles) === 1) {
          $shipping_profile = reset($shipping_profiles);
          /** @var \Drupal\address\AddressInterface $address */
          $address = $shipping_profile->address->first();
          $name = $address->getGivenName() . ' ' . $address->getFamilyName();
          $shipping_info = [
            'PAYMENTREQUEST_0_SHIPTONAME' => substr($name, 0, 32),
            'PAYMENTREQUEST_0_SHIPTOSTREET' => substr($address->getAddressLine1(), 0, 100),
            'PAYMENTREQUEST_0_SHIPTOSTREET2' => substr($address->getAddressLine2(), 0, 100),
            'PAYMENTREQUEST_0_SHIPTOCITY' => substr($address->getLocality(), 0, 40),
            'PAYMENTREQUEST_0_SHIPTOSTATE' => substr($address->getAdministrativeArea(), 0, 40),
            'PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE' => $address->getCountryCode(),
            'PAYMENTREQUEST_0_SHIPTOZIP' => substr($address->getPostalCode(), 0, 20),
          ];
          // Filter out empty values.
          $nvp_data += array_filter($shipping_info);

          // Do not prompt for an Address at Paypal.
          if ($configuration['shipping_prompt'] != self::SHIPPING_ASK_ALWAYS) {
            $nvp_data += [
              'NOSHIPPING' => '1',
              'ADDROVERRIDE' => '1',
            ];
          }
          else {
            $nvp_data += [
              'NOSHIPPING' => '0',
              'ADDROVERRIDE' => '0',
            ];
          }
        }
        else {
          $nvp_data['NOSHIPPING'] = '0';
        }
      }
    }

    // Send the order's email if not empty.
    if (!empty($order->getEmail())) {
      $nvp_data['PAYMENTREQUEST_0_EMAIL'] = $order->getEmail();
    }

    // Make the PayPal NVP API request.
    return $this->doRequest($nvp_data, $order);
  }

  /**
   * Returns a name-value pair array of information to the API request.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order entity.
   * @param string $currency_code
   *   The currency code.
   *
   * @return array
   *   A name-value pair array.
   */
  protected function itemizeOrder(OrderInterface $order, $currency_code) {
    $nvp_data = [];
    $n = 0;
    // Calculate the items total.
    $items_total = new Price('0', $currency_code);

    // Add order item data.
    foreach ($order->getItems() as $item) {
      $item_amount = $this->rounder->round($item->getUnitPrice());
      $nvp_data += [
        'L_PAYMENTREQUEST_0_NAME' . $n => $item->getTitle(),
        'L_PAYMENTREQUEST_0_AMT' . $n => $item_amount->getNumber(),
        'L_PAYMENTREQUEST_0_QTY' . $n => $item->getQuantity(),
      ];
      $items_total = $items_total->add($item->getTotalPrice());
      $n++;
    }

    // Initialize Shipping|Tax prices, they need to be sent
    // separately to PayPal.
    $shipping_amount = new Price('0', $currency_code);
    $tax_amount = new Price('0', $currency_code);

    // Collect the adjustments.
    $adjustments = [];
    foreach ($order->collectAdjustments() as $adjustment) {
      // Skip included adjustments.
      if ($adjustment->isIncluded()) {
        continue;
      }
      // Tax & Shipping adjustments need to be handled separately.
      if ($adjustment->getType() == 'shipping') {
        $shipping_amount = $shipping_amount->add($adjustment->getAmount());
      }
      // Add taxes that are not included in the items total.
      elseif ($adjustment->getType() == 'tax') {
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

    foreach ($adjustments as $adjustment) {
      $adjustment_amount = $this->rounder->round($adjustment['total']);
      $nvp_data += [
        'L_PAYMENTREQUEST_0_NAME' . $n => $adjustment['label'],
        'L_PAYMENTREQUEST_0_AMT' . $n => $adjustment_amount->getNumber(),
        'L_PAYMENTREQUEST_0_QTY' . $n => 1,
      ];
      // Add the adjustment to the items total.
      $items_total = $items_total->add($adjustment['total']);
      $n++;
    }

    // Send the items total.
    $items_total = $this->rounder->round($items_total);
    $nvp_data['PAYMENTREQUEST_0_ITEMAMT'] = $items_total->getNumber();

    // Send the shipping amount separately.
    if (!$shipping_amount->isZero()) {
      $shipping_amount = $this->rounder->round($shipping_amount);
      $nvp_data['PAYMENTREQUEST_0_SHIPPINGAMT'] = $shipping_amount->getNumber();
    }

    // Send the tax amount.
    if (!$tax_amount->isZero()) {
      $tax_amount = $this->rounder->round($tax_amount);
      $nvp_data['PAYMENTREQUEST_0_TAXAMT'] = $tax_amount->getNumber();
    }

    return $nvp_data;
  }

  /**
   * {@inheritdoc}
   */
  public function getExpressCheckoutDetails(OrderInterface $order) {
    // Get the Express Checkout order token.
    $order_express_checkout_data = $order->getData('paypal_express_checkout');

    // Build a name-value pair array to obtain buyer information from PayPal.
    $nvp_data = [
      'METHOD' => 'GetExpressCheckoutDetails',
      'TOKEN' => $order_express_checkout_data['token'],
    ];

    // Make the PayPal NVP API request.
    return $this->doRequest($nvp_data, $order);
  }

  /**
   * {@inheritdoc}
   */
  public function doExpressCheckoutDetails(OrderInterface $order) {
    // Build NVP data for PayPal API request.
    $order_express_checkout_data = $order->getData('paypal_express_checkout');
    $amount = $this->rounder->round($order->getTotalPrice());
    if ($order_express_checkout_data['capture']) {
      $payment_action = 'Sale';
    }
    else {
      $payment_action = 'Authorization';
    }
    $nvp_data = [
      'METHOD' => 'DoExpressCheckoutPayment',
      'TOKEN' => $order_express_checkout_data['token'],
      'PAYMENTREQUEST_0_AMT' => $amount->getNumber(),
      'PAYMENTREQUEST_0_CURRENCYCODE' => $amount->getCurrencyCode(),
      'PAYMENTREQUEST_0_INVNUM' => $order->getOrderNumber(),
      'PAYERID' => $order_express_checkout_data['payerid'],
      'PAYMENTREQUEST_0_PAYMENTACTION' => $payment_action,
    ];

    // Make the PayPal NVP API request.
    return $this->doRequest($nvp_data, $order);
  }

  /**
   * {@inheritdoc}
   */
  public function doCapture(PaymentInterface $payment, $amount) {
    $order = $payment->getOrder();

    // Build a name-value pair array for this transaction.
    $nvp_data = [
      'METHOD' => 'DoCapture',
      'AUTHORIZATIONID' => $payment->getRemoteId(),
      'AMT' => $amount,
      'CURRENCYCODE' => $payment->getAmount()->getCurrencyCode(),
      'INVNUM' => $order->getOrderNumber(),
      'COMPLETETYPE' => 'Complete',
    ];

    // Make the PayPal NVP API request.
    return $this->doRequest($nvp_data, $payment->getOrder());
  }

  /**
   * {@inheritdoc}
   */
  public function doVoid(PaymentInterface $payment) {
    // Build a name-value pair array for this transaction.
    $nvp_data = [
      'METHOD' => 'DoVoid',
      'AUTHORIZATIONID' => $payment->getRemoteId(),
    ];

    // Make the PayPal NVP API request.
    return $this->doRequest($nvp_data, $payment->getOrder());

  }

  /**
   * {@inheritdoc}
   */
  public function doRefundTransaction(PaymentInterface $payment, array $extra) {
    // Build a name-value pair array for this transaction.
    $nvp_data = [
      'METHOD' => 'RefundTransaction',
      'TRANSACTIONID' => $payment->getRemoteId(),
      'REFUNDTYPE' => $extra['refund_type'],
      'AMT' => $extra['amount'],
      'CURRENCYCODE' => $payment->getAmount()->getCurrencyCode(),
    ];

    // Make the PayPal NVP API request.
    return $this->doRequest($nvp_data, $payment->getOrder());
  }

  /**
   * {@inheritdoc}
   */
  public function doRequest(array $nvp_data, OrderInterface $order = NULL) {
    // Add the default name-value pairs to the array.
    $configuration = $this->getConfiguration();
    $nvp_data += [
      // API credentials.
      'USER' => $configuration['api_username'],
      'PWD' => $configuration['api_password'],
      'SIGNATURE' => $configuration['signature'],
      'VERSION' => '124.0',
    ];

    // Allow modules to alter the NVP request.
    $event = new ExpressCheckoutRequestEvent($nvp_data, $order);
    $this->eventDispatcher->dispatch($event, PayPalEvents::EXPRESS_CHECKOUT_REQUEST);
    $nvp_data = $event->getNvpData();
    // Make PayPal request.
    $request = $this->httpClient->post($this->getApiUrl(), [
      'form_params' => $nvp_data,
    ])->getBody()
      ->getContents();

    parse_str(html_entity_decode($request), $paypal_response);

    return $paypal_response;
  }

  /**
   * Returns a mapping of PayPal payment statuses to payment states.
   *
   * @param string $status
   *   (optional) The PayPal payment status.
   *
   * @return array|string
   *   An array containing the PayPal remote statuses as well as their
   *   corresponding states. if $status is specified, the corresponding state
   *   is returned.
   */
  protected function getStatusMapping($status = NULL) {
    $mapping = [
      'Voided' => 'authorization_voided',
      'Pending' => 'authorization',
      'Processed' => 'completed',
      'Completed' => 'completed',
      'Refunded' => 'refunded',
      'Partially-Refunded' => 'partially_refunded',
      'Expired' => 'authorization_expired',
    ];

    // If a status was passed, return its corresponding payment state.
    if (isset($status) && isset($mapping[$status])) {
      return $mapping[$status];
    }

    return $mapping;
  }

}
