<?php

namespace Drupal\commerce_recurring\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Subscription routes.
 */
class SubscriptionController extends ControllerBase {

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Constructs a new SubscriptionController.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   */
  public function __construct(EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * Displays the subscription type administration page.
   *
   * @return array
   *   A render array.
   */
  public function adminOverview() {
    $header = [
      $this->t('Subscription type'),
      $this->t('Operations'),
    ];

    $rows = [];
    $bundles = $this->entityTypeBundleInfo->getBundleInfo('commerce_subscription');
    foreach ($bundles as $bundle_name => $bundle_info) {
      $route_parameters = ['bundle' => $bundle_name];
      $links = [
        'manage-fields' => [
          'title' => $this->t('Manage fields'),
          'url' => Url::fromRoute('entity.commerce_subscription.field_ui_fields', $route_parameters),
        ],
        'manage-form-display' => [
          'title' => $this->t('Manage form display'),
          'url' => Url::fromRoute('entity.entity_form_display.commerce_subscription.default', $route_parameters),
        ],
        'manage-display' => [
          'title' => $this->t('Manage display'),
          'url' => Url::fromRoute('entity.entity_view_display.commerce_subscription.default', $route_parameters),
        ],
      ];
      $rows[] = [
        $bundle_info['label'],
        [
          'data' => [
            '#type' => 'operations',
            '#links' => $links,
          ],
        ],
      ];
    }

    $build['subscription_types'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];

    return $build;
  }

}
