<?php

namespace Drupal\KernelTests\Core\Test;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Core\Test\UserAgent;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests \Drupal\Tests\UserAgent.
 *
 * @group Test
 * @group FunctionalTests
 *
 * @coversDefaultClass \Drupal\Core\Test\UserAgent
 */
class UserAgentTest extends KernelTestBase {

  /**
   * Test that ::validate return expected string.
   *
   * @covers ::validate
   */
  public function testValidate() {
    $this->assertStringContainsString('test', UserAgent::validate(Request::createFromGlobals()));
  }

}
