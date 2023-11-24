<?php

namespace Drupal\dxpr_builder;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Provide overrides for core services.
 */
class DxprBuilderServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container): void {
    $definition = $container->getDefinition('menu.active_trail');
    $definition->setClass('Drupal\dxpr_builder\Menu\MenuActiveTrailOverride');
    $definition->setArguments(
      [
        new Reference('plugin.manager.menu.link'),
        new Reference('current_route_match'),
        new Reference('cache.menu'),
        new Reference('lock'),
        new Reference('request_stack'),
        new Reference('router'),
      ]
    );
  }

}
