<?php

namespace Drupal\Core\Entity;

use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;

/**
 * Provides an Iterator class for dealing with large amounts of entities
 * but not loading them all into memory.
 */
class ChunkedIterator implements \IteratorAggregate, \Countable {

  /**
   * The entity storage controller to load entities.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * An array of entity IDs to iterate over.
   *
   * @var array
   */
  protected $entityIds;

  /**
   * The size of each chunk of loaded entities.
   *
   * @var int
   */
  protected $chunkSize;

  /**
   * The memory cache to store but also reset loaded entities.
   *
   * @var \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface
   */
  protected $memoryCache;

  /**
   * Constructs an entity iterator object.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   * @param \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface $memory_cache
   * @param array $ids
   * @param int $chunk_size
   */
  public function __construct(EntityStorageInterface $entity_storage, MemoryCacheInterface $memory_cache, array $ids, $chunk_size = 50) {
    $this->entityStorage = $entity_storage;
    $this->memoryCache = $memory_cache;
    // Make sure we don't use a keyed array.
    $this->entityIds = array_values($ids);
    $this->chunkSize = (int) $chunk_size;
  }

  /**
   * Implements \Countable::count().
   */
  public function count() {
    return count($this->entityIds);
  }

  /**
   * Implements \IteratorAggregate::GetIterator()
   */
  public function getIterator() {
    foreach (array_chunk($this->entityIds, $this->chunkSize) as $ids_chunk) {
      yield from $this->entityStorage->loadMultiple($ids_chunk);
      // We clear all memory cache as we want to remove all referenced entites
      // as well, like for example the owner of an entity.
      $this->memoryCache->deleteAll();
    }
  }

}
