<?php

namespace Drupal\commerce_recurring\Entity;

use Drupal\commerce\EntityOwnerTrait;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_price\Price;
use Drupal\commerce_recurring\ScheduledChange;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\user\UserInterface;

/**
 * Defines the subscription entity.
 *
 * @ContentEntityType(
 *   id = "commerce_subscription",
 *   label = @Translation("Subscription"),
 *   label_collection = @Translation("Subscriptions"),
 *   label_singular = @Translation("subscription"),
 *   label_plural = @Translation("subscriptions"),
 *   label_count = @PluralTranslation(
 *     singular = "@count subscription",
 *     plural = "@count subscription",
 *   ),
 *   bundle_label = @Translation("Subscription type"),
 *   bundle_plugin_type = "commerce_subscription_type",
 *   handlers = {
 *     "event" = "Drupal\commerce_recurring\Event\SubscriptionEvent",
 *     "list_builder" = "Drupal\commerce_recurring\SubscriptionListBuilder",
 *     "storage" = "\Drupal\commerce_recurring\SubscriptionStorage",
 *     "access" = "Drupal\commerce_recurring\SubscriptionAccessControlHandler",
 *     "permission_provider" = "Drupal\commerce_recurring\SubscriptionPermissionProvider",
 *     "query_access" = "Drupal\entity\QueryAccess\UncacheableQueryAccessHandler",
 *     "form" = {
 *       "default" = "\Drupal\commerce_recurring\Form\SubscriptionForm",
 *       "edit" = "\Drupal\commerce_recurring\Form\SubscriptionForm",
 *       "customer" = "\Drupal\commerce_recurring\Form\SubscriptionForm",
 *       "delete" = "\Drupal\commerce_recurring\Form\SubscriptionDeleteForm",
 *       "cancel" = "\Drupal\commerce_recurring\Form\SubscriptionCancelForm",
 *     },
 *     "views_data" = "Drupal\commerce_recurring\SubscriptionViewsData",
 *     "route_provider" = {
 *       "default" = "Drupal\commerce_recurring\SubscriptionRouteProvider",
 *     },
 *   },
 *   base_table = "commerce_subscription",
 *   admin_permission = "administer commerce_subscription",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "subscription_id",
 *     "bundle" = "type",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/commerce/subscriptions/{commerce_subscription}",
 *     "add-page" = "/admin/commerce/subscriptions/add",
 *     "add-form" = "/admin/commerce/subscriptions/{type}/add",
 *     "edit-form" = "/admin/commerce/subscriptions/{commerce_subscription}/edit",
 *     "customer-view" = "/user/{user}/subscriptions/{commerce_subscription}",
 *     "customer-edit-form" = "/user/{user}/subscriptions/{commerce_subscription}/edit",
 *     "delete-form" = "/admin/commerce/subscriptions/{commerce_subscription}/delete",
 *     "collection" = "/admin/commerce/subscriptions",
 *     "cancel-form" = "/admin/commerce/subscriptions/{commerce_subscription}/cancel",
 *   },
 *   field_ui_base_route = "entity.commerce_subscription.admin_form",
 * )
 */
class Subscription extends ContentEntityBase implements SubscriptionInterface {

  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);
    $uri_route_parameters['user'] = $this->getOwnerId();
    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return new FormattableMarkup('@title #@id', [
      '@title' => $this->getTitle(),
      '@id' => $this->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    $subscription_type_manager = \Drupal::service('plugin.manager.commerce_subscription_type');
    return $subscription_type_manager->createInstance($this->bundle());
  }

  /**
   * {@inheritdoc}
   */
  public function getStore() {
    return $this->get('store_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getStoreId() {
    return $this->get('store_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getBillingSchedule() {
    return $this->get('billing_schedule')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setBillingSchedule(BillingScheduleInterface $billing_schedule) {
    $this->set('billing_schedule', $billing_schedule);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCustomer() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setCustomer(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCustomerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setCustomerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentMethod() {
    return $this->get('payment_method')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setPaymentMethod(PaymentMethodInterface $payment_method) {
    $this->set('payment_method', $payment_method);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentMethodId() {
    return $this->get('payment_method')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setPaymentMethodId($payment_method_id) {
    $this->set('payment_method', $payment_method_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasPurchasedEntity() {
    return !$this->get('purchased_entity')->isEmpty();
  }

  /**
   * {@inheritdoc}
   */
  public function getPurchasedEntity() {
    return $this->get('purchased_entity')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getPurchasedEntityId() {
    return $this->get('purchased_entity')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setPurchasedEntity(PurchasableEntityInterface $purchased_entity) {
    $this->set('purchased_entity', $purchased_entity);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->set('title', $title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuantity() {
    return (string) $this->get('quantity')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setQuantity($quantity) {
    $this->set('quantity', (string) $quantity);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUnitPrice() {
    if (!$this->get('unit_price')->isEmpty()) {
      return $this->get('unit_price')->first()->toPrice();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setUnitPrice(Price $unit_price) {
    $this->set('unit_price', $unit_price);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getState() {
    return $this->get('state')->first();
  }

  /**
   * {@inheritdoc}
   */
  public function setState($state_id) {
    $this->set('state', $state_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getInitialOrder() {
    return $this->get('initial_order')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setInitialOrder(OrderInterface $initial_order) {
    $this->set('initial_order', $initial_order);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getInitialOrderId() {
    return $this->get('initial_order')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentOrder() {
    $order_ids = $this->getOrderIds();
    if (empty($order_ids)) {
      return NULL;
    }
    $order_storage = $this->entityTypeManager()->getStorage('commerce_order');
    $order_ids = $order_storage->getQuery()
      ->condition('type', 'recurring')
      ->condition('order_id', $order_ids, 'IN')
      ->condition('state', ['draft', 'needs_payment'], 'IN')
      ->sort('order_id', 'DESC')
      ->accessCheck(FALSE)
      ->range(0, 1)
      ->execute();

    if (!$order_ids) {
      return NULL;
    }
    return $order_storage->load(key($order_ids));
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderIds() {
    $order_ids = [];
    foreach ($this->get('orders') as $field_item) {
      $order_ids[] = $field_item->target_id;
    }
    return $order_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrders() {
    return $this->get('orders')->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public function setOrders(array $orders) {
    $this->set('orders', $orders);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addOrder(OrderInterface $order) {
    if (!$this->hasOrder($order)) {
      $this->get('orders')->appendItem($order);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeOrder(OrderInterface $order) {
    $index = $this->getOrderIndex($order);
    if ($index !== FALSE) {
      $this->get('orders')->offsetUnset($index);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasOrder(OrderInterface $order) {
    return $this->getOrderIndex($order) !== FALSE;
  }

  /**
   * Gets the index of the given recurring order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The recurring order.
   *
   * @return int|bool
   *   The index of the given recurring order, or FALSE if not found.
   */
  protected function getOrderIndex(OrderInterface $order) {
    $values = $this->get('orders')->getValue();
    $order_ids = array_map(function ($value) {
      return $value['target_id'];
    }, $values);

    return array_search($order->id(), $order_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getNextRenewalTime() {
    return $this->get('next_renewal')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setNextRenewalTime($timestamp) {
    $this->set('next_renewal', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getNextRenewalDate() {
    if ($next_renewal_time = $this->getNextRenewalTime()) {
      return DrupalDateTime::createFromTimestamp($next_renewal_time);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    // Delete the orders of a deleted subscription. Otherwise they will
    // reference an invalid subscription and result in data integrity issues.
    // Deleting the orders will also remove all order items.
    $orders = [];
    /** @var \Drupal\commerce_recurring\Entity\SubscriptionInterface $entity */
    foreach ($entities as $entity) {
      foreach ($entity->getOrders() as $order) {
        $orders[$order->id()] = $order;
      }
    }
    /** @var \Drupal\commerce_order\OrderStorage $order_storage */
    $order_storage = \Drupal::service('entity_type.manager')->getStorage('commerce_order');
    $order_storage->delete($orders);
  }

  /**
   * {@inheritdoc}
   */
  public function getRenewedTime() {
    return $this->get('renewed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRenewedTime($timestamp) {
    $this->set('renewed', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTrialStartTime() {
    return $this->get('trial_starts')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTrialStartTime($timestamp) {
    $this->set('trial_starts', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTrialEndTime() {
    return $this->get('trial_ends')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTrialEndTime($timestamp) {
    $this->set('trial_ends', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTrialStartDate() {
    return DrupalDateTime::createFromTimestamp($this->getTrialStartTime());
  }

  /**
   * {@inheritdoc}
   */
  public function getTrialEndDate() {
    if ($end_time = $this->getTrialEndTime()) {
      return DrupalDateTime::createFromTimestamp($end_time);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getStartTime() {
    return $this->get('starts')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStartTime($timestamp) {
    $this->set('starts', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEndTime() {
    return $this->get('ends')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setEndTime($timestamp) {
    $this->set('ends', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStartDate() {
    return DrupalDateTime::createFromTimestamp($this->getStartTime());
  }

  /**
   * {@inheritdoc}
   */
  public function getEndDate() {
    if ($end_time = $this->getEndTime()) {
      return DrupalDateTime::createFromTimestamp($end_time);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentBillingPeriod() {
    if ($current_order = $this->getCurrentOrder()) {
      if (!$current_order->get('billing_period')->isEmpty()) {
        return $current_order->get('billing_period')->first()->toBillingPeriod();
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function hasScheduledChanges() {
    return !$this->get('scheduled_changes')->isEmpty();
  }

  /**
   * {@inheritdoc}
   */
  public function getScheduledChanges() {
    return $this->get('scheduled_changes')->getScheduledChanges();
  }

  /**
   * {@inheritdoc}
   */
  public function setScheduledChanges(array $scheduled_changes) {
    $this->set('scheduled_changes', $scheduled_changes);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addScheduledChange($field_name, $value) {
    if (!$this->hasField($field_name)) {
      throw new \InvalidArgumentException(sprintf('Invalid field_name "%s" specified for the given scheduled change.', $field_name));
    }
    if ($field_name === 'purchased_entity') {
      throw new \InvalidArgumentException('Scheduling a plan change is not yet supported.');
    }
    // Other scheduled changes are made irrelevant by a state change.
    if ($field_name === 'state') {
      $this->removeScheduledChanges();
    }
    else {
      // There can only be a single scheduled change for a given field.
      $this->removeScheduledChanges($field_name);
    }
    $scheduled_change = new ScheduledChange($field_name, $value, \Drupal::time()->getRequestTime());
    $this->get('scheduled_changes')->appendItem($scheduled_change);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeScheduledChanges($field_name = NULL) {
    foreach ($this->getScheduledChanges() as $scheduled_change) {
      if (!$field_name || $scheduled_change->getFieldName() === $field_name) {
        $this->get('scheduled_changes')->removeScheduledChange($scheduled_change);
      }
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasScheduledChange($field_name, $value = NULL) {
    foreach ($this->getScheduledChanges() as $change) {
      if ($change->getFieldName() != $field_name) {
        continue;
      }
      if (is_null($value) || $change->getValue() == $value) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function applyScheduledChanges() {
    foreach ($this->getScheduledChanges() as $scheduled_change) {
      $this->set($scheduled_change->getFieldName(), $scheduled_change->getValue());
    }
    $this->removeScheduledChanges();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function cancel($schedule = TRUE) {
    if ($schedule) {
      $transition = $this->getState()->getWorkflow()->getTransition('cancel');
      $state_id = $transition->getToState()->getId();
      $this->addScheduledChange('state', $state_id);
    }
    elseif ($this->getState()->isTransitionAllowed('cancel')) {
      $this->getState()->applyTransitionById('cancel');
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    foreach (['store_id', 'billing_schedule', 'uid', 'title', 'unit_price'] as $field) {
      if ($this->get($field)->isEmpty()) {
        throw new EntityMalformedException(sprintf('Required subscription field "%s" is empty.', $field));
      }
    }

    $state = $this->getState()->getId();
    $original_state = isset($this->original) ? $this->original->getState()->getId() : '';

    if ($original_state !== $state) {
      $this->removeScheduledChanges();
    }

    if ($state === 'trial' && $original_state !== 'trial') {
      if (empty($this->getTrialStartTime())) {
        $this->setTrialStartTime(\Drupal::time()->getRequestTime());
      }
      if (empty($this->getTrialEndTime()) && $billing_schedule = $this->getBillingSchedule()) {
        $billing_schedule_plugin = $billing_schedule->getPlugin();
        if ($billing_schedule_plugin->allowTrials()) {
          $trial_period = $billing_schedule_plugin->generateTrialPeriod($this->getTrialStartDate());
          $trial_end_time = $trial_period->getEndDate()->getTimestamp();
          $this->setTrialEndTime($trial_end_time);
        }
      }
      if (empty($this->getStartTime()) && !empty($this->getTrialEndTime())) {
        $this->setStartTime($this->getTrialEndTime());
      }
    }
    elseif ($state === 'active' && $original_state !== 'active') {
      if (empty($this->getStartTime())) {
        $this->setStartTime(\Drupal::time()->getRequestTime());
      }
      if (!empty($this->getEndTime())) {
        $this->setEndTime(NULL);
      }
    }
    else {
      if ($this->isNew()) {
        return;
      }

      if ($state == 'expired' && $original_state != 'expired') {
        $this->getType()->onSubscriptionExpire($this);
      }
      elseif ($state == 'canceled' && $original_state != 'canceled') {
        if ($original_state == 'trial') {
          $this->getType()->onSubscriptionTrialCancel($this);
        }
        else {
          $this->getType()->onSubscriptionCancel($this);
        }

        $this->setEndTime(\Drupal::time()->getRequestTime());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    $current_order = $this->getCurrentOrder();
    if (!isset($this->original) ||
      empty($current_order) ||
      $current_order->getState()->getId() !== 'draft') {
      return;
    }

    $fields_affecting_current_order = [
      'payment_method',
      'state',
    ];
    foreach ($fields_affecting_current_order as $field_name) {
      if (!$this->get($field_name)->equals($this->original->get($field_name))) {
        $current_order->setRefreshState(OrderInterface::REFRESH_ON_SAVE);
        $current_order->save();
        break;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['store_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Store'))
      ->setDescription(t('The store to which the subscription belongs.'))
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setSetting('target_type', 'commerce_store')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('form', [
        'type' => 'commerce_entity_select',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['billing_schedule'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Billing schedule'))
      ->setDescription(t('The billing schedule.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'commerce_billing_schedule')
      ->setSetting('handler', 'default')
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['uid']
      ->setLabel(t('Customer'))
      ->setDescription(t('The subscribed customer.'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['payment_method'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Payment method'))
      ->setDescription(t('The payment method.'))
      ->setSetting('target_type', 'commerce_payment_method')
      ->setDisplayOptions('form', [
        'type' => 'commerce_recurring_payment_method',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setReadOnly(TRUE);

    $fields['purchased_entity'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Purchased entity'))
      ->setDescription(t('The purchased entity.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -1,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The subscription title.'))
      ->setRequired(TRUE)
      ->setSettings([
        'default_value' => '',
        'max_length' => 512,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['quantity'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Quantity'))
      ->setDescription(t('The subscription quantity.'))
      ->setRequired(TRUE)
      ->setSetting('unsigned', TRUE)
      ->setSetting('min', 0)
      ->setDefaultValue(1)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['unit_price'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('Unit price'))
      ->setDescription(t('The subscription unit price.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'commerce_price_default',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['state'] = BaseFieldDefinition::create('state')
      ->setLabel(t('State'))
      ->setDescription(t('The subscription state.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setSetting('workflow', 'subscription_default')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'list_default',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['initial_order'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Initial order'))
      ->setDescription(t('The non-recurring order which started the subscription.'))
      ->setSetting('target_type', 'commerce_order')
      ->setSetting('handler', 'default')
      ->setSetting('display_description', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['orders'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Recurring orders'))
      ->setDescription(t('The recurring orders.'))
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setSetting('target_type', 'commerce_order')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'type' => 'subscription_orders',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time when the subscription was created.'))
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['next_renewal'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Next renewal'))
      ->setDescription(t('The next renewal time.'))
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['renewed'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Last renewed'))
      ->setDescription(t('The time when the subscription was last renewed.'))
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['trial_starts'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Trial starts'))
      ->setDescription(t('The time when the subscription trial starts.'))
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['trial_ends'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Trial ends'))
      ->setDescription(t('The time when the subscription trial ends.'))
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'commerce_recurring_end_timestamp',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['starts'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Starts'))
      ->setDescription(t('The time when the subscription starts.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['ends'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Ends'))
      ->setDescription(t('The time when the subscription ends.'))
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'commerce_recurring_end_timestamp',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['scheduled_changes'] = BaseFieldDefinition::create('commerce_scheduled_change')
      ->setLabel(t('Scheduled changes'))
      ->setRequired(FALSE)
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'commerce_scheduled_change_default',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    /** @var \Drupal\commerce_recurring\SubscriptionTypeManager $subscription_type_manager */
    $subscription_type_manager = \Drupal::service('plugin.manager.commerce_subscription_type');

    /** @var \Drupal\commerce_recurring\Plugin\Commerce\SubscriptionType\SubscriptionTypeInterface $subscription_type */
    $subscription_type = $subscription_type_manager->createInstance($bundle);

    $fields = [
      'purchased_entity' => clone $base_field_definitions['purchased_entity'],
    ];

    $entity_type_id = $subscription_type->getPurchasableEntityTypeId();
    if ($entity_type_id) {
      /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
      $entity_type_manager = \Drupal::service('entity_type.manager');

      /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
      $entity_type = $entity_type_manager->getDefinition($entity_type_id);

      $fields['purchased_entity']
        ->setLabel($entity_type->getLabel())
        ->setSetting('target_type', $entity_type_id);
    }
    else {
      // This subscription type doesn't reference a purchasable entity. The field
      // can't be removed here, or converted to a configurable one, so it's
      // hidden instead. https://www.drupal.org/node/2346347#comment-10254087.
      $fields['purchased_entity']
        ->setRequired(FALSE)
        ->setDisplayOptions('form', [
          'region' => 'hidden',
        ])
        ->setDisplayConfigurable('form', FALSE)
        ->setDisplayConfigurable('view', FALSE)
        ->setReadOnly(TRUE);
    }

    return $fields;
  }

}
