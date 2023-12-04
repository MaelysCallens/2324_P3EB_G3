<?php

namespace Drupal\Tests\commerce_recurring\Functional;

use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_payment\Entity\PaymentMethod;
use Drupal\commerce_recurring\Entity\BillingSchedule;
use Drupal\commerce_recurring\Entity\Subscription;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\profile\Entity\Profile;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Tests the subscription UI.
 *
 * @group commerce_recurring
 */
class SubscriptionTest extends CommerceBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'commerce_payment_example',
    'commerce_product',
    'commerce_recurring',
  ];

  /**
   * The test billing schedule.
   *
   * @var \Drupal\commerce_recurring\Entity\BillingScheduleInterface
   */
  protected $billingSchedule;

  /**
   * The test billing profile.
   *
   * @var \Drupal\profile\Entity\ProfileInterface
   */
  protected $billingProfile;

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
   * Holds the date pattern string for the "html_date" format.
   *
   * @var string
   */
  protected $dateFormat;

  /**
   * Holds the date pattern string for the "html_time" format.
   *
   * @var string
   */
  protected $timeFormat;

  /**
   * The test variation.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationInterface
   */
  protected $variation;

  /**
   * The recurring order manager.
   *
   * @var \Drupal\commerce_recurring\RecurringOrderManagerInterface
   */
  protected $recurringOrderManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->variation = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'price' => [
        'number' => '39.99',
        'currency_code' => 'USD',
      ],
    ]);
    $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => 'My product',
      'variations' => [$this->variation],
      'stores' => [$this->store],
    ]);
    /** @var \Drupal\commerce_recurring\Entity\BillingScheduleInterface $billing_schedule */
    $this->billingSchedule = $this->createEntity('commerce_billing_schedule', [
      'id' => 'test_id',
      'label' => 'Hourly schedule',
      'displayLabel' => 'Hourly schedule',
      'billingType' => BillingSchedule::BILLING_TYPE_POSTPAID,
      'plugin' => 'fixed',
      'configuration' => [
        'interval' => [
          'number' => '1',
          'unit' => 'hour',
        ],
      ],
    ]);

    $profile = Profile::create([
      'type' => 'customer',
      'address' => [
        'country_code' => 'US',
      ],
    ]);
    $profile->save();
    $this->billingProfile = $this->reloadEntity($profile);

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
      'card_number' => 1,
      'uid' => $this->adminUser->id(),
      'billing_profile' => $this->billingProfile,
    ]);
    $payment_method->save();
    $this->paymentMethod = $this->reloadEntity($payment_method);

    $this->dateFormat = DateFormat::load('html_date')->getPattern();
    $this->timeFormat = DateFormat::load('html_time')->getPattern();

    $this->recurringOrderManager = $this->container->get('commerce_recurring.order_manager');
  }

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return [
      'administer commerce_subscription',
    ] + parent::getAdministratorPermissions();
  }

  /**
   * Tests creating a subscription.
   */
  public function testSubscriptionCreation() {
    $this->drupalGet('admin/commerce/subscriptions/add');
    $page = $this->getSession()->getPage();
    $page->clickLink('Product variation');
    $this->assertSession()->addressEquals('admin/commerce/subscriptions/product_variation/add');
    $start_date = DrupalDateTime::createFromTimestamp(time() + 3600);

    $values = [
      'title[0][value]' => 'Test subscription',
      'billing_schedule' => $this->billingSchedule->id(),
      'payment_method[target_id]' => $this->paymentMethod->label() . ' (' . $this->paymentMethod->id() . ')',
      'purchased_entity[0][target_id]' => $this->variation->getTitle() . ' (' . $this->variation->id() . ')',
      'uid[0][target_id]' => $this->adminUser->label() . ' (' . $this->adminUser->id() . ')',
      'unit_price[0][number]' => '9.99',
      'starts[0][value][date]' => $start_date->format($this->dateFormat),
      'starts[0][value][time]' => $start_date->format($this->timeFormat),
    ];
    $this->submitForm($values, 'Save');
    $this->assertSession()->pageTextContains('A subscription been successfully saved.');

    /** @var \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription */
    $subscription = Subscription::load(1);
    $this->assertSession()->pageTextContains($subscription->getTitle());
    $this->assertSession()->pageTextContains($subscription->getState()->getId());
    $this->assertSession()->pageTextContains($subscription->getBillingSchedule()->label());
    $this->assertEquals($values['title[0][value]'], $subscription->getTitle());
    $this->assertEquals($this->billingSchedule->id(), $subscription->getBillingSchedule()->id());
    $this->assertEquals($this->paymentMethod->id(), $subscription->getPaymentMethodId());
    $this->assertNull($subscription->getTrialStartTime());
    $this->assertNull($subscription->getTrialEndTime());
    $this->assertNull($subscription->getEndTime());
    $this->assertEquals($start_date->getTimestamp(), $subscription->getStartTime());
    $this->assertEquals($subscription->getCustomerId(), $this->adminUser->id());
    $this->assertEquals('pending', $subscription->getState()->getId());
  }

  /**
   * Tests editing a subscription.
   */
  public function testSubscriptionEditing() {
    /** @var \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription */
    $subscription = $this->createEntity('commerce_subscription', [
      'title' => $this->randomString(),
      'uid' => $this->adminUser->id(),
      'billing_schedule' => $this->billingSchedule,
      'type' => 'product_variation',
      'purchased_entity' => $this->variation,
      'store_id' => $this->store->id(),
      'unit_price' => $this->variation->getPrice(),
      'starts' => time() + 3600,
      'trial_starts' => time(),
      'state' => 'trial',
    ]);
    $trial_end = DrupalDateTime::createFromTimestamp($subscription->getStartTime());
    $end = DrupalDateTime::createFromTimestamp($subscription->getStartTime() + 7200);
    $this->drupalGet('admin/commerce/subscriptions/' . $subscription->id() . '/edit');
    $this->assertSession()->pageTextContains('Trial');
    $this->assertSession()->buttonExists('Cancel subscription');
    $values = [
      'title[0][value]' => 'Test (Modified)',
      'trial_ends[0][has_value]' => 1,
      'trial_ends[0][container][value][date]' => $trial_end->format($this->dateFormat),
      'trial_ends[0][container][value][time]' => $trial_end->format($this->timeFormat),
      'ends[0][has_value]' => 1,
      'ends[0][container][value][date]' => $end->format($this->dateFormat),
      'ends[0][container][value][time]' => $end->format($this->timeFormat),
    ];
    $this->submitForm($values, 'Save');
    $this->assertSession()->pageTextContains('A subscription been successfully saved.');
    $subscription = $this->reloadEntity($subscription);
    $this->assertEquals($values['title[0][value]'], $subscription->getTitle());
    $this->assertSession()->pageTextContains($subscription->getTitle());
    $this->assertNotEmpty($subscription->getTrialStartTime());
    $this->assertEquals($subscription->getStartTime(), $subscription->getTrialEndTime());
  }

  /**
   * Tests editing the payment method of a subscription.
   */
  public function testSubscriptionPaymentMethodEditing() {
    /** @var \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription */
    $subscription = $this->createEntity('commerce_subscription', [
      'title' => $this->randomString(),
      'uid' => $this->adminUser->id(),
      'billing_schedule' => $this->billingSchedule,
      'payment_method' => $this->paymentMethod,
      'type' => 'product_variation',
      'purchased_entity' => $this->variation,
      'store_id' => $this->store->id(),
      'unit_price' => $this->variation->getPrice(),
      'starts' => time() + 3600,
      'state' => 'active',
    ]);

    // Create an additional payment method for the subscription owner, as well
    // as another one for a different user.
    $payment_method_2 = PaymentMethod::create([
      'type' => 'credit_card',
      'payment_gateway' => $this->paymentGateway,
      'card_type' => 'visa',
      'card_number' => 2,
      'uid' => $this->adminUser->id(),
      'billing_profile' => $this->billingProfile,
    ]);
    $payment_method_2->save();

    $another_user = $this->drupalCreateUser();
    $payment_method_3 = PaymentMethod::create([
      'type' => 'credit_card',
      'payment_gateway' => $this->paymentGateway,
      'card_type' => 'visa',
      'card_number' => 3,
      'uid' => $another_user->id(),
      'billing_profile' => $this->billingProfile,
    ]);
    $payment_method_3->save();
    $this->container->get('entity_type.manager')->getStorage('commerce_payment_method')->resetCache();

    $this->drupalGet('admin/commerce/subscriptions/' . $subscription->id() . '/edit');
    $this->assertSession()->pageTextContains('Visa ending in 1');
    $this->assertSession()->pageTextContains('Visa ending in 2');
    $this->assertSession()->pageTextNotContains('Visa ending in 3');

    $values = [
      'payment_method[target_id]' => 2,
    ];
    $this->submitForm($values, 'Save');
    $subscription = $this->reloadEntity($subscription);
    $this->assertEquals($payment_method_2->id(), $subscription->getPaymentMethodId());
  }

  /**
   * Tests cancelling a subscription immediately.
   */
  public function testSubscriptionImmediateCancelling() {
    $this->drupalGet('admin/commerce/subscriptions/add');
    $page = $this->getSession()->getPage();
    $page->clickLink('Product variation');
    $start_date = DrupalDateTime::createFromTimestamp(time() + 3600);

    $values = [
      'title[0][value]' => 'Test subscription',
      'billing_schedule' => $this->billingSchedule->id(),
      'purchased_entity[0][target_id]' => $this->variation->getTitle() . ' (' . $this->variation->id() . ')',
      'uid[0][target_id]' => $this->adminUser->label() . ' (' . $this->adminUser->id() . ')',
      'unit_price[0][number]' => '9.99',
      'starts[0][value][date]' => $start_date->format($this->dateFormat),
      'starts[0][value][time]' => $start_date->format($this->timeFormat),
    ];
    $this->submitForm($values, 'Save');
    $this->assertSession()->pageTextContains('A subscription been successfully saved.');

    /** @var \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription */
    $subscription = Subscription::load(1);

    // Since we use no payment provider in this test, the subscription gets the
    // state Pending instead of Active. We nudge it to Active to get things
    // going again.
    $subscription->getState()->applyTransitionById('activate');
    $subscription->save();
    $this->recurringOrderManager->startRecurring($subscription);

    $this->drupalGet('admin/commerce/subscriptions/' . $subscription->id() . '/cancel');
    $radio = $this->getSession()->getPage()->findField('edit-cancel-option-now');
    $name = $radio->getAttribute('name');
    $this->assertEqual($name, 'cancel_option');
    $option = $radio->getAttribute('value');
    $this->assertEqual($option, 'now');
    $this->getSession()->getPage()->selectFieldOption($name, $option);
    $this->getSession()->getPage()->findButton('Confirm')->click();
    $this->assertSession()->pageTextContains('The subscription has been canceled.');

    // Check that already cancelled subscriptions aren't cancelable anymore.
    $this->drupalGet('admin/commerce/subscriptions/' . $subscription->id() . '/cancel');
    $this->assertSession()->pageTextContains('The subscription has already been canceled.');
  }

  /**
   * Tests scheduled subscription cancellation.
   */
  public function testSubscriptionScheduledCancelling() {
    $this->drupalGet('admin/commerce/subscriptions/add');
    $page = $this->getSession()->getPage();
    $page->clickLink('Product variation');
    $start_date = DrupalDateTime::createFromTimestamp(time() + 3600);

    $values = [
      'title[0][value]' => 'Test subscription',
      'billing_schedule' => $this->billingSchedule->id(),
      'purchased_entity[0][target_id]' => $this->variation->getTitle() . ' (' . $this->variation->id() . ')',
      'uid[0][target_id]' => $this->adminUser->label() . ' (' . $this->adminUser->id() . ')',
      'unit_price[0][number]' => '9.99',
      'starts[0][value][date]' => $start_date->format($this->dateFormat),
      'starts[0][value][time]' => $start_date->format($this->timeFormat),
    ];
    $this->submitForm($values, 'Save');
    $this->assertSession()->pageTextContains('A subscription been successfully saved.');

    /** @var \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription */
    $subscription = Subscription::load(1);

    // Since we use no payment provider in this test, the subscription gets the
    // state Pending instead of Active. We nudge it to Active to get things
    // going again.
    $subscription->getState()->applyTransitionById('activate');
    $subscription->save();
    $this->recurringOrderManager->startRecurring($subscription);

    $this->drupalGet('admin/commerce/subscriptions/' . $subscription->id() . '/cancel');
    $radio = $this->getSession()->getPage()->findField('edit-cancel-option-scheduled');
    $name = $radio->getAttribute('name');
    $this->assertEqual($name, 'cancel_option');
    $option = $radio->getAttribute('value');
    $this->assertEqual($option, 'scheduled');
    $this->getSession()->getPage()->selectFieldOption($name, $option);
    $this->getSession()->getPage()->findButton('Confirm')->click();
    $this->assertSession()->pageTextContains('The subscription has been scheduled for cancellation.');

    $this->drupalGet('admin/commerce/subscriptions/' . $subscription->id() . '/cancel');
    $this->assertSession()->pageTextContains('A cancellation has already been scheduled for ');
  }

}
