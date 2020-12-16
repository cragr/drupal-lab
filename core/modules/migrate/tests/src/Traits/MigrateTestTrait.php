<?php

namespace Drupal\Tests\migrate\Traits;

use Drupal\migrate\Plugin\MigrateIdMapInterface;

/**
 * Reusable code for 'migrate' module testing.
 */
trait MigrateTestTrait {

  /**
   * Prepares to run a full update.
   *
   * @param string $map_table_name
   *   The migrate map table name.
   */
  public function prepareUpdate(string $map_table_name): void {
    \Drupal::database()->update($map_table_name)
      ->fields(['source_row_status' => MigrateIdMapInterface::STATUS_NEEDS_UPDATE])
      ->execute();
  }

}
