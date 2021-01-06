<?php

namespace Drupal\entity_test_event\EventSubscriber;

use Drupal\Core\Entity\Event\EntityDeleteEvent;
use Drupal\Core\Entity\Event\EntityInsertEvent;
use Drupal\Core\Entity\Event\EntityUpdateEvent;
use Drupal\Core\Entity\Event\EventBase;
use Drupal\entity_test\Entity\EntityTest;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Defines the test event subscriber class.
 */
class TestEventSubscriber implements EventSubscriberInterface {

  /**
   * Test EntityInsertEvent.
   *
   * @param \Drupal\Core\Entity\EntityInsertEvent $event
   *   Insert event.
   */
  public function onInsert(EventBase $event) {
    if ($event->getEntity()->name->value === 'hei') {
      $event->getEntity()->name->value .= ' ho';
    }
  }

  /**
   * Test EntityUpdateEvent.
   *
   * @param \Drupal\Core\Entity\EntityEvent $event
   *   Update event.
   */
  public function onUpdate(EventBase $event) {
    if ($event->getEntity()->name->value === 'hei') {
      $event->getEntity()->name->value .= ' ho';
    }
  }

  /**
   * Test EntityDeleteEvent.
   *
   * @param \Drupal\Core\Entity\EntityDeleteEvent $event
   *   Delete event.
   */
  public function onDelete(EventBase $event) {
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
    $events[EntityInsertEvent::class] = 'onInsert';
    $events[EntityUpdateEvent::class] = 'onUpdate';
    $events[EntityDeleteEvent::class] = 'onDelete';
    return $events;
  }

}
