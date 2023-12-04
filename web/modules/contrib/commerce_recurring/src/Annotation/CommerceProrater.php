<?php

namespace Drupal\commerce_recurring\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the prorater plugin annotation object.
 *
 * Plugin namespace: Plugin\Commerce\Prorater.
 *
 * @see plugin_api
 *
 * @Annotation
 */
class CommerceProrater extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

}
