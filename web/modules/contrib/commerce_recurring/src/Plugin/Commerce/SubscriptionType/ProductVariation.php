<?php

namespace Drupal\commerce_recurring\Plugin\Commerce\SubscriptionType;

/**
 * Provides the product variation subscription type.
 *
 * @CommerceSubscriptionType(
 *   id = "product_variation",
 *   label = @Translation("Product variation"),
 *   purchasable_entity_type = "commerce_product_variation",
 * )
 */
class ProductVariation extends SubscriptionTypeBase {}
