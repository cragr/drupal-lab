<?php

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Event\EntityCreateEvent;
use Drupal\Core\Entity\Event\EntityPreSaveEvent;
use Drupal\Core\Entity\Event\EntityInsertEvent;
use Drupal\Core\Entity\Event\EntityUpdateEvent;
use Drupal\Core\Entity\Event\EntityPreDeleteEvent;
use Drupal\Core\Entity\Event\EntityDeleteEvent;
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
   * Invoke entity create hooks.
   *
   * @param \Drupal\Core\Entity\Event\EntityCreateEvent $event
   *   The entity event.
   */
  public function onEntityCreate(EntityCreateEvent $event) {
    $this->invokeHooks('create', $event->getEntity());
  }

  /**
   * Invoke entity presave hooks.
   *
   * @param \Drupal\Core\Entity\Event\EntityPreSaveEvent $event
   *   The entity event.
   */
  public function onEntityPreSave(EntityPreSaveEvent $event) {
    $this->invokeHooks('presave', $event->getEntity());
  }

  /**
   * Invoke entity insert hooks.
   *
   * @param \Drupal\Core\Entity\Event\EntityInsertEvent $event
   *   The entity event.
   */
  public function onEntityInsert(EntityInsertEvent $event) {
    $this->invokeHooks('insert', $event->getEntity());
  }

  /**
   * Invoke entity update hooks.
   *
   * @param \Drupal\Core\Entity\Event\EntityUpdateEvent $event
   *   The entity event.
   */
  public function onEntityUpdate(EntityUpdateEvent $event) {
    $this->invokeHooks('update', $event->getEntity());
  }

  /**
   * Invoke entity predelete hooks.
   *
   * @param \Drupal\Core\Entity\Event\EntityPreDeleteEvent $event
   *   The entity event.
   */
  public function onEntityPreDelete(EntityPreDeleteEvent $event) {
    $this->invokeHooks('predelete', $event->getEntity());
  }

  /**
   * Invoke entity delete hooks.
   *
   * @param \Drupal\Core\Entity\Event\EntityDeleteEvent $event
   *   The entity event.
   */
  public function onEntityDelete(EntityDeleteEvent $event) {
    $this->invokeHooks('delete', $event->getEntity());
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Hooks should be executed before other subscribers for BC.
    $priority = -1000;
    $events[EntityCreateEvent::class][] = ['onEntityCreate', $priority];
    $events[EntityPreSaveEvent::class][] = ['onEntityPreSave', $priority];
    $events[EntityInsertEvent::class][] = ['onEntityInsert', $priority];
    $events[EntityUpdateEvent::class][] = ['onEntityUpdate', $priority];
    $events[EntityPreDeleteEvent::class][] = ['onEntityPreDelete', $priority];
    $events[EntityDeleteEvent::class][] = ['onEntityDelete', $priority];
    return $events;
  }

  /**
   * Invoke hook_entity_{$hook} and hook_ENTITY_TYPE_{$hook}.
   *
   * @param string $hook
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  private function invokeHooks(string $hook, EntityInterface $entity) {
    $this->moduleHandler->invokeAll('entity_' . $hook, [$entity]);
    $this->moduleHandler->invokeAll($entity->getEntityTypeId() . '_' . $hook, [$entity]);
  }
}
