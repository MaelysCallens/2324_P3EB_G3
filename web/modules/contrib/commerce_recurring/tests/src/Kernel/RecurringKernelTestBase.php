<?php

namespace Drupal\Tests\commerce_recurring\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_payment\Entity\PaymentMethod;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\commerce_recurring\Entity\BillingSchedule;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;

/**
 * Provides a base class for Recurring kernel tests.
 */
abstract class RecurringKernelTestBase extends OrderKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'advancedqueue',
    'commerce_payment',
    'commerce_payment_example',
    'commerce_recurring',
  ];

  /**
   * The test billing schedule.
   *
   * @var \Drupal\commerce_recurring\Entity\BillingScheduleInterface
   */
  protected $billingSchedule;

  /**
   * The test payment gateway.
   *
   * @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface
   */
  protected $paymentGateway;

  /**
   * The test payment method.
   *
   * @var \Drupal\commerce_payment\Entity\PaymentMethodInterface
   */
  protected $paymentMethod;

  /**
   * The test variation.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationInterface
   */
  protected $variation;

  /**
   * The test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('commerce_payment');
    $this->installEntitySchema('commerce_payment_method');
    $this->installEntitySchema('commerce_subscription');
    $this->installEntitySchema('user');
    $this->installSchema('advancedqueue', 'advancedqueue');
    $this->installConfig('entity');
    $this->installConfig('commerce_recurring');

    $user = $this->createUser();
    $this->user = $this->reloadEntity($user);

    /** @var \Drupal\commerce_recurring\Entity\BillingScheduleInterface $billing_schedule */
    $billing_schedule = BillingSchedule::create([
      'id' => 'test_id',
      'label' => 'Monthly schedule',
      'displayLabel' => 'Monthly schedule',
      'billingType' => BillingSchedule::BILLING_TYPE_POSTPAID,
      'plugin' => 'fixed',
      'configuration' => [
        'trial_interval' => [
          'number' => '10',
          'unit' => 'day',
        ],
        'interval' => [
          'number' => '1',
          'unit' => 'month',
        ],
      ],
    ]);
    $billing_schedule->save();
    $this->billingSchedule = $this->reloadEntity($billing_schedule);

    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
    $payment_gateway = PaymentGateway::create([
      'id' => 'example',
      'label' => 'Example',
      'plugin' => 'example_onsite',
    ]);
    $payment_gateway->save();
    $this->paymentGateway = $this->reloadEntity($payment_gateway);

    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = PaymentMethod::create([
      'type' => 'credit_card',
      'payment_gateway' => $this->paymentGateway,
      'card_type' => 'visa',
      'uid' => $this->user->id(),
    ]);
    $payment_method->save();
    $this->paymentMethod = $this->reloadEntity($payment_method);

    /** @var \Drupal\commerce_product\Entity\ProductVariationTypeInterface $product_variation_type */
    $product_variation_type = ProductVariationType::load('default');
    $product_variation_type->setGenerateTitle(FALSE);
    $product_variation_type->save();
    // Install the variation trait.
    $trait_manager = \Drupal::service('plugin.manager.commerce_entity_trait');
    $trait = $trait_manager->createInstance('purchasable_entity_subscription');
    $trait_manager->installTrait($trait, 'commerce_product_variation', 'default');

    $variation = ProductVariation::create([
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'sku' => strtolower($this->randomMachineName()),
      'price' => [
        'number' => '10.00',
        'currency_code' => 'USD',
      ],
      'billing_schedule' => $this->billingSchedule,
      'subscription_type' => [
        'target_plugin_id' => 'product_variation',
      ],
    ]);
    $variation->save();
    $this->variation = $this->reloadEntity($variation);
  }

  /**
   * Creates an order with an order item that will start a subscription.
   *
   * @param bool $trial
   *   Whether to enable a trial interval for the billing schedule. Defaults to
   *   FALSE.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   A commerce order.
   */
  protected function createInitialOrder($trial = FALSE) {
    if (!$trial) {
      $configuration = $this->billingSchedule->getPluginConfiguration();
      unset($configuration['trial_interval']);
      $this->billingSchedule->setPluginConfiguration($configuration);
      $this->billingSchedule->save();
    }

    $first_order_item = OrderItem::create([
      'type' => 'test',
      'title' => 'I promise not to start a subscription',
      'unit_price' => [
        'number' => '10.00',
        'currency_code' => 'USD',
      ],
      'quantity' => 1,
    ]);
    $first_order_item->save();
    $second_order_item = OrderItem::create([
      'type' => 'default',
      'purchased_entity' => $this->variation,
      'unit_price' => [
        'number' => '2.00',
        'currency_code' => 'USD',
      ],
      'quantity' => '3',
    ]);
    $second_order_item->save();
    $initial_order = Order::create([
      'type' => 'default',
      'store_id' => $this->store,
      'uid' => $this->user,
      'order_items' => [$first_order_item, $second_order_item],
      'state' => 'draft',
    ]);
    $initial_order->save();

    return $initial_order;
  }

  /**
   * Changes the current time.
   *
   * @param int $new_time
   *   The new time.
   */
  protected function rewindTime($new_time) {
    $mock_time = $this->prophesize(TimeInterface::class);
    $mock_time->getCurrentTime()->willReturn($new_time);
    $mock_time->getRequestTime()->willReturn($new_time);
    $this->container->set('datetime.time', $mock_time->reveal());
  }

}
