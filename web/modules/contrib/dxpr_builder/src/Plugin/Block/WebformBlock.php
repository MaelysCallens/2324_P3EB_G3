<?php

namespace Drupal\dxpr_builder\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\webform\Entity\Webform;

/**
 * Provides a 'Webform' block.
 *
 * @Block(
 *   id = "dxpr_builder_webform",
 *   category = @Translation("DXPR builder"),
 *   deriver = "Drupal\dxpr_builder\Plugin\Block\WebformBlockDeriver"
 * )
 */
class WebformBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load($this->getDerivativeId());
    /* @phpstan-ignore-next-line */
    if (!$webform || !$webform->access('submission_create', $account)) {
      return AccessResult::forbidden();
    }
    return parent::blockAccess($account);
  }

  /**
   * {@inheritdoc}
   *
   * @return array
   *   Build array with webform.
   *
   * @phpstan-return array<string, mixed>
   */
  public function build(): array {
    $build = [];
    if (Webform::load($this->getDerivativeId())) {
      $build = [
        '#type' => 'webform',
        '#webform' => $this->getDerivativeId(),
        '#default_data' => [],
      ];
    }
    return $build;
  }

}
