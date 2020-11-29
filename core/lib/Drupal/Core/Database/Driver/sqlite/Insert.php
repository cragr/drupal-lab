<?php

namespace Drupal\Core\Database\Driver\sqlite;

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

    try {
      if (count($this->insertFields) || !empty($this->fromQuery)) {
        return parent::execute();
      }
      else {
        $stmt = $this->connection->prepareStatement('INSERT INTO {' . $this->table . '} DEFAULT VALUES', $this->queryOptions);
        $stmt->execute(NULL, $this->queryOptions);
        return $this->connection->lastInsertId();
      }
    }
    catch (\PDOException $e) {
      $message = $e->getMessage() . ": " . (string) $this;

      // SQLSTATE 23xxx errors indicate an integrity constraint violation.
      if (substr($e->getCode(), -6, -3) == '23') {
        throw new IntegrityConstraintViolationException($message, $e->getCode(), $e);
      }

      throw new DatabaseExceptionWrapper($message, 0, $e->getCode());
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
