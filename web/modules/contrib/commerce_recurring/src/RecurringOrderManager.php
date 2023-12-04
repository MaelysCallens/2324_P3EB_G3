<?php

namespace Drupal\commerce_recurring;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Exception\HardDeclineException;
use Drupal\commerce_recurring\Entity\BillingSchedule;
use Drupal\commerce_recurring\Entity\SubscriptionInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides the default recurring order manager.
 *
 * Currently assumes that there's a single subscription per recurring order.
 */
class RecurringOrderManager implements RecurringOrderManagerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new RecurringOrderManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, TimeInterface $time) {
    $this->entityTypeManager = $entity_type_manager;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public function startTrial(SubscriptionInterface $subscription) {
    $state = $subscription->getState()->getId();
    if ($state != 'trial') {
      throw new \InvalidArgumentException(sprintf('Unexpected subscription state "%s".', $state));
    }
    $billing_schedule = $subscription->getBillingSchedule();
    if (!$billing_schedule->getPlugin()->allowTrials()) {
      throw new \InvalidArgumentException(sprintf('The billing schedule "%s" does not allow trials.', $billing_schedule->id()));
    }

    $start_date = $subscription->getTrialStartDate();
    $end_date = $subscription->getTrialEndDate();
    $trial_period = new BillingPeriod($start_date, $end_date);
    $order = $this->findOrCreateOrder($subscription, $trial_period);
    $this->applyCharges($order, $subscription, $trial_period);
    // Allow the type to modify the subscription and order before they're saved.
    $subscription->getType()->onSubscriptionTrialStart($subscription, $order);

    $order->save();
    $subscription->addOrder($order);
    $subscription->save();

    return $order;
  }

  /**
   * {@inheritdoc}
   */
  public function startRecurring(SubscriptionInterface $subscription) {
    $state = $subscription->getState()->getId();
    if ($state != 'active') {
      throw new \InvalidArgumentException(sprintf('Unexpected subscription state "%s".', $state));
    }

    $start_date = $subscription->getStartDate();
    $billing_schedule = $subscription->getBillingSchedule();
    $billing_period = $billing_schedule->getPlugin()->generateFirstBillingPeriod($start_date);
    $subscription->setNextRenewalTime($billing_period->getEndDate()->getTimestamp());
    $order = $this->findOrCreateOrder($subscription, $billing_period);
    $this->applyCharges($order, $subscription, $billing_period);
    // Allow the type to modify the subscription and order before they're saved.
    $subscription->getType()->onSubscriptionActivate($subscription, $order);

    $order->save();
    $subscription->addOrder($order);
    $subscription->save();

    return $order;
  }

  /**
   * {@inheritdoc}
   */
  public function refreshOrder(OrderInterface $order) {
    /** @var \Drupal\commerce_recurring\Plugin\Field\FieldType\BillingPeriodItem $billing_period_item */
    $billing_period_item = $order->get('billing_period')->first();
    $billing_period = $billing_period_item->toBillingPeriod();
    $subscriptions = $this->collectSubscriptions($order);
    $payment_method = $this->selectPaymentMethod($subscriptions);
    $billing_profile = $payment_method ? $payment_method->getBillingProfile() : NULL;
    $payment_gateway_id = $payment_method ? $payment_method->getPaymentGatewayId() : NULL;

    $order->set('billing_profile', $billing_profile);
    $order->set('payment_method', $payment_method);
    $order->set('payment_gateway', $payment_gateway_id);
    foreach ($subscriptions as $subscription) {
      $this->applyCharges($order, $subscription, $billing_period);
    }
    $order_items = $order->getItems();
    // OrderRefresh skips empty orders, an order without items can't stay open.
    if (!$order_items) {
      $order->set('state', 'canceled');
    }
    // The same workaround that \Drupal\commerce_order\OrderRefresh does.
    foreach ($order_items as $order_item) {
      if ($order_item->isNew()) {
        $order_item->order_id->entity = $order;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function closeOrder(OrderInterface $order) {
    $order_state = $order->getState()->getId();
    if ($order->isPaid()) {
      if (in_array('mark_paid', array_keys($order->getState()
        ->getTransitions()))) {
        $order->getState()->applyTransitionById('mark_paid');
        $order->save();
      }
    }
    if (in_array($order_state, ['canceled', 'completed']) || $order->isPaid()) {
      return;
    }

    if ($order_state == 'draft') {
      $order->getState()->applyTransitionById('place');
      $order->save();
    }

    $subscriptions = $this->collectSubscriptions($order);
    $payment_method = $this->selectPaymentMethod($subscriptions);
    if (!$payment_method) {
      throw new HardDeclineException('Payment method not found.');
    }
    $payment_gateway = $payment_method->getPaymentGateway();
    if (!$payment_gateway) {
      throw new HardDeclineException('Payment gateway not found');
    }
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment_gateway->getPlugin();
    if (!$order->isPaid()) {
      $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
      /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
      $payment = $payment_storage->create([
        'payment_gateway' => $payment_gateway->id(),
        'payment_method' => $payment_method->id(),
        'order_id' => $order,
        'amount' => $order->getTotalPrice(),
        'state' => 'new',
      ]);
      // The createPayment() call might throw a decline exception, which is
      // supposed to be handled by the caller, to allow for dunning.
      $payment_gateway_plugin->createPayment($payment);

      if ($order->getState()->isTransitionAllowed('mark_paid')) {
        $order->getState()->applyTransitionById('mark_paid');
      }
      $order->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function renewOrder(OrderInterface $order) {
    $subscriptions = $this->collectSubscriptions($order);
    $next_order = NULL;
    foreach ($subscriptions as $subscription) {
      if (!$subscription || $subscription->getState()->getId() !== 'active') {
        // The subscription was deleted or deactivated.
        continue;
      }

      $billing_schedule = $subscription->getBillingSchedule();
      $start_date = $subscription->getStartDate();
      /** @var \Drupal\commerce_recurring\Plugin\Field\FieldType\BillingPeriodItem $billing_period_item */
      $billing_period_item = $order->get('billing_period')->first();
      $current_billing_period = $billing_period_item->toBillingPeriod();
      $next_billing_period = $billing_schedule->getPlugin()->generateNextBillingPeriod($start_date, $current_billing_period);

      $next_order = $this->findOrCreateOrder($subscription, $next_billing_period);
      $this->applyCharges($next_order, $subscription, $next_billing_period);
      // Allow the subscription type to modify the order before it is saved.
      $subscription->getType()->onSubscriptionRenew($subscription, $order, $next_order);
      $next_order->save();
      // Update the subscription with the new order and renewal timestamp.
      $subscription->addOrder($next_order);
      $subscription->setRenewedTime($this->time->getCurrentTime());
      $subscription->setNextRenewalTime($next_billing_period->getEndDate()->getTimestamp());
      $subscription->save();
    }
    return $next_order;
  }

  /**
   * {@inheritdoc}
   */
  public function collectSubscriptions(OrderInterface $order) {
    $subscriptions = [];
    foreach ($order->getItems() as $order_item) {
      if (!$order_item->hasField('subscription') || $order_item->get('subscription')->isEmpty()) {
        // A recurring order item without a subscription ID is malformed.
        continue;
      }
      /** @var \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription */
      $subscription = $order_item->get('subscription')->entity;
      // Guard against deleted subscription entities.
      if ($subscription) {
        $subscriptions[$subscription->id()] = $subscription;
      }
    }

    return $subscriptions;
  }

  /**
   * Find/create a recurring order for the given subscription.
   *
   * @param \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription
   *   The subscription.
   * @param \Drupal\commerce_recurring\BillingPeriod $billing_period
   *   The billing period.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   The existing recurring order if found, the created unsaved one if it
   *   doesn't.
   */
  protected function findOrCreateOrder(SubscriptionInterface $subscription, BillingPeriod $billing_period) {
    $billing_schedule = $subscription->getBillingSchedule();
    assert($billing_schedule instanceof BillingSchedule);
    $payment_method = $subscription->getPaymentMethod();

    // Check whether we should attempt to combine subscriptions into a single
    // recurring order.
    if ($billing_schedule->allowCombiningSubscriptions()) {

      $order_storage = $this->entityTypeManager->getStorage('commerce_order');
      $query = $order_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', 'recurring')
        ->condition('state', 'draft')
        ->condition('store_id', $subscription->getStoreId())
        ->condition('uid', $subscription->getCustomerId())
        ->condition('billing_schedule', $billing_schedule->id())
        ->condition('billing_period.starts', $billing_period->getStartDate()->getTimestamp(), '=')
        ->condition('billing_period.ends', $billing_period->getEndDate()->getTimestamp(), '=');

      // If the subscription references a payment method, ensure we fetch a
      // recurring order referencing the same payment method to prevent
      // consolidating subscriptions with different payment methods into the same
      // recurring order.
      if ($payment_method) {
        $query->condition('payment_method', $payment_method->id());
      }

      $existing = $query->execute();
      if ($existing) {
        $existing_recurring_order = $order_storage->load(reset($existing));
        assert($existing_recurring_order instanceof OrderInterface);
        return $existing_recurring_order;
      }
    }

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $this->entityTypeManager->getStorage('commerce_order')->create([
      'type' => 'recurring',
      'store_id' => $subscription->getStoreId(),
      'uid' => $subscription->getCustomerId(),
      'billing_profile' => $payment_method ? $payment_method->getBillingProfile() : NULL,
      'payment_method' => $payment_method,
      'payment_gateway' => $payment_method ? $payment_method->getPaymentGatewayId() : NULL,
      'billing_period' => $billing_period,
      'billing_schedule' => $subscription->getBillingSchedule(),
    ]);

    return $order;
  }

  /**
   * Applies subscription charges to the given recurring order.
   *
   * Note: The order items are not saved.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The recurring order.
   * @param \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription
   *   The subscription.
   * @param \Drupal\commerce_recurring\BillingPeriod $billing_period
   *   The billing period.
   */
  protected function applyCharges(OrderInterface $order, SubscriptionInterface $subscription, BillingPeriod $billing_period) {
    /** @var \Drupal\commerce_order\OrderItemStorageInterface $order_item_storage */
    $order_item_storage = $this->entityTypeManager->getStorage('commerce_order_item');
    $existing_order_items = [];
    foreach ($order->getItems() as $order_item) {
      if ($order_item->get('subscription')->target_id == $subscription->id()) {
        $existing_order_items[] = $order_item;
      }
    }
    if ($subscription->getState()->getId() == 'trial') {
      $charges = $subscription->getType()->collectTrialCharges($subscription, $billing_period);
    }
    else {
      $charges = $subscription->getType()->collectCharges($subscription, $billing_period);
    }

    foreach ($charges as $charge) {
      $order_item = array_shift($existing_order_items);
      if (!$order_item) {
        /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
        $order_item = $order_item_storage->create([
          'type' => $this->getOrderItemTypeId($subscription),
          'order_id' => $order,
          'subscription' => $subscription->id(),
        ]);
      }

      // @todo Add a purchased_entity setter to OrderItemInterface.
      $order_item->set('purchased_entity', $charge->getPurchasedEntity());
      $order_item->setTitle($charge->getTitle());
      $order_item->setQuantity($charge->getQuantity());
      $order_item->set('billing_period', $charge->getBillingPeriod());
      // Populate the initial unit price, then prorate it.
      $order_item->setUnitPrice($charge->getUnitPrice());
      if ($charge->needsProration()) {
        $prorater = $subscription->getBillingSchedule()->getProrater();
        $prorated_unit_price = $prorater->prorateOrderItem($order_item, $charge->getBillingPeriod(), $charge->getFullBillingPeriod());
        $order_item->setUnitPrice($prorated_unit_price, TRUE);
      }
      // Avoid setting unsaved order items for now, to avoid #3017259.
      if ($order_item->isNew()) {
        $order_item->save();
      }
      $order->addItem($order_item);
    }

    // Delete any previous leftover order items.
    if ($existing_order_items) {
      foreach ($existing_order_items as $existing_order_item) {
        $order->removeItem($existing_order_item);
      }
      $order_item_storage->delete($existing_order_items);
    }
  }

  /**
   * Selects the payment method for the given subscriptions.
   *
   * It is assumed that even if the billing schedule allows multiple
   * subscriptions per recurring order, there will still be a single enforced
   * payment method per customer. In case multiple payment methods are found,
   * the more recent one will be used.
   *
   * @param \Drupal\commerce_recurring\Entity\SubscriptionInterface[] $subscriptions
   *   The subscriptions.
   *
   * @return \Drupal\commerce_payment\Entity\PaymentMethodInterface|null
   *   The payment method, or NULL if none were found.
   */
  protected function selectPaymentMethod(array $subscriptions) {
    $payment_methods = [];
    foreach ($subscriptions as $subscription) {
      if ($payment_method = $subscription->getPaymentMethod()) {
        $payment_methods[$payment_method->id()] = $payment_method;
      }
    }
    krsort($payment_methods, SORT_NUMERIC);
    $payment_method = reset($payment_methods);

    return $payment_method ?: NULL;
  }

  /**
   * Gets the order item type ID for the given subscription.
   *
   * @param \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription
   *   The subscription.
   *
   * @return string
   *   The order item type ID.
   */
  protected function getOrderItemTypeId(SubscriptionInterface $subscription) {
    if ($purchasable_entity_type_id = $subscription->getType()->getPurchasableEntityTypeId()) {
      return 'recurring_' . str_replace('commerce_', '', $purchasable_entity_type_id);
    }
    else {
      return 'recurring_standalone';
    }
  }

}
