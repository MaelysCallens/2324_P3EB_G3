<?php

namespace Drupal\commerce_recurring;

use Drupal\commerce_recurring\Annotation\CommerceSubscriptionType;
use Drupal\commerce_recurring\Plugin\Commerce\SubscriptionType\SubscriptionTypeInterface;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages discovery and instantiation of subscription type plugins.
 *
 * @see \Drupal\commerce_recurring\Annotation\CommerceSubscriptionType
 * @see plugin_api
 */
class SubscriptionTypeManager extends DefaultPluginManager {

  /**
   * Constructs a new SubscriptionTypeManager object.
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
    parent::__construct('Plugin/Commerce/SubscriptionType', $namespaces, $module_handler, SubscriptionTypeInterface::class, CommerceSubscriptionType::class);

    $this->alterInfo('commerce_subscription_type_info');
    $this->setCacheBackend($cache_backend, 'commerce_subscription_type_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    foreach (['id', 'label'] as $required_property) {
      if (empty($definition[$required_property])) {
        throw new PluginException(sprintf('The subscription type "%s" must define the "%s" property.', $plugin_id, $required_property));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function findDefinitions() {
    $definitions = parent::findDefinitions();
    // The module ships a subscription type dependent on commerce_product
    // but doesn't depend on commerce_product.
    if (!$this->moduleHandler->moduleExists('commerce_product')) {
      unset($definitions['product_variation']);
    }
    return $definitions;
  }

}
