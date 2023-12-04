<?php

namespace Drupal\commerce_recurring\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the subscription type plugin annotation object.
 *
 * Plugin namespace: Plugin\Commerce\SubscriptionType.
 *
 * @see plugin_api
 *
 * @Annotation
 */
class CommerceSubscriptionType extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The subscription type label.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The purchasable entity type ID.
   *
   * @var string
   */
  protected $purchasable_entity_type;

}
