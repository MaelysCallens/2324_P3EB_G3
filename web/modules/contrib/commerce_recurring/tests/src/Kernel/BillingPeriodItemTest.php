<?php

namespace Drupal\Tests\commerce_recurring\Kernel;

use Drupal\commerce_recurring\BillingPeriod;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the billing period field type.
 *
 * @group commerce_recurring
 */
class BillingPeriodItemTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'commerce',
    'commerce_price',
    'commerce_order',
    'commerce_recurring',
    'field',
    'entity_test',
    'profile',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test');

    FieldStorageConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'field_billing_period',
      'type' => 'commerce_billing_period',
    ])->save();

    FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'field_billing_period',
      'bundle' => 'entity_test',
    ])->save();
  }

  /**
   * Tests the field.
   */
  public function testField() {
    $start_date = new DrupalDateTime('2019-10-19 15:07:12');
    $end_date = new DrupalDateTime('2019-11-19 15:07:12');

    $entity = EntityTest::create([
      'field_billing_period' => [
        'starts' => $start_date->format('U'),
        'ends' => $end_date->format('U'),
      ],
    ]);
    $entity->save();

    $entity = EntityTest::load($entity->id());
    /** @var \Drupal\commerce_recurring\Plugin\Field\FieldType\BillingPeriodItem $billing_period_field */
    $billing_period_field = $entity->get('field_billing_period')->first();
    $this->assertEquals($start_date->format('U'), $billing_period_field->starts);
    $this->assertEquals($end_date->format('U'), $billing_period_field->ends);

    $billing_period = $billing_period_field->toBillingPeriod();
    $this->assertInstanceOf(BillingPeriod::class, $billing_period);
    $this->assertEquals($start_date, $billing_period->getStartDate());
    $this->assertEquals($end_date, $billing_period->getEndDate());

    // Test passing billing periods.
    $new_end_date = new DrupalDateTime('2019-12-19 15:07:12');
    $billing_period = new BillingPeriod($end_date, $new_end_date);
    $entity->set('field_billing_period', $billing_period);
    /** @var \Drupal\commerce_recurring\Plugin\Field\FieldType\BillingPeriodItem $billing_period_field */
    $billing_period_field = $entity->get('field_billing_period')->first();

    $returned_billing_period = $billing_period_field->toBillingPeriod();
    $this->assertEquals($billing_period, $returned_billing_period);
  }

}
