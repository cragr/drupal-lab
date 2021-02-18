<?php

namespace Drupal\Tests\system\Traits;

/**
 * Provides common functionality for security advisory test classes.
 */
trait SecurityAdvisoriesTestTrait {

  /**
   * Asserts the expected error messages were logged on the system logger.
   *
   * The test module 'advisory_feed_test' must be installed to use this method.
   * The stored error messages are cleared during this method.
   *
   * @param string[] $expected_messages
   *   The expected error messages.
   *
   * @see \Drupal\advisory_feed_test\TestSystemLoggerChannel::log()
   */
  protected function assertServiceAdvisoryLoggedErrors(array $expected_messages): void {
    $state = $this->container->get('state');
    $messages = $state->get('advisory_feed_test.error_messages', []);
    $this->assertSame($expected_messages, $messages);
    $state->set('advisory_feed_test.error_messages', []);
  }

}
