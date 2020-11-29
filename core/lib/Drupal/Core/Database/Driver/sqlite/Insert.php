<?php

namespace Drupal\Core\Database\Driver\sqlite;

use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Database\IntegrityConstraintViolationException;
use Drupal\Core\Database\Query\Insert as QueryInsert;

/**
 * SQLite implementation of \Drupal\Core\Database\Query\Insert.
 *
 * We ignore all the default fields and use the clever SQLite syntax:
 *   INSERT INTO table DEFAULT VALUES
 * for degenerated "default only" queries.
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
    if (!empty($this->fromQuery)) {
      $sql = (string) $this;
      // The SelectQuery may contain arguments, load and pass them through.
      return $this->connection->query($sql, $this->fromQuery->getArguments(), $this->queryOptions);
    }

    // We wrap the insert in a transaction so that it is atomic where possible.
    // In SQLite, this is also a notable performance boost.
    $transaction = $this->connection->startTransaction();

    try {
      if (count($this->insertFields)) {
        $last_insert_id = 0;

        // Each insert happens in its own query.
        $stmt = $this->connection->prepareStatement((string) $this, $this->queryOptions);
        foreach ($this->insertValues as $insert_values) {
          $stmt->execute($insert_values, $this->queryOptions);
          $last_insert_id = $this->connection->lastInsertId();
        }

        // Re-initialize the values array so that we can re-use this query.
        $this->insertValues = [];

        // Transaction commits here when $transaction looses scope.
        return $last_insert_id;
      }
      else {
        $stmt = $this->connection->prepareStatement('INSERT INTO {' . $this->table . '} DEFAULT VALUES', $this->queryOptions);
        $stmt->execute(NULL, $this->queryOptions);
        return $this->connection->lastInsertId();
      }
    }
    catch (\PDOException $e) {
      // One of the INSERTs failed, rollback the whole batch.
      $transaction->rollBack();

      $message = $e->getMessage() . ": " . (string) $this;
      $code = is_int($e->getCode()) ? $e->getCode() : 0;

      // SQLSTATE 23xxx errors indicate an integrity constraint violation.
      if (substr($e->getCode(), -6, -3) == '23') {
        throw new IntegrityConstraintViolationException($message, $code, $e);
      }

      throw new DatabaseExceptionWrapper($message, $code, $e);
    }
  }

  public function __toString() {
    // Create a sanitized comment string to prepend to the query.
    $comments = $this->connection->makeComment($this->comments);

    // Produce as many generic placeholders as necessary.
    $placeholders = [];
    if (!empty($this->insertFields)) {
      $placeholders = array_fill(0, count($this->insertFields), '?');
    }

    $insert_fields = array_map(function ($field) {
      return $this->connection->escapeField($field);
    }, $this->insertFields);

    // If we're selecting from a SelectQuery, finish building the query and
    // pass it back, as any remaining options are irrelevant.
    if (!empty($this->fromQuery)) {
      $insert_fields_string = $insert_fields ? ' (' . implode(', ', $insert_fields) . ') ' : ' ';
      return $comments . 'INSERT INTO {' . $this->table . '}' . $insert_fields_string . $this->fromQuery;
    }

    return $comments . 'INSERT INTO {' . $this->table . '} (' . implode(', ', $insert_fields) . ') VALUES (' . implode(', ', $placeholders) . ')';
  }

}
