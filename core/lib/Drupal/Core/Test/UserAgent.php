<?php

namespace Drupal\Core\Test;

use Drupal\Component\Utility\Crypt;
use Symfony\Component\HttpFoundation\Request;

/**
 * Generates and validates test user agents.
 */
class UserAgent {

  /**
   * Test prefix.
   *
   * @var string
   */
  protected static $testPrefix;

  /**
   * Test last prefix.
   *
   * @var string
   */
  protected static $testLastPrefix;

  /**
   * Test key.
   *
   * @var string
   */
  protected static $testKey;

  /**
   * Returns the test prefix if this is an internal request from SimpleTest.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request to use to determine testing site.
   * @param string|null $new_prefix
   *   Internal use only. A new testing database prefix to be stored.
   *
   * @return string|false
   *   Either the simpletest prefix (the string "simpletest" followed by any
   *   number of digits) or FALSE if the user agent does not contain a valid
   *   HMAC and timestamp.
   */
  public static function validate(Request $request, $new_prefix = NULL) {
    if (isset($new_prefix)) {
      static::$testPrefix = $new_prefix;
    }
    if (isset(static::$testPrefix)) {
      return static::$testPrefix;
    }
    // Unless the below User-Agent and HMAC validation succeeds, we are not in
    // a test environment.
    static::$testPrefix = FALSE;

    // A valid browser test request will contain a hashed and salted
    // authentication code. Check if this code is present in a cookie or custom
    // user agent string.
    $http_user_agent = $request->server->get('HTTP_USER_AGENT');
    $user_agent = $request->cookies->get('SIMPLETEST_USER_AGENT', $http_user_agent);
    if (isset($user_agent) && preg_match("/^simple(\w+\d+):(.+):(.+):(.+)$/", $user_agent, $matches)) {
      [, $prefix, $time, $salt, $hmac] = $matches;
      $check_string = $prefix . ':' . $time . ':' . $salt;
      // Read the hash salt prepared by UserAgent::generate().
      // This function is called before settings.php is read and Drupal's error
      // handlers are set up. While Drupal's error handling may be properly
      // configured on production sites, the server's PHP error_reporting may
      // not. Ensure that no information leaks on production sites.
      $test_db = new TestDatabase($prefix);
      $key_file = DRUPAL_ROOT . '/' . $test_db->getTestSitePath() . '/.htkey';
      if (!is_readable($key_file) || is_dir($key_file)) {
        header($request->server->get('SERVER_PROTOCOL') . ' 403 Forbidden');
        exit;
      }
      $private_key = file_get_contents($key_file);
      // The string from drupal_generate_test_ua() is 74 bytes long. If we don't
      // have it, tests cannot be allowed.
      if (empty($private_key) || strlen($private_key) < 74) {
        header($request->server->get('SERVER_PROTOCOL') . ' 403 Forbidden');
        exit;
      }
      // The file properties add more entropy not easily accessible to others.
      $key = $private_key . filectime(__FILE__) . fileinode(__FILE__);
      $time_diff = $request->server->get('REQUEST_TIME') - $time;
      $test_hmac = Crypt::hmacBase64($check_string, $key);
      // Since we are making a local request a 600 second time window is
      // allowed, and the HMAC must match.
      if ($time_diff >= 0 && $time_diff <= 600 && hash_equals($test_hmac, $hmac)) {
        static::$testPrefix = $prefix;
      }
      else {
        header($request->server->get('SERVER_PROTOCOL') . ' 403 Forbidden (SIMPLETEST_USER_AGENT invalid)');
        exit;
      }
    }
    return static::$testPrefix;
  }

  /**
   * Generates a user agent string with a HMAC and timestamp for simpletest.
   *
   * @param string $prefix
   *   The testing database prefix.
   *
   * @return string
   *   User agent string.
   */
  public static function generate($prefix) {
    if (!isset(static::$testKey) || static::$testLastPrefix != $prefix) {
      static::$testLastPrefix = $prefix;
      $test_db = new TestDatabase($prefix);
      $key_file = DRUPAL_ROOT . '/' . $test_db->getTestSitePath() . '/.htkey';
      // When issuing an outbound HTTP client request from within an inbound
      // test request, then the outbound request has to use the same User-Agent
      // header as the inbound request. A newly generated private key for the
      // same test prefix would invalidate all subsequent inbound requests.
      // @see \Drupal\Core\Test\HttpClientMiddleware\TestHttpClientMiddleware
      if (defined('DRUPAL_TEST_IN_CHILD_SITE') && DRUPAL_TEST_IN_CHILD_SITE && $parent_prefix = static::validate(Request::createFromGlobals())) {
        if ($parent_prefix != $prefix) {
          throw new \RuntimeException("Malformed User-Agent: Expected '$parent_prefix' but got '$prefix'.");
        }
        // If the file is not readable, a PHP warning is expected in this case.
        $private_key = file_get_contents($key_file);
      }
      else {
        // Generate and save a new hash salt for a test run.
        // Consumed by \Drupal\Core\Test\UserAgent::validate() before
        // settings.php is loaded.
        $private_key = Crypt::randomBytesBase64(55);
        file_put_contents($key_file, $private_key);
      }
      // The file properties add more entropy not easily accessible to others.
      static::$testKey = $private_key . filectime(__FILE__) . fileinode(__FILE__);
    }
    // Generate a moderately secure HMAC based on the database credentials.
    $salt = uniqid('', TRUE);
    $check_string = $prefix . ':' . time() . ':' . $salt;
    return 'simple' . $check_string . ':' . Crypt::hmacBase64($check_string, static::$testKey);
  }

}
