<?php

namespace Drupal\commerce_recurring\Plugin\Commerce\SubscriptionType;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_recurring\BillingPeriod;
use Drupal\commerce_recurring\Charge;
use Drupal\commerce_recurring\Entity\BillingScheduleInterface;
use Drupal\commerce_recurring\Entity\SubscriptionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the subscription base class.
 */
abstract class SubscriptionTypeBase extends PluginBase implements SubscriptionTypeInterface, ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new SubscriptionTypeBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getPurchasableEntityTypeId() {
    if (!empty($this->pluginDefinition['purchasable_entity_type'])) {
      return $this->pluginDefinition['purchasable_entity_type'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function collectTrialCharges(SubscriptionInterface $subscription, BillingPeriod $trial_period) {
    $start_date = $subscription->getStartDate();
    $billing_type = $subscription->getBillingSchedule()->getBillingType();
    if ($billing_type == BillingScheduleInterface::BILLING_TYPE_PREPAID) {
      $billing_schedule = $subscription->getBillingSchedule()->getPlugin();
      // The base charge for prepaid subscriptions always covers the next
      // period, which in the case of trials is the first billing period.
      $base_unit_price = $subscription->getUnitPrice();
      $first_billing_period = $billing_schedule->generateFirstBillingPeriod($start_date);
      $base_billing_period = $this->adjustBillingPeriod($first_billing_period, $subscription);
      $full_billing_period = $first_billing_period;
    }
    else {
      // The base charge for postpaid subscriptions covers the current (trial)
      // period, which means that the plan needs to be free.
      $base_unit_price = $subscription->getUnitPrice()->multiply('0');
      $base_billing_period = $this->adjustTrialPeriod($trial_period, $subscription);
      $full_billing_period = $trial_period;
    }
    $base_charge = new Charge([
      'purchased_entity' => $subscription->getPurchasedEntity(),
      'title' => $subscription->getTitle(),
      'quantity' => $subscription->getQuantity(),
      'unit_price' => $base_unit_price,
      'billing_period' => $base_billing_period,
      'full_billing_period' => $full_billing_period,
    ]);

    return [$base_charge];
  }

  /**
   * {@inheritdoc}
   */
  public function collectCharges(SubscriptionInterface $subscription, BillingPeriod $billing_period) {
    $start_date = $subscription->getStartDate();
    $billing_type = $subscription->getBillingSchedule()->getBillingType();
    if ($billing_type == BillingScheduleInterface::BILLING_TYPE_PREPAID) {
      // The subscription has either ended, or is scheduled for cancellation,
      // meaning there's nothing left to prepay.
      if ($subscription->getState()->getId() != 'active' ||
        $subscription->hasScheduledChange('state', 'canceled')) {
        return [];
      }
      $billing_schedule = $subscription->getBillingSchedule()->getPlugin();
      // The initial order (which starts the subscription) pays the first
      // billing period, so the base charge is always for the next one.
      // The October recurring order (ending on Nov 1st) charges for November.
      $next_billing_period = $billing_schedule->generateNextBillingPeriod($start_date, $billing_period);
      $base_billing_period = $this->adjustBillingPeriod($next_billing_period, $subscription);
      $full_billing_period = $next_billing_period;
    }
    else {
      // Postpaid means we're always charging for the current billing period.
      // The October recurring order (ending on Nov 1st) charges for October.
      $base_billing_period = $this->adjustBillingPeriod($billing_period, $subscription);
      $full_billing_period = $billing_period;
    }
    $base_charge = new Charge([
      'purchased_entity' => $subscription->getPurchasedEntity(),
      'title' => $subscription->getTitle(),
      'quantity' => $subscription->getQuantity(),
      'unit_price' => $subscription->getUnitPrice(),
      'billing_period' => $base_billing_period,
      'full_billing_period' => $full_billing_period,
    ]);

    return [$base_charge];
  }

  /**
   * Adjusts the trial period to reflect the trial end date.
   *
   * There is no need to adjust the start date because trial periods are
   * always rolling.
   *
   * @param \Drupal\commerce_recurring\BillingPeriod $trial_period
   *   The trial period.
   * @param \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription
   *   The subscription.
   *
   * @return \Drupal\commerce_recurring\BillingPeriod
   *   The adjusted trial period.
   */
  protected function adjustTrialPeriod(BillingPeriod $trial_period, SubscriptionInterface $subscription) {
    $trial_end_date = $subscription->getTrialEndDate();
    $end_date = $trial_period->getEndDate();
    if ($trial_end_date && $trial_period->contains($trial_end_date)) {
      // The trial ended before the end of the trial period.
      $end_date = $trial_end_date;
    }

    return new BillingPeriod($trial_period->getStartDate(), $end_date);
  }

  /**
   * Adjusts the billing period to reflect the subscription start/end dates.
   *
   * @param \Drupal\commerce_recurring\BillingPeriod $billing_period
   *   The billing period.
   * @param \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription
   *   The subscription.
   *
   * @return \Drupal\commerce_recurring\BillingPeriod
   *   The adjusted billing period.
   */
  protected function adjustBillingPeriod(BillingPeriod $billing_period, SubscriptionInterface $subscription) {
    $subscription_start_date = $subscription->getStartDate();
    $subscription_end_date = $subscription->getEndDate();
    $start_date = $billing_period->getStartDate();
    $end_date = $billing_period->getEndDate();
    if ($billing_period->contains($subscription_start_date)) {
      // The subscription started after the billing period (E.g: customer
      // subscribed on Mar 10th for a Mar 1st - Apr 1st period).
      $start_date = $subscription_start_date;
    }
    if ($subscription_end_date && $billing_period->contains($subscription_end_date)) {
      // The subscription ended before the end of the billing period.
      $end_date = $subscription_end_date;
    }

    return new BillingPeriod($start_date, $end_date);
  }

  /**
   * {@inheritdoc}
   */
  public function onSubscriptionCreate(SubscriptionInterface $subscription, OrderItemInterface $order_item) {}

  /**
   * {@inheritdoc}
   */
  public function onSubscriptionTrialStart(SubscriptionInterface $subscription, OrderInterface $order) {}

  /**
   * {@inheritdoc}
   */
  public function onSubscriptionTrialCancel(SubscriptionInterface $subscription) {}

  /**
   * {@inheritdoc}
   */
  public function onSubscriptionActivate(SubscriptionInterface $subscription, OrderInterface $order) {}

  /**
   * {@inheritdoc}
   */
  public function onSubscriptionRenew(SubscriptionInterface $subscription, OrderInterface $order, OrderInterface $next_order) {}

  /**
   * {@inheritdoc}
   */
  public function onSubscriptionExpire(SubscriptionInterface $subscription) {}

  /**
   * {@inheritdoc}
   */
  public function onSubscriptionCancel(SubscriptionInterface $subscription) {}

}
