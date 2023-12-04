<?php

namespace Drupal\commerce_recurring\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the billing schedule plugin annotation object.
 *
 * Plugin namespace: Plugin\Commerce\BillingSchedule.
 *
 * @see plugin_api
 *
 * @Annotation
 */
class CommerceBillingSchedule extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The billing schedule label.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

}
