<?php

namespace Drupal\Core\Config\Entity;

use Drupal\Core\Config\ConfigNameException;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * A base class for config entity types that act as bundles.
 *
 * Entity types that want to use this base class must use bundle_of in their
 * annotation to specify for which entity types they are providing bundles for.
 */
abstract class ConfigEntityBundleBase extends ConfigEntityBase {

  /**
   * Deletes display if a bundle is deleted.
   */
  protected function deleteDisplays() {
    // Remove entity displays of the deleted bundle.
    if ($displays = $this->loadDisplays('entity_view_display')) {
      $storage = $this->entityTypeManager()->getStorage('entity_view_display');
      $storage->delete($displays);
    }

    // Remove entity form displays of the deleted bundle.
    if ($displays = $this->loadDisplays('entity_form_display')) {
      $storage = $this->entityTypeManager()->getStorage('entity_form_display');
      $storage->delete($displays);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    $entity_type_manager = $this->entityTypeManager();
    $bundle_of = $this->getEntityType()->getBundleOfEntityTypeIds();
    if (!$update) {
      /** @var \Drupal\Core\Entity\EntityBundleListenerInterface $entity_bundle_listener */
      $entity_bundle_listener = \Drupal::service('entity_bundle.listener');
      foreach ($bundle_of as $entity_type_id) {
        $entity_bundle_listener->onBundleCreate($this->id(), $entity_type_id);
      }
    }
    else {
      // Invalidate the render cache of entities for which this entity
      // is a bundle.
      foreach ($bundle_of as $entity_type_id) {
        if ($entity_type_manager->hasHandler($entity_type_id, 'view_builder')) {
          $entity_type_manager->getViewBuilder($entity_type_id)->resetCache();
        }
      }
      // Entity bundle field definitions may depend on bundle settings.
      \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
      $this->entityTypeBundleInfo()->clearCachedBundles();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    foreach ($entities as $entity) {
      $entity->deleteDisplays();
      /** @var \Drupal\Core\Entity\EntityBundleListenerInterface $entity_bundle_listener */
      $entity_bundle_listener = \Drupal::service('entity_bundle.listener');
      foreach ($entity->getEntityType()->getBundleOfEntityTypeIds() as $entity_type_id) {
        $entity_bundle_listener->onBundleDelete($entity->id(), $entity_type_id);
      }
    }
  }

  /**
   * Acts on an entity before the presave hook is invoked.
   *
   * Used before the entity is saved and before invoking the presave hook.
   *
   * Ensure that config entities which are bundles of other entities cannot have
   * their ID changed.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage object.
   *
   * @throws \Drupal\Core\Config\ConfigNameException
   *   Thrown when attempting to rename a bundle entity.
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // Only handle renames, not creations.
    if (!$this->isNew() && $this->getOriginalId() !== $this->id()) {
      $bundle_type = $this->getEntityType();
      if (!empty($bundle_type->getBundleOfEntityTypeIds())) {
        throw new ConfigNameException("The machine name of the '{$bundle_type->getLabel()}' bundle cannot be changed.");
      }
    }
  }

  /**
   * Returns view or form displays for this bundle.
   *
   * @param string $display_entity_type_id
   *   The entity type ID of the display type to load.
   *
   * @return \Drupal\Core\Entity\Display\EntityDisplayInterface[]
   *   A list of matching displays.
   */
  protected function loadDisplays($display_entity_type_id) {
    $query = \Drupal::entityQuery($display_entity_type_id);

    $or = $query->orConditionGroup();
    foreach ($this->getEntityType()->getBundleOfEntityTypeIds() as $entity_type_id) {
      $or->condition('id', "{$entity_type_id}.{$this->getOriginalId()}.", 'STARTS_WITH');
    }
    $ids = $query->condition($or)->execute();
    if ($ids) {
      $storage = $this->entityTypeManager()->getStorage($display_entity_type_id);
      return $storage->loadMultiple($ids);
    }
    return [];
  }

}
