<?php

namespace Drupal\dxpr_builder\Service\Handler;

use Drupal\Core\Asset\AttachedAssets;

/**
 * Description.
 */
interface ViewHandlerInterface {

  /**
   * Retrieve a view for a given ID.
   *
   * @param string $viewId
   *   The view ID.
   * @param string $exp_input
   *   The input.
   * @param string $displayId
   *   The ID of the view to retrieve.
   * @param mixed[] $data
   *   The data.
   * @param \Drupal\Core\Asset\AttachedAssets $assets
   *   Any retrieved libraries and/or settings should be attached to this.
   *
   * @return string|bool
   *   The HTML of the retrieved view or FALSE if view not found.
   */
  public function getView($viewId, $exp_input, $displayId, array $data, AttachedAssets $assets);

}
