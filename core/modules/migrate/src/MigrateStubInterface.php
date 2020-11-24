<?php

namespace Drupal\migrate;

/**
 * Provides an interface for the migrate stub service.
 */
interface MigrateStubInterface {

  /**
   * Creates a stub.
   *
   * @param string $migration_id
   *   The migration to stub.
   * @param array $source_ids
   *   An array of source ids.
   * @param array $default_values
   *   (optional) An array of default values to add to the stub.
   * @param bool $key_by_destination_ids
   *   (optional) NULL or TRUE to force indexing of the return array by
   *   destination id keys (default), or FALSE to return the raw return value of
   *   the destination plugin's ::import() method. The return value from
   *   MigrateDestinationInterface::import() is very poorly defined as "The
   *   entity ID or an indication of success". In practice, the mapping systems
   *   expect and all destination plugins return an array of destination
   *   identifiers. Unfortunately these arrays are inconsistently keyed. The
   *   core destination plugins return a numerically indexed array of
   *   destination identifiers, but several contrib destinations return an array
   *   of identifiers indexed by the destination keys. This method will
   *   generally index all return arrays for consistency and to provide as much
   *   information as possible, but this parameter is added for backwards
   *   compatibility to allow accessing the original array.
   *
   * @return array|false
   *   An array of destination ids for the new stub, keyed by destination id
   *   key, or false if the stub failed.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\migrate\MigrateException
   */
  public function createStub($migration_id, array $source_ids, array $default_values = [], $key_by_destination_ids = NULL);

}
