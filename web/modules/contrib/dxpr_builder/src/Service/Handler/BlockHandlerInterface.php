<?php

namespace Drupal\dxpr_builder\Service\Handler;

use Drupal\Core\Asset\AttachedAssets;

/**
 * Description.
 */
interface BlockHandlerInterface {

  /**
   * Generate a block given it's module and delta.
   *
   * @param mixed[] $blockInfo
   *   An array of info providing information on how the
   *   block should be loaded. Keys:
   *   - type: Will always be block
   *   - provider: Either 'content_block' or 'plugin',
   *   depending on the block type
   *   - uuid (content_block only): The UUID of the content block
   *   - id (plugin block only): The ID of the plugin.
   * @param string $configuration
   *   Block configuration.
   * @param \Drupal\Core\Asset\AttachedAssets $assets
   *   Any retrieved libraries and/or settings should be attached to this.
   * @param mixed[] $data
   *   Element data.
   *
   * @return string
   *   The HTML of the retrieved block
   */
  public function getBlock(array $blockInfo, string $configuration, AttachedAssets $assets, array $data);

  /**
   * Checks block access.
   *
   * @param string $block_id
   *   The id of the block.
   * @param mixed[] $definition
   *   Block definition array.
   *
   * @return bool|\Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function blockAccess($block_id, array $definition);

}
