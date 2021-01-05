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
  public static $modules = ['entity_test', 'entity_test_event', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test');
  }

  /**
   * Test insert event.
   *
   * @see \Drupal\entity_test_event\EventSubscriber\TestEventSubscriber
   */
  public function testEventsInsert() {
    $entity = EntityTest::create([
      'name' => 'hei',
    ]);
    $entity->save();
    $this->assertEquals('hei ho', $entity->name->value);
  }

  /**
   * Test update event.
   *
   * @see \Drupal\entity_test_event\EventSubscriber\TestEventSubscriber
   */
  public function testEventsUpdate() {
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
   * Test delete event.
   *
   * @see \Drupal\entity_test_event\EventSubscriber\TestEventSubscriber
   */
  public function testEventsDelete() {
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
