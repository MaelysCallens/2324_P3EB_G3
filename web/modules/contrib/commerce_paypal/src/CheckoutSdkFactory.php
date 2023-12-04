<?php

namespace Drupal\commerce_paypal;

use Drupal\commerce_order\AdjustmentTransformerInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\State\StateInterface;
use GuzzleHttp\HandlerStack;
use Sainsburys\Guzzle\Oauth2\Middleware\OAuthMiddleware;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Defines a factory for our custom PayPal checkout SDK.
 */
class CheckoutSdkFactory implements CheckoutSdkFactoryInterface {

  /**
   * The core client factory.
   *
   * @var \Drupal\Core\Http\ClientFactory
   */
  protected $clientFactory;

  /**
   * The handler stack.
   *
   * @var \GuzzleHttp\HandlerStack
   */
  protected $stack;

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
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Array of all instantiated PayPal Checkout SDKs.
   *
   * @var \Drupal\commerce_paypal\CheckoutSdkInterface[]
   */
  protected $instances = [];

  /**
   * Constructs a new CheckoutSdkFactory object.
   *
   * @param \Drupal\Core\Http\ClientFactory $client_factory
   *   The client factory.
   * @param \GuzzleHttp\HandlerStack $stack
   *   The handler stack.
   * @param \Drupal\commerce_order\AdjustmentTransformerInterface $adjustment_transformer
   *   The adjustment transformer.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time.
   */
  public function __construct(ClientFactory $client_factory, HandlerStack $stack, AdjustmentTransformerInterface $adjustment_transformer, EventDispatcherInterface $event_dispatcher, ModuleHandlerInterface $module_handler, StateInterface $state, TimeInterface $time) {
    $this->clientFactory = $client_factory;
    $this->stack = $stack;
    $this->adjustmentTransformer = $adjustment_transformer;
    $this->eventDispatcher = $event_dispatcher;
    $this->moduleHandler = $module_handler;
    $this->state = $state;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public function get(array $configuration) {
    $client_id = $configuration['client_id'];
    if (!isset($this->instances[$client_id])) {
      $client = $this->getClient($configuration);
      $this->instances[$client_id] = new CheckoutSdk($client, $this->adjustmentTransformer, $this->eventDispatcher, $this->moduleHandler, $this->time, $configuration);
    }

    return $this->instances[$client_id];
  }

  /**
   * Gets a preconfigured HTTP client instance for use by the SDK.
   *
   * @param array $config
   *   The config for the client.
   *
   * @return \GuzzleHttp\Client
   *   The API client.
   */
  protected function getClient(array $config) {
    switch ($config['mode']) {
      case 'live':
        $base_uri = 'https://api.paypal.com';
        break;

      case 'test':
      default:
        $base_uri = 'https://api.sandbox.paypal.com';
        break;
    }
    $attribution_id = (isset($config['payment_solution']) && $config['payment_solution'] == 'custom_card_fields') ? 'Centarro_Commerce_PCP' : 'CommerceGuys_Cart_SPB';
    $options = [
      'base_uri' => $base_uri,
      'headers' => [
        'PayPal-Partner-Attribution-Id' => $attribution_id,
      ],
    ];
    $client = $this->clientFactory->fromOptions($options);
    // Generates a key for storing the OAuth2 token retrieved from PayPal.
    // This is useful in case multiple PayPal checkout gateway instances are
    // configured.
    $token_key = 'commerce_paypal.oauth2_token.' . md5($config['client_id'] . $config['secret']);
    $config = [
      ClientCredentials::CONFIG_CLIENT_ID => $config['client_id'],
      ClientCredentials::CONFIG_CLIENT_SECRET => $config['secret'],
      ClientCredentials::CONFIG_TOKEN_URL => '/v1/oauth2/token',
      'token_key' => $token_key,
    ];
    $grant_type = new ClientCredentials($client, $config, $this->state);
    $middleware = new OAuthMiddleware($client, $grant_type);
    // Check if we've already requested an OAuth2 token, note that we do not
    // need to check for the expires timestamp here as the middleware is already
    // taking care of that.
    $token = $this->state->get($token_key);
    if (!empty($token)) {
      $middleware->setAccessToken($token['token'], 'client_credentials', $token['expires']);
    }
    $this->stack->push($middleware->onBefore());
    $this->stack->push($middleware->onFailure(2));
    $options['handler'] = $this->stack;
    $options['auth'] = 'oauth2';
    return $this->clientFactory->fromOptions($options);
  }

}
