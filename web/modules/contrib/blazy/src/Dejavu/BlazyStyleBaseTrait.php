<?php

namespace Drupal\blazy\Dejavu;

use Drupal\blazy\Views\BlazyStyleBaseTrait as StyleBaseTrait;

@trigger_error('The ' . __NAMESPACE__ . '\BlazyStyleBaseTrait is deprecated in blazy:8.x-2.14 and is removed from blazy:8.x-3.0. Use \Drupal\blazy\Views\BlazyStyleBase instead. See https://www.drupal.org/node/3367304', E_USER_DEPRECATED);

/**
 * Deprecated in blazy:8.x-2.14.
 *
 * @internal
 *   This is an internal part of the Blazy system and should only be used by
 *   blazy-related code in Blazy module. Please extend base classes intead.
 *
 * @deprecated in blazy:8.x-2.14 and is removed from blazy:8.x-3.0. Use
 *   \Drupal\blazy\Views\BlazyStyleBase instead.
 * @see https://www.drupal.org/node/3367304
 */
trait BlazyStyleBaseTrait {

  use StyleBaseTrait;

}
