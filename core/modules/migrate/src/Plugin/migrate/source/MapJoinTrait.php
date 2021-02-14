<?php

namespace Drupal\migrate\Plugin\migrate\source;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Database\Query\ConditionInterface;
use Drupal\migrate\Plugin\MigrateIdMapInterface;

/**
 * Provides a helper to join an SQL source query to the map table.
 */
trait MapJoinTrait {

  /**
   * Joins a source query to the map table.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The source query to add the join to.
   * @param \Drupal\Core\Database\Query\ConditionInterface $condition_group
   *   (optional) A condition group that the map table conditions should be
   *   added to, instead of directly to the query. If this is specified, the
   *   caller is responsible for applying the condition group to the query.
   *
   * @return string
   *   The alias of the map table in the query.
   */
  public function addMapJoin(SelectInterface $query, ConditionInterface $condition_group = NULL) {
    // Build the join to the map table. Because the source key could have
    // multiple fields, we need to build things up.
    $count = 1;
    $map_join = '';
    $delimiter = '';

    foreach ($this->getIds() as $field_name => $field_schema) {
      if (isset($field_schema['alias'])) {
        $field_name = $field_schema['alias'] . '.' . $query->escapeField($field_name);
      }
      $map_join .= "$delimiter$field_name = map.sourceid" . $count++;
      $delimiter = ' AND ';
    }

    $alias = $query->leftJoin($this->migration->getIdMap()
      ->getQualifiedMapTableName(), 'map', $map_join);

    if (!$condition_group) {
      $condition_group = $query->orConditionGroup();
      $apply_condition = TRUE;
    }

    $condition_group->isNull($alias . '.sourceid1');
    $condition_group->condition($alias . '.source_row_status', MigrateIdMapInterface::STATUS_NEEDS_UPDATE);

    if (!empty($apply_condition)) {
      $query->condition($condition_group);
    }

    return $alias;
  }

}
