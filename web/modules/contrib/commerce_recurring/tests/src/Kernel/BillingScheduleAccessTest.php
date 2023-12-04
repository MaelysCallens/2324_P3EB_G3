<?php

namespace Drupal\Tests\commerce_recurring\Kernel;

use Drupal\commerce_recurring\Entity\Subscription;

/**
 * Tests the commerce_billing_schedule access control.
 *
 * @coversDefaultClass \Drupal\commerce_recurring\BillingScheduleAccessControlHandler
 * @group commerce_recurring
 */
class BillingScheduleAccessTest extends RecurringKernelTestBase {

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
  public function testAccess() {
    $account = $this->createUser([], ['access administration pages']);
    $this->assertFalse($this->billingSchedule->access('view label', $account));
    $this->assertFalse($this->billingSchedule->access('view', $account));
    $this->assertFalse($this->billingSchedule->access('update', $account));
    $this->assertFalse($this->billingSchedule->access('delete', $account));

    // Check that the general 'administer commerce_billing_schedule' permission
    // allows access to every operation.
    $account = $this->createUser([], ['administer commerce_billing_schedule']);
    $this->assertTrue($this->billingSchedule->access('view label', $account));
    $this->assertTrue($this->billingSchedule->access('view', $account));
    $this->assertTrue($this->billingSchedule->access('update', $account));
    $this->assertTrue($this->billingSchedule->access('delete', $account));

    // Check that certain 'commerce_subscription' permissions give access to
    // view the label of 'commerce_billing_schedule' entities.
    $account = $this->createUser([], ['view own commerce_subscription']);
    $this->assertTrue($this->billingSchedule->access('view label', $account));
    $this->assertFalse($this->billingSchedule->access('view', $account));
    $this->assertFalse($this->billingSchedule->access('update', $account));
    $this->assertFalse($this->billingSchedule->access('delete', $account));

    $account = $this->createUser([], ['view any commerce_subscription']);
    $this->assertTrue($this->billingSchedule->access('view label', $account));
    $this->assertFalse($this->billingSchedule->access('view', $account));
    $this->assertFalse($this->billingSchedule->access('update', $account));
    $this->assertFalse($this->billingSchedule->access('delete', $account));

    $account = $this->createUser([], ['update any commerce_subscription']);
    $this->assertTrue($this->billingSchedule->access('view label', $account));
    $this->assertFalse($this->billingSchedule->access('view', $account));
    $this->assertFalse($this->billingSchedule->access('update', $account));
    $this->assertFalse($this->billingSchedule->access('delete', $account));

    $account = $this->createUser([], ['administer commerce_subscription']);
    $this->assertTrue($this->billingSchedule->access('view label', $account));
    $this->assertFalse($this->billingSchedule->access('view', $account));
    $this->assertFalse($this->billingSchedule->access('update', $account));
    $this->assertFalse($this->billingSchedule->access('delete', $account));

    // Test denying the "delete" operation if the billing schedule is
    // referenced by subscriptions.
    $subscription = Subscription::create([
      'type' => 'product_variation',
      'title' => $this->randomString(),
      'uid' => $this->user->id(),
      'billing_schedule' => $this->billingSchedule,
      'purchased_entity' => $this->variation,
      'store_id' => $this->store->id(),
      'unit_price' => $this->variation->getPrice(),
      'starts' => time(),
      'state' => 'active',
    ]);
    $subscription->save();
    $account = $this->createUser([], ['administer commerce_billing_schedule']);
    $this->assertFalse($this->billingSchedule->access('delete', $account));
  }

}
