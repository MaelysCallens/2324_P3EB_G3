<?php

namespace Drupal\commerce_recurring;

use Drupal\commerce\CommerceEntityViewsData;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Provides views data for subscription.
 */
class SubscriptionViewsData extends CommerceEntityViewsData {

  /**
   * The purchasable entity type repository.
   *
   * @var Drupal\commerce\PurchasableEntityTypeRepositoryInterface
   */
  protected $purchasableEntityTypeRepository;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $instance = parent::createInstance($container, $entity_type);
    $instance->purchasableEntityTypeRepository = $container->get('commerce.purchasable_entity_type_repository');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Unset the default purchased entity relationship.
    // It does not work properly since the target type is not defined.
    unset($data['commerce_subscription']['purchased_entity']['relationship']);

    // Collect all purchasable entity types.
    /** @var \Drupal\commerce\PurchasableEntityInterface[] $entity_types */
    $entity_types = $this->purchasableEntityTypeRepository->getPurchasableEntityTypes();

    /** @var \Drupal\Core\Entity\Sql\TableMappingInterface $table_mapping */
    $table_mapping = $this->storage->getTableMapping();

    // Provide a relationship for each entity type found.
    foreach ($entity_types as $entity_type) {
      $entity_type_id = $entity_type->id();
      if (!$this->entityTypeManager->hasDefinition($entity_type_id)) {
        continue;
      }
      $data['commerce_subscription'][$entity_type_id] = [
        'relationship' => [
          'title' => $entity_type->getLabel(),
          'help' => $this->t('The purchased @entity_type.', ['@entity_type' => $entity_type->getSingularLabel()]),
          'base' => $this->getViewsTableForEntityType($entity_type),
          'base field' => $entity_type->getKey('id'),
          'relationship field' => $table_mapping->getColumnNames('purchased_entity')['target_id'],
          'id' => 'standard',
          'label' => $entity_type->getLabel(),
        ],
      ];
      $target_base_table = $this->getViewsTableForEntityType($entity_type);
      $data[$target_base_table]['reverse__commerce_subscription__purchased_entity'] = [
        'relationship' => [
          'title' => $this->entityType->getLabel(),
          'help' => $this->t('The @subscription_entity_type for this @entity_type.', [
            '@subscription_entity_type' => $this->entityType->getPluralLabel(),
            '@entity_type' => $entity_type->getSingularLabel(),
          ]),
          'group' => $entity_type->getLabel(),
          'base' => $this->getViewsTableForEntityType($this->entityType),
          'base field' => $table_mapping->getColumnNames('purchased_entity')['target_id'],
          'relationship field' => $entity_type->getKey('id'),
          'id' => 'standard',
          'label' => $this->entityType->getLabel(),
          'entity_type' => $this->entityType->id(),
        ],
      ];
    }

    return $data;
  }

}
