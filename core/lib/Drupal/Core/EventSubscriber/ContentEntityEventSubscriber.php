<?php

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\Event\EntityInsertEvent;
use Drupal\Core\Entity\Event\EntityPreSaveEvent;
use Drupal\Core\Entity\Event\EntityUpdateEvent;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Executes entity hooks.
 */
class ContentEntityEventSubscriber implements EventSubscriberInterface {

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
   * Execute field post save actions.
   *
   * @param \Drupal\Core\Entity\Event\EntityInsertEvent $event
   *   The entity event.
   */
  public function onEntityInsert(EntityInsertEvent $event) {
    $entity = $event->getEntity();
    if ($entity instanceof ContentEntityBase) {
      \Drupal::entityTypeManager()->getStorage($entity->getEntityTypeId())->invokeFieldPostSave($entity, FALSE);
    }
  }

  /**
   * Execute presave actions.
   *
   * @param \Drupal\Core\Entity\Event\EntityInsertEvent $event
   *   The entity event.
   */
  public function onEntityPreSave(EntityPreSaveEvent $event) {
    $entity = $event->getEntity();
    if ($entity instanceof ContentEntityBase) {
      \Drupal::entityTypeManager()->getStorage($entity->getEntityTypeId())->invokeFieldMethod('preSave', $entity);
    }
  }

  /**
   * Execute field post save actions.
   *
   * @param \Drupal\Core\Entity\Event\EntityUpdateEvent $event
   *   The entity event.
   */
  public function onEntityUpdate(EntityUpdateEvent $event) {
    $entity = $event->getEntity();
    if ($entity instanceof ContentEntityBase) {
      \Drupal::entityTypeManager()->getStorage($entity->getEntityTypeId())->invokeFieldPostSave($entity, TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Hooks should be executed before other subscribers for BC.
    $priority = -999;
    $events[EntityInsertEvent::class][] = ['onEntityInsert', $priority];
    $events[EntityPreSaveEvent::class][] = ['onEntityPreSave', $priority];
    $events[EntityUpdateEvent::class][] = ['onEntityUpdate', $priority];
    return $events;
  }

}
