<?php

namespace Drupal\commerce_recurring\Plugin\Commerce\EntityTrait;

use Drupal\entity\BundleFieldDefinition;
use Drupal\commerce\Plugin\Commerce\EntityTrait\EntityTraitBase;

/**
 * Provides a trait to enable purchasing of subscriptions.
 *
 * @CommerceEntityTrait(
 *   id = "purchasable_entity_subscription",
 *   label = @Translation("Allow subscriptions"),
 *   entity_types = {"commerce_product_variation"}
 * )
 */
class PurchasableEntitySubscriptionTrait extends EntityTraitBase {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = [];
    $fields['billing_schedule'] = BundleFieldDefinition::create('entity_reference')
      ->setLabel(t('Billing schedule'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'commerce_billing_schedule')
      ->setDisplayOptions('form', [
        'type' => 'options_select',
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['subscription_type'] = BundleFieldDefinition::create('commerce_plugin_item:commerce_subscription_type')
      ->setLabel(t('Subscription type'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'commerce_plugin_select',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

}
