<?php

namespace Drupal\Tests\commerce_recurring\Kernel;

use Drupal\commerce_price\Price;
use Drupal\commerce_recurring\Entity\Subscription;
use Drupal\Core\Entity\Entity\EntityFormDisplay;

/**
 * Tests the commerce_subscription entity operations.
 *
 * @coversDefaultClass \Drupal\commerce_recurring\SubscriptionListBuilder
 * @group commerce_recurring
 */
class SubscriptionOperationsTest extends RecurringKernelTestBase {

  /**
   * {@inheritdoc}
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
   * @covers ::getOperations
   */
  public function testOperations() {
    $admin_user = $this->createUser(['mail' => $this->randomString() . '@example.com'], ['administer commerce_subscription']);
    $privileged_user = $this->createUser(['mail' => $this->randomString() . '@example.com'], [
      'view any commerce_subscription',
      'update any commerce_subscription',
    ]);
    $user = $this->createUser(['mail' => $this->randomString() . '@example.com'], [
      'view own commerce_subscription',
      'update own commerce_subscription',
    ]);

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

    $list_builder = \Drupal::entityTypeManager()->getListBuilder('commerce_subscription');

    // Check that admins and privileged users get the regular edit form route.
    $this->drupalSetCurrentUser($admin_user);
    $operations = $list_builder->getOperations($subscription);
    $this->assertEquals('entity.commerce_subscription.edit_form', $operations['edit']['url']->getRouteName());

    $this->drupalSetCurrentUser($privileged_user);
    $operations = $list_builder->getOperations($subscription);
    $this->assertEquals('entity.commerce_subscription.edit_form', $operations['edit']['url']->getRouteName());

    // Check that customers get the dedicated customer-facing edit form route.
    $this->drupalSetCurrentUser($user);
    $operations = $list_builder->getOperations($subscription);
    $this->assertEquals('entity.commerce_subscription.customer_edit_form', $operations['edit']['url']->getRouteName());

    // Check that customers don't get any edit form route at all if the
    // 'Customer' form mode doesn't exist for the subscription type.
    $customer_form_display = EntityFormDisplay::load('commerce_subscription.product_variation.customer');
    $customer_form_display->delete();
    $operations = $list_builder->getOperations($subscription);
    $this->assertArrayNotHasKey('edit', $operations);
  }

}
