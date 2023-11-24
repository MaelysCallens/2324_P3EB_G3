<?php

namespace Drupal\dxpr_builder\Menu;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Menu\MenuActiveTrail;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Routing\AccessAwareRouterInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Overrides the MenuActiveTrail class.
 */
class MenuActiveTrailOverride extends MenuActiveTrail {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * The router.
   *
   * @var \Drupal\Core\Routing\AccessAwareRouterInterface
   */
  protected $router;

  /**
   * Constructs a MenuActiveTrailOverride object.
   *
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   *   The menu link plugin manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   A route match object for finding the active link.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock backend.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Routing\AccessAwareRouterInterface $router
   *   The router.
   */
  public function __construct(MenuLinkManagerInterface $menu_link_manager, RouteMatchInterface $route_match, CacheBackendInterface $cache, LockBackendInterface $lock, RequestStack $request_stack, AccessAwareRouterInterface $router) {
    parent::__construct($menu_link_manager, $route_match, $cache, $lock);
    $this->requestStack = $request_stack;
    $this->router = $router;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveLink($menu_name = NULL) {
    $request = $this->requestStack->getCurrentRequest();
    $path = $request->getPathInfo();
    $data = $request->get('data', NULL);

    // Implement custom logic for our ajax handler.
    if ($path === '/dxpr_builder/ajax' && is_array($data) && isset($data['originalPath'])) {
      $match = $this->router->match($data['originalPath']);
      $route_name = $match['_route'];
      $route_parameters = $match['_raw_variables']->all();

      $links = $this->menuLinkManager
        ->loadLinksByRoute($route_name, $route_parameters, $menu_name);
      if ($links) {
        // Return the first matched link.
        return reset($links);
      }

      return NULL;
    }

    return parent::getActiveLink($menu_name);
  }

}
