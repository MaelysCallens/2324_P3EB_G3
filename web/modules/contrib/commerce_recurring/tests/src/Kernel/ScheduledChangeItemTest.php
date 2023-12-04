<?php

namespace Drupal\Tests\commerce_recurring\Kernel;

use Drupal\commerce_recurring\ScheduledChange;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests the scheduled change field.
 *
 * @group commerce_recurring
 */
class ScheduledChangeItemTest extends RecurringKernelTestBase {

  /**
   * The test entity.
   *
   * @var \Drupal\entity_test\Entity\EntityTest
   */
  protected $testEntity;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'test_scheduled_changes',
      'entity_type' => 'entity_test',
      'type' => 'commerce_scheduled_change',
      'cardinality' => FieldStorageConfig::CARDINALITY_UNLIMITED,
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_name' => 'test_scheduled_changes',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ]);
    $field->save();

    $entity = EntityTest::create([
      'name' => 'Test',
    ]);
    $entity->save();
    $this->testEntity = $entity;
  }

  /**
   * Tests the scheduled change item.
   */
  public function testScheduledChangeItem() {
    /** @var \Drupal\Core\Field\FieldItemListInterface $scheduled_change_item_list */
    $scheduled_change_item_list = $this->testEntity->test_scheduled_changes;
    $scheduled_change_item_list->appendItem(new ScheduledChange('state', 'canceled', time()));
    /** @var \Drupal\commerce_recurring\ScheduledChange $scheduled_change */
    $scheduled_change = $scheduled_change_item_list->first()->toScheduledChange();
    $this->assertEquals('state', $scheduled_change->getFieldName());
    $this->assertEquals('canceled', $scheduled_change->getValue());
    $this->assertNotEmpty($scheduled_change->getCreatedTime());
    $this->assertEquals($scheduled_change, $scheduled_change_item_list->getScheduledChanges()[0]);
  }

}
