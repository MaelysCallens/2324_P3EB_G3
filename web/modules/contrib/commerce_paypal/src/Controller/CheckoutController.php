<?php

namespace Drupal\commerce_paypal\Controller;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentGatewayInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_paypal\CheckoutSdkFactoryInterface;
use Drupal\commerce_paypal\Plugin\Commerce\PaymentGateway\CheckoutInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Access\AccessException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use GuzzleHttp\Exception\BadResponseException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * PayPal checkout controller.
 */
class CheckoutController extends ControllerBase {

  /**
   * The PayPal Checkout SDK factory.
   *
   * @var \Drupal\commerce_paypal\CheckoutSdkFactoryInterface
   */
  protected $checkoutSdkFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a PayPalCheckoutController object.
   *
   * @param \Drupal\commerce_paypal\CheckoutSdkFactoryInterface $checkout_sdk_factory
   *   The PayPal Checkout SDK factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(CheckoutSdkFactoryInterface $checkout_sdk_factory, EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger, MessengerInterface $messenger) {
    $this->checkoutSdkFactory = $checkout_sdk_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_paypal.checkout_sdk_factory'),
      $container->get('entity_type.manager'),
      $container->get('logger.channel.commerce_paypal'),
      $container->get('messenger')
    );
  }

  /**
   * Create/update the order in PayPal.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $commerce_order
   *   The order.
   * @param \Drupal\commerce_payment\Entity\PaymentGatewayInterface $commerce_payment_gateway
   *   The payment gateway.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A response.
   */
  public function onCreate(OrderInterface $commerce_order, PaymentGatewayInterface $commerce_payment_gateway, Request $request) {
    if (!$commerce_payment_gateway->getPlugin() instanceof CheckoutInterface) {
      throw new AccessException('Invalid payment gateway provided.');
    }
    $config = $commerce_payment_gateway->getPluginConfiguration();
    $sdk = $this->checkoutSdkFactory->get($config);
    try {
      $address = NULL;
      $body = [];
      if ($request->getContent()) {
        $body = Json::decode($request->getContent());
        // Check if a billing profile or an address was sent.
        // When this route is called in the context of the Custom card fields, the
        // form is not yet submitted which means the billing profile is not yet
        // associated with the order, so we extract it from the DOM and pass it
        // to the controller.
        // If we don't do that, then the "payer" will be considered as anonymous
        // by PayPal.
        $address = $this->extractAddress($commerce_order, $request);
      }
      $commerce_order->set('payment_gateway', $commerce_payment_gateway);
      $commerce_order->setData('paypal_checkout_flow', $body['flow'] ?? 'mark');
      $response = $sdk->createOrder($commerce_order, $address);
      $paypal_order = Json::decode($response->getBody()->getContents());
      $commerce_order->setData('paypal_order_id', $paypal_order['id']);
      $commerce_order->setRefreshState(OrderInterface::REFRESH_SKIP);
      $commerce_order->save();

      return new JsonResponse(['id' => $paypal_order['id']]);
    }
    catch (BadResponseException $exception) {
      $this->logger->error($exception->getResponse()->getBody()->getContents());
      $message = $this->t('Payment failed. Please review your information and try again.');
      return new JsonResponse(['message' => $message], Response::HTTP_BAD_REQUEST);
    }
    catch (\Exception $exception) {
      $this->logger->error($exception->getMessage());
      $message = $this->t('Payment failed. Please review your information and try again.');
      return new JsonResponse(['message' => $message], Response::HTTP_BAD_REQUEST);
    }
  }

  /**
   * React to the PayPal checkout "onApprove" JS SDK callback.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A response.
   */
  public function onApprove(RouteMatchInterface $route_match, Request $request) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $route_match->getParameter('commerce_order');
    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
    $payment_gateway = $route_match->getParameter('commerce_payment_gateway');
    $payment_gateway_plugin = $payment_gateway->getPlugin();
    if (!$payment_gateway_plugin instanceof CheckoutInterface) {
      throw new AccessException('Unsupported payment gateway provided.');
    }
    try {
      // Note that we're using a custom route instead of the payment return
      // one since the payment return callback cannot be called from the cart
      // page.
      $payment_gateway_plugin->onReturn($order, $request);
      $step_id = $order->get('checkout_step')->value;

      // Redirect to the next checkout step if the current checkout step is
      // known, which isn't the case when in the "shortcut" flow.
      if (!empty($step_id)) {
        /** @var \Drupal\commerce_checkout\Entity\CheckoutFlowInterface $checkout_flow */
        $checkout_flow = $order->get('checkout_flow')->entity;
        $checkout_flow_plugin = $checkout_flow->getPlugin();
        $step_id = $checkout_flow_plugin->getNextStepId($step_id);
        $order->set('checkout_step', $step_id);
      }
      $order->save();
      $redirect_url = Url::fromRoute('commerce_checkout.form', [
        'commerce_order' => $order->id(),
        'step' => $step_id,
      ])->toString();

      return new JsonResponse(['redirectUrl' => $redirect_url]);
    }
    catch (PaymentGatewayException $e) {
      // When the payment fails, we don't instruct the JS to redirect, the page
      // will display the error.
      $this->logger->error($e->getMessage());
      $message = $this->t('Payment failed at the payment server. Please review your information and try again.');
      return new JsonResponse(['message' => $message], Response::HTTP_BAD_REQUEST);
    }
    catch (\Exception $exception) {
      $this->logger->error($exception->getMessage());
      $message = $this->t('Payment failed. Please review your information and try again.');
      return new JsonResponse(['message' => $message], Response::HTTP_BAD_REQUEST);
    }
  }

  /**
   * Extracts the billing address from the request body.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Drupal\address\AddressInterface|null
   *   The address, NULL if empty.
   */
  protected function extractAddress(OrderInterface $order, Request $request) {
    $body = Json::decode($request->getContent());

    // If the "profile copy" checkbox is checked, attempt to use the
    // shipping profile as the source of the address.
    if (!empty($body['profileCopy'])) {
      $profiles = $order->collectProfiles();

      if (isset($profiles['shipping']) && !$profiles['shipping']->get('address')->isEmpty()) {
        return $profiles['shipping']->get('address')->first();
      }
    }
    /** @var \Drupal\profile\ProfileStorageInterface $profile_storage */
    $profile_storage = $this->entityTypeManager->getStorage('profile');
    if (!empty($body['profile'])) {
      // When "_original" is passed, attempt to load/use the default profile.
      if ($body['profile'] === '_original') {
        $profile = $profile_storage->loadByUser($order->getCustomer(), 'customer');
      }
      else {
        $profile = $profile_storage->load($body['profile']);
      }

      if (($profile && $profile->access('view')) && !$profile->get('address')->isEmpty()) {
        return $profile->get('address')->first();
      }
    }
    elseif (!empty($body['address'])) {
      $profile = $profile_storage->create([
        'type' => 'customer',
        'address' => $body['address'],
      ]);
      return $profile->get('address')->first();
    }

    return NULL;
  }

}
