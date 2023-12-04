<?php

namespace Drupal\commerce_recurring\Plugin\Commerce\Prorater;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_price\RounderInterface;
use Drupal\commerce_price\Calculator;
use Drupal\commerce_recurring\BillingPeriod;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a proportional prorater.
 *
 * @CommerceProrater(
 *   id = "proportional",
 *   label = @Translation("Proportional"),
 * )
 */
class Proportional extends ProraterBase implements ContainerFactoryPluginInterface {

  /**
   * The price rounder service.
   *
   * @var \Drupal\commerce_price\RounderInterface
   */
  protected $rounder;

  /**
   * Constructs a new Proportional object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_price\RounderInterface $rounder
   *   The rounder.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RounderInterface $rounder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->rounder = $rounder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('commerce_price.rounder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function prorateOrderItem(OrderItemInterface $order_item, BillingPeriod $billing_period, BillingPeriod $full_billing_period) {
    $duration = $billing_period->getDuration();
    $full_duration = $full_billing_period->getDuration();
    $price = $order_item->getUnitPrice();
    $price = $price->multiply(Calculator::divide($duration, $full_duration));
    $price = $this->rounder->round($price);

    return $price;
  }

}
