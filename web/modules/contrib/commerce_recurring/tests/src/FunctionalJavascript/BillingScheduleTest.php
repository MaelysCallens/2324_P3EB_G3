<?php

namespace Drupal\Tests\commerce_recurring\FunctionalJavascript;

use Drupal\commerce_recurring\Entity\BillingSchedule;
use Drupal\commerce_recurring\Entity\BillingScheduleInterface;
use Drupal\commerce_recurring\Entity\Subscription;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\Tests\commerce\FunctionalJavascript\CommerceWebDriverTestBase;

/**
 * Tests the billing schedule UI.
 *
 * @group commerce_recurring
 */
class BillingScheduleTest extends CommerceWebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'commerce_product',
    'commerce_recurring',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return [
      'administer commerce_billing_schedule',
    ] + parent::getAdministratorPermissions();
  }

  /**
   * Tests creating a billing schedule.
   */
  public function testBillingScheduleCreation() {
    $this->drupalGet('admin/commerce/config/billing-schedules');
    $page = $this->getSession()->getPage();
    $page->clickLink('Add billing schedule');
    $this->assertSession()->addressEquals('admin/commerce/config/billing-schedules/add');
    $page->fillField('label', 'Test');
    $this->getSession()->wait(1000, 'jQuery("#edit-label-machine-name-suffix .machine-name-value").html() == "test"');
    $values = [
      'billingType' => BillingScheduleInterface::BILLING_TYPE_POSTPAID,
      'displayLabel' => 'Awesome test',
      'dunning[retry][0]' => '1',
      'dunning[retry][1]' => '2',
      'dunning[retry][2]' => '3',
      'dunning[unpaid_subscription_state]' => 'canceled',
      'plugin' => 'fixed',
      'configuration[fixed][trial_interval][allow_trials]' => 1,
      'configuration[fixed][trial_interval][number]' => '2',
      'configuration[fixed][trial_interval][unit]' => 'month',
      'configuration[fixed][interval][number]' => '2',
      'configuration[fixed][interval][unit]' => 'month',
      'configuration[fixed][start_day]' => '4',
      'prorater' => 'proportional',
      'combine_subscriptions' => TRUE,
    ];
    $this->submitForm($values, 'Save');
    $this->assertSession()->addressEquals('admin/commerce/config/billing-schedules');
    $this->assertSession()->responseContains('Test');

    $billing_schedule = BillingSchedule::load('test');
    $this->assertEquals('test', $billing_schedule->id());
    $this->assertEquals('Test', $billing_schedule->label());
    $this->assertEquals('Awesome test', $billing_schedule->getDisplayLabel());
    $this->assertEquals(BillingScheduleInterface::BILLING_TYPE_POSTPAID, $billing_schedule->getBillingType());
    $this->assertEquals([1, 2, 3], $billing_schedule->getRetrySchedule());
    $this->assertEquals('canceled', $billing_schedule->getUnpaidSubscriptionState());
    $this->assertEquals('fixed', $billing_schedule->getPluginId());
    $this->assertNotEmpty($billing_schedule->getPlugin()->allowTrials());
    $this->assertNotEmpty($billing_schedule->allowCombiningSubscriptions());
    $this->assertEquals([
      'interval' => [
        'number' => '2',
        'unit' => 'month',
      ],
      'start_month' => '1',
      'start_day' => '4',
      'trial_interval' => [
        'number' => '2',
        'unit' => 'month',
      ],
    ], $billing_schedule->getPluginConfiguration());
    $this->assertEquals($billing_schedule->getPluginConfiguration(), $billing_schedule->getPlugin()->getConfiguration());
    $this->assertEquals('proportional', $billing_schedule->getProraterId());
  }

  /**
   * Tests editing a billing schedule.
   */
  public function testBillingScheduleEditing() {
    $billing_schedule = BillingSchedule::create([
      'id' => 'test',
      'label' => 'Test',
      'displayLabel' => 'Awesome test',
      'billingType' => BillingScheduleInterface::BILLING_TYPE_POSTPAID,
      'plugin' => 'fixed',
      'configuration' => [
        'interval' => [
          'number' => '2',
          'unit' => 'month',
        ],
        'start_day' => '4',
      ],
      'prorater' => 'proportional',
      'proraterConfiguration' => [],
    ]);
    $billing_schedule->save();

    $this->drupalGet('admin/commerce/config/billing-schedules/manage/' . $billing_schedule->id());
    $this->getSession()->getPage()->selectFieldOption('dunning[num_retries]', '2');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->selectFieldOption('prorater', 'full_price');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->submitForm([
      'label' => 'Test (Modified)',
      'displayLabel' => 'Awesome test (Modified)',
      'billingType' => BillingScheduleInterface::BILLING_TYPE_PREPAID,
      'dunning[retry][0]' => '6',
      'dunning[retry][1]' => '7',
      'dunning[unpaid_subscription_state]' => 'active',
      'configuration[fixed][interval][number]' => '1',
      'configuration[fixed][interval][unit]' => 'year',
      'configuration[fixed][start_month]' => '2',
      'configuration[fixed][start_day]' => '5',
    ], 'Save');

    \Drupal::entityTypeManager()->getStorage('commerce_billing_schedule')->resetCache();
    $billing_schedule = BillingSchedule::load('test');
    $this->assertEquals('test', $billing_schedule->id());
    $this->assertEquals('Test (Modified)', $billing_schedule->label());
    $this->assertEquals('Awesome test (Modified)', $billing_schedule->getDisplayLabel());
    $this->assertEquals(BillingScheduleInterface::BILLING_TYPE_PREPAID, $billing_schedule->getBillingType());
    $this->assertEquals([6, 7], $billing_schedule->getRetrySchedule());
    $this->assertEquals('active', $billing_schedule->getUnpaidSubscriptionState());
    $this->assertEquals('fixed', $billing_schedule->getPluginId());
    $this->assertEquals([
      'interval' => [
        'number' => '1',
        'unit' => 'year',
      ],
      'start_month' => '2',
      'start_day' => '5',
      'trial_interval' => [],
    ], $billing_schedule->getPluginConfiguration());
    $this->assertEquals($billing_schedule->getPluginConfiguration(), $billing_schedule->getPlugin()->getConfiguration());
    $this->assertEquals('full_price', $billing_schedule->getProraterId());
    $this->assertFalse($billing_schedule->allowCombiningSubscriptions());

    // Check that some fields are disabled when editing a billing schedule that
    // is used by subscriptions.
    $variation = ProductVariation::create([
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'price' => [
        'number' => '39.99',
        'currency_code' => 'USD',
      ],
    ]);
    $variation->save();
    $subscription = Subscription::create([
      'type' => 'product_variation',
      'title' => $this->randomString(),
      'uid' => $this->adminUser->id(),
      'billing_schedule' => $billing_schedule,
      'purchased_entity' => $variation,
      'store_id' => $this->store->id(),
      'unit_price' => $variation->getPrice(),
      'starts' => time(),
      'state' => 'active',
    ]);
    $subscription->save();
    $this->drupalGet('admin/commerce/config/billing-schedules/manage/' . $billing_schedule->id());
    $page = $this->getSession()->getPage();
    $this->assertSession()->pageTextContains('Some fields are disabled since the Test (Modified) billing schedule is used by subscriptions.');
    $disabled_fields = [
      'edit-billingtype-prepaid',
      'edit-billingtype-postpaid',
      'edit-plugin-fixed',
      'edit-plugin-rolling',
      'edit-configuration-fixed-trial-interval-allow-trials',
      'edit-configuration-fixed-interval-number',
      'edit-configuration-fixed-interval-unit',
      'edit-prorater-full-price',
      'edit-prorater-proportional',
    ];
    foreach ($disabled_fields as $disabled_field) {
      $field = $page->findField($disabled_field);
      $this->assertEquals($field->getAttribute('disabled'), 'disabled');
    }
  }

  /**
   * Tests deleting a billing schedule.
   */
  public function testBillingScheduleDeletion() {
    $billing_schedule = BillingSchedule::create([
      'id' => 'test',
      'label' => 'Test',
      'displayLabel' => 'Awesome test',
      'billingType' => BillingScheduleInterface::BILLING_TYPE_POSTPAID,
      'plugin' => 'fixed',
      'configuration' => [
        'interval' => [
          'number' => '2',
          'unit' => 'month',
        ],
      ],
    ]);
    $billing_schedule->save();
    $this->drupalGet('admin/commerce/config/billing-schedules/manage/' . $billing_schedule->id() . '/delete');
    $this->submitForm([], 'Delete');
    $this->assertSession()->addressEquals('admin/commerce/config/billing-schedules');

    $billing_schedule_exists = (bool) BillingSchedule::load('test');
    $this->assertEmpty($billing_schedule_exists);
  }

}
