<?php

namespace Drupal\entity_test_event\EventSubscriber;

use Drupal\entity_test\Entity\EntityTest;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Entity\EntityEvents;
use Drupal\Core\Entity\EntityEvent;

/**
 * Defines the test event subscriber class.
 */
class TestEventSubscriber implements EventSubscriberInterface {

  /**
   * Test EntityEvents::INSERT event callback.
   *
   * @param \Drupal\Core\Entity\EntityEvent $event
   *   Insert event.
   */
  public function onInsert(EntityEvent $event) {
    if ($event->getEntity()->name->value === 'hei') {
      $event->getEntity()->name->value .= ' ho';
    }
  }

  /**
   * Test EntityEvents::UPDATE event callback.
   *
   * @param \Drupal\Core\Entity\EntityEvent $event
   *   Insert event.
   */
  public function onUpdate(EntityEvent $event) {
    if ($event->getEntity()->name->value === 'hei') {
      $event->getEntity()->name->value .= ' ho';
    }
  }

  /**
   * Test EntityEvents::DELETE event callback.
   *
   * @param \Drupal\Core\Entity\EntityEvent $event
   *   Insert event.
   */
  public function onDelete(EntityEvent $event) {
    if ($event->getEntity()->name->value === 'hei ho') {
      EntityTest::create([
        'name' => 'hei_ho',
      ])->save();
      $event->getEntity()->name->value .= ' ho';
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[EntityEvents::INSERT] = 'onInsert';
    $events[EntityEvents::UPDATE] = 'onUpdate';
    $events[EntityEvents::DELETE] = 'onDelete';
    return $events;
  }

}
