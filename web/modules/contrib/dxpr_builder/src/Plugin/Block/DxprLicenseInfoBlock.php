<?php

namespace Drupal\dxpr_builder\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\dxpr_builder\Service\DxprBuilderLicenseServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides the license info block.
 *
 * This block shows the number of seats used and available.
 *
 * @Block(
 *   id = "license_info",
 *   category = @Translation("DXPR builder"),
 *   admin_label = @Translation("License info")
 * )
 */
class DxprLicenseInfoBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The DXPR license service.
   *
   * @var \Drupal\dxpr_builder\Service\DxprBuilderLicenseServiceInterface
   */
  protected $license;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Block constructor.
   *
   * @param mixed[] $configuration
   *   Block configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed[] $plugin_definition
   *   Plugin definitions.
   * @param \Drupal\dxpr_builder\Service\DxprBuilderLicenseServiceInterface $license
   *   The DXPR license service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    array $plugin_definition,
    DxprBuilderLicenseServiceInterface $license,
    RequestStack $requestStack
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->license = $license;
    $this->requestStack = $requestStack;
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<mixed> $configuration
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('dxpr_builder.license_service'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param \Drupal\Core\Session\AccountInterface $account
   */
  protected function blockAccess(AccountInterface $account) {
    // Check if the user is a billable user
    // or has the 'Administer site configuration' permission.
    return AccessResult::allowedIf(
      $this->license->isBillableUser($account)
      || $account->hasPermission('Administer site configuration')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @return mixed[]
   *   Build array with licenses information.
   */
  public function build(): array {
    $info = $this->license->getLicenseInfo();
    if ($info) {
      $request = $this->requestStack->getCurrentRequest();
      $current_path = $request->getPathInfo();
      $more_info_link = Url::fromRoute('dxpr_builder.user_licenses')->toString();
      return [
        '#cache' => [
          'max-age' => 0,
        ],
        '#theme' => 'dxpr-license-info',
        '#block_label' => $this->t('DXPR User Licensing'),
        '#total_label' => $this->t('DXPR Builder users'),
        '#total_count' => $info['users_count'],
        '#used_label' => $this->t('Licenses used'),
        '#used_count' => min(intval($info['users_count']), intval($info['users_limit'])),
        '#limit' => $info['users_limit'],
        // Only show more link when not already on this page.
        '#more_info_link' => $current_path == $more_info_link ? NULL : $more_info_link,
        '#attached' => [
          'library' => ['dxpr_builder/user-licenses'],
        ],
      ];
    }
    else {
      return [
        '#cache' => [
          'max-age' => 0,
        ],
      ];
    }
  }

}
