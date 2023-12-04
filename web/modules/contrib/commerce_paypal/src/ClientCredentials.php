<?php

namespace Drupal\commerce_paypal;

use Drupal\Core\State\StateInterface;
use GuzzleHttp\ClientInterface;
use Sainsburys\Guzzle\Oauth2\GrantType\ClientCredentials as BaseClientCredentials;

/**
 * Client credentials grant type.
 */
class ClientCredentials extends BaseClientCredentials {

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new ClientCredentials object.
   *
   * @param \GuzzleHttp\ClientInterface $client
   *   The client.
   * @param array $config
   *   The configuration.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(ClientInterface $client, array $config, StateInterface $state) {
    parent::__construct($client, $config);

    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function getToken() {
    $token = parent::getToken();

    // Store the token retrieved for later reuse (to make sure we don't request
    // for a new one on each API request).
    $this->state->set($this->config['token_key'], [
      'token' => $token->getToken(),
      'expires' => $token->getExpires()->getTimestamp(),
    ]);

    return $token;
  }

}
