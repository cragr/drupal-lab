<?php

namespace Drupal\Core\Entity\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\Core\Entity\EntityInterface;

/**
 * Entity event base class.
 */
class EventBase extends Event {

  /**
   * The entity object.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * EventBase constructor.
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
