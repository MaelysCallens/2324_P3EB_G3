<?php

namespace Drupal\commerce_recurring;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * List builder for subscriptions.
 */
class SubscriptionListBuilder extends EntityListBuilder {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $instance = parent::createInstance($container, $entity_type);
    $instance->currentUser = $container->get('current_user');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['title'] = $this->t('Title');
    $header['type'] = $this->t('Type');
    $header['billing_schedule'] = $this->t('Billing schedule');
    $header['customer'] = $this->t('Customer');
    $header['state'] = $this->t('State');
    $header['start_date'] = $this->t('Start date');
    $header['end_date'] = $this->t('End date');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\commerce_recurring\Entity\SubscriptionInterface $entity */
    $row = [
      'id' => $entity->id(),
      'title' => $entity->getTitle(),
      'type' => $entity->getType()->getLabel(),
      'billing_schedule' => $entity->getBillingSchedule()->label(),
      'customer' => $entity->getCustomer()->getDisplayName(),
      'state' => $entity->getState()->getLabel(),
      'start_date' => $entity->getStartDate()->format('M jS Y H:i:s'),
      'end_date' => $entity->getEndDate() ? $entity->getEndDate()->format('M jS Y H:i:s') : '-',
    ];

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritDoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    // For users with the 'update own commerce_subscription' permission, allow
    // the Edit operation only if the 'customer' form mode exists for the
    // subscription type. We do this so we never show the Edit operation using
    // the default form display (where all the fields are usually editable) to
    // customers.
    if (isset($operations['edit'])
        && $this->currentUser->hasPermission('update own commerce_subscription')
        && !$this->currentUser->hasPermission('update any commerce_subscription')) {
      $customer_form_mode_exists = \Drupal::entityQuery('entity_form_display')
        ->condition('id', "{$entity->getEntityTypeId()}.{$entity->bundle()}.customer")
        ->condition('status', TRUE)
        ->accessCheck(FALSE)
        ->range(0, 1)
        ->count()
        ->execute();

      if ($customer_form_mode_exists && $entity->hasLinkTemplate('customer-edit-form')) {
        $operations['edit']['url'] = $this->ensureDestination($entity->toUrl('customer-edit-form'));
      }
      else {
        unset($operations['edit']);
      }
    }

    if ($entity->access('cancel') && $entity->hasLinkTemplate('cancel-form')) {
      $operations['cancel'] = [
        'title' => $this->t('Cancel'),
        'weight' => 10,
        'url' => $this->ensureDestination($entity->toUrl('cancel-form')),
      ];
    }

    return $operations;
  }

}
