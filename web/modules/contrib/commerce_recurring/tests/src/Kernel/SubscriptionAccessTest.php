<?php

namespace Drupal\Tests\commerce_recurring\Kernel;

use Drupal\commerce_price\Price;
use Drupal\commerce_recurring\Entity\Subscription;
use Drupal\Core\Session\AnonymousUserSession;

/**
 * Tests the commerce_subscription access control.
 *
 * @coversDefaultClass \Drupal\commerce_recurring\SubscriptionAccessControlHandler
 * @group commerce_recurring
 */
class SubscriptionAccessTest extends RecurringKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'commerce_recurring',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create uid: 1 here so that it's skipped in test cases.
    $admin_user = $this->createUser();
  }

  /**
   * @covers ::checkAccess
   */
  public function testViewAccess() {
    $admin_user = $this->createUser(['mail' => $this->randomString() . '@example.com'], ['administer commerce_subscription']);
    $user = $this->createUser(['mail' => $this->randomString() . '@example.com'], ['view own commerce_subscription']);
    $different_user = $this->createUser(['mail' => $this->randomString() . '@example.com'], ['view own commerce_subscription']);
    $anonymous_user = new AnonymousUserSession();

    /** @var \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription */
    $subscription = Subscription::create([
      'type' => 'product_variation',
      'store_id' => $this->store->id(),
      'billing_schedule' => $this->billingSchedule,
      'uid' => $user->id(),
      'purchased_entity' => $this->variation,
      'title' => $this->variation->getOrderItemTitle(),
      'unit_price' => new Price('2', 'USD'),
      'state' => 'active',
      'starts' => strtotime('2019-02-24 17:00'),
    ]);
    $subscription->save();

    // Tests the 'view own commerce_subscription' access checking.
    $this->assertTrue($subscription->access('view', $user));
    $this->assertFalse($subscription->access('view', $different_user));
    $this->assertTrue($subscription->access('view', $admin_user));
    $this->assertFalse($subscription->access('view', $anonymous_user));
  }

  /**
   * @covers ::checkAccess
   *
   * @dataProvider getCancelOperations
   */
  public function testCancelAccess($operation) {
    $admin_user = $this->createUser(['mail' => $this->randomString() . '@example.com'], ['administer commerce_subscription']);
    $privileged_user = $this->createUser(['mail' => $this->randomString() . '@example.com'], ["$operation any commerce_subscription"]);
    $user = $this->createUser(['mail' => $this->randomString() . '@example.com'], ["$operation own commerce_subscription"]);
    $different_user = $this->createUser(['mail' => $this->randomString() . '@example.com'], ["$operation own commerce_subscription"]);
    $anonymous_user = new AnonymousUserSession();

    /** @var \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription */
    $subscription = Subscription::create([
      'type' => 'product_variation',
      'store_id' => $this->store->id(),
      'billing_schedule' => $this->billingSchedule,
      'uid' => $user->id(),
      'purchased_entity' => $this->variation,
      'title' => $this->variation->getOrderItemTitle(),
      'unit_price' => new Price('2', 'USD'),
      'state' => 'active',
      'starts' => strtotime('2019-02-24 17:00'),
    ]);
    $subscription->save();

    // Tests the 'cancel' access checking.
    $this->assertTrue($subscription->access('cancel', $admin_user));
    $this->assertTrue($subscription->access('cancel', $privileged_user));
    $this->assertTrue($subscription->access('cancel', $user));
    $this->assertFalse($subscription->access('cancel', $different_user));
    $this->assertFalse($subscription->access('cancel', $anonymous_user));
  }

  /**
   * Provides the list of operations for canceling a subscription.
   */
  public function getCancelOperations() {
    return [
      ['cancel'],
      ['update'],
    ];
  }

}
