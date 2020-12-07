<?php

namespace Drupal\Core\Database\Driver\mysql;

use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Database\ExceptionHandler as BaseExceptionHandler;
use Drupal\Core\Database\IntegrityConstraintViolationException;
use Drupal\Core\Database\StatementInterface;

/**
 * MySql database exception handler class.
 */
class ExceptionHandler extends BaseExceptionHandler {

  /**
   * {@inheritdoc}
   */
  public function handleExecutionException(StatementInterface $statement, array $arguments = [], array $options = []) {
    $throw_exception = $options['throw_exception'] ?? FALSE;
    if (!$throw_exception) {
      return;
    }

    if ($this->exception instanceof \PDOException) {
      // Wrap the exception in another exception, because PHP does not allow
      // overriding Exception::getMessage(). Its message is the extra database
      // debug information.
      $message = $this->exception->getMessage() . ": " . $statement->getQueryString() . "; " . print_r($arguments, TRUE);
      $code = is_int($this->exception->getCode()) ? $this->exception->getCode() : 0;

      // SQLSTATE 23xxx errors indicate an integrity constraint violation. Also,
      // in case of attempted INSERT of a record with an undefined column and no
      // default value indicated in schema, MySql returns a 1364 error code.
      if (
        substr($this->exception->getCode(), -6, -3) == '23' ||
        ($this->exception->errorInfo[1] ?? NULL) === 1364
      ) {
        throw new IntegrityConstraintViolationException($message, $code, $this->exception);
      }

      // If a max_allowed_packet error occurs the message length is truncated.
      // This should prevent the error from recurring if the exception is logged
      // to the database using dblog or the like.
      if (($this->exception->errorInfo[1] ?? NULL) === 1153) {
        $message = Unicode::truncateBytes($this->exception->getMessage(), Connection::MIN_MAX_ALLOWED_PACKET);
        throw new DatabaseExceptionWrapper($message, $this->exception->getCode(), $this->exception);
      }

      throw new DatabaseExceptionWrapper($message, 0, $this->exception);
    }

    throw $this->exception;
  }

}
