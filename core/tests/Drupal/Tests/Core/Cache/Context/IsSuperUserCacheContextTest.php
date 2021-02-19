<?php

namespace Drupal\Tests\Core\Cache\Context;

use Drupal\Core\Cache\Context\IsSuperUserCacheContext;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Cache\Context\IsSuperUserCacheContext
 * @group Cache
 * @group legacy
 */
class IsSuperUserCacheContextTest extends UnitTestCase {

  /**
   * Tests deprecation of the \Drupal\Core\Cache\Context\IsSuperUserCacheContext class.
   *
   * @group legacy
   */
  public function testIsSuperUserCacheContextDeprecation() {
    $this->expectDeprecation('\Drupal\Core\Cache\Context\IsSuperUserCacheContext is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. See https://www.drupal.org/node/2910500 for more information.');
    $cache_context = new IsSuperUserCacheContext($this->getMockBuilder(AccountInterface::class)
      ->getMock());
  }

}
