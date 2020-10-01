<?php

namespace Drupal\FunctionalTests;

use Drupal\Tests\BrowserTestBase;

/**
 * Test legacy user agent functions.
 *
 * @group Test
 * @group legacy
 */
class UserAgentLegacyTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test drupal_valid_test_ua() and drupal_generate_test_ua() functions.
   *
   * @expectedDeprecation drupal_valid_test_ua() is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Use \Drupal\Core\Test\UserAgent::validate(). See https://www.drupal.org/node/3044173
   * @expectedDeprecation drupal_generate_test_ua() is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Use \Drupal\Core\Test\UserAgent::generate(). See https://www.drupal.org/node/3044173
   */
  public function testDrupalTestUa() {
    $test_prefix = drupal_generate_test_ua(drupal_valid_test_ua());
    $this->assertNotEmpty($test_prefix);
    $this->assertNotFalse(drupal_valid_test_ua($test_prefix));
  }

}
