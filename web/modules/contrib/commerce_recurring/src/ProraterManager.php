<?php

namespace Drupal\commerce_recurring;

use Drupal\commerce_recurring\Annotation\CommerceProrater;
use Drupal\commerce_recurring\Plugin\Commerce\Prorater\ProraterInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages discovery and instantiation of prorater plugins.
 *
 * @see \Drupal\commerce_recurring\Annotation\CommerceProrater
 * @see plugin_api
 */
class ProraterManager extends DefaultPluginManager {

  /**
   * Constructs a new ProraterManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/Commerce/Prorater', $namespaces, $module_handler, ProraterInterface::class, CommerceProrater::class
    );

    $this->alterInfo('commerce_prorater_info');
    $this->setCacheBackend($cache_backend, 'commerce_prorater_plugins');
  }

}
