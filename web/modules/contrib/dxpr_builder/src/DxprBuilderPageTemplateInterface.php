<?php

namespace Drupal\dxpr_builder;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a page template entity.
 */
interface DxprBuilderPageTemplateInterface extends ConfigEntityInterface {

  /**
   * Returns the image data of the page template image to use in #image element.
   *
   * @return string|false
   *   The base64 date of the page template image.
   */
  public function getImageData();

}
