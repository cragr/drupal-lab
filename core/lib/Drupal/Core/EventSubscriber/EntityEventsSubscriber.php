<?php

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Entity\EntityEvent;
use Drupal\Core\Entity\EntityEvents;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Executes entity hooks.
 */
class EntityEventsSubscriber implements EventSubscriberInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new EntityRouteProviderSubscriber instance.
   *
   * @todo Inject entity type handler to subscribe to entity type level hooks.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * Provides hooks for entity specific events.
   *
   * @param \Drupal\Core\Entity\EntityEvent $event
   *   The entity event.
   * @param string $event_name
   *   The related entity event name.
   */
  public function onEntityEvent(EntityEvent $event, $event_name) {
    $hook = array_search($event_name, EntityEvents::$hookToEventMap);
    if ($hook) {
      $this->moduleHandler->invokeAll('entity_' . $hook, [$event->getEntity()]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Hooks should be executed before other subscribers for BC.
    $priority = -1000;
    $events[EntityEvents::CREATE][] = ['onEntityEvent', $priority];
    $events[EntityEvents::PRESAVE][] = ['onEntityEvent', $priority];
    $events[EntityEvents::INSERT][] = ['onEntityEvent', $priority];
    $events[EntityEvents::UPDATE][] = ['onEntityEvent', $priority];
    $events[EntityEvents::PREDELETE][] = ['onEntityEvent', $priority];
    $events[EntityEvents::DELETE][] = ['onEntityEvent', $priority];
    return $events;
  }

}
