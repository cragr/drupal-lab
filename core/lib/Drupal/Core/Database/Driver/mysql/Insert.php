<?php

namespace Drupal\Core\Database\Driver\mysql;

use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Database\IntegrityConstraintViolationException;
use Drupal\Core\Database\Query\Insert as QueryInsert;

/**
 * MySQL implementation of \Drupal\Core\Database\Query\Insert.
 */
class Insert extends QueryInsert {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    if (!$this->preExecute()) {
      return NULL;
    }

    // If we're selecting from a SelectQuery, finish building the query and
    // pass it back, as any remaining options are irrelevant.
    if (empty($this->fromQuery)) {
      $max_placeholder = 0;
      $values = [];
      foreach ($this->insertValues as $insert_values) {
        foreach ($insert_values as $value) {
          $values[':db_insert_placeholder_' . $max_placeholder++] = $value;
        }
      }
    }
    else {
      $values = $this->fromQuery->getArguments();
    }

    try {
      $stmt = $this->connection->prepareStatement((string) $this, $this->queryOptions);
      $stmt->execute($values, $this->queryOptions);
      $last_insert_id = $this->connection->lastInsertId();
    }
    catch (\PDOException $e) {
      $message = $e->getMessage() . ": " . (string) $this;

      // SQLSTATE 23xxx errors indicate an integrity constraint violation. Also,
      // in case of attempted INSERT of a record with an undefined column and no
      // default value indicated in schema, MySql returns a 1364 error code.
      if (
        substr($e->getCode(), -6, -3) == '23' ||
        ($e->errorInfo[1] ?? NULL) === 1364
      ) {
        throw new IntegrityConstraintViolationException($message, is_int($e->getCode()) ? $e->getCode() : 0, $e);
      }

      throw new DatabaseExceptionWrapper($message, 0, $e);
    }

    // Re-initialize the values array so that we can re-use this query.
    $this->insertValues = [];

    return $last_insert_id;
  }

  public function __toString() {
    // Create a sanitized comment string to prepend to the query.
    $comments = $this->connection->makeComment($this->comments);

    // Default fields are always placed first for consistency.
    $insert_fields = array_merge($this->defaultFields, $this->insertFields);
    $insert_fields = array_map(function ($field) {
      return $this->connection->escapeField($field);
    }, $insert_fields);

    // If we're selecting from a SelectQuery, finish building the query and
    // pass it back, as any remaining options are irrelevant.
    if (!empty($this->fromQuery)) {
      $insert_fields_string = $insert_fields ? ' (' . implode(', ', $insert_fields) . ') ' : ' ';
      return $comments . 'INSERT INTO {' . $this->table . '}' . $insert_fields_string . $this->fromQuery;
    }

    $query = $comments . 'INSERT INTO {' . $this->table . '} (' . implode(', ', $insert_fields) . ') VALUES ';

    $values = $this->getInsertPlaceholderFragment($this->insertValues, $this->defaultFields);
    $query .= implode(', ', $values);

    return $query;
  }

}
