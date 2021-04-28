<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;

/**
 * @group Entity
 */
class EntityEventsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test', 'entity_test_event', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();

    $this->installEntitySchema('entity_test');
  }

  /**
   * Test entity insert event.
   *
   * @see \Drupal\entity_test_event\EventSubscriber\TestEventSubscriber
   */
  public function testInsertEvent() {
    $entity = EntityTest::create([
      'name' => 'hei',
    ]);
    $entity->save();
    $this->assertEquals('hei ho', $entity->name->value);
  }

  /**
   * Test entity update event.
   *
   * @see \Drupal\entity_test_event\EventSubscriber\TestEventSubscriber
   */
  public function testUpdateEvent() {
    $entity = EntityTest::create([
      'name' => 'meh',
    ]);
    $entity->save();
    $this->assertEquals('meh', $entity->name->value);

    $entity->name->value = 'hei';
    $entity->save();
    $this->assertEquals('hei ho', $entity->name->value);
  }

  /**
   * Test entity delete event.
   *
   * @see \Drupal\entity_test_event\EventSubscriber\TestEventSubscriber
   */
  public function testDeleteEvent() {
    $entities = \Drupal::entityTypeManager()->getStorage('entity_test')
      ->loadByProperties(['name' => 'hei_ho']);
    $this->assertCount(0, $entities);

    $entity = EntityTest::create([
      'name' => 'hei',
    ]);
    $entity->save();
    $entity->delete();

    // Note the delete event creates another entity.
    $entities = \Drupal::entityTypeManager()->getStorage('entity_test')
      ->loadByProperties(['name' => 'hei_ho']);
    $this->assertCount(1, $entities);
  }

}
