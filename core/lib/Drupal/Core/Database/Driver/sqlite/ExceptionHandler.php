<?php

namespace Drupal\Core\Database\Driver\sqlite;

use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Database\ExceptionHandler as BaseExceptionHandler;
use Drupal\Core\Database\IntegrityConstraintViolationException;
use Drupal\Core\Database\StatementInterface;

/**
 * SQLite database exception handler class.
 */
class ExceptionHandler extends BaseExceptionHandler {

  /**
   * {@inheritdoc}
   */
  public function handleExecutionException(StatementInterface $statement, array $arguments = [], array $options = []) {
    // The database schema might be changed by another process in between the
    // time that the statement was prepared and the time the statement was run
    // (e.g. usually happens when running tests). In this case, we need to
    // re-run the query.
    // @see http://www.sqlite.org/faq.html#q15
    // @see http://www.sqlite.org/rescode.html#schema
    if (($this->exception->errorInfo[1] ?? NULL) === 17) {
      return $this->connection->query($statement->getQueryString(), $arguments, $options);
    }

    $throw_exception = $options['throw_exception'] ?? FALSE;
    if (!$throw_exception) {
      return;
    }

    if ($this->exception instanceof \PDOException) {
      // Wrap the exception in another exception, because PHP does not allow
      // overriding Exception::getMessage(). Its message is the extra database
      // debug information.
      $message = $this->exception->getMessage() . ": " . $statement->getQueryString() . "; " . print_r($arguments, TRUE);
      // Match all SQLSTATE 23xxx errors.
      if (substr($this->exception->getCode(), -6, -3) == '23') {
        throw new IntegrityConstraintViolationException($message, $this->exception->getCode(), $this->exception);
      }
      throw new DatabaseExceptionWrapper($message, 0, $this->exception);
    }

    throw $this->exception;
  }

}
