<?php

namespace Drupal\Core\Entity;

use Drupal\Component\EventDispatcher\Event;

/**
 * Represents various entity events.
 *
 * @see \Drupal\Core\Entity\EntityEvents
 * @see \Drupal\Core\Entity\EntityStorageBase::invokeHook()
 */
class EntityEvent extends Event {

  /**
   * The entity object.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * EntityEvent constructor.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   */
  public function __construct(EntityInterface $entity) {
    $this->entity = $entity;
  }

  /**
   * Returns the entity wrapped by this event.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity object.
   */
  public function getEntity() {
    return $this->entity;
  }

}
