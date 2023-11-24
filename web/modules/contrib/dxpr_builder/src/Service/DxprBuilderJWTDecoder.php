<?php

namespace Drupal\dxpr_builder\Service;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Class to decode JSON Web Token and cache the result.
 */
class DxprBuilderJWTDecoder {

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   */
  public function __construct(CacheBackendInterface $cache_backend) {
    $this->cache = $cache_backend;
  }

  /**
   * Decodes jwt token.
   *
   * @param string $access_token
   *   The token.
   *
   * @return mixed[]
   *   The token properties.
   */
  public function decodeJwt(string $access_token) {
    $cid = 'config:dxpr_builder.settings';
    $data = $this->cache->get($cid);
    if (!$data) {
      $access_token_parts = explode('.', $access_token);
      if (!empty($access_token) && count($access_token_parts) >= 2) {
        $decoded_part = base64_decode(str_replace('_', '/', str_replace('-', '+', $access_token_parts[1])));
        $decodedJWT = json_decode($decoded_part);
      }
      else {
        $decodedJWT = NULL;
      }

      if (!$decodedJWT) {
        $decodedJWTResult = [
          'sub' => NULL,
          'scope' => NULL,
          'dxpr_tier' => NULL,
        ];
      }
      else {
        $decodedJWTResult = [
          'sub' => $decodedJWT->sub,
          'scope' => $decodedJWT->scope,
          'dxpr_tier' => $decodedJWT->dxpr_tier,
        ];
      }
      $cache_tags = ['config:dxpr_builder.settings'];
      $this->cache->set($cid, $decodedJWTResult, Cache::PERMANENT, $cache_tags);
    }
    else {
      $decodedJWTResult = $data->data;
    }
    return $decodedJWTResult;
  }

}
