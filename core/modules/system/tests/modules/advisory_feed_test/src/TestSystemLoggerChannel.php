<?php

namespace Drupal\advisory_feed_test;

use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\State\StateInterface;
use Drush\Log\LogLevel;
use Psr\Log\LoggerInterface;

/**
 * Provides a decorator for the 'logger.channel.system' service for testing.
 */
final class TestSystemLoggerChannel extends LoggerChannel {

  /**
   * The decorated logger.channel.system service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $innerLogger;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs an AdvisoriesTestHttpClient object.
   *
   * @param \Psr\Log\LoggerInterface $innerLogger
   *   The decorated logger.channel.system service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(LoggerInterface $innerLogger, StateInterface $state) {
    $this->innerLogger = $innerLogger;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []) {
    if ($level === LogLevel::ERROR) {
      $messages = $this->state->get('advisory_feed_test.error_messages', []);
      $messages[] = $message;
      $this->state->set('advisory_feed_test.error_messages', $messages);
    }
    $this->innerLogger->critical($message, $context);
  }

  /**
   * Gets the error messages logged on the 'system' channel.
   *
   * The stored error messages are cleared during this method.
   *
   * @return string[]
   *   The error messages logged on the 'system' channel since last call to this
   *   method.
   */
  public function getErrorMessages(): array {
    $messages = $this->state->get('advisory_feed_test.error_messages', []);
    $this->state->set('advisory_feed_test.error_messages', []);
    return $messages;
  }

}
