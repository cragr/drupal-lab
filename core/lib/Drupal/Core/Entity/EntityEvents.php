<?php

namespace Drupal\Core\Entity;

/**
 * Defines events for entity CRUD.
 *
 * @see \Drupal\Core\Entity\EntityEvent
 * @see \Drupal\Core\Entity\EntityStorageBase::invokeHook()
 */
final class EntityEvents {

  /**
   * Maps entity hook names to event names.
   *
   * @var string[]
   */
  public static $hookToEventMap = [
    'create' => self::CREATE,
    'presave' => self::PRESAVE,
    'insert' => self::INSERT,
    'update' => self::UPDATE,
    'predelete' => self::PREDELETE,
    'delete' => self::DELETE,
  ];

  /**
   * Name of the event fired when creating an entity.
   *
   * This hook runs after a new entity object has just been instantiated.
   *
   * @see hook_entity_create()
   *
   * @Event
   *
   * @var string
   */
  const CREATE = 'entity.create';

  /**
   * Name of the event fired before an entity is created or updated.
   *
   * You can get the original entity object from $entity->original when it is an
   * update of the entity.
   *
   * @see hook_entity_presave()
   *
   * @Event
   *
   * @var string
   */
  const PRESAVE = 'entity.presave';

  /**
   * Name of the event fired after creating an entity.
   *
   * This event fires once the entity has been stored. Note that changes to the
   * entity made by subscribers to the event will not be saved.
   *
   * @see hook_entity_insert()
   *
   * @Event
   *
   * @var string
   */
  const INSERT = 'entity.insert';

  /**
   * Name of the event fired after updating an existing entity.
   *
   * This event fires once the entity has been stored. Note that changes to the
   * entity made by subscribers to the event will not be saved. Get the original
   * entity object from $entity->original.
   *
   * @see hook_entity_update()
   *
   * @Event
   *
   * @var string
   */
  const UPDATE = 'entity.update';

  /**
   * Name of the event fired before deleting an entity.
   *
   * @see hook_entity_predelete()
   *
   * @Event
   *
   * @var string
   */
  const PREDELETE = 'entity.predelete';

  /**
   * Name of the event fired after deleting an entity.
   *
   * @see hook_entity_delete()
   *
   * @Event
   *
   * @var string
   */
  const DELETE = 'entity.delete';

  /**
   * Returns the event name for creation of an entity of a specific type.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return string
   *   The event name.
   */
  public static function create($entity_type_id) {
    return self::CREATE . '.' . $entity_type_id;
  }

  /**
   * Returns the event name when presaving an entity of a specific type.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return string
   *   The event name.
   */
  public static function presave($entity_type_id) {
    return self::PRESAVE . '.' . $entity_type_id;
  }

  /**
   * Returns the event name for inserting an entity of a specific type.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return string
   *   The event name.
   */
  public static function insert($entity_type_id) {
    return self::INSERT . '.' . $entity_type_id;
  }

  /**
   * Returns the event name for updating an entity of a specific type.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return string
   *   The event name.
   */
  public static function update($entity_type_id) {
    return self::UPDATE . '.' . $entity_type_id;
  }

  /**
   * Returns the event name just before deleting an entity of a specific type.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return string
   *   The event name.
   */
  public static function predelete($entity_type_id) {
    return self::PREDELETE . '.' . $entity_type_id;
  }

  /**
   * Returns the event name for deletion of an entity of a specific type.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return string
   *   The event name.
   */
  public static function delete($entity_type_id) {
    return self::DELETE . '.' . $entity_type_id;
  }

}
