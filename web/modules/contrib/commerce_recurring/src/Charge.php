<?php

namespace Drupal\commerce_recurring;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_price\Price;

/**
 * Represents a charge.
 *
 * Charges are returned from the subscription type, and then mapped to new
 * or existing recurring order items. This allows order items to be reused
 * when possible.
 */
final class Charge {

  /**
   * The purchased entity, when available.
   *
   * @var \Drupal\commerce\PurchasableEntityInterface|null
   */
  protected $purchasedEntity;

  /**
   * The title.
   *
   * @var string
   */
  protected $title;

  /**
   * The quantity.
   *
   * @var string
   */
  protected $quantity;

  /**
   * The unit price.
   *
   * @var \Drupal\commerce_price\Price
   */
  protected $unitPrice;

  /**
   * The billing period.
   *
   * @var \Drupal\commerce_recurring\BillingPeriod
   */
  protected $billingPeriod;

  /**
   * The full billing period.
   *
   * @var \Drupal\commerce_recurring\BillingPeriod
   */
  protected $fullBillingPeriod;

  /**
   * Constructs a new Charge object.
   *
   * @param array $definition
   *   The definition.
   */
  public function __construct(array $definition) {
    foreach (['title', 'unit_price', 'billing_period', 'full_billing_period'] as $required_property) {
      if (empty($definition[$required_property])) {
        throw new \InvalidArgumentException(sprintf('Missing required property "%s".', $required_property));
      }
    }
    if (isset($definition['purchased_entity']) && !($definition['purchased_entity'] instanceof PurchasableEntityInterface)) {
      throw new \InvalidArgumentException(sprintf('The "purchased_entity" property must be an instance of %s.', PurchasableEntityInterface::class));
    }
    if (!$definition['unit_price'] instanceof Price) {
      throw new \InvalidArgumentException(sprintf('The "unit_price" property must be an instance of %s.', Price::class));
    }
    if (!$definition['billing_period'] instanceof BillingPeriod) {
      throw new \InvalidArgumentException(sprintf('The "billing_period" property must be an instance of %s.', BillingPeriod::class));
    }
    if (!$definition['full_billing_period'] instanceof BillingPeriod) {
      throw new \InvalidArgumentException(sprintf('The "full_billing_period" property must be an instance of %s.', BillingPeriod::class));
    }

    $this->purchasedEntity = isset($definition['purchased_entity']) ? $definition['purchased_entity'] : NULL;
    $this->title = $definition['title'];
    $this->quantity = isset($definition['quantity']) ? $definition['quantity'] : '1';
    $this->unitPrice = $definition['unit_price'];
    $this->billingPeriod = $definition['billing_period'];
    $this->fullBillingPeriod = $definition['full_billing_period'];
  }

  /**
   * Gets the purchased entity.
   *
   * @return \Drupal\commerce\PurchasableEntityInterface|null
   *   The purchased entity, or NULL if the charge is not backed by one.
   */
  public function getPurchasedEntity() {
    return $this->purchasedEntity;
  }

  /**
   * Gets the title.
   *
   * @return string
   *   The title.
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * Gets the quantity.
   *
   * @return string
   *   The quantity.
   */
  public function getQuantity() {
    return $this->quantity;
  }

  /**
   * Gets the unit price.
   *
   * This is the price for a full billing period, and will be prorated on
   * the order item based on the actual billing period ($this->billingPeriod).
   *
   * @return \Drupal\commerce_price\Price
   *   The unit price.
   */
  public function getUnitPrice() {
    return $this->unitPrice;
  }

  /**
   * Gets the billing period.
   *
   * @return \Drupal\commerce_recurring\BillingPeriod
   *   The billing period.
   */
  public function getBillingPeriod() {
    return $this->billingPeriod;
  }

  /**
   * Gets the full billing period.
   *
   * @return \Drupal\commerce_recurring\BillingPeriod
   *   The full billing period.
   */
  public function getFullBillingPeriod() {
    return $this->fullBillingPeriod;
  }

  /**
   * Gets whether the unit price needs to be prorated.
   *
   * @return bool
   *   TRUE if the unit price needs to be prorated, FALSE otherwise.
   */
  public function needsProration() {
    return $this->fullBillingPeriod->getDuration() != $this->billingPeriod->getDuration();
  }

}
